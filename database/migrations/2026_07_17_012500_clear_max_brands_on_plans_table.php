<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->update(['max_brands' => null]);
    }

    public function down(): void
    {
        // Irreversible: previous per-plan brand limits are not restored.
    }
};
