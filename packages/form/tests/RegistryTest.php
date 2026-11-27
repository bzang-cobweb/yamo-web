<?php

namespace Bza\Form\Tests;

use Bza\Form\Support\FormRegistry;
use Bza\Form\Support\ListingRegistry;
use Bza\Form\Tests\Fixtures\ContactForm;
use Bza\Form\Tests\Fixtures\Note;
use InvalidArgumentException;

/**
 * The registries know the forms and lists by public name.
 */
class RegistryTest extends TestCase
{
    public function test_forms_are_found_by_name_and_built_for_the_account(): void
    {
        $registry = $this->app->make(FormRegistry::class);

        $this->assertTrue($registry->has('test.contact'));
        $this->assertContains('test.private', $registry->names());
        $this->assertInstanceOf(ContactForm::class, $registry->make('test.contact'));
        $this->assertNull($registry->make('test.missing'));
        // Registered but unavailable: like an unknown form.
        $this->assertTrue($registry->has('test.unavailable'));
        $this->assertNull($registry->make('test.unavailable'));
    }

    public function test_only_forms_and_lists_can_be_registered(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->app->make(FormRegistry::class)->register(Note::class);
    }

    public function test_lists_are_found_by_name(): void
    {
        $registry = $this->app->make(ListingRegistry::class);

        $this->assertSame(['test.notes'], array_values(array_intersect($registry->names(), ['test.notes'])));
        $this->assertNull($registry->make('test.missing'));

        $this->expectException(InvalidArgumentException::class);
        $registry->register(ContactForm::class);
    }
}
