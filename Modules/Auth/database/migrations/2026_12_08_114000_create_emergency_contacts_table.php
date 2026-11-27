<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le contact d'urgence d'un compte (décision 2026-11-27-contact-d-urgence) : un seul par compte ; nom, lien de parenté,
 * e-mail, téléphone (E.164, avec son pays) et la langue dans laquelle lui parler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32);
            $table->foreignId('phone_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('locale', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};
