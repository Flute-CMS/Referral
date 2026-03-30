<?php

namespace Flute\Modules\Referral\Services;

use Flute\Core\Database\Entities\User;
use Flute\Modules\Referral\database\Entities\Referral;
use Flute\Modules\Referral\database\Entities\ReferralCode;

interface ReferralServiceInterface
{
    public function getOrCreateCode(User $user): ReferralCode;

    public function getCodeByString(string $code): ?ReferralCode;

    public function createReferral(User $referrer, User $referred): Referral;

    public function hasReferrer(User $user): bool;

    public function getReferralsForUser(User $user): array;

    public function getReferralStats(User $user): array;

    /**
     * @param bool $force If true, skip min_activity_days (e.g. admin manual payout).
     *
     * @return bool True if the referrer was paid; false if skipped or already claimed.
     */
    public function processReferralReward(Referral $referral, bool $force = false): bool;

    /**
     * @param bool $useCache When false, always hit the database (e.g. before creating a referral).
     */
    public function hasReferrerReachedMaxReferrals(User $referrer, bool $useCache = true): bool;

    public function forgetReferrerLimitCache(int $referrerUserId): void;

    public function getSettings(): array;

    public function isCodeAvailable(string $code, ?int $excludeUserId = null): bool;

    public function changeCode(User $user, string $newCode): ReferralCode;
}
