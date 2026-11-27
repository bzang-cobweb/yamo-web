<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->decimal('price', 8, 2)->default(0);
            $table->enum('status', ['draft', 'live'])->default('draft')->comment('Publication');
            $table->timestamps();
        });
    }
};
