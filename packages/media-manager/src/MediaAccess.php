<?php

namespace Bza\MediaManager;

use Bza\MediaManager\Contracts\GranteeResolver;
use Bza\MediaManager\Models\FolderGrant;
use Bza\MediaManager\Models\MediaFolder;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Decides what a user may do on a target.
 *
 * For each grantee of the user (the user, its roles), an ability is decided by
 * the nearest folder, from the target up to the root, that gives or refuses
 * it: a subfolder overrides its parents for that ability only. The user may
 * act when at least one of its grantees may. The read-only folders of the
 * linked media inherit nothing: only their own rights count.
 *
 * Users accepted by the bypass callback (super administrators) may do anything.
 */
class MediaAccess
{
    private static ?Closure $bypass = null;

    /** @var array<int, int|null>|null Folder id => parent id. */
    private ?array $parents = null;

    /** @var array<string, array<string, FolderGrant>> Target key => grantee key => grant, per user. */
    private array $grants = [];

    public function __construct(private readonly GranteeResolver $resolver) {}

    /**
     * Who may do anything, whatever the rights: fn (Authenticatable $user): bool.
     */
    public static function bypassUsing(?Closure $callback): void
    {
        self::$bypass = $callback;
    }

    public function bypasses(?Authenticatable $user): bool
    {
        return $user !== null && self::$bypass !== null && (bool) (self::$bypass)($user);
    }

    public function allows(?Authenticatable $user, Ability $ability, Target $target): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->bypasses($user)) {
            return true;
        }

        $grants = $this->grantsOf($user);
        $chain = $this->chain($target);

        foreach ($this->resolver->grantees($user) as $grantee) {
            foreach ($chain as $key) {
                $value = ($grants[$key][$grantee->key()] ?? null)?->value($ability);

                if ($value !== null) {
                    if ($value) {
                        return true;
                    }

                    break;
                }
            }
        }

        return false;
    }

    public function allowsFolder(?Authenticatable $user, Ability $ability, ?MediaFolder $folder): bool
    {
        return $this->allows($user, $ability, Target::folder($folder));
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(?Authenticatable $user, Ability $ability, Target $target): void
    {
        if (! $this->allows($user, $ability, $target)) {
            throw new AuthorizationException(__('media-manager::media-manager.errors.forbidden'));
        }
    }

    /**
     * Whether the folder can be opened: viewed, or on the way to a viewable
     * subfolder (its own files then stay hidden).
     */
    public function canBrowse(?Authenticatable $user, ?MediaFolder $folder): bool
    {
        if ($folder === null || $this->allowsFolder($user, Ability::View, $folder)) {
            return $user !== null;
        }

        foreach ($this->descendants((int) $folder->getKey()) as $id) {
            if ($this->allows($user, Ability::View, Target::folder($id))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the manager has anything for the user: a folder to open, a
     * folder to create at the root, or linked media to see.
     *
     * @param  list<string>  $linkedTypes
     */
    public function hasAnyAccess(?Authenticatable $user, array $linkedTypes = []): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->bypasses($user) || $this->allows($user, Ability::Create, Target::root())) {
            return true;
        }

        foreach (array_keys($this->parents()) as $id) {
            if ($this->allows($user, Ability::View, Target::folder($id))) {
                return true;
            }
        }

        foreach ($linkedTypes as $type) {
            if ($this->allows($user, Ability::View, Target::linked($type))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Forgets the folders and rights read so far, after a change.
     */
    public function flush(): void
    {
        $this->parents = null;
        $this->grants = [];
    }

    /**
     * The target keys an ability is looked up in, nearest first.
     *
     * @return list<string>
     */
    private function chain(Target $target): array
    {
        if ($target->isLinked()) {
            return [$target->key()];
        }

        $chain = [];
        $parents = $this->parents();
        $id = $target->folderId;

        // The visited list guards against a cycle in corrupted data.
        while ($id !== null && ! in_array('folder:'.$id, $chain, true)) {
            $chain[] = 'folder:'.$id;
            $id = $parents[$id] ?? null;
        }

        $chain[] = 'root';

        return $chain;
    }

    /**
     * @return list<int>
     */
    private function descendants(int $id): array
    {
        $children = [];

        foreach ($this->parents() as $child => $parent) {
            if ($parent !== null) {
                $children[$parent][] = $child;
            }
        }

        $found = [];
        $queue = $children[$id] ?? [];

        while ($queue !== []) {
            $next = array_shift($queue);

            if (! in_array($next, $found, true)) {
                $found[] = $next;
                $queue = [...$queue, ...($children[$next] ?? [])];
            }
        }

        return $found;
    }

    /**
     * @return array<int, int|null>
     */
    private function parents(): array
    {
        return $this->parents ??= MediaFolder::query()->pluck('parent_id', 'id')
            ->map(fn (mixed $parent): ?int => $parent === null ? null : (int) $parent)
            ->all();
    }

    /**
     * @return array<string, array<string, FolderGrant>>
     */
    private function grantsOf(Authenticatable $user): array
    {
        $userKey = $user->getAuthIdentifierName().':'.$user->getAuthIdentifier();

        if (isset($this->grants[$userKey])) {
            return $this->grants[$userKey];
        }

        $grantees = $this->resolver->grantees($user);
        $byTarget = [];

        if ($grantees !== []) {
            $grants = FolderGrant::query()->where(function ($query) use ($grantees): void {
                foreach ($grantees as $grantee) {
                    $query->orWhere(fn ($query) => $query->where('grantee_type', $grantee->type)->where('grantee_id', $grantee->id));
                }
            })->get();

            foreach ($grants as $grant) {
                $byTarget[$grant->target()->key()][$grant->granteeKey()] = $grant;
            }
        }

        return $this->grants[$userKey] = $byTarget;
    }
}
