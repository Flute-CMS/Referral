<?php

namespace Flute\Modules\Referral\Listeners;

use Flute\Core\Modules\Auth\Events\RegisterValidatingEvent;
use Flute\Modules\Referral\Services\ReferralService;

/**
 * Validates referral code during registration and saves it to session.
 */
class ReferralValidationListener
{
    public static function handle(RegisterValidatingEvent $event): void
    {
        $settings = config('referral');

        if (!( $settings['enabled'] ?? true )) {
            return;
        }

        $referralCode = request()->input('referral_code') ?: session()->get('referral_code');

        if (empty($referralCode)) {
            return;
        }

        /** @var ReferralService $referralService */
        $referralService = app(ReferralService::class);

        $code = $referralService->getCodeByString($referralCode);

        if (!$code) {
            $event->addError('referral_code', __('referral.errors.invalid_code'));

            return;
        }

        if (!$code->active) {
            $event->addError('referral_code', __('referral.errors.code_inactive'));

            return;
        }

        if ($referralService->hasReferrerReachedMaxReferrals($code->user)) {
            $event->addError('referral_code', __('referral.errors.referrer_limit'));

            return;
        }

        if (!session()->has('referral_code')) {
            session()->set('referral_code', $referralCode);
        }
    }
}
