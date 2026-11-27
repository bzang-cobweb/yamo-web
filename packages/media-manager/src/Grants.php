<?php

namespace Bza\MediaManager;

use Bza\MediaManager\Models\FolderGrant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Reads and replaces the rights set on a target.
 */
class Grants
{
    public function __construct(private readonly MediaAccess $access) {}

    /**
     * @return Collection<int, FolderGrant>
     */
    public function on(Target $target): Collection
    {
        return FolderGrant::query()->onTarget($target)->orderBy('id')->get();
    }

    /**
     * Replaces the rights of the target. Each row gives a grantee its
     * abilities: true given, false refused, null or missing inherited. A row
     * with nothing given nor refused is not kept.
     *
     * @param  list<array{grantee: Grantee, abilities: array<string, bool|null>}>  $rows
     */
    public function replace(Target $target, array $rows): void
    {
        DB::transaction(function () use ($target, $rows): void {
            FolderGrant::query()->onTarget($target)->delete();

            $kept = [];

            foreach ($rows as $row) {
                $abilities = [];

                foreach (Ability::cases() as $ability) {
                    $value = $row['abilities'][$ability->value] ?? null;

                    if ($value !== null) {
                        $abilities[$ability->value] = (bool) $value;
                    }
                }

                // The last row of a grantee wins, as in a form listing it twice.
                if ($abilities !== []) {
                    $kept[$row['grantee']->key()] = [$row['grantee'], $abilities];
                } else {
                    unset($kept[$row['grantee']->key()]);
                }
            }

            foreach ($kept as [$grantee, $abilities]) {
                FolderGrant::query()->create([
                    'folder_id' => $target->folderId,
                    'linked_type' => $target->linkedType,
                    'grantee_type' => $grantee->type,
                    'grantee_id' => $grantee->id,
                    'abilities' => $abilities,
                ]);
            }
        });

        $this->access->flush();
    }

    /**
     * Who rights can be given to: model class => attribute shown in the lists.
     *
     * @return array<class-string<Model>, string>
     */
    public function granteeModels(): array
    {
        $configured = config('media-manager.grantees');

        if (is_array($configured)) {
            return $configured;
        }

        $models = [];

        if (is_string($role = config('permission.models.role')) && class_exists($role)) {
            $models[$role] = 'name';
        }

        $guard = config('auth.defaults.guard');
        $provider = config('auth.guards.'.$guard.'.provider');

        if (is_string($user = config('auth.providers.'.$provider.'.model')) && class_exists($user)) {
            $models[$user] = 'name';
        }

        return $models;
    }
}
