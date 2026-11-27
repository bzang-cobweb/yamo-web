<?php

namespace Bza\ConfigViewer\Contracts;

/**
 * Decides which values are sensitive and must not be shown.
 */
interface ValueMasker
{
    public function shouldMask(string $key, mixed $value): bool;

    /** The text shown in place of a masked value. */
    public function placeholder(): string;
}
