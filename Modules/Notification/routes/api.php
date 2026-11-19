<?php

// Routes d'API du module Notification (préfixe `api`, noms `api.`) : les
// notifications du compte connecté, et seulement les siennes (décision
// 2026-11-19-contrat-centre-notifications).

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\Api\NotificationController;

Route::middleware('auth:sanctum')->prefix('v1/notifications')->name('v1.notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::post('read', [NotificationController::class, 'readAll'])->name('read-all');
    Route::post('{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->name('read');
});
