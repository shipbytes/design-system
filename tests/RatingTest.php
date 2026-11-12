<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * The rating's markup: real radios, the "no rating" choice, the readonly image.
 * Choosing, the preview and the arrow keys are in test-behaviour.mjs.
 */
final class RatingTest extends TestCase
{
    private function xpath(string $blade, array $data = []): \DOMXPath
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.Blade::render($blade, $data));
        libxml_clear_errors();

        return new \DOMXPath($document);
    }

    #[Test]
    public function it_is_a_radio_per_star_then_no_rating_and_the_value_is_checked(): void
    {
        $x = $this->xpath('<x-ds::rating name="score" label="Quality" :value="3" />');

        $radios = iterator_to_array($x->query('//input[@type="radio"]'));
        $this->assertSame(['1', '2', '3', '4', '5', ''], array_map(fn ($r) => $r->getAttribute('value'), $radios));
        $this->assertSame(['score'], array_values(array_unique(array_map(fn ($r) => $r->getAttribute('name'), $radios))));
        $this->assertSame(['3'], array_map(fn ($r) => $r->getAttribute('value'), iterator_to_array($x->query('//input[@checked]'))));

        $names = array_map(fn ($r) => trim($r->parentNode->textContent), $radios);
        $this->assertSame(['1 star', '2 stars', '3 stars', '4 stars', '5 stars', 'No rating'], $names);

        $group = $x->query('//fieldset')->item(0);
        $this->assertSame('radiogroup', $group->getAttribute('role'));
        $this->assertSame('Quality', trim($x->query('//*[@id="'.$group->getAttribute('aria-labelledby').'"]')->item(0)->textContent));
    }

    #[Test]
    public function with_no_value_no_rating_is_the_checked_one(): void
    {
        $x = $this->xpath('<x-ds::rating name="score" />');

        $this->assertSame([''], array_map(fn ($r) => $r->getAttribute('value'), iterator_to_array($x->query('//input[@checked]'))));
    }

    #[Test]
    public function required_drops_no_rating_and_marks_the_stars(): void
    {
        $x = $this->xpath('<x-ds::rating name="score" required />');

        $this->assertSame(5, $x->query('//input[@type="radio"]')->length);
        $this->assertSame(5, $x->query('//input[@required]')->length);
        $this->assertSame(0, $x->query('//input[@checked]')->length);
        $this->assertSame('true', $x->query('//fieldset')->item(0)->getAttribute('aria-required'));
    }

    #[Test]
    public function a_binding_reaches_every_radio_and_the_rest_stays_on_the_group(): void
    {
        $x = $this->xpath('<x-ds::rating name="score" wire:model.live="score" class="mt-4" data-test="1" />');

        $this->assertSame(6, $x->query('//input[@*[name()="wire:model.live"]]')->length);
        $group = $x->query('//fieldset')->item(0);
        $this->assertStringContainsString('mt-4', $group->getAttribute('class'));
        $this->assertSame('1', $group->getAttribute('data-test'));
        $this->assertFalse($group->hasAttribute('wire:model.live'));
    }

    #[Test]
    public function an_error_describes_and_invalidates_the_group(): void
    {
        $x = $this->xpath('<x-ds::rating name="score" error="Choose one." help="Ignored." />');
        $group = $x->query('//fieldset')->item(0);

        $this->assertSame('true', $group->getAttribute('aria-invalid'));
        $this->assertStringContainsString('Choose one.', $x->query('//*[@id="'.$group->getAttribute('aria-describedby').'"]')->item(0)->textContent);
        $this->assertStringNotContainsString('Ignored', $x->document->textContent);
    }

    #[Test]
    public function readonly_is_one_image_named_to_the_nearest_half(): void
    {
        foreach ([[3.5, '3.5 out of 5'], [4.24, '4 out of 5'], [4.26, '4.5 out of 5'], [9, '5 out of 5'], [null, 'Not rated']] as [$value, $spoken]) {
            $x = $this->xpath('<x-ds::rating :readonly="true" :value="$v" />'.PHP_EOL, ['v' => $value]);
            $this->assertSame($spoken, $x->query('//*[@role="img"]')->item(0)->getAttribute('aria-label'));
            $this->assertSame(0, $x->query('//input')->length, 'a readonly rating posts nothing');
        }
    }

    #[Test]
    public function readonly_draws_a_half_star_as_a_clipped_overlay(): void
    {
        $x = $this->xpath('<x-ds::rating :readonly="true" :value="2.5" />');

        $stars = $x->query('//*[@role="img"]/span');
        $this->assertSame(5, $stars->length);
        $this->assertSame(1, $x->query('//*[@role="img"]/span[3]/span[contains(@class, "w-1/2")]')->length, 'the third star is not half filled');
        $this->assertSame(1, $x->query('//*[@role="img"]//span[contains(@class, "w-1/2")]')->length);
    }

    #[Test]
    public function it_refuses_a_missing_name_and_a_silly_max(): void
    {
        foreach (['<x-ds::rating />' => 'needs a `name`', '<x-ds::rating name="s" :max="0" />' => 'out of range'] as $blade => $message) {
            try {
                Blade::render($blade);
                $this->fail("{$blade} rendered");
            } catch (\Throwable $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }
}
