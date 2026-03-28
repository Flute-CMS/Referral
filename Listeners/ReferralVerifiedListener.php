<?php

namespace Flute\Modules\Referral\Listeners;

use Exception;
use Flute\Core\Modules\Auth\Events\UserVerifiedEvent;
use Flute\Modules\Referral\database\Entities\Referral;
use Flute\Modules\Referral\Services\ReferralService;

/**
 * Processes referral bonuses when user verifies their email.
 */
class ReferralVerifiedListener
{
    public static function handle(UserVerifiedEvent $event): void
    {
        $settings = config('referral');

        if (!( $settings['enabled'] ?? true )) {
            return;
        }

        $user = $event->getUser();

        $referral = Referral::query()->where('referred_id', $user->id)->fetchOne();

        if (!$referral) {
            return;
        }

        if ($referral->reward_claimed) {
            return;
        }

        /** @var ReferralService $referralService */
        $referralService = app(ReferralService::class);

        try {
            $referralService->processReferredBonus($user);
            logs('referral')->info("Referred bonus processed for verified user {$user->id}");

            if ($settings['auto_reward'] ?? true) {
                if ($referralService->processReferralReward($referral)) {
                    logs('referral')->info("Referrer reward processed for referral {$referral->id}");
                    ReferralDeferredRewardListener::forgetDeferredCooldownForUser($user->id);
                }
            }
        } catch (Exception $e) {
            logs('referral')->error('Error processing referral on verification: ' . $e->getMessage());
        }
    }
}
