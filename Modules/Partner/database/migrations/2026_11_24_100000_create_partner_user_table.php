<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les comptes rattachés à un partenaire (décision 2026-11-24-partenaires-par-les-membres) : plusieurs par
 * partenaire ; `is_admin` : administrateur, vrai pour celui qui le propose.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->unique(['partner_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_user');
    }
};
