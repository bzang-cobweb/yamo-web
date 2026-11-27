<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les choix de notifications du compte (décision 2026-11-27-notifications-du-profil) : activité du compte et rappels
 * (e-mail, SMS, push), offres et nouveautés (oui ou non). Vide : les choix par défaut, tout est reçu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_settings');
        });
    }
};
