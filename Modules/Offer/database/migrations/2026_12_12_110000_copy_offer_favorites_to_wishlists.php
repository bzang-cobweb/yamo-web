<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les favoris d'avant les listes (`offer_favorites`) rangés dans une liste « Favoris » par membre, dans sa langue
 * (décision 2026-11-27-listes-de-favoris). La table `offer_favorites` reste, plus lue. Rejouable : un membre qui a
 * déjà une liste nommée n'en reçoit pas d'autre, et une offre déjà rangée ne l'est pas deux fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('offer_favorites')->distinct()->pluck('user_id');

        foreach ($users as $userId) {
            $listId = DB::table('wishlists')->where('user_id', $userId)->where('kind', 'custom')->orderBy('id')->value('id');
            if ($listId === null) {
                $locale = DB::table('users')->where('id', $userId)->value('locale');
                $now = now();
                $listId = DB::table('wishlists')->insertGetId([
                    'user_id' => $userId,
                    'name' => trans('offer::offer.wishlists.default_name', [], is_string($locale) && $locale !== '' ? $locale : config('app.default_locale', 'fr')),
                    'kind' => 'custom',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $favorites = DB::table('offer_favorites')->where('user_id', $userId)->orderBy('id')->get(['offer_id', 'created_at', 'updated_at']);
            foreach ($favorites as $favorite) {
                DB::table('wishlist_items')->insertOrIgnore([
                    'wishlist_id' => $listId,
                    'offer_id' => $favorite->offer_id,
                    'created_at' => $favorite->created_at,
                    'updated_at' => $favorite->updated_at,
                ]);
            }
        }
    }

    /** Rien à défaire : `offer_favorites` n'a pas été touchée ; les listes partent avec leur table. */
    public function down(): void {}
};
