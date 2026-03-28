<?php

namespace Flute\Modules\Referral\Listeners;

use Exception;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Flute\Modules\Referral\Services\ReferralService;

/**
 * Handles referral creation on user registration.
 * If email verification is required, bonuses are processed in ReferralVerifiedListener.
 */
class ReferralListener
{
    public static function handle(UserRegisteredEvent $event): void
    {
        $settings = config('referral');

        if (!( $settings['enabled'] ?? true )) {
            return;
        }

        $user = $event->getUser();

        $referralCode = request()->input('referral_code') ?: session()->get('referral_code') ?: request()->input('ref');

        if (!$referralCode) {
            return;
        }

        /** @var ReferralService $referralService */
        $referralService = app(ReferralService::class);

        if ($referralService->hasReferrer($user)) {
            session()->remove('referral_code');

            return;
        }

        $code = $referralService->getCodeByString($referralCode);

        if (!$code) {
            logs('referral')->warning("Invalid referral code: {$referralCode}");
            session()->remove('referral_code');

            return;
        }

        if (!( $settings['allow_self_referral'] ?? false ) && $code->user->id === $user->id) {
            logs('referral')->info("Self-referral attempt blocked for user {$user->id}");
            session()->remove('referral_code');

            return;
        }

        if ($referralService->hasReferrerReachedMaxReferrals($code->user)) {
            logs('referral')->info("Referrer {$code->user->id} reached referral limit");
            session()->remove('referral_code');

            return;
        }

        try {
            $referral = $referralService->createReferral($code->user, $user);

            ReferralDeferredRewardListener::forgetDeferredCooldownForUser($user->id);
            $referralService->forgetReferrerLimitCache($code->user->id);

            logs('referral')->info("Referral created: user {$user->id} referred by {$code->user->id}");

            if (!config('auth.registration.confirm_email')) {
                $referralService->processReferredBonus($user);

                if ($settings['auto_reward'] ?? true) {
                    if ($referralService->processReferralReward($referral)) {
                        logs('referral')->info("Auto reward processed for referral {$referral->id}");
                    }
                }
            } else {
                logs('referral')->info("Referral {$referral->id} pending verification");
            }
        } catch (Exception $e) {
            if ($e instanceof \RuntimeException && $e->getMessage() === 'REFERRER_MAX_REFERRALS') {
                logs('referral')->warning('Referral limit reached (concurrent registration)');
            } else {
                logs('referral')->error('Error processing referral: ' . $e->getMessage());
            }
        }

        session()->remove('referral_code');
    }
}
