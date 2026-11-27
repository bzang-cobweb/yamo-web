<?php

namespace Bza\MediaManager\Tests;

use Bza\MediaManager\Grantee;
use Bza\MediaManager\Grants;
use Bza\MediaManager\LinkedMedia;
use Bza\MediaManager\MediaLibrary;
use Bza\MediaManager\MediaManagerException;
use Bza\MediaManager\Models\FolderGrant;
use Bza\MediaManager\Models\MediaFolder;
use Bza\MediaManager\Target;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Folders and files: unique names per folder, moves that keep the file where
 * it is on the disk, empty folders only deleted, linked media read-only.
 */
class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['media-library.disk_name' => 'public', 'media-manager.disk' => null]);
    }

    private function library(): MediaLibrary
    {
        return $this->app->make(MediaLibrary::class);
    }

    private function upload(MediaFolder $folder, string $name, string $content = 'content'): Media
    {
        $path = tempnam(sys_get_temp_dir(), 'media');
        file_put_contents($path, $content);

        try {
            return $this->library()->upload($folder, $path, $name);
        } finally {
            @unlink($path);
        }
    }

    public function test_folder_names_are_unique_in_their_parent_whatever_the_case(): void
    {
        $photos = $this->library()->createFolder(null, ' Photos ');
        $this->library()->createFolder($photos, 'Photos');

        $this->assertSame('Photos', $photos->name);

        $this->expectException(MediaManagerException::class);
        $this->library()->createFolder(null, 'PHOTOS');
    }

    public function test_a_name_with_a_slash_is_refused(): void
    {
        $this->expectException(MediaManagerException::class);

        $this->library()->createFolder(null, 'a/b');
    }

    public function test_a_folder_cannot_be_moved_into_its_own_subfolder(): void
    {
        $parent = $this->library()->createFolder(null, 'Parent');
        $child = $this->library()->createFolder($parent, 'Child');

        $this->expectException(MediaManagerException::class);
        $this->library()->moveFolder($parent, $child);
    }

    public function test_a_folder_is_moved_and_renamed(): void
    {
        $archive = $this->library()->createFolder(null, 'Archive');
        $folder = $this->library()->createFolder(null, '2025');

        $this->library()->moveFolder($folder, $archive);
        $this->library()->renameFolder($folder, 'Year 2025');

        $this->assertSame([$archive->id, 'Year 2025'], [$folder->fresh()->parent_id, $folder->fresh()->name]);
    }

    public function test_only_an_empty_folder_is_deleted(): void
    {
        $folder = $this->library()->createFolder(null, 'Docs');
        $file = $this->upload($folder, 'plan.pdf');

        try {
            $this->library()->deleteFolder($folder);
            $this->fail('A folder holding a file was deleted.');
        } catch (MediaManagerException) {
            $this->assertModelExists($folder);
        }

        $this->library()->deleteFile($file);
        $this->library()->deleteFolder($folder);

        $this->assertModelMissing($folder);
    }

    public function test_an_uploaded_file_gets_a_free_name_in_its_folder(): void
    {
        $folder = $this->library()->createFolder(null, 'Docs');

        $first = $this->upload($folder, 'Plan du site.pdf');
        $second = $this->upload($folder, 'Plan du site.PDF');

        $this->assertSame('Plan-du-site.pdf', $first->file_name);
        $this->assertSame('Plan-du-site-1.pdf', $second->file_name);
        $this->assertSame('media-manager/'.$first->uuid.'/Plan-du-site.pdf', $first->getPathRelativeToRoot());
        Storage::disk('public')->assertExists($first->getPathRelativeToRoot());
    }

    public function test_a_renamed_file_keeps_its_extension_and_is_renamed_on_the_disk(): void
    {
        $folder = $this->library()->createFolder(null, 'Docs');
        $file = $this->upload($folder, 'draft.txt');

        $this->library()->renameFile($file, 'final version');

        $this->assertSame('final-version.txt', $file->fresh()->file_name);
        Storage::disk('public')->assertExists('media-manager/'.$file->uuid.'/final-version.txt');
        Storage::disk('public')->assertMissing('media-manager/'.$file->uuid.'/draft.txt');
    }

    public function test_a_moved_file_keeps_its_address(): void
    {
        $from = $this->library()->createFolder(null, 'From');
        $to = $this->library()->createFolder(null, 'To');
        $file = $this->upload($from, 'logo.txt');
        $url = $file->getUrl();

        $this->library()->moveFile($file, $to);

        $this->assertTrue($this->library()->folderOf($file->fresh())->is($to));
        $this->assertSame($url, $file->fresh()->getUrl());
        $this->assertSame(0, $this->library()->files($from)->count());
    }

    public function test_a_file_cannot_be_moved_onto_a_taken_name(): void
    {
        $from = $this->library()->createFolder(null, 'From');
        $to = $this->library()->createFolder(null, 'To');
        $file = $this->upload($from, 'logo.txt');
        $this->upload($to, 'LOGO.txt');

        $this->expectException(MediaManagerException::class);
        $this->library()->moveFile($file, $to);
    }

    public function test_the_media_of_other_models_are_listed_read_only(): void
    {
        $folder = $this->library()->createFolder(null, 'Docs');
        $this->upload($folder, 'mine.txt');
        // A media attached to another model, written as the media library would.
        $linked = $this->upload($folder, 'avatar.txt');
        $linked->forceFill(['model_type' => 'user', 'model_id' => 5, 'collection_name' => 'avatar'])->save();

        $linkedMedia = $this->app->make(LinkedMedia::class);

        $this->assertSame(['user' => 1], $linkedMedia->types());
        $this->assertSame(['avatar' => 1], $linkedMedia->collections('user'));
        $this->assertSame(1, $this->library()->files($folder)->count());

        $this->expectException(MediaManagerException::class);
        $this->library()->deleteFile($linked->fresh());
    }

    public function test_rights_are_replaced_and_empty_rows_dropped(): void
    {
        $folder = $this->library()->createFolder(null, 'Docs');
        $grants = $this->app->make(Grants::class);

        $grants->replace(Target::folder($folder), [
            ['grantee' => Grantee::fromKey('role#1'), 'abilities' => ['view' => true, 'delete' => false, 'create' => null]],
            ['grantee' => Grantee::fromKey('role#2'), 'abilities' => ['view' => null]],
        ]);

        $this->assertSame([['role#1', ['view' => true, 'delete' => false]]], $grants->on(Target::folder($folder))
            ->map(fn (FolderGrant $grant): array => [$grant->granteeKey(), $grant->abilities])->all());
        $this->assertSame(0, $grants->on(Target::root())->count());

        // Deleting the folder deletes its rights.
        $this->library()->deleteFolder($folder);
        $this->assertSame(0, FolderGrant::query()->count());
    }
}
