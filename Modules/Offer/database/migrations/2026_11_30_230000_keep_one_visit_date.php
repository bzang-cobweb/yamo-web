<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Une seule date par demande de visite (décision 2026-11-22-visite-une-seule-date) : la seconde date proposée
 * (`check_out` d'une visite) est retirée ; la première reste. Pas de retour arrière : les secondes dates ne sont pas
 * gardées.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')->where('type', 'visit')->whereNotNull('check_out')->update(['check_out' => null]);
    }

    public function down(): void
    {
        // Les secondes dates ne sont pas gardées.
    }
};
