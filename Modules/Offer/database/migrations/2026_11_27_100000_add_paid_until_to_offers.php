<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date jusqu'à laquelle l'offre est payée (décision 2026-11-19-acces-avis-backoffice) :
 * renseignée dans le back-office au lancement ; plus tard, une offre non payée ne
 * sera plus visible (décision 2026-11-19-modele-proprietaire-payant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->timestamp('paid_until')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['paid_until']);
            $table->dropColumn('paid_until');
        });
    }
};
