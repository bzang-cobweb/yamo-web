<?php

namespace Modules\Auth\Tests\Concerns;

use Illuminate\Support\Facades\Mail;
use Modules\Auth\Mail\VerificationCodeMail;
use Modules\Auth\Sms\SmsSender;

/**
 * Lit les codes envoyés par e-mail (Mail::fake) et par SMS (FakeSmsSender).
 */
trait CapturesVerificationCodes
{
    protected FakeSmsSender $sms;

    protected function captureVerificationCodes(): void
    {
        Mail::fake();
        $this->sms = new FakeSmsSender;
        $this->app->instance(SmsSender::class, $this->sms);
    }

    protected function emailCode(string $to): string
    {
        $mail = Mail::sent(VerificationCodeMail::class, fn (VerificationCodeMail $mail): bool => $mail->hasTo($to))->last();
        $this->assertNotNull($mail, "Aucun code envoyé à $to.");

        return $mail->code;
    }

    protected function smsCode(string $to): string
    {
        $sent = array_values(array_filter($this->sms->messages, fn (array $sms): bool => $sms['to'] === $to));
        $this->assertNotEmpty($sent, "Aucun SMS envoyé à $to.");
        preg_match('/\d{6}/', end($sent)['message'], $match);

        return $match[0];
    }
}
