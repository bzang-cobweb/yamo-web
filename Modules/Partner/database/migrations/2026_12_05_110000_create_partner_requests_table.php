<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les demandes d'un partenaire (décision 2026-11-24-partenaire-demandes) : un ajout ou une modification depuis l'app,
 * en validation jusqu'à la réponse de l'équipe ; ses notifications forment un fil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            // Le compte qui l'a faite ; gardée si le compte est supprimé.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // `created` (ajout) ou `updated` (modification).
            $table->string('action', 16);
            // `pending` tant que l'équipe n'a pas répondu, puis le statut donné au partenaire.
            $table->string('status', 16)->default('pending');
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_requests');
    }
};
