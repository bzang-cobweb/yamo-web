<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Connexion par code, sans mot de passe (décision 2026-11-27-connexion-sans-mot-de-passe) :
 * un compte inscrit depuis l'app n'en a pas. Retour arrière : un mot de passe aléatoire
 * est donné aux comptes qui n'en ont pas, avant de rendre la colonne obligatoire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('password')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update(['password' => Hash::make(Str::random(40))]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
