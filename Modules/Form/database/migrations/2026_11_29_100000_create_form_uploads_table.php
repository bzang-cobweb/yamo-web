<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fichiers envoyés pour un champ `upload`, en attente de l'enregistrement du
 * formulaire (décision 2026-11-19-contrat-champ-upload-photos) ; supprimés après
 * 24 h s'ils ne servent pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('form');
            $table->string('field');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_uploads');
    }
};
