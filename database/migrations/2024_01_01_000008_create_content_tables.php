<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['brand_id', 'slug']);
        });

        Schema::create('ai_generation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('content_type', [
                'post',
                'carousel',
                'reel_script',
                'image_caption',
                'hashtags',
                'thirty_day_plan',
                'thread',
            ]);
            $table->json('platforms');
            $table->text('prompt');
            $table->enum('status', ['pending', 'processing', 'complete', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'status']);
        });

        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('content_folders')->nullOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreignId('ai_generation_request_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('content_type', [
                'post',
                'carousel',
                'reel_script',
                'image_caption',
                'hashtags',
                'thirty_day_plan',
                'thread',
            ]);
            $table->enum('platform', [
                'facebook',
                'instagram',
                'linkedin',
                'x',
                'youtube',
                'pinterest',
                'threads',
                'google_business',
                'multi',
            ]);
            $table->string('title', 500)->nullable();
            $table->longText('body');
            $table->enum('status', ['draft', 'approved', 'scheduled', 'published', 'failed'])->default('draft');
            $table->unsignedTinyInteger('variation_number')->nullable()->default(1);
            $table->text('generation_prompt')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('external_post_id')->nullable();
            $table->string('external_post_url', 500)->nullable();
            $table->unsignedInteger('reach')->nullable()->default(0);
            $table->decimal('engagement_rate', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('content_items')->nullOnDelete();
            $table->index(['brand_id', 'status']);
            $table->index(['brand_id', 'platform']);
            $table->index('folder_id');
            $table->index('parent_id');
            $table->index('scheduled_at');
            $table->index('published_at');
            $table->fullText(['title', 'body']);
        });

        Schema::create('content_hashtags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->string('hashtag', 100);
            $table->timestamps();

            $table->unique(['content_item_id', 'hashtag']);
            $table->index('hashtag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_hashtags');
        Schema::dropIfExists('content_items');
        Schema::dropIfExists('ai_generation_requests');
        Schema::dropIfExists('content_folders');
    }
};
