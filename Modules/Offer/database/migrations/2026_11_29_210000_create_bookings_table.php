<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les demandes de réservation ou de visite (décision 2026-11-19-contrat-reservations) :
 * le membre qui demande, l'offre, les dates, le prix figé à l'envoi, et la réponse
 * du propriétaire. Rien n'est supprimé : une demande change de statut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // `stay` (séjour) ou `visit` (visite).
            $table->string('type', 16);
            // Séjour : arrivée et départ ; visite : la date souhaitée, sans départ.
            $table->date('check_in');
            $table->date('check_out')->nullable();
            $table->unsignedTinyInteger('guests')->nullable();
            $table->text('message')->nullable();
            // Prix figé à l'envoi : le prix unitaire, le total d'un séjour et la devise.
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('status', 16)->default('pending');
            $table->text('refusal_message')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['offer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
