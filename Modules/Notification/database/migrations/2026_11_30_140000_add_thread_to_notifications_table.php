<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le fil d'une notification (décision 2026-11-20-suivi-demande) : `booking:12` pour
 * les étapes d'une demande. Les modules remplissent celui des notifications
 * existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('thread', 64)->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['thread']);
            $table->dropColumn('thread');
        });
    }
};
