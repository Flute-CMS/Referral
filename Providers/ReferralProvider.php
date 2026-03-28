<?php

namespace Flute\Modules\Referral\Providers;

use Flute\Core\Events\ResponseEvent;
use Flute\Core\Modules\Auth\Events\RegisterFormRenderingEvent;
use Flute\Core\Modules\Auth\Events\RegisterValidatingEvent;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Flute\Core\Modules\Auth\Events\UserVerifiedEvent;
use Flute\Core\Modules\Profile\Services\ProfileEditTabService;
use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\Referral\Admin\Package\ReferralAdminPackage;
use Flute\Modules\Referral\Listeners\ReferralFormListener;
use Flute\Modules\Referral\Listeners\ReferralListener;
use Flute\Modules\Referral\Listeners\ReferralDeferredRewardListener;
use Flute\Modules\Referral\Listeners\ReferralValidationListener;
use Flute\Modules\Referral\Listeners\ReferralVerifiedListener;
use Flute\Modules\Referral\Services\ReferralService;
use Flute\Modules\Referral\Services\ReferralServiceInterface;
use Flute\Modules\Referral\Tabs\ReferralProfileTab;

class ReferralProvider extends ModuleServiceProvider
{
    public array $extensions = [];

    public function boot(\DI\Container $container): void
    {
        $this->bootstrapModule();

        $container->set(ReferralServiceInterface::class, \DI\get(ReferralService::class));
        $container->set(ReferralService::class, \DI\autowire(ReferralService::class));

        $this->loadViews('Resources/views', 'referral');
        $this->loadScss('Resources/assets/scss/referral.scss');

        $this->loadPackage(new ReferralAdminPackage());

        events()->addListener(UserRegisteredEvent::NAME, [ReferralListener::class, 'handle']);
        events()->addListener(UserVerifiedEvent::NAME, [ReferralVerifiedListener::class, 'handle']);

        if (!is_cli()) {
            events()->addListener(ResponseEvent::NAME, [ReferralDeferredRewardListener::class, 'handleResponse']);
        }
        events()->addListener(RegisterFormRenderingEvent::NAME, [ReferralFormListener::class, 'handle']);
        events()->addListener(RegisterValidatingEvent::NAME, [ReferralValidationListener::class, 'handle']);

        $this->registerProfileTab($container);
        $this->handleReferralCode();
    }

    public function register(\DI\Container $container): void
    {
    }

    protected function registerProfileTab(\DI\Container $container): void
    {
        if ($container->has(ProfileEditTabService::class)) {
            $profileEditTab = $container->get(ProfileEditTabService::class);
            $profileEditTab->register(new ReferralProfileTab());
        }
    }

    protected function handleReferralCode(): void
    {
        $refCode = request()->input('ref');

        if ($refCode && !session()->has('referral_code')) {
            session()->set('referral_code', $refCode);
        }
    }
}
