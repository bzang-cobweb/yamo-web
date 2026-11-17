<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le menu du profil est défini dans `ProfileMenuService`, plus dans le back-office
 * (décision 2026-11-17-regions-alphabetiques-menu-en-code) : sa table disparaît.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('profile_menu_links');
    }

    public function down(): void
    {
        Schema::create('profile_menu_links', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->unsignedInteger('position')->default(0)->index();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('profile_menu_links')->insert(array_map(
            fn (string $key, int $index): array => ['key' => $key, 'position' => $index + 1, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            $keys = ['personal', 'email', 'phone', 'password', 'preferences'],
            array_keys($keys),
        ));
    }
};
