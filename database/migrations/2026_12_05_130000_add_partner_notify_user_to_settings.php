<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le compte du back-office prévenu d'un ajout ou d'une modification de partenaire depuis l'app, choisi sur la page
 * Application (décision 2026-11-24-partenaire-compte-prevenu) ; vide : personne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('partner_notify_user_id')->nullable()->after('partner_payment_required')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_notify_user_id');
        });
    }
};
