<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Afficher l'adresse ou non, choisi par le propriétaire : sur l'offre (le bloc « Localisation » de la
 * fiche) et sur chaque demande (la ligne « Adresse » de son détail), qui part de la valeur de l'offre
 * (décision 2026-11-21-afficher-adresse). Vrai par défaut : rien ne change pour l'existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->boolean('show_address')->default(true)->after('details');
        });
        Schema::table('bookings', function (Blueprint $table): void {
            $table->boolean('show_address')->default(true)->after('cancel_message');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('show_address');
        });
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropColumn('show_address');
        });
    }
};
