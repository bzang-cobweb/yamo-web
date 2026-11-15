<?php

namespace Modules\Auth\Models;

use Modules\Auth\Enums\PermissionEnum;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Libellé traduit de la permission, ou son nom si elle n'est plus déclarée.
     */
    public function label(): string
    {
        return PermissionEnum::tryFrom($this->name)?->label() ?? $this->name;
    }
}
