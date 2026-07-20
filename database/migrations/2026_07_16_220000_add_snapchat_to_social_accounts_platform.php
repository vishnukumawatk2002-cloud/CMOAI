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

        DB::statement("ALTER TABLE social_accounts MODIFY COLUMN platform ENUM(
            'facebook',
            'instagram',
            'linkedin',
            'x',
            'youtube',
            'snapchat',
            'pinterest',
            'threads',
            'google_business'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('social_accounts')->where('platform', 'snapchat')->delete();

        DB::statement("ALTER TABLE social_accounts MODIFY COLUMN platform ENUM(
            'facebook',
            'instagram',
            'linkedin',
            'x',
            'youtube',
            'pinterest',
            'threads',
            'google_business'
        ) NOT NULL");
    }
};
