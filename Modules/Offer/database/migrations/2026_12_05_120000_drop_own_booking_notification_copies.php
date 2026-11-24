<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Une seule notification par action d'une demande, à l'autre partie (décision 2026-11-24-notifications-fil-de-groupe) :
 * les copies que recevait celui qui agit (`data.own`) sont effacées, l'action restant dans la notification de l'autre
 * partie, dont il est l'auteur (`user_id`) ; `own` est retiré des autres. Rien à défaire : l'auteur retrouve son
 * action sans sa copie.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')->where('data', 'like', '%"own":%')->orderBy('id')->get(['id', 'data'])
            ->each(function (object $row): void {
                $data = json_decode((string) $row->data, true);
                if (! is_array($data) || ! array_key_exists('own', $data)) {
                    return;
                }
                if ($data['own'] === true) {
                    DB::table('notifications')->where('id', $row->id)->delete();

                    return;
                }

                unset($data['own']);
                DB::table('notifications')->where('id', $row->id)->update(['data' => json_encode($data)]);
            });
    }

    public function down(): void {}
};
