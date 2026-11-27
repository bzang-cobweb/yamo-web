<?php

namespace Bza\Form\Tests\Fixtures;

use Bza\Form\Support\Form;

/**
 * A registered form that is switched off: it answers like an unknown one.
 */
class UnavailableForm extends Form
{
    public static function name(): string
    {
        return 'test.unavailable';
    }

    public static function isAvailable(): bool
    {
        return false;
    }

    public function submitLabel(): string
    {
        return 'Send';
    }

    public function fields(): array
    {
        return [];
    }
}
