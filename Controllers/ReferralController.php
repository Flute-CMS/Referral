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
}
