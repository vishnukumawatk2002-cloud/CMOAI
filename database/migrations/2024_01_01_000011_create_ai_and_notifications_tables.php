<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->enum('insight_type', [
                'posting_time',
                'platform',
                'content_type',
                'recommendation',
                'warning',
            ]);
            $table->string('title')->nullable();
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_dismissed')->default(false);
            $table->timestamps();

            $table->index(['brand_id', 'is_dismissed']);
            $table->index('insight_type');
        });

        Schema::create('ai_suggested_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('content_type', [
                'post',
                'carousel',
                'reel_script',
                'image_caption',
                'hashtags',
                'thirty_day_plan',
                'thread',
            ])->nullable();
            $table->enum('platform', [
                'facebook',
                'instagram',
                'linkedin',
                'x',
                'youtube',
                'pinterest',
                'threads',
                'google_business',
            ])->nullable();
            $table->string('label');
            $table->text('prompt_text');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['brand_id', 'is_active']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 100);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ai_suggested_prompts');
        Schema::dropIfExists('ai_insights');
    }
};
