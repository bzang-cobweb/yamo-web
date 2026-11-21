<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `updated_at` ne dit plus que la modification d'une étape (décision 2026-11-21-etape-modifiee-date) :
 * celles qu'une lecture a touchées reprennent leur date de création.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')->whereColumn('updated_at', '!=', 'created_at')->update(['updated_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Rien à défaire : les anciennes dates de lecture ne sont pas gardées.
    }
};
