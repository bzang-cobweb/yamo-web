<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Écriture des seuls réglages non secrets du `.env`, par `project:init` (décision
 * 2026-11-18-initialisation-par-domaine). Une clé hors de `WRITABLE` est refusée ;
 * les autres lignes du fichier, secrets compris, ne sont ni lues ni réécrites
 * autrement que telles quelles.
 */
final class EnvFile
{
    /** Clés que `project:init` peut écrire. */
    public const WRITABLE = ['APP_NAME', 'APP_LOCALE', 'OFFER_DOMAIN', 'AUTH_CHANNEL_EMAIL', 'AUTH_CHANNEL_PHONE'];

    public function __construct(private readonly string $path) {}

    public static function current(): self
    {
        return new self(app()->environmentFilePath());
    }

    /**
     * Écrit les clés données ; une clé absente est ajoutée à la fin. Rend les clés
     * dont la valeur a changé.
     *
     * @param  array<string, string>  $values
     * @return list<string>
     */
    public function set(array $values): array
    {
        $unknown = array_diff(array_keys($values), self::WRITABLE);

        if ($unknown !== []) {
            throw new InvalidArgumentException('Clé(s) .env non modifiable(s) par project:init : '.implode(', ', $unknown).'.');
        }

        $lines = is_file($this->path) ? preg_split('/\R/', (string) file_get_contents($this->path)) : [];
        $changed = [];

        foreach ($values as $key => $value) {
            $line = $key.'='.self::quote($value);
            $found = false;

            foreach ($lines as $i => $existing) {
                if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=/', $existing) === 1) {
                    $found = true;

                    if ($existing !== $line) {
                        $lines[$i] = $line;
                        $changed[] = $key;
                    }
                }
            }

            if (! $found) {
                // Avant la dernière ligne vide éventuelle.
                if ($lines !== [] && end($lines) === '') {
                    array_splice($lines, count($lines) - 1, 0, [$line]);
                } else {
                    $lines[] = $line;
                }
                $changed[] = $key;
            }
        }

        if ($changed !== []) {
            file_put_contents($this->path, implode("\n", $lines));
        }

        return array_values(array_unique($changed));
    }

    private static function quote(string $value): string
    {
        return preg_match('/^[A-Za-z0-9_.,:\/@+-]*$/', $value) === 1 ? $value : '"'.addcslashes($value, '"\\').'"';
    }
}
