<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Permission;
use Modules\Auth\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Synchronise les permissions de PermissionEnum et les rôles de RoleEnum en base.
 * Rejouable : à lancer après chaque ajout de permission. Chaque rôle reçoit ses
 * permissions de base (`RoleEnum::basePermissions()`) sans perdre celles données
 * dans le back-office.
 */
class AuthDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Permission::query()
            ->whereNotIn('name', array_column(PermissionEnum::cases(), 'value'))
            ->delete();

        foreach (RoleEnum::cases() as $role) {
            // Ajout sans retrait : givePermissionTo ne détache rien.
            Role::findOrCreate($role->value)->givePermissionTo($role->basePermissions());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
