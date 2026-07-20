<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::query()->exists()) {
            return;
        }

        $subscriptions = Subscription::query()->with('plan')->get();

        foreach ($subscriptions as $subscription) {
            if (! $subscription->plan) {
                continue;
            }

            $amount = $subscription->billing_cycle === 'yearly'
                ? $subscription->plan->price_yearly
                : $subscription->plan->price_monthly;

            Order::query()->create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'amount' => $amount,
                'currency' => 'INR',
                'status' => 'paid',
                'paid_at' => $subscription->starts_at ?? $subscription->created_at,
                'created_at' => $subscription->created_at,
                'updated_at' => $subscription->updated_at,
            ]);
        }

        $starter = Plan::query()->where('slug', 'starter')->first();
        $users = User::query()->take(3)->get();

        foreach ($users as $index => $user) {
            if (! $starter || Order::query()->where('user_id', $user->id)->exists()) {
                continue;
            }

            for ($m = 2; $m >= 0; $m--) {
                $date = now()->subMonths($m)->subDays($index);

                Order::query()->create([
                    'user_id' => $user->id,
                    'plan_id' => $starter->id,
                    'amount' => $starter->price_monthly,
                    'currency' => 'INR',
                    'status' => 'paid',
                    'paid_at' => $date,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
}
