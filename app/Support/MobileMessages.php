<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use LogicException;

/**
 * Textes de l'interface de l'app mobile, servis par `GET /api/v1/messages`.
 *
 * Chaque module ou paquet déclare les siens dans `lang/<langue>/mobile.php` de son
 * espace de traduction (`Modules/Auth/lang`, `packages/form/lang`), l'application les
 * textes communs dans `lang/<langue>/mobile.php`. Les tableaux
 * sont aplatis par clé (`form.stepOf`) ; les variables suivent la convention
 * Laravel (`:name`) et sont remplacées par l'app.
 */
final class MobileMessages
{
    public const GROUP = 'mobile';

    /**
     * Tous les textes de l'app dans une langue, triés par clé.
     *
     * @return array<string, string>
     *
     * @throws LogicException une même clé déclarée par deux sources
     */
    public static function for(string $locale): array
    {
        $messages = [];

        foreach (self::sources() as $source) {
            $group = trans($source, [], $locale);

            if (! is_array($group)) {
                continue;
            }

            foreach (Arr::dot($group) as $key => $text) {
                if (array_key_exists($key, $messages)) {
                    throw new LogicException("Texte de l'app déclaré deux fois : « {$key} » ({$source}).");
                }

                $messages[$key] = (string) $text;
            }
        }

        ksort($messages);

        return $messages;
    }

    /**
     * Groupes de traduction lus : celui de l'application, puis celui de chaque espace de traduction enregistré
     * (module activé ou paquet, décision 2026-11-27-paquet-form) qui a un fichier `mobile.php`, par ordre alphabétique
     * (`auth::mobile`, `form::mobile`…).
     *
     * @return list<string>
     */
    public static function sources(): array
    {
        $namespaces = array_keys(array_filter(
            Lang::getLoader()->namespaces(),
            fn (string $path): bool => glob($path.'/*/'.self::GROUP.'.php') !== [],
        ));
        sort($namespaces);

        return [self::GROUP, ...array_map(fn (string $namespace): string => $namespace.'::'.self::GROUP, $namespaces)];
    }
}
