<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Enums\VerificationChannel;
use Modules\Auth\Enums\VerificationPurpose;
use Modules\Auth\Mail\VerificationCodeMail;
use Modules\Auth\Models\User;
use Modules\Auth\Models\VerificationCode;
use Modules\Auth\Sms\SmsSender;

/**
 * Seul domicile des codes de vérification : création, envoi (e-mail ou SMS),
 * vérification, renvoi. Un code a 6 chiffres et n'est jamais stocké en clair. Sa
 * durée, son nombre d'essais et le délai avant renvoi viennent de la config
 * `auth.verification_codes` (par défaut 10 minutes, 5 essais, 60 secondes).
 */
final class VerificationCodeService
{
    public function __construct(private readonly SmsSender $sms) {}

    /** Durée de validité d'un code, en minutes. */
    public function minutes(): int
    {
        return (int) config('auth.verification_codes.minutes', 10);
    }

    /** Essais permis avant que le code soit épuisé. */
    public function maxAttempts(): int
    {
        return (int) config('auth.verification_codes.max_attempts', 5);
    }

    /** Délai minimal entre deux envois, en secondes. */
    public function resendSeconds(): int
    {
        return (int) config('auth.verification_codes.resend_seconds', 60);
    }

    /**
     * Ouvre un défi et envoie un code vers chaque destination.
     *
     * @param  array<string, string>  $destinations  canal (`email`, `sms`) => destination
     * @param  array<string, mixed>|null  $payload  données à appliquer une fois les codes validés
     */
    public function start(VerificationPurpose $purpose, ?User $user, array $destinations, ?array $payload = null): string
    {
        $challenge = (string) Str::uuid();

        foreach ($destinations as $channel => $destination) {
            $this->issue(new VerificationCode([
                'challenge' => $challenge,
                'purpose' => $purpose,
                'user_id' => $user?->getKey(),
                'channel' => VerificationChannel::from($channel),
                'destination' => $destination,
                'payload' => $payload,
            ]));
        }

        return $challenge;
    }

    /**
     * Vérifie un code du défi (le canal précise lequel quand il y en a plusieurs).
     *
     * @throws ValidationException code inconnu, expiré, épuisé ou faux (erreur sur `code`)
     */
    public function verify(string $challenge, ?VerificationChannel $channel, string $code, ?User $actingUser = null): VerificationCode
    {
        $pending = VerificationCode::query()->ofChallenge($challenge)->whereNull('verified_at')
            ->when($channel !== null, fn ($query) => $query->where('channel', $channel))
            ->orderBy('channel')
            ->first();

        if ($pending === null || ($pending->purpose->needsSignedInUser() && ! $actingUser?->is($pending->user))) {
            throw $this->fail('unknown');
        }

        if ($pending->expires_at->isPast()) {
            throw $this->fail('expired');
        }

        if ($pending->attempts >= $this->maxAttempts()) {
            throw $this->fail('exhausted');
        }

        if (! Hash::check($code, $pending->code_hash)) {
            $pending->increment('attempts');

            throw $pending->attempts >= $this->maxAttempts() ? $this->fail('exhausted') : $this->fail('wrong');
        }

        $pending->forceFill(['verified_at' => now()])->save();

        return $pending;
    }

    /**
     * Prochain code à saisir du défi, ou `null` si tous sont validés.
     */
    public function nextPending(string $challenge): ?VerificationCode
    {
        return VerificationCode::query()->ofChallenge($challenge)->whereNull('verified_at')->orderBy('channel')->first();
    }

    /**
     * Renvoie un nouveau code pour chaque destination encore en attente.
     *
     * @throws ValidationException trop tôt, ou défi inconnu
     */
    public function resend(string $challenge, ?User $actingUser = null): void
    {
        $pending = VerificationCode::query()->ofChallenge($challenge)->whereNull('verified_at')->get();

        if ($pending->isEmpty() || ($pending->first()->purpose->needsSignedInUser() && ! $actingUser?->is($pending->first()->user))) {
            throw $this->fail('unknown');
        }

        if ($pending->contains(fn (VerificationCode $code): bool => $code->sent_at->gt(now()->subSeconds($this->resendSeconds())))) {
            throw $this->fail('too_soon');
        }

        $pending->each(fn (VerificationCode $code) => $this->issue($code));
    }

    /**
     * Une fois l'opération achevée, ses codes ne servent plus.
     */
    public function close(string $challenge): void
    {
        VerificationCode::query()->ofChallenge($challenge)->delete();
    }

    /**
     * Nouveau code : haché, remis à zéro, envoyé.
     */
    private function issue(VerificationCode $verification): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verification->forceFill([
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'sent_at' => now(),
            'expires_at' => now()->addMinutes($this->minutes()),
        ])->save();

        if ($verification->channel === VerificationChannel::EMAIL) {
            Mail::to($verification->destination)->locale(App::getLocale())->send(new VerificationCodeMail($code, $this->minutes()));
        } else {
            $this->sms->send($verification->destination, __('auth::verification.sms', ['code' => $code, 'minutes' => $this->minutes()]));
        }
    }

    private function fail(string $reason): ValidationException
    {
        return ValidationException::withMessages(['code' => __('auth::verification.errors.'.$reason)]);
    }
}
