<?php

namespace Tests\Feature;

use Bza\MediaManager\Filament\Pages\MediaManagerPage;
use Bza\MediaManager\Grantee;
use Bza\MediaManager\Grants;
use Bza\MediaManager\MediaLibrary;
use Bza\MediaManager\Models\FolderGrant;
use Bza\MediaManager\Models\MediaFolder;
use Bza\MediaManager\Target;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Auth\Enums\RoleEnum;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use Modules\Auth\Tests\Feature\Filament\AuthFilamentTestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Page « Médias » du back-office (paquet bza/laravel-media-manager) : dossiers et
 * fichiers, droits par dossier donnés aux rôles et aux utilisateurs, médias des
 * contenus en lecture seule ; le super administrateur a tous les droits.
 */
class MediaManagerPageTest extends AuthFilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);
    }

    private function library(): MediaLibrary
    {
        return app(MediaLibrary::class);
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::ADMIN);
        $this->actingAs($user);

        return $user;
    }

    private function upload(MediaFolder $folder, string $name): Media
    {
        // The fake file is deleted with its object: keep it until the upload is done.
        $file = UploadedFile::fake()->create($name, 4);

        return $this->library()->upload($folder, $file->getRealPath(), $name);
    }

    /**
     * @param  array<string, bool>  $abilities
     */
    private function grantAdmins(Target $target, array $abilities): void
    {
        app(Grants::class)->replace($target, [[
            'grantee' => Grantee::of(Role::findByName(RoleEnum::ADMIN->value)),
            'abilities' => $abilities,
        ]]);
    }

    public function test_a_super_admin_creates_a_folder_and_uploads_files_into_it(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(MediaManagerPage::class)
            ->callAction('newFolder', ['name' => 'Brochures'])
            ->assertHasNoActionErrors()
            ->assertCanSeeTableRecords(['folder:'.MediaFolder::query()->sole()->id])
            ->assertActionHidden('upload');

        $folder = MediaFolder::query()->sole();

        Livewire::withQueryParams(['folder' => $folder->id])
            ->test(MediaManagerPage::class)
            ->callAction('upload', ['files' => [UploadedFile::fake()->create('Tarifs 2026.pdf', 20, 'application/pdf')]])
            ->assertHasNoActionErrors()
            ->assertSee('Tarifs-2026.pdf');

        $this->assertSame(['Tarifs-2026.pdf'], $this->library()->files($folder)->pluck('file_name')->all());
    }

    public function test_a_super_admin_renames_moves_and_deletes(): void
    {
        $this->actingAsSuperAdmin();
        $archive = $this->library()->createFolder(null, 'Archive');
        $folder = $this->library()->createFolder(null, 'Drafts');
        $file = $this->upload($folder, 'notes.txt');

        Livewire::withQueryParams(['folder' => $folder->id])
            ->test(MediaManagerPage::class)
            ->callAction(TestAction::make('rename')->table('file:'.$file->id), ['name' => 'minutes'])
            ->callAction(TestAction::make('move')->table('file:'.$file->id), ['destination' => (string) $archive->id])
            ->assertHasNoActionErrors();

        $this->assertSame(['minutes.txt', $archive->id], [$file->fresh()->file_name, (int) $file->fresh()->model_id]);

        Livewire::withQueryParams([])
            ->test(MediaManagerPage::class)
            ->callAction(TestAction::make('delete')->table('folder:'.$folder->id));

        $this->assertModelMissing($folder);
    }

    public function test_a_folder_holding_files_is_not_deleted(): void
    {
        $this->actingAsSuperAdmin();
        $folder = $this->library()->createFolder(null, 'Docs');
        $this->upload($folder, 'plan.txt');

        Livewire::test(MediaManagerPage::class)
            ->callAction(TestAction::make('delete')->table('folder:'.$folder->id))
            ->assertNotified(__('media-manager::media-manager.errors.folder_not_empty'));

        $this->assertModelExists($folder);
    }

    public function test_without_any_right_the_page_is_closed(): void
    {
        $this->actingAsAdmin();
        $this->library()->createFolder(null, 'Private');

        $this->get(MediaManagerPage::getUrl())->assertForbidden();
    }

    public function test_an_admin_only_sees_and_does_what_the_rights_give(): void
    {
        $this->actingAsAdmin();
        $shared = $this->library()->createFolder(null, 'Shared');
        $private = $this->library()->createFolder(null, 'Private');
        $file = $this->upload($shared, 'guide.txt');
        $this->grantAdmins(Target::folder($shared), ['view' => true, 'download' => true]);

        $this->get(MediaManagerPage::getUrl())->assertOk();

        Livewire::test(MediaManagerPage::class)
            ->assertCanSeeTableRecords(['folder:'.$shared->id])
            ->assertCanNotSeeTableRecords(['folder:'.$private->id])
            ->assertActionHidden('newFolder')
            ->assertActionHidden('currentPermissions')
            ->assertActionHidden(TestAction::make('rename')->table('folder:'.$shared->id));

        Livewire::withQueryParams(['folder' => $shared->id])
            ->test(MediaManagerPage::class)
            ->assertCanSeeTableRecords(['file:'.$file->id])
            ->assertActionVisible(TestAction::make('download')->table('file:'.$file->id))
            ->assertActionHidden(TestAction::make('delete')->table('file:'.$file->id))
            ->assertActionHidden('upload');

        $this->get(MediaManagerPage::getUrl(['folder' => $private->id]))->assertForbidden();
    }

    public function test_a_subfolder_right_overrides_the_parent_one(): void
    {
        $this->actingAsAdmin();
        $team = $this->library()->createFolder(null, 'Team');
        $hr = $this->library()->createFolder($team, 'HR');
        $this->grantAdmins(Target::folder($team), ['view' => true, 'create' => true]);
        $this->grantAdmins(Target::folder($hr), ['view' => false]);

        Livewire::withQueryParams(['folder' => $team->id])
            ->test(MediaManagerPage::class)
            ->assertActionVisible('upload')
            ->assertCanNotSeeTableRecords(['folder:'.$hr->id]);
    }

    public function test_a_super_admin_sets_the_rights_of_a_folder(): void
    {
        $this->actingAsSuperAdmin();
        $folder = $this->library()->createFolder(null, 'Press');
        $admin = Role::findByName(RoleEnum::ADMIN->value);

        Livewire::test(MediaManagerPage::class)
            ->callAction(TestAction::make('permissions')->table('folder:'.$folder->id), [
                'grants' => [['grantee' => Grantee::of($admin)->key(), 'view' => 'allow', 'download' => 'allow', 'delete' => 'deny']],
            ])
            ->assertHasNoActionErrors();

        $grant = FolderGrant::query()->sole();
        $this->assertSame([$folder->id, ['view' => true, 'download' => true, 'delete' => false]], [$grant->folder_id, $grant->abilities]);
    }

    public function test_the_media_of_the_contents_are_shown_read_only(): void
    {
        $user = $this->actingAsSuperAdmin();
        $folder = $this->library()->createFolder(null, 'Tmp');
        $avatar = $this->upload($folder, 'avatar.txt');
        $avatar->forceFill(['model_type' => $user->getMorphClass(), 'model_id' => $user->id, 'collection_name' => 'avatar'])->save();

        Livewire::test(MediaManagerPage::class)
            ->assertCanSeeTableRecords(['linked:'.$user->getMorphClass()]);

        Livewire::withQueryParams(['linked' => $user->getMorphClass(), 'collection' => 'avatar'])
            ->test(MediaManagerPage::class)
            ->assertCanSeeTableRecords(['file:'.$avatar->id])
            ->assertActionVisible(TestAction::make('download')->table('file:'.$avatar->id))
            ->assertActionHidden(TestAction::make('rename')->table('file:'.$avatar->id))
            ->assertActionHidden(TestAction::make('delete')->table('file:'.$avatar->id))
            ->assertActionHidden('upload')
            ->assertActionHidden('newFolder');
    }

    public function test_the_media_of_the_contents_need_their_own_right(): void
    {
        $this->actingAsAdmin();
        $other = User::factory()->create();
        $folder = $this->library()->createFolder(null, 'Tmp');
        $this->upload($folder, 'avatar.txt')->forceFill(['model_type' => $other->getMorphClass(), 'model_id' => $other->id, 'collection_name' => 'avatar'])->save();
        $this->grantAdmins(Target::root(), ['view' => true]);

        $this->get(MediaManagerPage::getUrl(['linked' => $other->getMorphClass()]))->assertForbidden();

        $this->grantAdmins(Target::linked($other->getMorphClass()), ['view' => true]);

        $this->get(MediaManagerPage::getUrl(['linked' => $other->getMorphClass()]))->assertOk();
    }

    public function test_folders_open_in_place_from_the_list_and_the_tree(): void
    {
        $this->actingAsSuperAdmin();
        $projects = $this->library()->createFolder(null, 'Projects');
        $site = $this->library()->createFolder($projects, 'Website');
        $file = $this->upload($site, 'mockup.txt');

        Livewire::withQueryParams([])
            ->test(MediaManagerPage::class)
            // The tree lists every folder, the listing only the root's.
            ->assertSee('Website')
            ->assertCanNotSeeTableRecords(['folder:'.$site->id])
            ->callAction(TestAction::make('open')->table('folder:'.$projects->id))
            ->assertSet('folder', $projects->id)
            ->assertCanSeeTableRecords(['folder:'.$site->id])
            ->call('navigate', $site->id)
            ->assertSet('folder', $site->id)
            ->assertCanSeeTableRecords(['file:'.$file->id])
            ->call('navigate')
            ->assertSet('folder', null)
            ->assertCanSeeTableRecords(['folder:'.$projects->id]);
    }

    public function test_the_tree_only_shows_and_opens_what_the_rights_give(): void
    {
        $this->actingAsAdmin();
        $shared = $this->library()->createFolder(null, 'Shared');
        $secret = $this->library()->createFolder(null, 'Secret plans');
        $this->grantAdmins(Target::folder($shared), ['view' => true]);

        Livewire::withQueryParams([])
            ->test(MediaManagerPage::class)
            ->assertSee('Shared')
            ->assertDontSee('Secret plans')
            ->call('navigate', $secret->id)
            ->assertForbidden();
    }

    public function test_items_show_as_a_grid_of_icons_or_as_a_list(): void
    {
        $this->actingAsSuperAdmin();
        $folder = $this->library()->createFolder(null, 'Photos');
        $this->upload($folder, 'a.txt');
        $this->library()->createFolder($folder, 'Old');

        Livewire::withQueryParams([])
            ->test(MediaManagerPage::class)
            ->assertSet('display', 'grid')
            ->assertSeeHtml('mm-tile')
            ->assertSee('2 éléments')
            ->callAction('display')
            ->assertSet('display', 'list')
            ->assertDontSeeHtml('class="mm-tile"')
            ->assertSee(__('media-manager::media-manager.columns.size'))
            ->assertCanSeeTableRecords(['folder:'.$folder->id]);
    }
}
