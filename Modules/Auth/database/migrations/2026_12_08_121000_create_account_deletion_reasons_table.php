<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La raison donnée en supprimant son compte (décision 2026-11-27-confidentialite), gardée pour l'équipe : elle
 * survit à la purge du compte (`user_id` remis à vide).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_reasons');
    }
};
