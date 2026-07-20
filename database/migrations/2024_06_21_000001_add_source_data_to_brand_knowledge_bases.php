<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_knowledge_bases', function (Blueprint $table) {
            $table->json('source_data')->nullable()->after('top_keywords');
        });
    }

    public function down(): void
    {
        Schema::table('brand_knowledge_bases', function (Blueprint $table) {
            $table->dropColumn('source_data');
        });
    }
};
