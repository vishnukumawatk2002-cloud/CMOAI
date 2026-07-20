<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->foreignId('plan_id')
                ->nullable()
                ->after('user_id')
                ->constrained('plans')
                ->nullOnDelete();
        });

        $starterId = DB::table('plans')->where('slug', 'starter')->value('id');
        $growthId = DB::table('plans')->where('slug', 'growth')->value('id');
        $proId = DB::table('plans')->where('slug', 'agency')->value('id')
            ?? DB::table('plans')->where('slug', 'pro')->value('id');

        $brands = DB::table('brands')->whereNull('deleted_at')->get(['id', 'user_id', 'name']);

        foreach ($brands as $brand) {
            $name = strtolower((string) $brand->name);
            $planId = null;

            if (str_contains($name, '1000') || str_contains($name, 'starter')) {
                $planId = $starterId;
            } elseif (
                str_contains($name, '2000')
                || str_contains($name, '2500')
                || str_contains($name, 'growth')
            ) {
                $planId = $growthId;
            } elseif (
                str_contains($name, '5000')
                || str_contains($name, 'pro')
                || str_contains($name, 'agency')
            ) {
                $planId = $proId;
            } else {
                $planId = DB::table('subscriptions')
                    ->where('user_id', $brand->user_id)
                    ->whereIn('status', ['active', 'trial'])
                    ->orderByDesc('id')
                    ->value('plan_id') ?? $starterId;
            }

            if ($planId) {
                DB::table('brands')->where('id', $brand->id)->update(['plan_id' => $planId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });
    }
};
