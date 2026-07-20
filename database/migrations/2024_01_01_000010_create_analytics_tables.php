<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->unsignedBigInteger('total_reach')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->unsignedInteger('link_clicks')->default(0);
            $table->integer('followers_gained')->default(0);
            $table->unsignedInteger('posts_published')->default(0);
            $table->timestamps();

            $table->unique(['brand_id', 'snapshot_date']);
            $table->index('snapshot_date');
        });

        Schema::create('post_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('engagement')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('saves')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index('content_item_id');
            $table->index(['social_account_id', 'recorded_at']);
            $table->index('recorded_at');
        });

        Schema::create('social_account_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('posts_published')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->unsignedInteger('followers')->default(0);
            $table->timestamps();

            $table->unique(['social_account_id', 'stat_date']);
            $table->index('stat_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_account_daily_stats');
        Schema::dropIfExists('post_analytics');
        Schema::dropIfExists('analytics_snapshots');
    }
};
