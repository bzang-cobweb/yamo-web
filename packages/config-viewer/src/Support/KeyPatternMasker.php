<?php

namespace Bza\ConfigViewer\Support;

use Bza\ConfigViewer\Contracts\ValueMasker;
use Illuminate\Support\Str;

/**
 * Masks non-empty strings whose key matches a pattern, either on its last
 * segment ("password") or on the full dotted key ("database.connections.*.url").
 */
class KeyPatternMasker implements ValueMasker
{
    /**
     * @param  list<string>  $segmentPatterns
     * @param  list<string>  $keyPatterns
     */
    public function __construct(
        private readonly array $segmentPatterns = [],
        private readonly array $keyPatterns = [],
        private readonly string $placeholder = '••••••••',
    ) {}

    public function shouldMask(string $key, mixed $value): bool
    {
        // Numbers, booleans and empty strings tell nothing secret, and hiding them
        // would only hide settings such as "auth.password_timeout".
        if (! is_string($value) || $value === '') {
            return false;
        }

        $segment = Str::afterLast($key, '.');

        if (Str::is($this->segmentPatterns, $segment, ignoreCase: true)) {
            return true;
        }

        return Str::is($this->keyPatterns, $key, ignoreCase: true);
    }

    public function placeholder(): string
    {
        return $this->placeholder;
    }
}
