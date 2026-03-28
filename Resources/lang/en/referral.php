<?php

return [
    'title' => 'Referral Program',

    'hero' => [
        'badge' => 'Rewards',
        'title' => 'Invite Friends — Earn Rewards',
        'subtitle' => 'Share your referral link and earn bonuses for every friend you invite',
    ],

    'link' => [
        'title' => 'Your Referral Link',
        'copy' => 'Copy',
        'copied' => 'Copied!',
        'code' => 'Code',
    ],

    'registration' => [
        'label' => 'Referral Code',
        'placeholder' => 'Enter friend\'s code (optional)',
        'code_applied' => 'Code applied! You will receive a bonus after registration',
    ],

    'auth' => [
        'referral_code' => 'Referral Code',
        'referral_code_placeholder' => 'Enter friend\'s code (optional)',
        'referral_code_help' => 'If a friend invited you, enter their code to receive a bonus',
        'code_applied' => 'Code :code applied! You will receive a bonus after registration',
        'invited_by' => 'You were invited by a friend',
    ],

    'errors' => [
        'invalid_code' => 'The specified referral code was not found',
        'code_inactive' => 'This referral code is no longer active',
        'referrer_limit' => 'This invite link has reached the maximum number of referrals',
    ],

    'stats' => [
        'title' => 'Your Statistics',
        'total_referrals' => 'Total Referrals',
        'claimed_rewards' => 'Rewards Claimed',
        'pending_rewards' => 'Pending',
        'total_earnings' => 'Total Earned',
    ],

    'list' => [
        'title' => 'Your Referrals',
        'pending' => 'Pending',
        'empty' => 'You don\'t have any referrals yet',
    ],

    'info' => [
        'title' => 'Rewards',
        'referrer_reward' => 'Referral Reward',
        'referred_bonus' => 'New Player Bonus',
    ],

    'how' => [
        'title' => 'How does it work?',
        'step1' => 'Copy your referral link',
        'step2' => 'Share the link with friends',
        'step3' => 'Friend registers via link',
        'step4' => 'You both get bonuses!',
    ],

    'profile' => [
        'title' => 'Referrals',
        'description' => 'Invite friends and earn bonuses',
        'link_title' => 'Your Referral Link',
        'your_code' => 'Code',
        'stats' => [
            'total' => 'Total Referrals',
            'claimed' => 'Rewards Paid',
            'earned' => 'Total Earned',
        ],
        'rewards_title' => 'Rewards',
        'reward_per_invite' => 'Per Invite',
        'bonus_for_friend' => 'New Player Bonus',
        'your_referrals' => 'Your Referrals',
        'pending' => 'Pending',
        'no_referrals' => 'You don\'t have any referrals yet',
        'share_link' => 'Share the link with friends and start earning',
        'copy_and_share' => 'Copy Link',
    ],

    'admin' => [
        'menu' => 'Referrals',

        'title' => [
            'list' => 'Referral System',
            'list_description' => 'Manage referrals and rewards',
            'settings' => 'Referral Settings',
            'settings_description' => 'Configure referral system parameters',
            'stats' => 'Referral Statistics',
            'stats_description' => 'Overall referral program statistics',
            'user_stats' => 'User Statistics',
            'user_stats_description' => 'Detailed user referral statistics',
        ],

        'tabs' => [
            'overview' => 'Overview',
            'charts' => 'Charts',
            'top_referrers' => 'Top Referrers',
            'referrals' => 'Referrals',
        ],

        'charts' => [
            'new_referrals' => 'New Referrals',
            'rewards_paid' => 'Rewards Paid',
            'referrals' => 'Referrals',
            'earnings' => 'Earnings',
            'daily_referrals' => 'Referrals (Last 14 Days)',
            'daily_referrals_desc' => 'Number of new referrals per day',
            'daily_rewards' => 'Rewards (Last 14 Days)',
            'daily_rewards_desc' => 'Amount of rewards paid per day',
            'monthly_referrals' => 'Referrals by Month',
            'monthly_referrals_desc' => 'Referral acquisition trends',
            'monthly_rewards' => 'Rewards by Month',
            'monthly_rewards_desc' => 'Reward payout trends',
            'referrals_over_time' => 'Referrals Over Time',
            'earnings_over_time' => 'Earnings Over Time',
        ],

        'user' => [
            'referral_code' => 'Referral Code',
            'referral_link' => 'Referral Link',
            'total_referrals' => 'Total Referrals',
            'claimed_rewards' => 'Claimed',
            'pending_rewards' => 'Pending',
            'total_earnings' => 'Total Earnings',
            'link_info' => 'Link Information',
            'code_uses' => 'Code Uses',
            'code_status' => 'Code Status',
            'conversion' => 'Conversion',
            'conversion_rate' => 'Conversion Rate',
            'avg_earnings' => 'Average Earnings',
        ],

        'buttons' => [
            'settings' => 'Settings',
            'stats' => 'Statistics',
            'pay_reward' => 'Pay Reward',
            'seed_test' => 'Test Data',
        ],

        'fields' => [
            'referrer' => 'Referrer',
            'referred' => 'Referred',
            'status' => 'Status',
            'reward' => 'Reward',
            'date' => 'Date',
            'enabled' => 'Enabled',
            'enabled_help' => 'Enable or disable the referral system',
            'auto_reward' => 'Auto Pay',
            'auto_reward_help' => 'Automatically pay reward when referral registers',
            'show_in_profile' => 'Show in Profile',
            'show_in_profile_help' => 'Display referral tab in profile settings',
            'allow_self_referral' => 'Allow Self-Referral',
            'allow_self_referral_help' => 'Allow users to use their own referral code',
            'referrer_reward' => 'Referral Reward',
            'referrer_reward_help' => 'Amount the referrer will receive',
            'referred_bonus' => 'New Player Bonus',
            'referred_bonus_help' => 'Amount the referred player will receive',
            'min_activity_days' => 'Minimum Activity Days',
            'min_activity_days_help' => 'Full days after the invited user registered before the referrer reward is paid (0 = immediate). While this is greater than zero, eligibility is rechecked on each request when the invited user is logged in, and when their email is verified.',
            'max_referrals' => 'Max Referrals',
            'max_referrals_help' => 'Maximum referrals per user (0 = unlimited)',
        ],

        'sections' => [
            'general' => 'General Settings',
            'rewards' => 'Reward Settings',
        ],

        'status' => [
            'claimed' => 'Claimed',
            'pending' => 'Pending',
        ],

        'confirms' => [
            'delete' => 'Are you sure you want to delete this record?',
            'seed_test' => 'Create test referral data? This will add several records to the database.',
        ],

        'messages' => [
            'not_found' => 'Record not found',
            'deleted' => 'Record successfully deleted',
            'reward_paid' => 'Reward successfully paid',
            'already_paid' => 'Reward has already been paid',
            'settings_saved' => 'Settings saved successfully',
            'test_data_seeded' => 'Test data created successfully',
        ],

        'stats' => [
            'total_referrals' => 'Total Referrals',
            'total_rewards' => 'Total Rewards Paid',
            'top_referrers' => 'Top Referrers',
            'referrals_count' => 'referrals',
            'no_data' => 'No data to display yet',
            'reward_per_referral' => 'Reward per Referral',
            'bonus_per_user' => 'New User Bonus',
            'system_status' => 'System Status',
            'conversion' => 'Conversion',
            'total_codes' => 'Total Codes',
            'active_referrers' => 'Active Referrers',
            'avg_per_referrer' => 'Average per Referrer',
        ],
    ],
];
