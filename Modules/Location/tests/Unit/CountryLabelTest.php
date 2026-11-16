<?php

namespace Modules\Location\Tests\Unit;

use Modules\Location\Models\Country;
use Tests\TestCase;

class CountryLabelTest extends TestCase
{
    public function test_label_appends_the_native_name_when_it_differs(): void
    {
        $germany = new Country([
            'code' => 'DE',
            'name' => ['fr' => 'Allemagne', 'en' => 'Germany'],
            'native_name' => 'Deutschland',
        ]);

        app()->setLocale('fr');
        $this->assertSame('Allemagne (Deutschland)', $germany->label());

        app()->setLocale('en');
        $this->assertSame('Germany (Deutschland)', $germany->label());
    }

    public function test_label_is_the_name_alone_when_the_native_name_is_the_same(): void
    {
        $france = new Country([
            'code' => 'FR',
            'name' => ['fr' => 'France', 'en' => 'France'],
            'native_name' => 'France',
        ]);

        app()->setLocale('fr');

        $this->assertSame('France', $france->label());
    }
}
