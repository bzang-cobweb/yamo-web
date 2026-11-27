<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les numéros supplémentaires d'un compte (décision 2026-11-27-plusieurs-numeros) : le principal reste `users.phone` ;
 * chacun ici est en E.164, avec le pays de son indicatif, vérifié par un code SMS à l'ajout ; un même numéro une fois
 * par compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone', 20);
            $table->foreignId('phone_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'phone']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_phones');
    }
};
