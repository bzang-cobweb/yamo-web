<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le pivot `address_user` devient la table du modèle `UserAddress`, qui porte le
 * destinataire (genre, prénom, nom). Les lignes existantes le reprennent du
 * compte. Un compte peut viser la même adresse plusieurs fois : l'unicité
 * compte et adresse disparaît (décision 2026-11-18-adresse-du-compte-modele).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('address_user', 'user_addresses');

        Schema::table('user_addresses', function (Blueprint $table) {
            // L'index unique servait aussi la clé étrangère `address_id` : un index
            // simple le remplace avant qu'il disparaisse.
            $table->index('address_id', 'user_addresses_address_id_index');
            $table->dropUnique('address_user_address_id_user_id_unique');
            $table->string('gender')->nullable()->after('user_id');
            $table->string('first_name')->default('')->after('gender');
            $table->string('last_name')->default('')->after('first_name');
        });

        DB::table('user_addresses')->orderBy('id')->each(function (object $row): void {
            $user = DB::table('users')->where('id', $row->user_id)->first(['gender', 'first_name', 'last_name']);

            DB::table('user_addresses')->where('id', $row->id)->update([
                'gender' => $user?->gender,
                'first_name' => (string) ($user?->first_name ?? ''),
                'last_name' => (string) ($user?->last_name ?? ''),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['gender', 'first_name', 'last_name']);
            $table->unique(['address_id', 'user_id'], 'address_user_address_id_user_id_unique');
            $table->dropIndex('user_addresses_address_id_index');
        });

        Schema::rename('user_addresses', 'address_user');
    }
};
