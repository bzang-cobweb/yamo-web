<?php

namespace Bza\Form\Tests;

use Bza\Form\Support\FormAuth;
use Bza\Form\Support\FormRegistry;
use Bza\Form\Support\ListingRegistry;
use Bza\Form\Tests\Fixtures\ContactForm;
use Bza\Form\Tests\Fixtures\NoteListing;
use Bza\Form\Tests\Fixtures\PrivateForm;
use Bza\Form\Tests\Fixtures\UnavailableForm;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * The package's tests run inside the host application (its database, its `form` configuration), but only on the
 * forms and lists of `Fixtures`: nothing of the application's own modules is used.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $forms = $this->app->make(FormRegistry::class);
        $forms->register(ContactForm::class);
        $forms->register(PrivateForm::class);
        $forms->register(UnavailableForm::class);

        $this->app->make(ListingRegistry::class)->register(NoteListing::class);
    }

    /**
     * An account of the application's user model, signed in on the package's guard (`form.guard`).
     */
    protected function signIn(string $email = 'someone@example.test'): Authenticatable&Model
    {
        /** @var class-string<Model> $model */
        $model = config('auth.providers.users.model');
        /** @var Authenticatable&Model $user */
        $user = $model::factory()->create(['email' => $email]);

        $this->actingAs($user, FormAuth::guard());

        return $user;
    }

    /**
     * The URL of one of the package's routes, whatever the configured prefix and name prefix.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function packageRoute(string $name, array $parameters = []): string
    {
        return route(config('form.routes.name').$name, $parameters);
    }
}
