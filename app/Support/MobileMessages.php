<?php

namespace App\Support;

use Illuminate\Support\Arr;
use LogicException;
use Nwidart\Modules\Facades\Module;

/**
 * Textes de l'interface de l'app mobile, servis par `GET /api/v1/messages`.
 *
 * Chaque module déclare les siens dans `Modules/<Module>/lang/<langue>/mobile.php`,
 * l'application les textes communs dans `lang/<langue>/mobile.php`. Les tableaux
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
     * Groupes de traduction lus : celui de l'application, puis celui de chaque
     * module activé (`auth::mobile`, `form::mobile`…).
     *
     * @return list<string>
     */
    public static function sources(): array
    {
        $modules = array_map(
            fn ($module): string => $module->getLowerName().'::'.self::GROUP,
            array_values(Module::allEnabled()),
        );

        return [self::GROUP, ...$modules];
    }
}
