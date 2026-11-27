<?php

namespace Bza\MediaManager;

use Bza\MediaManager\Models\MediaFolder;

/**
 * What rights are set on: the root, a folder, or the read-only folder of the
 * media attached to one model type.
 */
final readonly class Target
{
    private function __construct(
        public ?int $folderId,
        public ?string $linkedType,
    ) {}

    public static function root(): self
    {
        return new self(null, null);
    }

    public static function folder(MediaFolder|int|null $folder): self
    {
        if ($folder === null) {
            return self::root();
        }

        return new self($folder instanceof MediaFolder ? (int) $folder->getKey() : $folder, null);
    }

    public static function linked(string $modelType): self
    {
        return new self(null, $modelType);
    }

    public function isRoot(): bool
    {
        return $this->folderId === null && $this->linkedType === null;
    }

    public function isLinked(): bool
    {
        return $this->linkedType !== null;
    }

    public function key(): string
    {
        return match (true) {
            $this->folderId !== null => 'folder:'.$this->folderId,
            $this->linkedType !== null => 'linked:'.$this->linkedType,
            default => 'root',
        };
    }
}
