<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le message facultatif du propriétaire qui accepte une demande, comme celui d'un refus (décision
 * 2026-11-22-accepter-avec-message).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->text('acceptance_message')->nullable()->after('refusal_message');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('acceptance_message');
        });
    }
};
