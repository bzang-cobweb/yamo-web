<?php

namespace Bza\ConfigViewer\Tests;

use Bza\ConfigViewer\Support\KeyPatternMasker;
use PHPUnit\Framework\TestCase;

class KeyPatternMaskerTest extends TestCase
{
    private function masker(): KeyPatternMasker
    {
        return new KeyPatternMasker(['*password*', 'key', '*_key'], ['database.connections.*.url']);
    }

    public function test_it_masks_by_last_segment_case_insensitively(): void
    {
        $this->assertTrue($this->masker()->shouldMask('mail.mailers.smtp.PASSWORD', 'hunter2'));
        $this->assertTrue($this->masker()->shouldMask('app.key', 'base64:abc'));
        $this->assertTrue($this->masker()->shouldMask('services.stripe.secret_key', 'sk'));
    }

    public function test_it_masks_by_full_key(): void
    {
        $this->assertTrue($this->masker()->shouldMask('database.connections.mysql.url', 'mysql://u:p@h/db'));
        $this->assertFalse($this->masker()->shouldMask('app.url', 'https://example.test'));
    }

    public function test_it_only_masks_non_empty_strings(): void
    {
        $this->assertFalse($this->masker()->shouldMask('auth.password_timeout', 10800));
        $this->assertFalse($this->masker()->shouldMask('mail.mailers.smtp.password', ''));
        $this->assertFalse($this->masker()->shouldMask('mail.mailers.smtp.password', null));
    }

    public function test_a_parent_segment_does_not_trigger_masking(): void
    {
        $this->assertFalse($this->masker()->shouldMask('auth.passwords.users.provider', 'users'));
    }
}
