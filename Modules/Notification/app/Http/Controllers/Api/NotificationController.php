<?php

namespace Modules\Notification\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Auth\Models\User;
use Modules\Notification\Http\Resources\NotificationPageResource;
use Modules\Notification\Http\Resources\NotificationResource;
use Modules\Notification\Support\NotificationFilters;
use Modules\Notification\Support\NotificationThreads;

/**
 * Les notifications de l'app du compte connecté (décision
 * 2026-11-19-contrat-centre-notifications) : la liste, la plus récente en tête, une
 * carte par fil (les étapes d'une demande, décision 2026-11-20-suivi-demande),
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
    public function index(Request $request, NotificationFilters $filters, NotificationThreads $threads): NotificationPageResource
    {
        $user = $this->user($request);
        $chosen = $request->query('filter');
        $query = $user->notifications()->reorder()->orderByDesc('created_at')->orderByDesc('id');
        // Une carte par fil : sa notification la plus récente.
        $threads->latestOnly($query->getQuery());
        $filters->apply($query->getQuery(), is_array($chosen) ? $chosen : [], $user);
        $search = $request->query('search');
        if (is_string($search)) {
            $filters->search($query->getQuery(), $search);
        }

        $page = $query->cursorPaginate((int) config('notification.per_page', 20));
        $inPage = [];
        foreach ($page->items() as $notification) {
            /** @var DatabaseNotification $notification */
            $thread = $notification->getAttribute('thread');
            if (is_string($thread)) {
                $inPage[] = $thread;
            }
        }

        return (new NotificationPageResource($page))
            ->withFilters($filters->fields($user))
            ->withUnreadThreads($threads->unreadThreads($user, $inPage));
    }

    /**
     * Le nombre de ses notifications non lues, un fil (une demande) comptant pour une.
     */
    public function unreadCount(Request $request, NotificationThreads $threads): JsonResponse
    {
        return response()->json(['data' => ['unread' => $threads->unreadCount($this->user($request))]]);
    }

    /**
     * Une de ses notifications, pour sa page (404 pour une autre).
     */
    public function show(Request $request, string $notification): NotificationResource
    {
        // Et son fil : le suivi, des deux côtés, et ce qu'y ajoute son module (décisions
        // 2026-11-20-suivi-demande et 2026-11-20-notification-generique).
        return (new NotificationResource($this->user($request)->notifications()->whereKey($notification)->firstOrFail()))->withThread();
    }

    /**
     * Marquer une de ses notifications comme lue, et tout son fil (une demande).
     */
    public function read(Request $request, string $notification, NotificationThreads $threads): Response
    {
        $user = $this->user($request);
        $threads->markRead($user, $user->notifications()->whereKey($notification)->firstOrFail());

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
