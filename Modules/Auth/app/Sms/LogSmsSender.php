<?php

namespace Modules\Auth\Sms;

use Illuminate\Support\Facades\Log;

/**
 * SMS écrit dans le journal Laravel (`storage/logs/laravel.log`) au lieu d'être
 * envoyé : aucun compte ni coût en développement.
 */
final class LogSmsSender implements SmsSender
{
    public function send(string $to, string $message): void
    {
        Log::info('[SMS] '.$to.' : '.$message);
    }
}
