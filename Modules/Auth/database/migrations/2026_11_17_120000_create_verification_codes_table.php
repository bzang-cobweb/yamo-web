<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Codes de vérification envoyés par e-mail ou SMS. Un défi (`challenge`) regroupe
 * les codes d'une même opération (connexion, inscription, changement d'e-mail ou
 * de téléphone) ; le code n'est jamais stocké en clair.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->uuid('challenge')->index();
            $table->string('purpose', 32);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel', 8);
            $table->string('destination');
            $table->text('payload')->nullable();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
