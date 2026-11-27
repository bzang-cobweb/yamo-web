<?php

namespace Bza\MediaManager\Tests;

use Bza\MediaManager\Ability;
use Bza\MediaManager\Contracts\GranteeResolver;
use Bza\MediaManager\Grantee;
use Bza\MediaManager\Grants;
use Bza\MediaManager\MediaAccess;
use Bza\MediaManager\Models\MediaFolder;
use Bza\MediaManager\Target;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

/**
 * Per-folder rights: inherited by the subfolders, overridden ability by
 * ability, given through any of the user's grantees.
 */
class MediaAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(GranteeResolver::class, FakeGranteeResolver::class);
        MediaAccess::bypassUsing(null);
    }

    protected function tearDown(): void
    {
        MediaAccess::bypassUsing(null);

        parent::tearDown();
    }

    private function access(): MediaAccess
    {
        return $this->app->make(MediaAccess::class);
    }

    /**
     * @param  array<string, bool|null>  $abilities
     */
    private function grant(Target $target, string $grantee, array $abilities): void
    {
        $grants = $this->app->make(Grants::class);
        $rows = $grants->on($target)->map(fn ($grant): array => ['grantee' => Grantee::fromKey($grant->granteeKey()), 'abilities' => $grant->abilities])->all();
        $rows[] = ['grantee' => Grantee::fromKey($grantee), 'abilities' => $abilities];

        $grants->replace($target, $rows);
    }

    public function test_a_right_given_on_a_folder_is_inherited_by_its_subfolders(): void
    {
        $photos = MediaFolder::query()->create(['name' => 'Photos']);
        $summer = MediaFolder::query()->create(['name' => 'Summer', 'parent_id' => $photos->id]);
        $other = MediaFolder::query()->create(['name' => 'Other']);
        $this->grant(Target::folder($photos), 'role#editor', ['view' => true]);

        $editor = new FakeUser('1', 'role#editor');

        $this->assertTrue($this->access()->allowsFolder($editor, Ability::View, $summer));
        $this->assertFalse($this->access()->allowsFolder($editor, Ability::Delete, $summer));
        $this->assertFalse($this->access()->allowsFolder($editor, Ability::View, $other));
        $this->assertFalse($this->access()->allowsFolder(new FakeUser('2', 'role#member'), Ability::View, $photos));
    }

    public function test_a_subfolder_overrides_its_parents_for_that_ability_only(): void
    {
        $photos = MediaFolder::query()->create(['name' => 'Photos']);
        $private = MediaFolder::query()->create(['name' => 'Private', 'parent_id' => $photos->id]);
        $this->grant(Target::root(), 'role#editor', ['view' => true, 'download' => true]);
        $this->grant(Target::folder($private), 'role#editor', ['view' => false, 'delete' => true]);

        $editor = new FakeUser('1', 'role#editor');

        $this->assertTrue($this->access()->allowsFolder($editor, Ability::View, $photos));
        $this->assertFalse($this->access()->allowsFolder($editor, Ability::View, $private));
        $this->assertTrue($this->access()->allowsFolder($editor, Ability::Download, $private));
        $this->assertTrue($this->access()->allowsFolder($editor, Ability::Delete, $private));
        $this->assertFalse($this->access()->allowsFolder($editor, Ability::Delete, $photos));
    }

    public function test_one_grantee_allowing_is_enough(): void
    {
        $folder = MediaFolder::query()->create(['name' => 'Shared']);
        $this->grant(Target::folder($folder), 'role#member', ['view' => false]);
        $this->grant(Target::folder($folder), 'user#7', ['view' => true]);

        $this->assertTrue($this->access()->allowsFolder(new FakeUser('7', 'role#member'), Ability::View, $folder));
        $this->assertFalse($this->access()->allowsFolder(new FakeUser('8', 'role#member'), Ability::View, $folder));
    }

    public function test_the_super_user_may_do_anything(): void
    {
        $folder = MediaFolder::query()->create(['name' => 'Locked']);
        MediaAccess::bypassUsing(fn (FakeUser $user): bool => $user->getAuthIdentifier() === '1');

        $this->assertTrue($this->access()->allowsFolder(new FakeUser('1'), Ability::Manage, $folder));
        $this->assertFalse($this->access()->allowsFolder(new FakeUser('2'), Ability::View, $folder));
        $this->assertFalse($this->access()->allowsFolder(null, Ability::View, $folder));
    }

    public function test_linked_media_do_not_inherit_the_root(): void
    {
        $this->grant(Target::root(), 'role#editor', ['view' => true]);
        $this->grant(Target::linked('offer'), 'role#sales', ['view' => true]);

        $this->assertFalse($this->access()->allows(new FakeUser('1', 'role#editor'), Ability::View, Target::linked('offer')));
        $this->assertTrue($this->access()->allows(new FakeUser('2', 'role#sales'), Ability::View, Target::linked('offer')));
        $this->assertFalse($this->access()->allows(new FakeUser('2', 'role#sales'), Ability::View, Target::linked('user')));
    }

    public function test_a_folder_on_the_way_to_a_viewable_subfolder_can_be_browsed(): void
    {
        $clients = MediaFolder::query()->create(['name' => 'Clients']);
        $acme = MediaFolder::query()->create(['name' => 'Acme', 'parent_id' => $clients->id]);
        $this->grant(Target::folder($acme), 'role#acme', ['view' => true]);

        $user = new FakeUser('1', 'role#acme');

        $this->assertFalse($this->access()->allowsFolder($user, Ability::View, $clients));
        $this->assertTrue($this->access()->canBrowse($user, $clients));
        $this->assertTrue($this->access()->hasAnyAccess($user));
        $this->assertFalse($this->access()->hasAnyAccess(new FakeUser('2', 'role#member')));
        $this->assertFalse($this->access()->hasAnyAccess(new FakeUser('3', 'role#sales'), ['offer']));
    }

    public function test_rights_follow_a_moved_folder(): void
    {
        $public = MediaFolder::query()->create(['name' => 'Public']);
        $draft = MediaFolder::query()->create(['name' => 'Draft']);
        $this->grant(Target::folder($public), 'role#member', ['view' => true]);
        $member = new FakeUser('1', 'role#member');

        $this->assertFalse($this->access()->allowsFolder($member, Ability::View, $draft));

        $draft->update(['parent_id' => $public->id]);
        $this->access()->flush();

        $this->assertTrue($this->access()->allowsFolder($member, Ability::View, $draft));
    }
}
