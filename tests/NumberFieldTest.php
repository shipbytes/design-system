<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * The native input underneath, and the buttons' state as the server renders it.
 * Pressing and holding is in test-behaviour.mjs.
 */
final class NumberFieldTest extends TestCase
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
    public function it_is_a_native_number_input_that_posts_by_itself(): void
    {
        $input = $this->xpath('<x-ds::number-field name="seats" label="Seats" :value="3" :min="1" :max="10" />')
            ->query('//input')->item(0);

        foreach (['type' => 'number', 'name' => 'seats', 'value' => '3', 'min' => '1', 'max' => '10', 'step' => '1', 'inputmode' => 'numeric'] as $attribute => $expected) {
            $this->assertSame($expected, $input->getAttribute($attribute), $attribute);
        }
    }

    #[Test]
    public function the_buttons_are_named_inert_to_tab_and_never_submit(): void
    {
        $buttons = iterator_to_array($this->xpath('<x-ds::number-field name="seats" label="Seats" id="s" />')->query('//button'));

        $this->assertSame(['Decrease Seats', 'Increase Seats'], array_map(fn ($b) => $b->getAttribute('aria-label'), $buttons));

        foreach ($buttons as $button) {
            $this->assertSame('button', $button->getAttribute('type'));
            $this->assertSame('-1', $button->getAttribute('tabindex'));
            $this->assertSame('s', $button->getAttribute('aria-controls'));
        }
    }

    #[Test]
    public function a_button_at_its_bound_is_disabled_before_any_script_runs(): void
    {
        $disabled = fn (string $blade) => array_map(
            fn ($b) => $b->hasAttribute('disabled'),
            iterator_to_array($this->xpath($blade)->query('//button')),
        );

        $this->assertSame([true, false], $disabled('<x-ds::number-field name="n" :value="1" :min="1" :max="3" />'));
        $this->assertSame([false, true], $disabled('<x-ds::number-field name="n" :value="3" :min="1" :max="3" />'));
        $this->assertSame([false, false], $disabled('<x-ds::number-field name="n" :value="2" :min="1" :max="3" />'));
        $this->assertSame([false, false], $disabled('<x-ds::number-field name="n" />'), 'no value and no bounds');
        $this->assertSame([true, true], $disabled('<x-ds::number-field name="n" :value="2" :disabled="true" />'));
    }

    #[Test]
    public function the_keyboard_a_phone_raises_fits_the_numbers_allowed(): void
    {
        $mode = fn (string $blade) => $this->xpath($blade)->query('//input')->item(0)->getAttribute('inputmode') ?: null;

        $this->assertSame('numeric', $mode('<x-ds::number-field name="n" :min="0" />'));
        $this->assertSame('decimal', $mode('<x-ds::number-field name="n" :min="0" :step="0.5" />'));
        // iOS's digit pads have no minus key.
        $this->assertNull($mode('<x-ds::number-field name="n" :min="-5" />'));
        $this->assertNull($mode('<x-ds::number-field name="n" />'));
    }

    #[Test]
    public function small_is_the_32px_control(): void
    {
        $class = $this->xpath('<x-ds::number-field name="n" size="sm" />')->query('//input')->item(0)->getAttribute('class');

        $this->assertStringContainsString('py-[calc(--spacing(1)-1px)]', $class);
        $this->assertStringNotContainsString('py-[calc(--spacing(2.5)-1px)]', $class);
    }
}
