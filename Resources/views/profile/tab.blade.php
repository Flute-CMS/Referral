@php
    $currency = config('lk.currency_view', __('def.currency_symbol'));
@endphp

<div class="ref-profile">
    <x-card>
        <div class="ref-profile__link-block">
            <div class="ref-profile__link-field">
                <input type="text"
                    class="ref-profile__link-input"
                    id="profileReferralLink"
                    value="{{ $stats['referral_link'] }}"
                    readonly>
                <button type="button" class="ref-profile__copy-btn" id="profileCopyLinkBtn" data-copy="{{ $stats['referral_link'] }}">
                    <x-icon path="ph.bold.copy-bold" />
                    <span>{{ __('referral.link.copy') }}</span>
                </button>
            </div>
            <div class="ref-profile__code-pill" data-copy="{{ $stats['referral_code'] }}" data-tooltip="{{ __('referral.link.copy') }}">
                <span class="ref-profile__code-label">{{ __('referral.profile.your_code') }}</span>
                <code class="ref-profile__code-value">{{ $stats['referral_code'] }}</code>
            </div>
        </div>
    </x-card>

    <x-metrics variant="cards">
        <x-metric
            :label="__('referral.profile.stats.total')"
            :value="$stats['total_referrals']"
            icon="ph.bold.users-bold"
            color="primary" />
        <x-metric
            :label="__('referral.profile.stats.claimed')"
            :value="$stats['claimed_rewards']"
            icon="ph.bold.check-circle-bold"
            color="success" />
        <x-metric
            :label="__('referral.profile.stats.earned')"
            :value="number_format($stats['total_earnings'], 2)"
            :suffix="$currency"
            icon="ph.bold.coin-bold"
            color="warning" />
    </x-metrics>

    <div class="ref-profile__info-row">
        <div class="ref-profile__info-item">
            <x-icon path="ph.bold.gift-bold" />
            <span class="ref-profile__info-label">{{ __('referral.profile.reward_per_invite') }}</span>
            <span class="ref-profile__info-value">+{{ number_format($settings['referrer_reward'], 2) }} {{ $currency }}</span>
        </div>
        <div class="ref-profile__info-sep"></div>
        <div class="ref-profile__info-item">
            <x-icon path="ph.bold.user-plus-bold" />
            <span class="ref-profile__info-label">{{ __('referral.profile.bonus_for_friend') }}</span>
            <span class="ref-profile__info-value">+{{ number_format($settings['referred_bonus'], 2) }} {{ $currency }}</span>
        </div>
    </div>

    @if (!empty($stats['referrals']))
        <x-card withoutPadding>
            <x-slot:header>
                <div class="ref-profile__list-header">
                    <h5 class="card-title">{{ __('referral.profile.your_referrals') }}</h5>
                    <x-badge type="primary">{{ count($stats['referrals']) }}</x-badge>
                </div>
            </x-slot:header>

            <div class="ref-profile__list">
                @foreach ($stats['referrals'] as $referral)
                    <div class="ref-profile__list-item">
                        <div class="ref-profile__list-user">
                            <img src="{{ $referral->referred->avatar ?? '/assets/img/default-avatar.webp' }}"
                                alt="{{ $referral->referred->name }}"
                                class="ref-profile__list-avatar"
                                loading="lazy">
                            <div class="ref-profile__list-info">
                                <span class="ref-profile__list-name">{{ $referral->referred->name }}</span>
                                <time class="ref-profile__list-date" datetime="{{ $referral->createdAt->format('c') }}">{{ $referral->createdAt->format('d.m.Y') }}</time>
                            </div>
                        </div>
                        @if ($referral->reward_claimed)
                            <span class="ref-profile__list-badge ref-profile__list-badge--success">
                                +{{ number_format($referral->reward_amount, 2) }} {{ $currency }}
                            </span>
                        @else
                            <span class="ref-profile__list-badge ref-profile__list-badge--pending">
                                {{ __('referral.profile.pending') }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>
    @else
        <div class="ref-profile__empty">
            <x-icon path="ph.bold.share-network-bold" />
            <p>{{ __('referral.profile.share_link') }}</p>
        </div>
    @endif
</div>
