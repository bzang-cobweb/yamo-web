<?php

namespace Modules\Form\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LogicException;
use Modules\Form\Enums\SuccessAction;

/**
 * Envoi d'un formulaire, décrit par le serveur pour que l'app l'exécute sans le
 * connaître d'avance (décision 2026-11-17-formulaires-profil-generiques) : point
 * d'API, méthode, bouton rouge, et suite donnée au succès.
 *
 * Méthode et chemin sont lus sur la route nommée : une seule source.
 */
final readonly class Submission
{
    public function __construct(
        public string $method,
        /** Chemin relatif à l'API (`/v1/…`), comme l'app l'appelle. */
        public string $endpoint,
        public SuccessAction $success,
        /** Message affiché après le succès, traduit. */
        public string $message,
        public bool $destructive = false,
    ) {}

    /**
     * L'envoi vers la route d'API nommée `$name` (`api.v1.auth.me.personal-info.update`),
     * avec ses paramètres (`['address' => 12]`) s'il en a.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function toRoute(string $name, SuccessAction $success, string $message, bool $destructive = false, array $parameters = []): self
    {
        $route = Route::getRoutes()->getByName($name)
            ?? throw new LogicException("Route d'envoi inconnue : {$name}");

        $method = collect($route->methods())->first(fn (string $method): bool => $method !== 'HEAD');
        $path = ltrim(route($name, $parameters, false), '/');

        return new self($method, '/'.Str::after($path, 'api/'), $success, $message, $destructive);
    }

    /**
     * @return array{method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'endpoint' => $this->endpoint,
            'destructive' => $this->destructive,
            'success' => ['action' => $this->success->value, 'message' => $this->message],
        ];
    }
}
