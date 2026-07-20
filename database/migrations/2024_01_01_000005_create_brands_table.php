<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('website', 500)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('country', 100)->default('India');
            $table->string('language', 50)->default('English');
            $table->string('tone', 100)->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->text('short_description')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->unsignedTinyInteger('setup_step')->default(1)->comment('1=business, 2=assets, 3=social');
            $table->timestamp('setup_completed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index('is_active');
        });

        Schema::create('brand_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'editor', 'viewer'])->default('editor');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_members');
        Schema::dropIfExists('brands');
    }
};
