<?php

namespace Bza\ConfigViewer\Tests;

use Bza\ConfigViewer\Support\ConfigFlattener;
use PHPUnit\Framework\TestCase;

class ConfigFlattenerTest extends TestCase
{
    public function test_it_walks_into_associative_arrays(): void
    {
        $flat = (new ConfigFlattener)->flatten(['smtp' => ['host' => 'localhost', 'port' => 25]], 'mail');

        $this->assertSame(['mail.smtp.host' => 'localhost', 'mail.smtp.port' => 25], $flat);
    }

    public function test_a_list_of_plain_values_and_an_empty_array_stay_one_entry(): void
    {
        $flat = (new ConfigFlattener)->flatten(['types' => ['image/png', 'image/webp'], 'none' => []], 'logo');

        $this->assertSame(['logo.types' => ['image/png', 'image/webp'], 'logo.none' => []], $flat);
    }

    public function test_a_list_of_arrays_is_walked_into_by_index(): void
    {
        $flat = (new ConfigFlattener)->flatten([['a' => 1], ['a' => 2]], 'items');

        $this->assertSame(['items.0.a' => 1, 'items.1.a' => 2], $flat);
    }
}
