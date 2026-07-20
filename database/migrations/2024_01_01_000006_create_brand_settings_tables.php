<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_voice_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('tone_style', [
                'professional',
                'casual',
                'bold',
                'educational',
                'witty',
                'luxury',
            ])->default('professional');
            $table->text('company_description')->nullable();
            $table->text('products_services')->nullable();
            $table->text('target_audience')->nullable();
            $table->json('keywords')->nullable();
            $table->json('avoid_words')->nullable();
            $table->timestamps();
        });

        Schema::create('brand_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50);
            $table->char('hex_value', 7);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('brand_id');
        });

        Schema::create('brand_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path', 500);
            $table->string('disk', 50)->default('local');
            $table->enum('file_type', [
                'logo',
                'image',
                'pdf',
                'video',
                'audio',
                'docx',
                'website',
                'guidelines',
            ]);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->enum('status', ['uploading', 'processing', 'indexed', 'failed'])->default('uploading');
            $table->timestamp('indexed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['brand_id', 'status']);
            $table->index('file_type');
        });

        Schema::create('brand_knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('detected_tone')->nullable();
            $table->string('detected_audience', 500)->nullable();
            $table->text('detected_services')->nullable();
            $table->json('top_keywords')->nullable();
            $table->enum('training_status', ['idle', 'processing', 'complete', 'failed'])->default('idle');
            $table->timestamp('last_trained_at')->nullable();
            $table->text('training_error')->nullable();
            $table->timestamps();

            $table->index('training_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_knowledge_bases');
        Schema::dropIfExists('brand_assets');
        Schema::dropIfExists('brand_colors');
        Schema::dropIfExists('brand_voice_settings');
    }
};
