<?php

namespace Bza\MigrationViewer\Generator;

/**
 * The column types the generator offers, named after their Blueprint method.
 */
enum ColumnType: string
{
    case String = 'string';
    case Char = 'char';
    case Text = 'text';
    case MediumText = 'mediumText';
    case LongText = 'longText';
    case Integer = 'integer';
    case BigInteger = 'bigInteger';
    case SmallInteger = 'smallInteger';
    case TinyInteger = 'tinyInteger';
    case UnsignedInteger = 'unsignedInteger';
    case UnsignedBigInteger = 'unsignedBigInteger';
    case Decimal = 'decimal';
    case Float = 'float';
    case Double = 'double';
    case Boolean = 'boolean';
    case Date = 'date';
    case DateTime = 'dateTime';
    case Time = 'time';
    case Timestamp = 'timestamp';
    case Json = 'json';
    case Uuid = 'uuid';
    case Ulid = 'ulid';
    case ForeignId = 'foreignId';

    /** string(name, length) and char(name, length). */
    public function hasLength(): bool
    {
        return $this === self::String || $this === self::Char;
    }

    /** decimal(name, total, places). */
    public function hasPrecision(): bool
    {
        return $this === self::Decimal;
    }

    public function isForeign(): bool
    {
        return $this === self::ForeignId;
    }

    public function isInteger(): bool
    {
        return in_array($this, [self::Integer, self::BigInteger, self::SmallInteger, self::TinyInteger, self::UnsignedInteger, self::UnsignedBigInteger], true);
    }

    public function isNumeric(): bool
    {
        return $this->isInteger() || in_array($this, [self::Decimal, self::Float, self::Double], true);
    }

    /**
     * Whether a default value makes sense for this type.
     */
    public function acceptsDefault(): bool
    {
        return ! in_array($this, [self::Text, self::MediumText, self::LongText, self::Json, self::ForeignId], true);
    }
}
