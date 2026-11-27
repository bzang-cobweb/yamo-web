<?php

namespace Bza\MediaManager;

use Illuminate\Database\Eloquent\Model;

/**
 * Who a right is given to: a role, a user, any model, by morph type and key.
 */
final readonly class Grantee
{
    public function __construct(
        public string $type,
        public string $id,
    ) {}

    public static function of(Model $model): self
    {
        return new self($model->getMorphClass(), (string) $model->getKey());
    }

    public static function fromKey(string $key): self
    {
        [$type, $id] = explode('#', $key, 2) + [1 => ''];

        return new self($type, $id);
    }

    public function key(): string
    {
        return $this->type.'#'.$this->id;
    }
}
