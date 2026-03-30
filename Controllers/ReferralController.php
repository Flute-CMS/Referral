<?php

namespace Flute\Modules\Referral\Controllers;

use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\BaseController;
use Flute\Modules\Referral\Services\ReferralServiceInterface;

class ReferralController extends BaseController
{
    protected ReferralServiceInterface $referralService;

    public function __construct(ReferralServiceInterface $referralService)
    {
        $this->referralService = $referralService;
    }

    #[Route('/referral', name: 'referral.index', methods: ['GET'], middleware: ['auth'])]
    public function index()
    {
        $user = user()->getCurrentUser();

        if (!$user) {
            return redirect('/');
        }

        $settings = $this->referralService->getSettings();

        if (!( $settings['enabled'] ?? true )) {
            return redirect('/');
        }

        $stats = $this->referralService->getReferralStats($user);

        return view('referral::index', [
            'stats' => $stats,
            'settings' => $settings,
        ]);
    }

    #[Route('/referral/copy-link', name: 'referral.copy_link', methods: ['POST'], middleware: ['auth'])]
    public function copyLink()
    {
        $user = user()->getCurrentUser();

        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        if (!( $this->referralService->getSettings()['enabled'] ?? true )) {
            return response()->json(['success' => false], 403);
        }

        $code = $this->referralService->getOrCreateCode($user);

        return response()->json([
            'success' => true,
            'link' => $code->getLink(),
            'code' => $code->code,
        ]);
    }

    #[Route('/referral/stats', name: 'referral.stats', methods: ['GET'], middleware: ['auth'])]
    public function stats()
    {
        $user = user()->getCurrentUser();

        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        if (!( $this->referralService->getSettings()['enabled'] ?? true )) {
            return response()->json(['success' => false], 403);
        }

        $stats = $this->referralService->getReferralStats($user);

        return response()->json([
            'success' => true,
            'stats' => [
                'total_referrals' => $stats['total_referrals'],
                'claimed_rewards' => $stats['claimed_rewards'],
                'pending_rewards' => $stats['pending_rewards'],
                'total_earnings' => $stats['total_earnings'],
            ],
        ]);
    }

    #[Route('/referral/check-code', name: 'referral.check_code', methods: ['POST'], middleware: ['auth'])]
    public function checkCode()
    {
        $user = user()->getCurrentUser();

        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        $code = trim((string) request()->input('code', ''));

        if ($code === '') {
            return response()->json(['success' => false, 'available' => false]);
        }

        $available = $this->referralService->isCodeAvailable($code, $user->id);

        return response()->json([
            'success' => true,
            'available' => $available,
        ]);
    }

    #[Route('/referral/change-code', name: 'referral.change_code', methods: ['POST'], middleware: ['auth'])]
    public function changeCode()
    {
        $user = user()->getCurrentUser();

        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        if (!( $this->referralService->getSettings()['enabled'] ?? true )) {
            return response()->json(['success' => false, 'message' => __('referral.errors.disabled')], 403);
        }

        $newCode = trim((string) request()->input('code', ''));

        try {
            $referralCode = $this->referralService->changeCode($user, $newCode);

            return response()->json([
                'success' => true,
                'code' => $referralCode->code,
                'link' => $referralCode->getLink(),
            ]);
        } catch (\InvalidArgumentException $e) {
            $messageKey = match ($e->getMessage()) {
                'EMPTY_CODE' => 'referral.errors.empty_code',
                'INVALID_CODE_FORMAT' => 'referral.errors.invalid_code_format',
                'CODE_ALREADY_TAKEN' => 'referral.errors.code_taken',
                default => 'referral.errors.invalid_code',
            };

            return response()->json([
                'success' => false,
                'message' => __($messageKey),
            ], 422);
        }
    }

    #[Route('/referral/use-nickname', name: 'referral.use_nickname', methods: ['POST'], middleware: ['auth'])]
    public function useNickname()
    {
        $user = user()->getCurrentUser();

        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        if (!( $this->referralService->getSettings()['enabled'] ?? true )) {
            return response()->json(['success' => false, 'message' => __('referral.errors.disabled')], 403);
        }

        $nickname = $user->name;

        if (!preg_match('/^[a-zA-Z0-9_-]{3,32}$/', $nickname)) {
            return response()->json([
                'success' => false,
                'message' => __('referral.errors.nickname_not_suitable'),
            ], 422);
        }

        try {
            $referralCode = $this->referralService->changeCode($user, $nickname);

            return response()->json([
                'success' => true,
                'code' => $referralCode->code,
                'link' => $referralCode->getLink(),
            ]);
        } catch (\InvalidArgumentException $e) {
            $messageKey = match ($e->getMessage()) {
                'CODE_ALREADY_TAKEN' => 'referral.errors.code_taken',
                default => 'referral.errors.invalid_code_format',
            };

            return response()->json([
                'success' => false,
                'message' => __($messageKey),
            ], 422);
        }
    }
}
