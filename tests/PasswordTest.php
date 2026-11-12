<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * ds::input's `reveal` and ds::password-strength, as the server renders them.
 * That every input WITHOUT `reveal` is unchanged is InputUnchangedTest.
 */
final class PasswordTest extends TestCase
{
    private function xpath(string $blade): \DOMXPath
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.Blade::render($blade));
        libxml_clear_errors();

        return new \DOMXPath($document);
    }

    #[Test]
    public function reveal_is_a_toggle_button_for_its_own_field(): void
    {
        $x = $this->xpath('<x-ds::input type="password" id="pw" name="password" label="Password" :reveal="true" />');
        $button = $x->query('//button')->item(0);

        $this->assertSame('button', $button->getAttribute('type'));
        $this->assertSame('pw', $button->getAttribute('aria-controls'));
        $this->assertSame('false', $button->getAttribute('aria-pressed'));
        $this->assertSame('Show password', $button->getAttribute('aria-label'));
        $this->assertStringContainsString('dsPassword', $button->getAttribute('x-data'));
        $this->assertSame('password', $x->query('//input')->item(0)->getAttribute('type'));
    }

    #[Test]
    public function reveal_means_nothing_off_a_password_field(): void
    {
        $this->assertStringNotContainsString(
            '<button',
            Blade::render('<x-ds::input type="text" name="name" :reveal="true" />'),
        );
    }

    #[Test]
    public function the_meter_is_told_which_field_and_speaks_its_level(): void
    {
        $x = $this->xpath('<x-ds::password-strength for="pw" :minLength="10" :labels="[\'Too weak\']" />');
        $root = $x->query('//div[@x-data]')->item(0);

        $this->assertSame('0', $root->getAttribute('data-level'));
        $this->assertStringContainsString(
            'dsPasswordStrength({"for":"pw","minLength":10,"labels":["Too weak","Fair","Good","Strong"]})',
            html_entity_decode($root->getAttribute('x-data')),
        );

        $live = $x->query('//*[@aria-live]')->item(0);
        $this->assertSame('polite', $live->getAttribute('aria-live'));
        $this->assertSame('true', $live->getAttribute('aria-atomic'));
        $this->assertStringContainsString('Password strength:', $live->textContent);
    }
}
