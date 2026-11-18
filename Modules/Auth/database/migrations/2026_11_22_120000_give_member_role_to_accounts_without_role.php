<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Comptes déjà inscrits : `member` à ceux qui n'ont aucun rôle (décision
 * 2026-11-18-role-membre). Les comptes servis sont notés, pour que le retour en
 * arrière ne retire le rôle qu'à eux.
 */
return new class extends Migration
{
    private const SERVED = 'member_role_backfill';

    public function up(): void
    {
        $roles = config('permission.table_names.roles');
        $assigned = config('permission.table_names.model_has_roles');

        $memberId = DB::table($roles)->where('name', 'member')->where('guard_name', 'web')->value('id')
            ?? DB::table($roles)->insertGetId(['name' => 'member', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);

        Schema::create(self::SERVED, function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
        });

        $withoutRole = DB::table('users')
            ->whereNotExists(fn ($query) => $query->from($assigned)
                ->whereColumn($assigned.'.model_id', 'users.id')
                ->where($assigned.'.model_type', User::class))
            ->pluck('id');

        foreach ($withoutRole->chunk(500) as $chunk) {
            DB::table($assigned)->insert($chunk->map(fn ($userId) => ['role_id' => $memberId, 'model_type' => User::class, 'model_id' => $userId])->values()->all());
            DB::table(self::SERVED)->insert($chunk->map(fn ($userId) => ['user_id' => $userId])->values()->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $memberId = DB::table(config('permission.table_names.roles'))->where('name', 'member')->where('guard_name', 'web')->value('id');

        if ($memberId !== null && Schema::hasTable(self::SERVED)) {
            DB::table(config('permission.table_names.model_has_roles'))
                ->where('role_id', $memberId)
                ->where('model_type', User::class)
                ->whereIn('model_id', DB::table(self::SERVED)->select('user_id'))
                ->delete();
        }

        Schema::dropIfExists(self::SERVED);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
