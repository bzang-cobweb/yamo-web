<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menu du profil géré dans le back-office : une ligne par écran de l'app, dans
 * l'ordre affiché jusqu'ici (décision 2026-11-17-tri-regions-menu-profil).
 */
return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::dropIfExists('profile_menu_links');
    }
};
