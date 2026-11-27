<?php

namespace Bza\Form\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LogicException;
use Bza\Form\Enums\SuccessAction;

/**
 * Submission of a form, described by the server so that the app runs it without
 * knowing it in advance (decision 2026-11-17-formulaires-profil-generiques): API
 * endpoint, method, red button, and what follows a success.
 *
 * Method and path are read from the named route: a single source.
 */
final readonly class Submission
{
    public function __construct(
        public string $method,
        /** Path relative to the API (`/v1/…`), as the app calls it. */
        public string $endpoint,
        public SuccessAction $success,
        /** Message displayed after the success, translated. */
        public string $message,
        public bool $destructive = false,
    ) {}

    /**
     * The submission to the API route named `$name` (`api.v1.auth.me.personal-info.update`),
     * with its parameters (`['address' => 12]`) if it has any.
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
