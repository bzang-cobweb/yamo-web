<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            // A folder is only deleted empty: the database refuses to orphan its subfolders.
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->restrictOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['parent_id', 'name']);
        });

        // The rights given on a target: the root (both null), a folder, or the
        // read-only folder of a model type's media (linked_type).
        Schema::create('media_folder_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
            $table->string('linked_type')->nullable();
            $table->morphs('grantee');
            // Ability => true (allowed) or false (denied); a missing ability is inherited.
            $table->json('abilities');
            $table->timestamps();

            $table->index(['folder_id', 'linked_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_folder_grants');
        Schema::dropIfExists('media_folders');
    }
};
