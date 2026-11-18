<?php

namespace Modules\Offer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Offer\Domains\DomainRegistry;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sans domaine choisi, les routes des offres répondent comme inexistantes (404).
 */
class RequireOfferDomain
{
    public function __construct(private readonly DomainRegistry $domains) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_if($this->domains->active() === null, 404);

        return $next($request);
    }
}
