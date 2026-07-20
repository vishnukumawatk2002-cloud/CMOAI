<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE content_items MODIFY COLUMN platform ENUM(
            'facebook',
            'instagram',
            'linkedin',
            'x',
            'youtube',
            'snapchat',
            'pinterest',
            'threads',
            'google_business',
            'multi'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('content_items')->where('platform', 'snapchat')->update(['platform' => 'multi']);

        DB::statement("ALTER TABLE content_items MODIFY COLUMN platform ENUM(
            'facebook',
            'instagram',
            'linkedin',
            'x',
            'youtube',
            'pinterest',
            'threads',
            'google_business',
            'multi'
        ) NOT NULL");
    }
};
