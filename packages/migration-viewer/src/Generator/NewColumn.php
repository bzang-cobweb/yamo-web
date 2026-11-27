<?php

namespace Bza\MigrationViewer\Generator;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * A column to generate.
 */
final readonly class NewColumn
{
    /** Names of tables and columns: lower case, digits and underscores. */
    public const NAME_PATTERN = '/^[a-z][a-z0-9_]{0,63}$/';

    public function __construct(
        public string $name,
        public ColumnType $type,
        public ?int $length = null,
        public ?int $total = null,
        public ?int $places = null,
        public bool $nullable = false,
        /** The default as typed by the user; null or '' for none. */
        public ?string $default = null,
        public bool $unique = false,
        public bool $index = false,
        /** For a foreign id: the referenced table; null lets Laravel guess it from the name. */
        public ?string $references = null,
        public ?OnDelete $onDelete = null,
    ) {
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            throw new InvalidArgumentException("Invalid column name [{$name}].");
        }

        if ($references !== null && preg_match(self::NAME_PATTERN, $references) !== 1) {
            throw new InvalidArgumentException("Invalid referenced table [{$references}].");
        }

        if ($onDelete === OnDelete::SetNull && ! $nullable) {
            throw new InvalidArgumentException("The column [{$name}] must be nullable to be set to null on delete.");
        }
    }

    /**
     * From a form's state: {name, type, length, total, places, nullable, default, unique, index, references, on_delete}.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $integer = fn (string $key): ?int => isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
        $string = fn (string $key): ?string => isset($data[$key]) && $data[$key] !== '' ? (string) $data[$key] : null;

        return new self(
            name: (string) ($data['name'] ?? ''),
            type: ColumnType::tryFrom((string) ($data['type'] ?? '')) ?? throw new InvalidArgumentException('Unknown column type ['.($data['type'] ?? '').'].'),
            length: $integer('length'),
            total: $integer('total'),
            places: $integer('places'),
            nullable: (bool) ($data['nullable'] ?? false),
            default: $string('default'),
            unique: (bool) ($data['unique'] ?? false),
            index: (bool) ($data['index'] ?? false),
            references: $string('references'),
            onDelete: OnDelete::tryFrom((string) ($data['on_delete'] ?? '')),
        );
    }

    /**
     * The same column under another name.
     */
    public function withName(string $name): self
    {
        return new self($name, $this->type, $this->length, $this->total, $this->places, $this->nullable, $this->default, $this->unique, $this->index, $this->references, $this->onDelete);
    }

    /**
     * Same definition, whatever the name.
     */
    public function sameDefinitionAs(self $other): bool
    {
        return $this->withName('x') == $other->withName('x');
    }

    /**
     * The table a foreign id points to: the given one, or Laravel's guess
     * from the name ("user_id" → "users"), as constrained() makes it.
     */
    public function referencedTable(): string
    {
        return $this->references ?? Str::of($this->name)->beforeLast('_id')->plural()->toString();
    }

    public function hasDefault(): bool
    {
        return $this->default !== null && $this->default !== '' && $this->type->acceptsDefault();
    }
}
