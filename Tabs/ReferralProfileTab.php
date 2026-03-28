<?php

namespace Flute\Modules\Referral\Tabs;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Support\ProfileTab;
use Flute\Modules\Referral\Services\ReferralService;

class ReferralProfileTab extends ProfileTab
{
    public function getId(): string
    {
        return 'referral';
    }

    public function getPath(): string
    {
        return 'referral';
    }

    public function getIcon(): string
    {
        return 'ph.bold.users-three-bold';
    }

    public function getDescription(): ?string
    {
        return __('referral.profile.description');
    }

    public function getTitle(): string
    {
        return __('referral.profile.title');
    }

    public function getOrder(): int
    {
        return 80;
    }

    public function canView(User $user): bool
    {
        $settings = app(ReferralService::class)->getSettings();

        return ( $settings['enabled'] ?? false )
            && ( $settings['show_in_profile'] ?? true )
            && user()->id === $user->id;
    }

    public function getContent(User $user)
    {
        $service = app(ReferralService::class);
        $stats = $service->getReferralStats($user);
        $settings = $service->getSettings();

        return view('referral::profile.tab', [
            'user' => $user,
            'stats' => $stats,
            'settings' => $settings,
        ]);
    }
}
