<?php

namespace Modules\Auth\Enums;

enum RoleEnum: string
{
    /** Tous les droits : court-circuite les policies (HasBeforeAbility). */
    case SUPER_ADMIN = 'super_admin';
}
