<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('plans')->insert([
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price_monthly' => 999.00,
                'price_yearly' => 699.00,
                'max_brands' => null,
                'max_social_accounts' => 3,
                'max_posts_per_month' => 30,
                'bulk_scheduling' => false,
                'ai_insights' => false,
                'white_label_reports' => false,
                'api_access' => false,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'price_monthly' => 2499.00,
                'price_yearly' => 1749.00,
                'max_brands' => null,
                'max_social_accounts' => 10,
                'max_posts_per_month' => 300,
                'bulk_scheduling' => true,
                'ai_insights' => true,
                'white_label_reports' => false,
                'api_access' => false,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Agency',
                'slug' => 'agency',
                'price_monthly' => 5999.00,
                'price_yearly' => 4199.00,
                'max_brands' => null,
                'max_social_accounts' => null,
                'max_posts_per_month' => null,
                'bulk_scheduling' => true,
                'ai_insights' => true,
                'white_label_reports' => true,
                'api_access' => true,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'max_brands' => null,
                'max_social_accounts' => null,
                'max_posts_per_month' => null,
                'bulk_scheduling' => true,
                'ai_insights' => true,
                'white_label_reports' => true,
                'api_access' => true,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
