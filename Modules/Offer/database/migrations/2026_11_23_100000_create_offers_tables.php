<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offres et catégories (décision 2026-11-18-offres-et-domaines). Les attributs
 * propres au domaine sont dans `details` (JSON) : la colonne ne s'appelle pas
 * `attributes`, propriété interne des modèles Eloquent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('offer_categories')->nullOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->decimal('price', 12, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
        Schema::dropIfExists('offer_categories');
    }
};
