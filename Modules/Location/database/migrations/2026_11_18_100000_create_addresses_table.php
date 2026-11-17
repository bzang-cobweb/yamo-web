<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adresses d'un pays, chacune unique, et leur rattachement aux comptes par le
 * pivot `address_user` (décision 2026-11-18-adresses-liste-generique).
 * `fingerprint` est l'empreinte de la rue, du NPA et de la ville sans casse ni
 * espaces en trop : l'unicité tient quelle que soit la base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('street');
            $table->string('zip', 20);
            $table->string('city');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->char('fingerprint', 40);
            $table->timestamps();

            $table->unique(['country_id', 'fingerprint']);
        });

        Schema::create('address_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('address_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['address_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_user');
        Schema::dropIfExists('addresses');
    }
};
