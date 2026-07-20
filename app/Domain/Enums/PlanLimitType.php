<?php

namespace App\Domain\Enums;

enum PlanLimitType: string
{
    case Brands = 'brands';
    case SocialAccounts = 'social_accounts';
    case PostsPerMonth = 'posts_per_month';
}
