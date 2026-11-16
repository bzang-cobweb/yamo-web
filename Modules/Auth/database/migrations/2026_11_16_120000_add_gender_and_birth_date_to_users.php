<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Genre (`GenderEnum`) et date de naissance. Les deux acceptent `null` : les
 * comptes existants et ceux créés par `auth:create-user` n'en ont pas ; le genre
 * est exigé par le formulaire Informations personnelles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 16)->nullable()->after('last_name');
            $table->date('birth_date')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'birth_date']);
        });
    }
};
