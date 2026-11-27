<?php

namespace Bza\ConfigViewer\Support;

use Closure;
use UnitEnum;

/**
 * The type of a configuration value, as shown to the user.
 */
enum ValueType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Float = 'float';
    case Boolean = 'boolean';
    case Null = 'null';
    case Array = 'array';
    case Enum = 'enum';
    case Closure = 'closure';
    case Object = 'object';

    public static function of(mixed $value): self
    {
        return match (true) {
            is_string($value) => self::String,
            is_int($value) => self::Integer,
            is_float($value) => self::Float,
            is_bool($value) => self::Boolean,
            $value === null => self::Null,
            is_array($value) => self::Array,
            $value instanceof UnitEnum => self::Enum,
            $value instanceof Closure => self::Closure,
            default => self::Object,
        };
    }
}
