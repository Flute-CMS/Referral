<?php

namespace Flute\Modules\Referral\Admin\Package\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;

class ReferralSettingsScreen extends Screen
{
    public ?string $name = 'referral.admin.title.settings';

    public ?string $description = 'referral.admin.title.settings_description';

    public ?string $permission = 'admin.referral';

    public function mount(): void
    {
        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(
            __('referral.admin.title.list'),
            url('/admin/referral'),
        )->add(__('referral.admin.title.settings'));
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('def.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('save'),

            Button::make(__('def.cancel'))->type(Color::OUTLINE_SECONDARY)->redirect(url('/admin/referral')),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::split([
                LayoutFactory::block([
                    LayoutFactory::field(Toggle::make('enabled')->checked(filter_var(
                        request()->input('enabled', config('referral.enabled', true)),
                        FILTER_VALIDATE_BOOLEAN,
                    )))
                        ->label(__('referral.admin.fields.enabled'))
                        ->small(__('referral.admin.fields.enabled_help')),

                    LayoutFactory::field(Toggle::make('auto_reward')->checked(filter_var(
                        request()->input('auto_reward', config('referral.auto_reward', true)),
                        FILTER_VALIDATE_BOOLEAN,
                    )))
                        ->label(__('referral.admin.fields.auto_reward'))
                        ->small(__('referral.admin.fields.auto_reward_help')),

                    LayoutFactory::field(Toggle::make('show_in_profile')->checked(filter_var(
                        request()->input('show_in_profile', config('referral.show_in_profile', true)),
                        FILTER_VALIDATE_BOOLEAN,
                    )))
                        ->label(__('referral.admin.fields.show_in_profile'))
                        ->small(__('referral.admin.fields.show_in_profile_help')),

                    LayoutFactory::field(Toggle::make('allow_self_referral')->checked(filter_var(
                        request()->input('allow_self_referral', config('referral.allow_self_referral', false)),
                        FILTER_VALIDATE_BOOLEAN,
                    )))
                        ->label(__('referral.admin.fields.allow_self_referral'))
                        ->small(__('referral.admin.fields.allow_self_referral_help')),
                ])->title(__('referral.admin.sections.general')),

                LayoutFactory::block([
                    LayoutFactory::field(
                        Input::make('referrer_reward')
                            ->type('number')
                            ->value(request()->input('referrer_reward', config('referral.referrer_reward', 10)))
                            ->step('0.01')
                            ->min('0'),
                    )
                        ->label(__('referral.admin.fields.referrer_reward'))
                        ->small(__('referral.admin.fields.referrer_reward_help')),

                    LayoutFactory::field(
                        Input::make('referred_bonus')
                            ->type('number')
                            ->value(request()->input('referred_bonus', config('referral.referred_bonus', 5)))
                            ->step('0.01')
                            ->min('0'),
                    )
                        ->label(__('referral.admin.fields.referred_bonus'))
                        ->small(__('referral.admin.fields.referred_bonus_help')),

                    LayoutFactory::field(
                        Input::make('min_activity_days')
                            ->type('number')
                            ->value(request()->input('min_activity_days', config('referral.min_activity_days', 0)))
                            ->min('0'),
                    )
                        ->label(__('referral.admin.fields.min_activity_days'))
                        ->small(__('referral.admin.fields.min_activity_days_help')),

                    LayoutFactory::field(
                        Input::make('max_referrals_per_user')
                            ->type('number')
                            ->value(request()->input('max_referrals_per_user', config(
                                'referral.max_referrals_per_user',
                                0,
                            )))
                            ->min('0'),
                    )
                        ->label(__('referral.admin.fields.max_referrals'))
                        ->small(__('referral.admin.fields.max_referrals_help')),
                ])->title(__('referral.admin.sections.rewards')),
            ])->ratio('50/50'),
        ];
    }

    public function save(): void
    {
        $data = request()->input();

        try {
            config()->set('referral.enabled', isset($data['enabled']));
            config()->set('referral.auto_reward', isset($data['auto_reward']));
            config()->set('referral.show_in_profile', isset($data['show_in_profile']));
            config()->set('referral.allow_self_referral', isset($data['allow_self_referral']));
            config()->set('referral.referrer_reward', (float) ($data['referrer_reward'] ?? 10));
            config()->set('referral.referred_bonus', (float) ($data['referred_bonus'] ?? 5));
            config()->set('referral.min_activity_days', (int) ($data['min_activity_days'] ?? 0));
            config()->set('referral.max_referrals_per_user', (int) ($data['max_referrals_per_user'] ?? 0));

            config()->save();

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate(path('config/referral.php'), true);
            }

            $this->flashMessage(__('referral.admin.messages.settings_saved'), 'success');
        } catch (\Throwable $e) {
            $this->flashMessage(__('referral.admin.messages.settings_saved') . ': ' . $e->getMessage(), 'error');
        }

        $this->redirectTo('/admin/referral/settings', 300);
    }
}
