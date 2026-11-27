<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An upload may belong to no account: a visitor's, when `form.uploads.guests` is on (decision
 * 2026-11-27-paquet-form-acces). Additive: existing uploads keep their account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_uploads', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Back to a required account: visitors' uploads, short-lived anyway, are removed first.
        DB::table('form_uploads')->whereNull('user_id')->delete();
        Schema::table('form_uploads', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
