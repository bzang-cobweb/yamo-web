<?php

namespace Modules\Notification\Http\Controllers\Api;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Models\User;
use Modules\Notification\Http\Resources\NotificationPageResource;

/**
 * Les notifications de l'app du compte connecté (décision
 * 2026-11-19-contrat-centre-notifications) : la liste, la plus récente en tête,
 * par pages ; le nombre de non lues (la pastille de l'onglet) ; marquer une
 * notification, ou toutes, comme lues. Une notification d'un autre compte
 * répond 404.
 */
class NotificationController
{
    /**
     * Ses notifications, la plus récente en tête, par pages.
     */
    #[QueryParameter('cursor', description: 'Curseur de la page voulue, tel que servi dans `next_cursor` ; absent : première page.', type: 'string', required: false)]
    public function index(Request $request): NotificationPageResource
    {
        return new NotificationPageResource(
            $this->user($request)->notifications()->reorder()->orderByDesc('created_at')->orderByDesc('id')
                ->cursorPaginate((int) config('notification.per_page', 20)),
        );
    }

    /**
     * Le nombre de ses notifications non lues.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['data' => ['unread' => $this->user($request)->unreadNotifications()->count()]]);
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
