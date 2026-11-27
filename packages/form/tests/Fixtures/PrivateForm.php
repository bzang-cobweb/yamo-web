<?php

namespace Bza\Form\Tests\Fixtures;

use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A form only some accounts may request: those whose e-mail ends with `@allowed.test`.
 */
class PrivateForm extends Form
{
    public static function name(): string
    {
        return 'test.private';
    }

    public function submitLabel(): string
    {
        return 'Save';
    }

    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return $user !== null && str_ends_with((string) $user->getAttribute('email'), '@allowed.test');
    }

    public function fields(): array
    {
        return [Field::text('note')->label('Note')];
    }
}
