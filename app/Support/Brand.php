<?php

namespace App\Support;

/**
 * Couleurs de marque, tirées du logo YaMo (`public/images/logo`) : violet pour
 * l'action principale, rose en accent. Mêmes valeurs dans l'app mobile
 * (`src/shared/theme/theme.ts`).
 */
final class Brand
{
    /** Violet du logo. */
    public const PRIMARY = '#7B5CFA';

    /** Rose du logo. */
    public const ACCENT = '#FF5A7A';

    /** Violet profond du logo (recouvrement des deux cercles). */
    public const DEEP = '#3E1F9E';

    /** Variantes du logo « inverse », sur fond sombre. */
    public const PRIMARY_ON_DARK = '#9C82FF';

    public const ACCENT_ON_DARK = '#FF7A93';
}
