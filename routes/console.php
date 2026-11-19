<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Form\Models\FormUpload;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fichiers envoyés pour un formulaire et jamais utilisés : supprimés après 24 h
// (décision 2026-11-19-contrat-champ-upload-photos).
Schedule::command('model:prune', ['--model' => [FormUpload::class]])->hourly();
