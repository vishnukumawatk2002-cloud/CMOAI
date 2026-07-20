<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->enum('platform', [
                'facebook',
                'instagram',
                'linkedin',
                'x',
                'youtube',
                'snapchat',
                'pinterest',
                'threads',
                'google_business',
            ]);
            $table->string('account_name');
            $table->string('account_handle')->nullable();
            $table->enum('account_type', ['page', 'profile', 'channel', 'group', 'business'])->default('page');
            $table->string('external_id');
            $table->unsignedInteger('follower_count')->default(0);
            $table->string('profile_image_url', 500)->nullable();
            $table->enum('status', ['active', 'expired', 'disconnected', 'error'])->default('active');
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['brand_id', 'platform', 'external_id']);
            $table->index(['brand_id', 'status']);
            $table->index('platform');
        });

        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('token_type', 50)->default('Bearer');
            $table->timestamp('expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
        Schema::dropIfExists('social_accounts');
    }
};
