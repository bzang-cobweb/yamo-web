<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Form\Models\FormUpload;
use Modules\Offer\Console\CompleteBookingsCommand;
use Modules\Offer\Console\ExpireBookingsCommand;
use Modules\Offer\Console\InviteReviewsCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fichiers envoyés pour un formulaire et jamais utilisés : supprimés après 24 h
// (décision 2026-11-19-contrat-champ-upload-photos).
Schedule::command('model:prune', ['--model' => [FormUpload::class]])->hourly();

// Chaque nuit, les demandes acceptées dont le dernier jour est passé deviennent « Terminée »
// (décision 2026-11-22-statut-termine-enregistre).
Schedule::command(CompleteBookingsCommand::class)->dailyAt('00:05');

// Chaque nuit, les demandes restées en attente après leur date deviennent « Expirée »
// (décision 2026-11-22-statut-expire-enregistre).
Schedule::command(ExpireBookingsCommand::class)->dailyAt('00:05');

// Chaque matin, l'invitation à donner son avis après un séjour ou une visite terminé
// (décision 2026-11-22-invitation-avis).
Schedule::command(InviteReviewsCommand::class)->dailyAt('10:00');
