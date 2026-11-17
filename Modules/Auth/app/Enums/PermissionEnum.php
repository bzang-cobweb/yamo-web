<?php

namespace Modules\Auth\Enums;

/**
 * Les permissions vérifiées par le code. Elles se déclarent ici et se synchronisent
 * en base par AuthDatabaseSeeder ; le back-office ne les crée ni ne les supprime.
 */
enum PermissionEnum: string
{
    case ACCESS_ADMIN = 'admin.access';

    case VIEW_USERS = 'users.view';
    case CREATE_USERS = 'users.create';
    case UPDATE_USERS = 'users.update';
    case DELETE_USERS = 'users.delete';

    case VIEW_ROLES = 'roles.view';
    case CREATE_ROLES = 'roles.create';
    case UPDATE_ROLES = 'roles.update';
    case DELETE_ROLES = 'roles.delete';

    case VIEW_PERMISSIONS = 'permissions.view';

    case VIEW_COUNTRIES = 'countries.view';
    case CREATE_COUNTRIES = 'countries.create';
    case UPDATE_COUNTRIES = 'countries.update';
    case DELETE_COUNTRIES = 'countries.delete';

    case VIEW_REGIONS = 'regions.view';
    case CREATE_REGIONS = 'regions.create';
    case UPDATE_REGIONS = 'regions.update';
    case DELETE_REGIONS = 'regions.delete';

    case VIEW_ADDRESSES = 'addresses.view';
    case CREATE_ADDRESSES = 'addresses.create';
    case UPDATE_ADDRESSES = 'addresses.update';
    case DELETE_ADDRESSES = 'addresses.delete';

    case UPDATE_SETTINGS = 'settings.update';

    public function label(): string
    {
        return __('auth::permission.names.'.$this->value);
    }
}
