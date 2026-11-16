<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-mail facultatif (un compte a un e-mail et/ou un téléphone), pays et région,
 * téléphone (E.164, unique, avec le pays de son indicatif et sa date de
 * vérification), et dernière activité (statut en ligne).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->foreignId('country_id')->nullable()->after('birth_date')->constrained('countries')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->after('country_id')->constrained('regions')->nullOnDelete();
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->foreignId('phone_country_id')->nullable()->after('phone')->constrained('countries')->nullOnDelete();
            $table->timestamp('phone_verified_at')->nullable()->after('phone_country_id');
            $table->timestamp('last_seen_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropConstrainedForeignId('phone_country_id');
            $table->dropUnique(['phone']);
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn(['phone', 'phone_verified_at', 'last_seen_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
