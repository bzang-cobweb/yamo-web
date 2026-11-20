<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Qui agit dans une notification (décision 2026-11-20-notification-user-id) : une
 * colonne `user_id`, liée aux comptes et vidée quand le compte est supprimé, à la
 * place de `actor_id` dans les données. Les notifications existantes y gardent le
 * leur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('notifiable_id')->constrained()->nullOnDelete();
        });

        DB::table('notifications')->orderBy('id')->each(function (object $row): void {
            $data = json_decode((string) $row->data, true);
            if (! is_array($data) || ! array_key_exists('actor_id', $data)) {
                return;
            }
            $actor = is_int($data['actor_id']) && DB::table('users')->where('id', $data['actor_id'])->exists() ? $data['actor_id'] : null;
            unset($data['actor_id']);
            DB::table('notifications')->where('id', $row->id)->update(['user_id' => $actor, 'data' => json_encode($data)]);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
