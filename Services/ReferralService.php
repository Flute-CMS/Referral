<?php

namespace Flute\Modules\Referral\Services;

use DateTimeImmutable;
use Flute\Core\Database\Entities\User;
use Flute\Modules\Referral\database\Entities\Referral;
use Flute\Modules\Referral\database\Entities\ReferralCode;

class ReferralService implements ReferralServiceInterface
{
    public function getOrCreateCode(User $user): ReferralCode
    {
        $code = ReferralCode::findOne(['user_id' => $user->id]);

        if (!$code) {
            $code = new ReferralCode();
            $code->user = $user;
            $code->code = $this->generateUniqueCode();
            $code->saveOrFail();
        }

        return $code;
    }

    public function getCodeByString(string $code): ?ReferralCode
    {
        return ReferralCode::query()
            ->where('code', $code)
            ->where('active', true)
            ->load('user')
            ->fetchOne();
    }

    public function createReferral(User $referrer, User $referred): Referral
    {
        if ($this->hasReferrerReachedMaxReferrals($referrer, false)) {
            throw new \RuntimeException('REFERRER_MAX_REFERRALS');
        }

        $referral = new Referral();
        $referral->referrer = $referrer;
        $referral->referred = $referred;
        $referral->saveOrFail();

        $code = ReferralCode::findOne(['user_id' => $referrer->id]);
        if ($code) {
            $code->incrementUses();
        }

        return $referral;
    }

    public function hasReferrer(User $user): bool
    {
        return Referral::findOne(['referred_id' => $user->id]) !== null;
    }

    public function getReferralsForUser(User $user): array
    {
        return Referral::query()
            ->where('referrer_id', $user->id)
            ->load('referred')
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }

    public function getReferralStats(User $user): array
    {
        $referrals = $this->getReferralsForUser($user);
        $totalReferrals = count($referrals);
        $claimedRewards = 0;
        $totalEarnings = 0;

        foreach ($referrals as $referral) {
            if ($referral->reward_claimed) {
                $claimedRewards++;
                $totalEarnings += $referral->reward_amount;
            }
        }

        $code = $this->getOrCreateCode($user);

        return [
            'total_referrals' => $totalReferrals,
            'claimed_rewards' => $claimedRewards,
            'pending_rewards' => $totalReferrals - $claimedRewards,
            'total_earnings' => $totalEarnings,
            'referral_code' => $code->code,
            'referral_link' => $code->getLink(),
            'referrals' => $referrals,
        ];
    }

    public function processReferralReward(Referral $referral, bool $force = false): bool
    {
        $referral = Referral::query()
            ->where('id', $referral->id)
            ->load('referrer')
            ->load('referred')
            ->fetchOne();

        if (!$referral || $referral->reward_claimed) {
            return false;
        }

        $settings = $this->getSettings();
        $rewardAmount = (float) ( $settings['referrer_reward'] ?? 0 );

        if ($rewardAmount <= 0) {
            return false;
        }

        $minDays = (int) ( $settings['min_activity_days'] ?? 0 );

        if (!$force && $minDays > 0 && !$this->isReferredEligibleForReferrerReward($referral->referred, $minDays)) {
            return false;
        }

        $referrer = $referral->referrer;
        $referrer->balance += $rewardAmount;
        $referrer->saveOrFail();

        $referral->claimReward($rewardAmount);

        return true;
    }

    public function processReferredBonus(User $referred): void
    {
        $settings = $this->getSettings();
        $bonusAmount = (float) ( $settings['referred_bonus'] ?? 0 );

        if ($bonusAmount <= 0) {
            return;
        }

        $referral = Referral::findOne(['referred_id' => $referred->id]);
        if (!$referral || ( $referral->referred_bonus_claimed ?? false )) {
            return;
        }

        $referred->balance += $bonusAmount;
        $referred->saveOrFail();

        if (property_exists($referral, 'referred_bonus_claimed')) {
            $referral->referred_bonus_claimed = true;
            $referral->saveOrFail();
        }
    }

    public function hasReferrerReachedMaxReferrals(User $referrer, bool $useCache = true): bool
    {
        $max = (int) ( $this->getSettings()['max_referrals_per_user'] ?? 0 );

        if ($max <= 0) {
            return false;
        }

        $cacheKey = 'referral.referrer_at_limit.' . $referrer->id;

        if ($useCache && function_exists('cache')) {
            $cached = cache()->get($cacheKey);

            if ($cached !== null) {
                return (bool) $cached;
            }
        }

        $count = Referral::query()
            ->where('referrer_id', $referrer->id)
            ->count();

        $atLimit = $count >= $max;

        if ($useCache && function_exists('cache')) {
            cache()->set($cacheKey, $atLimit ? 1 : 0, 45);
        }

        return $atLimit;
    }

    public function forgetReferrerLimitCache(int $referrerUserId): void
    {
        if (!function_exists('cache')) {
            return;
        }

        cache()->deleteImmediately('referral.referrer_at_limit.' . $referrerUserId);
    }

    public function getSettings(): array
    {
        return [
            'enabled' => (bool) config('referral.enabled', true),
            'referrer_reward' => (float) config('referral.referrer_reward', 10),
            'referred_bonus' => (float) config('referral.referred_bonus', 5),
            'auto_reward' => (bool) config('referral.auto_reward', true),
            'min_activity_days' => (int) config('referral.min_activity_days', 0),
            'show_in_profile' => (bool) config('referral.show_in_profile', true),
            'allow_self_referral' => (bool) config('referral.allow_self_referral', false),
            'max_referrals_per_user' => (int) config('referral.max_referrals_per_user', 0),
        ];
    }

    public function getAllReferrals(): array
    {
        return Referral::query()
            ->load('referrer')
            ->load('referred')
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }

    public function getTotalStats(): array
    {
        $referrals = $this->getAllReferrals();
        $totalReferrals = count($referrals);
        $totalRewardsPaid = 0;
        $uniqueReferrers = [];

        foreach ($referrals as $referral) {
            if ($referral->reward_claimed) {
                $totalRewardsPaid += $referral->reward_amount;
            }
            $uniqueReferrers[$referral->referrer->id] = true;
        }

        $topReferrers = $this->getTopReferrers(10);

        return [
            'total_referrals' => $totalReferrals,
            'total_rewards_paid' => $totalRewardsPaid,
            'top_referrers' => $topReferrers,
            'active_referrers' => count($uniqueReferrers),
        ];
    }

    public function getTopReferrers(int $limit = 10): array
    {
        $referrals = Referral::query()->load('referrer')->fetchAll();

        $referrerCounts = [];
        foreach ($referrals as $referral) {
            $referrerId = $referral->referrer->id;
            if (!isset($referrerCounts[$referrerId])) {
                $referrerCounts[$referrerId] = [
                    'user' => $referral->referrer,
                    'count' => 0,
                    'earnings' => 0,
                ];
            }
            $referrerCounts[$referrerId]['count']++;
            if ($referral->reward_claimed) {
                $referrerCounts[$referrerId]['earnings'] += $referral->reward_amount;
            }
        }

        usort($referrerCounts, static fn($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($referrerCounts, 0, $limit);
    }

    public function getReferralsChartData(int $days = 30): array
    {
        $now = new DateTimeImmutable();
        $startDate = $now->modify("-{$days} days")->setTime(0, 0);

        $dailyData = [];
        $labels = [];

        for ($i = 0; $i < $days; $i++) {
            $dayStart = $startDate->modify("+{$i} day");
            $dayEnd = $dayStart->modify('+1 day');

            $labels[] = \Carbon\Carbon::parse($dayStart)->translatedFormat('d M');

            $count = Referral::query()
                ->where('created_at', '>=', $dayStart)
                ->where('created_at', '<', $dayEnd)
                ->count();

            $dailyData[] = $count;
        }

        return [
            'series' => [
                [
                    'name' => __('referral.admin.charts.new_referrals'),
                    'data' => $dailyData,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getRewardsChartData(int $days = 30): array
    {
        $now = new DateTimeImmutable();
        $startDate = $now->modify("-{$days} days")->setTime(0, 0);

        $dailyRewards = [];
        $labels = [];

        for ($i = 0; $i < $days; $i++) {
            $dayStart = $startDate->modify("+{$i} day");
            $dayEnd = $dayStart->modify('+1 day');

            $labels[] = \Carbon\Carbon::parse($dayStart)->translatedFormat('d M');

            $referrals = Referral::query()
                ->where('reward_claimed', true)
                ->where('created_at', '>=', $dayStart)
                ->where('created_at', '<', $dayEnd)
                ->fetchAll();

            $sum = 0;
            foreach ($referrals as $referral) {
                $sum += $referral->reward_amount;
            }

            $dailyRewards[] = $sum;
        }

        return [
            'series' => [
                [
                    'name' => __('referral.admin.charts.rewards_paid'),
                    'data' => $dailyRewards,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getUserReferralStats(int $userId): array
    {
        $user = User::findByPK($userId);
        if (!$user) {
            return [];
        }

        $referrals = $this->getReferralsForUser($user);
        $code = $this->getOrCreateCode($user);

        $totalReferrals = count($referrals);
        $claimedRewards = 0;
        $totalEarnings = 0;
        $pendingRewards = 0;

        foreach ($referrals as $referral) {
            if ($referral->reward_claimed) {
                $claimedRewards++;
                $totalEarnings += $referral->reward_amount;
            } else {
                $pendingRewards++;
            }
        }

        return [
            'user' => $user,
            'code' => $code,
            'total_referrals' => $totalReferrals,
            'claimed_rewards' => $claimedRewards,
            'pending_rewards' => $pendingRewards,
            'total_earnings' => $totalEarnings,
            'referrals' => $referrals,
        ];
    }

    public function getUserReferralsChartData(int $userId, int $months = 6): array
    {
        $now = new DateTimeImmutable();
        $startDate = $now->modify("-{$months} months");

        $monthlyData = [];
        $labels = [];

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->modify("+{$i} month");
            $monthEnd = $startDate->modify('+' . ( $i + 1 ) . ' month');

            $labels[] = \Carbon\Carbon::parse($monthStart)->translatedFormat('M Y');

            $count = Referral::query()
                ->where('referrer_id', $userId)
                ->where('created_at', '>=', $monthStart)
                ->where('created_at', '<', $monthEnd)
                ->count();

            $monthlyData[] = $count;
        }

        return [
            'series' => [
                [
                    'name' => __('referral.admin.charts.referrals'),
                    'data' => $monthlyData,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getUserEarningsChartData(int $userId, int $months = 6): array
    {
        $now = new DateTimeImmutable();
        $startDate = $now->modify("-{$months} months");

        $monthlyEarnings = [];
        $labels = [];

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->modify("+{$i} month");
            $monthEnd = $startDate->modify('+' . ( $i + 1 ) . ' month');

            $labels[] = \Carbon\Carbon::parse($monthStart)->translatedFormat('M Y');

            $referrals = Referral::query()
                ->where('referrer_id', $userId)
                ->where('reward_claimed', true)
                ->where('created_at', '>=', $monthStart)
                ->where('created_at', '<', $monthEnd)
                ->fetchAll();

            $sum = 0;
            foreach ($referrals as $referral) {
                $sum += $referral->reward_amount;
            }

            $monthlyEarnings[] = $sum;
        }

        return [
            'series' => [
                [
                    'name' => __('referral.admin.charts.earnings'),
                    'data' => $monthlyEarnings,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getMonthlyReferralsChartData(int $months = 9): array
    {
        $now = new DateTimeImmutable();
        $startDate = $now->modify("-{$months} months");

        $monthlyData = [];
        $labels = [];

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->modify("+{$i} month");
            $monthEnd = $startDate->modify('+' . ( $i + 1 ) . ' month');

            $labels[] = \Carbon\Carbon::parse($monthStart)->translatedFormat('M');

            $count = Referral::query()
                ->where('created_at', '>=', $monthStart)
                ->where('created_at', '<', $monthEnd)
                ->count();

            $monthlyData[] = $count;
        }

        return [
            'series' => [
                [
                    'name' => __('referral.admin.charts.new_referrals'),
                    'data' => $monthlyData,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getMonthlyRewardsChartData(int $months = 9): array
    {
        $now = new DateTimeImmutable();
        $startDate = $now->modify("-{$months} months");

        $monthlyRewards = [];
        $labels = [];

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->modify("+{$i} month");
            $monthEnd = $startDate->modify('+' . ( $i + 1 ) . ' month');

            $labels[] = \Carbon\Carbon::parse($monthStart)->translatedFormat('M');

            $referrals = Referral::query()
                ->where('reward_claimed', true)
                ->where('created_at', '>=', $monthStart)
                ->where('created_at', '<', $monthEnd)
                ->fetchAll();

            $sum = 0;
            foreach ($referrals as $referral) {
                $sum += $referral->reward_amount;
            }

            $monthlyRewards[] = $sum;
        }

        return [
            'series' => [
                [
                    'name' => __('referral.admin.charts.rewards_paid'),
                    'data' => $monthlyRewards,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Referrer reward is allowed after the referred user's account has existed for at least N full days (from registration).
     */
    private function isReferredEligibleForReferrerReward(User $referred, int $minDays): bool
    {
        if ($minDays <= 0) {
            return true;
        }

        $deadline = $referred->createdAt->modify('+' . $minDays . ' days');

        return ( new DateTimeImmutable() ) >= $deadline;
    }

    public function isCodeAvailable(string $code, ?int $excludeUserId = null): bool
    {
        $query = ReferralCode::query()->where('code', $code);

        if ($excludeUserId !== null) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $query->count() === 0;
    }

    public function changeCode(User $user, string $newCode): ReferralCode
    {
        $newCode = trim($newCode);

        if ($newCode === '') {
            throw new \InvalidArgumentException('EMPTY_CODE');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{3,32}$/', $newCode)) {
            throw new \InvalidArgumentException('INVALID_CODE_FORMAT');
        }

        if (!$this->isCodeAvailable($newCode, $user->id)) {
            throw new \InvalidArgumentException('CODE_ALREADY_TAKEN');
        }

        $referralCode = $this->getOrCreateCode($user);
        $referralCode->code = $newCode;
        $referralCode->saveOrFail();

        return $referralCode;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (ReferralCode::findOne(['code' => $code]));

        return $code;
    }
}
