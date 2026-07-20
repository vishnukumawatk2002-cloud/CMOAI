<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->unsignedSmallInteger('max_brands')->nullable()->comment('NULL = unlimited');
            $table->unsignedSmallInteger('max_social_accounts')->nullable()->comment('NULL = unlimited');
            $table->unsignedInteger('max_posts_per_month')->nullable()->comment('NULL = unlimited');
            $table->boolean('bulk_scheduling')->default(false);
            $table->boolean('ai_insights')->default(false);
            $table->boolean('white_label_reports')->default(false);
            $table->boolean('api_access')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
