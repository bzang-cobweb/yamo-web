<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quand le membre a été invité à donner son avis, une fois sa demande terminée : une seule invitation par demande
 * (décision 2026-11-22-invitation-avis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('review_invited_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('review_invited_at');
        });
    }
};
