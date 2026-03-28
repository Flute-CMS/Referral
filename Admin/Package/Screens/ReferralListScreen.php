<?php

namespace Flute\Modules\Referral\Admin\Package\Screens;

use Carbon\Carbon;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\Referral\database\Entities\Referral;
use Flute\Modules\Referral\Listeners\ReferralDeferredRewardListener;
use Flute\Modules\Referral\Services\ReferralService;

class ReferralListScreen extends Screen
{
    public ?string $name = 'referral.admin.title.list';

    public ?string $description = 'referral.admin.title.list_description';

    public ?string $permission = 'admin.referral';

    public $referrals;

    public function mount(): void
    {
        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('referral.admin.title.list'));

        $this->referrals = Referral::query()
            ->load('referrer')
            ->load('referred')
            ->orderBy('created_at', 'DESC');
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('referral.admin.buttons.stats'))
                ->type(Color::OUTLINE_PRIMARY)
                ->icon('ph.bold.chart-bar-bold')
                ->redirect(url('/admin/referral/stats')),

            Button::make(__('referral.admin.buttons.settings'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.gear-bold')
                ->redirect(url('/admin/referral/settings')),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('referrals', [
                TD::make('referrer', __('referral.admin.fields.referrer'))
                    ->width('200px')
                    ->render(
                        static fn(Referral $referral) => view('admin-referral::cells.user-cell', [
                            'user' => $referral->referrer,
                            'link' => url('/admin/referral/user?id=' . $referral->referrer->id),
                        ])->render(),
                    )
                    ->cantHide(),

                TD::make('referred', __('referral.admin.fields.referred'))
                    ->width('200px')
                    ->render(
                        static fn(Referral $referral) => view('admin-referral::cells.user-cell', [
                            'user' => $referral->referred,
                        ])->render(),
                    ),

                TD::make('reward_claimed', __('referral.admin.fields.status'))
                    ->width('120px')
                    ->align(TD::ALIGN_CENTER)
                    ->render(static fn(Referral $referral) => $referral->reward_claimed
                        ? '<span class="badge success">' . __('referral.admin.status.claimed') . '</span>'
                        : '<span class="badge warning">' . __('referral.admin.status.pending') . '</span>'),

                TD::make('reward_amount', __('referral.admin.fields.reward'))
                    ->width('100px')
                    ->align(TD::ALIGN_CENTER)
                    ->render(static fn(Referral $referral) => $referral->reward_claimed
                        ? number_format($referral->reward_amount, 2) . ' ' . config('lk.currency_view', __('def.currency_symbol'))
                        : '—'),

                TD::make('created_at', __('referral.admin.fields.date'))
                    ->width('150px')
                    ->sort()
                    ->defaultSort(true, 'desc')
                    ->render(static fn(Referral $referral) => ( new Carbon($referral->createdAt) )->diffForHumans()),

                TD::make(__('def.actions'))
                    ->class('actions-col')
                    ->align(TD::ALIGN_CENTER)
                    ->disableSearch()
                    ->width('100px')
                    ->cantHide()
                    ->render(static fn(Referral $referral) => DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list([
                            DropDownItem::make(__('referral.admin.buttons.pay_reward'))
                                ->type(Color::OUTLINE_SUCCESS)
                                ->icon('ph.regular.coin')
                                ->size('small')
                                ->fullWidth()
                                ->disabled($referral->reward_claimed)
                                ->method('payReward', [
                                    'id' => $referral->id,
                                ]),

                            DropDownItem::make(__('def.delete'))
                                ->fullWidth()
                                ->confirm(__('referral.admin.confirms.delete'))
                                ->type(Color::OUTLINE_DANGER)
                                ->icon('ph.regular.trash')
                                ->size('small')
                                ->method('deleteReferral', [
                                    'id' => $referral->id,
                                ]),
                        ])),
            ])
                ->perPage(20)
                ->searchable(['referrer.name', 'referred.name']),
        ];
    }

    public function payReward(): void
    {
        $id = (int) request()->input('id');
        $referral = Referral::query()
            ->where('id', $id)
            ->load('referrer')
            ->load('referred')
            ->fetchOne();

        if (!$referral) {
            $this->flashMessage(__('referral.admin.messages.not_found'), 'error');

            return;
        }

        if ($referral->reward_claimed) {
            $this->flashMessage(__('referral.admin.messages.already_paid'), 'warning');

            return;
        }

        $service = app(ReferralService::class);
        $service->processReferralReward($referral, true);
        ReferralDeferredRewardListener::forgetDeferredCooldownForUser($referral->referred->id);

        $this->flashMessage(__('referral.admin.messages.reward_paid'), 'success');
        $this->redirectTo('/admin/referral');
    }

    public function deleteReferral(): void
    {
        $id = (int) request()->input('id');
        $referral = Referral::query()
            ->where('id', $id)
            ->load('referrer')
            ->load('referred')
            ->fetchOne();

        if (!$referral) {
            $this->flashMessage(__('referral.admin.messages.not_found'), 'error');

            return;
        }

        $referredId = $referral->referred->id;
        $referrerId = $referral->referrer->id;

        $referral->delete();

        ReferralDeferredRewardListener::forgetDeferredCooldownForUser($referredId);
        app(ReferralService::class)->forgetReferrerLimitCache($referrerId);

        $this->flashMessage(__('referral.admin.messages.deleted'), 'success');
        $this->redirectTo('/admin/referral');
    }
}
