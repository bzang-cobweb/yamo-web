<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Touches no schema: writes to a table the test creates for real, to prove the
 * replay never reaches the database.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('migration_viewer_guard')->insert(['label' => 'written']);
        DB::table('migration_viewer_guard')->delete();
    }
};
