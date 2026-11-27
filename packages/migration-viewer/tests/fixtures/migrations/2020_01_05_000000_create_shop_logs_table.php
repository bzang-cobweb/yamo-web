<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Throws after its first table: the replay keeps what was recorded before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_logs', function (Blueprint $table) {
            $table->id();
            $table->text('message');
        });

        throw new RuntimeException('Broken on purpose');
    }
};
