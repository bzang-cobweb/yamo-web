<?php

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Models\User;
use Tests\TestCase;

class UserInitialsTest extends TestCase
{
    private function user(array $attributes): User
    {
        return new User(['name' => 'pseudo', 'first_name' => '', 'last_name' => '', ...$attributes]);
    }

    public function test_initials_are_the_first_letters_in_upper_case_without_accents(): void
    {
        $this->assertSame('JD', $this->user(['first_name' => 'jeanne', 'last_name' => 'dupont'])->initials);
        $this->assertSame('EA', $this->user(['first_name' => 'Élodie', 'last_name' => 'Ängel'])->initials);
        $this->assertSame('CO', $this->user(['first_name' => 'Çédric', 'last_name' => 'Øster'])->initials);
    }

    public function test_without_first_and_last_name_the_pseudonym_gives_the_initial(): void
    {
        $this->assertSame('E', $this->user(['name' => 'étoile'])->initials);
    }

    public function test_full_name_joins_first_and_last_name(): void
    {
        $this->assertSame('Élodie Ängel', $this->user(['first_name' => 'Élodie', 'last_name' => 'Ängel'])->full_name);
    }
}
