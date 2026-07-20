<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->json('feature_list')->nullable()->after('api_access');
        });

        foreach (DB::table('plans')->orderBy('id')->get() as $plan) {
            $features = [];

            if ($plan->bulk_scheduling) {
                $features[] = ['name' => 'Bulk Scheduling', 'enabled' => true];
            }
            if ($plan->ai_insights) {
                $features[] = ['name' => 'AI Insights', 'enabled' => true];
            }
            if ($plan->white_label_reports) {
                $features[] = ['name' => 'White Label Reports', 'enabled' => true];
            }
            if ($plan->api_access) {
                $features[] = ['name' => 'API Access', 'enabled' => true];
            }

            DB::table('plans')->where('id', $plan->id)->update([
                'feature_list' => json_encode($features),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('feature_list');
        });
    }
};
