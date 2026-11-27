<?php

namespace Tests\Feature\Form;

use Bza\Form\Models\FormUpload;
use Bza\Form\Support\FormAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Tests\TestCase;

/**
 * Le paquet de formulaires n'impose ni garde ni accès : tout se règle dans `config/form.php` (décision
 * 2026-11-27-paquet-form-acces).
 */
class FormAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_guard_is_the_configured_one_or_laravels_default(): void
    {
        $this->assertSame('sanctum', FormAuth::guard());

        config(['form.guard' => null]);
        $this->assertNull(FormAuth::guard());
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertSame($user->getKey(), FormAuth::id());
    }

    public function test_uploads_require_an_account_of_the_guard_unless_guests_may_upload(): void
    {
        $this->assertSame(['auth:sanctum', 'throttle:60,1'], FormAuth::uploadMiddleware());

        config(['form.guard' => null]);
        $this->assertSame(['auth', 'throttle:60,1'], FormAuth::uploadMiddleware());

        config(['form.uploads.guests' => true, 'form.uploads.middleware' => []]);
        $this->assertSame([], FormAuth::uploadMiddleware());
    }

    public function test_a_visitors_upload_counts_only_when_guests_may_upload(): void
    {
        $upload = FormUpload::query()->create([
            'uuid' => (string) Str::uuid(), 'user_id' => null, 'form' => 'offer.offer', 'field' => 'photos',
            'path' => 'form-uploads/x', 'mime_type' => 'image/jpeg', 'size' => 10,
        ]);

        $this->assertFalse(FormUpload::isPendingFor($upload->uuid, null, 'photos'));
        config(['form.uploads.guests' => true]);
        $this->assertTrue(FormUpload::isPendingFor($upload->uuid, null, 'photos'));
        // Le fichier d'un visiteur n'est pas celui d'un compte.
        $this->assertFalse(FormUpload::isPendingFor($upload->uuid, 1, 'photos'));
    }
}
