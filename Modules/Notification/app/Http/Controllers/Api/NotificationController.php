<?php

namespace Modules\Notification\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Notification\Http\Resources\NotificationPageResource;
use Modules\Notification\Http\Resources\NotificationResource;
use Modules\Notification\Support\NotificationFilters;

/**
 * Les notifications de l'app du compte connecté (décision
 * 2026-11-19-contrat-centre-notifications) : la liste, la plus récente en tête,
 * par pages, avec sa recherche et ses filtres ; le nombre de non lues (la pastille de l'onglet) ; marquer une
 * notification, ou toutes, comme lues. Une notification d'un autre compte
 * répond 404.
 */
class NotificationController
{
    /**
     * Ses notifications, la plus récente en tête, par pages.
     */
    #[QueryParameter('cursor', description: 'Curseur de la page voulue, tel que servi dans `next_cursor` ; absent : première page.', type: 'string', required: false)]
    #[QueryParameter('search', description: 'Recherche : le nom de qui agit, le titre du logement (décision 2026-11-20-filtres-notifications).', type: 'string', required: false)]
    #[QueryParameter('filter', description: 'Filtres choisis, `filter[offer]=12`, `filter[offer_status]=published` (décision 2026-11-20-filtres-notifications).', type: 'object', required: false)]
    public function index(Request $request, NotificationFilters $filters): NotificationPageResource
    {
        $user = $this->user($request);
        $chosen = $request->query('filter');
        $query = $user->notifications()->reorder()->orderByDesc('created_at')->orderByDesc('id');
        $filters->apply($query->getQuery(), is_array($chosen) ? $chosen : [], $user);
        $search = $request->query('search');
        if (is_string($search)) {
            $filters->search($query->getQuery(), $search);
        }

        return (new NotificationPageResource($query->cursorPaginate((int) config('notification.per_page', 20))))
            ->withFilters($filters->fields($user));
    }

    /**
     * Le nombre de ses notifications non lues.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['data' => ['unread' => $this->user($request)->unreadNotifications()->count()]]);
    }

    /**
     * Une de ses notifications, pour sa page (404 pour une autre).
     */
    public function show(Request $request, string $notification): NotificationResource
    {
        // Sur sa page : les coordonnées de qui agit (décision 2026-11-20-coordonnees-notification).
        return (new NotificationResource($this->user($request)->notifications()->whereKey($notification)->firstOrFail()))->withContact();
    }

    /**
     * Marquer une de ses notifications comme lue.
     */
    public function read(Request $request, string $notification): Response
    {
        $this->user($request)->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return response()->noContent();
    }

    /**
     * Marquer toutes ses notifications comme lues.
     */
    public function readAll(Request $request): Response
    {
        $this->user($request)->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
