<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->enum('status', ['pending', 'publishing', 'published', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('published_at')->nullable();
            $table->string('external_post_id')->nullable();
            $table->string('external_post_url', 500)->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->index(['scheduled_at', 'status']);
            $table->index('content_item_id');
            $table->index('social_account_id');
            $table->index('status');
        });

        Schema::create('publish_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_post_id')->constrained()->cascadeOnDelete();
            $table->enum('event', ['queued', 'started', 'success', 'failed', 'retry', 'cancelled']);
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('scheduled_post_id');
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publish_logs');
        Schema::dropIfExists('scheduled_posts');
    }
};
