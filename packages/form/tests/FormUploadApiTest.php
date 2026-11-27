<?php

namespace Bza\Form\Tests;

use Bza\Form\Models\FormUpload;
use Bza\Form\Tests\Fixtures\ContactForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Files of an `upload` field are sent one by one before the form, then referenced by their identifier.
 */
class FormUploadApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(FormUpload::DISK);
    }

    private function uploadUrl(string $field = 'photos'): string
    {
        return $this->packageRoute('v1.forms.uploads.store', ['form' => 'test.contact', 'field' => $field]);
    }

    public function test_an_account_uploads_a_file_and_gets_its_identifier(): void
    {
        $user = $this->signIn();

        $id = $this->postJson($this->uploadUrl(), ['file' => UploadedFile::fake()->image('photo.jpg', 200, 100)])
            ->assertCreated()
            ->json('data.id');

        $upload = FormUpload::query()->where('uuid', $id)->sole();
        $this->assertSame($user->getKey(), $upload->user_id);
        $this->assertSame(['test.contact', 'photos'], [$upload->form, $upload->field]);
        Storage::disk(FormUpload::DISK)->assertExists($upload->path);
    }

    public function test_the_file_is_checked_against_the_field(): void
    {
        $this->signIn();

        $this->postJson($this->uploadUrl(), ['file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
        $this->postJson($this->uploadUrl(), ['file' => UploadedFile::fake()->image('big.jpg')->size(600)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
        // Not a file field.
        $this->postJson($this->uploadUrl('name'), ['file' => UploadedFile::fake()->image('photo.jpg')])->assertNotFound();
    }

    public function test_a_visitor_cannot_upload_by_default(): void
    {
        $this->postJson($this->uploadUrl(), ['file' => UploadedFile::fake()->image('photo.jpg')])->assertUnauthorized();
        $this->assertSame(0, FormUpload::query()->count());
    }

    public function test_the_form_accepts_only_the_accounts_own_pending_files(): void
    {
        $this->signIn('first@example.test');
        $mine = $this->postJson($this->uploadUrl(), ['file' => UploadedFile::fake()->image('photo.jpg')])->json('data.id');

        $form = new ContactForm;
        $data = ['name' => 'Ada', 'email' => 'ada@example.test'];

        $this->assertTrue(Validator::make([...$data, 'photos' => [$mine]], $form->rules())->passes());
        $this->assertTrue(Validator::make([...$data, 'photos' => ['unknown']], $form->rules())->fails());
        // At most two files, each once.
        $this->assertTrue(Validator::make([...$data, 'photos' => [$mine, $mine]], $form->rules())->fails());

        // Another account cannot reuse it.
        $this->signIn('second@example.test');
        $this->assertTrue(Validator::make([...$data, 'photos' => [$mine]], $form->rules())->fails());
    }

    public function test_an_expired_file_is_pruned_with_its_content(): void
    {
        $this->signIn();
        $id = $this->postJson($this->uploadUrl(), ['file' => UploadedFile::fake()->image('photo.jpg')])->json('data.id');
        $upload = FormUpload::query()->where('uuid', $id)->sole();

        $this->travel(FormUpload::LIFETIME_HOURS + 1)->hours();
        $this->assertFalse(FormUpload::isPendingFor($id, $upload->user_id, 'photos'));

        $this->artisan('model:prune', ['--model' => [FormUpload::class]])->assertSuccessful();
        $this->assertModelMissing($upload);
        Storage::disk(FormUpload::DISK)->assertMissing($upload->path);
    }
}
