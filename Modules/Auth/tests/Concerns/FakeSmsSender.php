<?php

namespace Modules\Auth\Tests\Concerns;

use Modules\Auth\Sms\SmsSender;

/**
 * SMS gardés en mémoire pour les tests.
 */
final class FakeSmsSender implements SmsSender
{
    /** @var list<array{to: string, message: string}> */
    public array $messages = [];

    public function send(string $to, string $message): void
    {
        $this->messages[] = ['to' => $to, 'message' => $message];
    }
}
