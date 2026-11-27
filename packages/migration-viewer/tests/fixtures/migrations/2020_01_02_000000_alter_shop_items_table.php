<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->renameColumn('name', 'title');
            $table->dropColumn('price');
            $table->foreignId('category_id')->nullable()->constrained('shop_categories')->nullOnDelete();
        });
    }
};
