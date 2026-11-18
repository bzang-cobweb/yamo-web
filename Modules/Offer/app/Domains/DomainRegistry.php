<?php

namespace Modules\Offer\Domains;

use InvalidArgumentException;
use LogicException;

/**
 * Les domaines connus, par clé, et le domaine actif (`config('offer.domain')`).
 * Un domaine s'inscrit avec `register()`, comme les formulaires et les listes.
 */
final class DomainRegistry
{
    /** @var array<string, class-string<Domain>> */
    private array $domains = [];

    /**
     * @param  class-string<Domain>  $domain
     */
    public function register(string $domain): void
    {
        $clash = array_intersect((new $domain)->attributeNames(), Domain::COMMON_FIELDS);

        if ($clash !== []) {
            throw new LogicException("Domaine {$domain::key()} : attribut(s) au nom d'un champ commun (".implode(', ', $clash).').');
        }

        $this->domains[$domain::key()] = $domain;
    }

    /**
     * Clés des domaines connus.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->domains);
    }

    /**
     * Un domaine connu, par sa clé.
     */
    public function make(string $key): Domain
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Domaine d'offres inconnu : « {$key} ».");
        }

        return new ($this->domains[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->domains[$key]);
    }

    /**
     * Le domaine choisi par la configuration, ou `null` sans domaine choisi.
     *
     * @throws InvalidArgumentException domaine inconnu
     */
    public function active(): ?Domain
    {
        $key = config('offer.domain');

        if (blank($key)) {
            return null;
        }

        if (! $this->has((string) $key)) {
            throw new InvalidArgumentException("Domaine d'offres inconnu : « {$key} » (config offer.domain). Connus : ".implode(', ', array_keys($this->domains)).'.');
        }

        return new ($this->domains[(string) $key]);
    }

    /**
     * Le domaine actif, quand il est requis.
     */
    public function require(): Domain
    {
        return $this->active() ?? throw new LogicException('Aucun domaine d\'offres choisi (config offer.domain).');
    }
}
