<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per table the migrations touch, as the last synchronization saw it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('dropped')->default(false);
            $table->json('columns');
            $table->json('migrations');
            $table->unsignedInteger('columns_count')->default(0);
            $table->unsignedInteger('migrations_count')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->string('last_migration')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_tables');
    }
};
