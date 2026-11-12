<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * What the OTP input posts and how its boxes are named, before any script runs.
 * What the boxes DO is in test-behaviour.mjs.
 */
final class OtpInputTest extends TestCase
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
    public function it_posts_one_field_and_the_boxes_post_nothing(): void
    {
        $x = $this->xpath('<x-ds::otp-input name="code" label="Code" value="4821" />');

        $named = $x->query('//input[@name]');
        $this->assertSame(1, $named->length, 'exactly one element carries a name');
        $this->assertSame('hidden', $named->item(0)->getAttribute('type'));
        $this->assertSame('code', $named->item(0)->getAttribute('name'));
        $this->assertSame('4821', $named->item(0)->getAttribute('value'));

        $boxes = $x->query('//input[@data-ds-otp-box]');
        $this->assertSame(6, $boxes->length);
        $this->assertSame(['4', '8', '2', '1', '', ''], array_map(fn ($b) => $b->getAttribute('value'), iterator_to_array($boxes)));
    }

    #[Test]
    public function a_value_longer_than_the_field_is_cut_to_it(): void
    {
        $x = $this->xpath('<x-ds::otp-input name="pin" :length="4" value="123456" />');

        $this->assertSame(4, $x->query('//input[@data-ds-otp-box]')->length);
        $this->assertSame('1234', $x->query('//input[@name="pin"]')->item(0)->getAttribute('value'));
    }

    #[Test]
    public function every_box_is_named_and_only_the_first_asks_for_the_code(): void
    {
        $x = $this->xpath('<x-ds::otp-input name="code" label="Verification code" />');

        $group = $x->query('//*[@role="group"]')->item(0);
        $label = $x->query('//*[@id="'.$group->getAttribute('aria-labelledby').'"]')->item(0);
        $this->assertSame('Verification code', trim($label->textContent));

        $boxes = iterator_to_array($x->query('//input[@data-ds-otp-box]'));
        $this->assertSame('Digit 1 of 6', $boxes[0]->getAttribute('aria-label'));
        $this->assertSame('Digit 6 of 6', $boxes[5]->getAttribute('aria-label'));
        $this->assertSame('one-time-code', $boxes[0]->getAttribute('autocomplete'));
        $this->assertSame(['off'], array_values(array_unique(array_map(fn ($b) => $b->getAttribute('autocomplete'), array_slice($boxes, 1)))));
        $this->assertSame('numeric', $boxes[0]->getAttribute('inputmode'));

        // The label points at the first box, so pressing it starts the code.
        $this->assertSame($boxes[0]->getAttribute('id'), $label->getAttribute('for'));
    }

    #[Test]
    public function letters_are_characters_and_have_no_digit_keyboard(): void
    {
        $box = $this->xpath('<x-ds::otp-input name="ref" :numeric="false" />')->query('//input[@data-ds-otp-box]')->item(0);

        $this->assertSame('Character 1 of 6', $box->getAttribute('aria-label'));
        $this->assertFalse($box->hasAttribute('inputmode'));
    }

    #[Test]
    public function an_error_reaches_every_box(): void
    {
        $x = $this->xpath('<x-ds::otp-input name="code" error="Expired." help="Ignored while the error shows." />');

        foreach ($x->query('//input[@data-ds-otp-box]') as $box) {
            $this->assertSame('true', $box->getAttribute('aria-invalid'));
            $this->assertStringContainsString('Expired.', $x->query('//*[@id="'.$box->getAttribute('aria-describedby').'"]')->item(0)->textContent);
        }
        $this->assertStringNotContainsString('Ignored', $x->document->textContent);
    }

    #[Test]
    public function masked_autofocus_and_disabled(): void
    {
        $x = $this->xpath('<x-ds::otp-input name="pin" :length="4" value="12" :masked="true" :autofocus="true" />');
        $boxes = iterator_to_array($x->query('//input[@data-ds-otp-box]'));

        $this->assertSame(['password'], array_values(array_unique(array_map(fn ($b) => $b->getAttribute('type'), $boxes))));
        $this->assertSame([false, false, true, false], array_map(fn ($b) => $b->hasAttribute('autofocus'), $boxes), 'autofocus lands on the first empty box');

        $off = $this->xpath('<x-ds::otp-input name="pin" :disabled="true" />');
        $this->assertSame(7, $off->query('//input[@disabled]')->length, 'the boxes AND the posted field are disabled');
    }

    #[Test]
    public function the_hosts_attributes_reach_the_field_that_posts(): void
    {
        $x = $this->xpath('<x-ds::otp-input name="code" wire:model="code" class="mt-4" />');

        $posted = $x->query('//input[@type="hidden"]')->item(0);
        $this->assertSame('code', $posted->getAttribute('wire:model'));
        $this->assertStringNotContainsString('mt-4', $posted->getAttribute('class'));
        $this->assertStringContainsString('dsOtp', $x->query('//div[contains(@class, "mt-4")]')->item(0)->getAttribute('x-data'));
    }
}
