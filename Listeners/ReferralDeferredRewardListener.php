<?php

namespace Flute\Modules\Referral\Listeners;

use DateTimeImmutable;
use Flute\Core\Database\Entities\User;
use Flute\Core\Events\ResponseEvent;
use Flute\Modules\Referral\database\Entities\Referral;
use Flute\Modules\Referral\Services\ReferralService;
use Throwable;

/**
 * Retries referrer auto-reward when min_activity_days defers payout until the referred user is eligible.
 * Uses ResponseEvent so it runs for existing sessions (not only explicit login).
 * Cooldown cache avoids a DB query on every request for users without pending rewards.
 */
class ReferralDeferredRewardListener
{
    public const DEFERRED_CACHE_TAG = 'referral.deferred';

    private const COOLDOWN_NO_PENDING_TTL = 604800;

    private const COOLDOWN_PENDING_MIN = 120;

    private const COOLDOWN_PENDING_MAX = 900;

    private static bool $checkedThisRequest = false;

    public static function handleResponse(ResponseEvent $event): void
    {
        if (self::$checkedThisRequest) {
            return;
        }
        self::$checkedThisRequest = true;

        $settings = config('referral');

        if (!( $settings['enabled'] ?? true ) || !( $settings['auto_reward'] ?? true )) {
            return;
        }

        if ((int) ( $settings['min_activity_days'] ?? 0 ) <= 0) {
            return;
        }

        if (!function_exists('user') || !user()->isLoggedIn()) {
            return;
        }

        $current = user()->getCurrentUser();
        if (!$current instanceof User) {
            return;
        }

        self::tryProcessPendingReferrerReward($current, $settings);
    }

    /**
     * Clear deferred cooldown for a referred user (e.g. new referral row just created).
     */
    public static function forgetDeferredCooldownForUser(int $referredUserId): void
    {
        if (!function_exists('cache')) {
            return;
        }

        try {
            cache()->deleteImmediately(self::cooldownKey($referredUserId));
        } catch (Throwable) {
        }
    }

    /**
     * Drop all referral deferred cooldown entries (e.g. admin changed min_activity_days).
     */
    public static function purgeDeferredCooldownCache(): void
    {
        if (!function_exists('cache')) {
            return;
        }

        try {
            cache()->deleteByTag(self::DEFERRED_CACHE_TAG);
        } catch (Throwable) {
        }
    }

    private static function cooldownKey(int $userId): string
    {
        return 'referral.deferred_cd.' . $userId;
    }

    private static function registerCooldown(string $key, int $ttlSeconds): void
    {
        if (!function_exists('cache') || $ttlSeconds <= 0) {
            return;
        }

        try {
            $cache = cache();
            $cache->set($key, 1, $ttlSeconds);
            $cache->tagKey(self::DEFERRED_CACHE_TAG, $key);
        } catch (Throwable) {
        }
    }

    private static function tryProcessPendingReferrerReward(User $user, array $settings): void
    {
        if (!function_exists('cache')) {
            self::runDeferredCheck($user, $settings);

            return;
        }

        $key = self::cooldownKey($user->id);

        try {
            if (cache()->get($key) !== null) {
                return;
            }
        } catch (Throwable) {
            self::runDeferredCheck($user, $settings);

            return;
        }

        self::runDeferredCheck($user, $settings);
    }

    private static function runDeferredCheck(User $user, array $settings): void
    {
        $key = self::cooldownKey($user->id);

        $referral = Referral::query()
            ->where('referred_id', $user->id)
            ->where('reward_claimed', false)
            ->fetchOne();

        if (!$referral) {
            self::registerCooldown($key, self::COOLDOWN_NO_PENDING_TTL);

            return;
        }

        /** @var ReferralService $referralService */
        $referralService = app(ReferralService::class);
        $paid = $referralService->processReferralReward($referral, false);

        if ($paid) {
            self::registerCooldown($key, self::COOLDOWN_NO_PENDING_TTL);

            return;
        }

        $minDays = (int) ( $settings['min_activity_days'] ?? 0 );
        $backoff = self::computePendingBackoffSeconds($user, $minDays);
        self::registerCooldown($key, $backoff);
    }

    private static function computePendingBackoffSeconds(User $referred, int $minDays): int
    {
        if ($minDays <= 0) {
            return self::COOLDOWN_PENDING_MAX;
        }

        $deadline = $referred->createdAt->modify('+' . $minDays . ' days');
        $secondsUntil = $deadline->getTimestamp() - ( new DateTimeImmutable() )->getTimestamp();

        if ($secondsUntil <= 0) {
            return self::COOLDOWN_PENDING_MIN;
        }

        return max(
            self::COOLDOWN_PENDING_MIN,
            min(self::COOLDOWN_PENDING_MAX, $secondsUntil),
        );
    }
}
