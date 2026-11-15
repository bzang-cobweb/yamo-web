<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remet `name`, obligatoire et unique, comme pseudonyme
 * (décision 2026-11-15-name-pseudonyme-unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        // Reprise : « prénom nom », suffixé en cas de doublon.
        $taken = [];
        DB::table('users')->orderBy('id')->each(function (object $user) use (&$taken): void {
            $base = trim($user->first_name.' '.$user->last_name) ?: 'user';
            $name = $base;

            for ($i = 2; in_array(mb_strtolower($name), $taken, true); $i++) {
                $name = $base.' '.$i;
            }

            $taken[] = mb_strtolower($name);
            DB::table('users')->where('id', $user->id)->update(['name' => $name]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn('name');
        });
    }
};
