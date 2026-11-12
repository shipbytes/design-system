<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * The stepper's list semantics, what each status says aloud, and the
 * narrow-width summary it reads back from its own steps.
 */
final class StepperTest extends TestCase
{
    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.$html);
        libxml_clear_errors();

        return new \DOMXPath($document);
    }

    private function stepper(string $steps, string $props = 'label="Checkout"'): string
    {
        return Blade::render("<x-ds::stepper {$props}>{$steps}</x-ds::stepper>");
    }

    #[Test]
    public function it_is_an_ordered_list_with_the_current_step_marked(): void
    {
        $x = $this->xpath($this->stepper(
            '<x-ds::step status="complete" title="Account" />'
            .'<x-ds::step status="current" title="Plan" />'
            .'<x-ds::step title="Review" />'
        ));

        $this->assertSame('Checkout', $x->query('//ol')->item(0)->getAttribute('aria-label'));
        $this->assertSame(3, $x->query('//ol/li')->length);

        $current = $x->query('//li[@aria-current]');
        $this->assertSame(1, $current->length, 'exactly one step is current');
        $this->assertSame('step', $current->item(0)->getAttribute('aria-current'));
        $this->assertStringContainsString('Plan', $current->item(0)->textContent);
    }

    #[Test]
    public function every_status_is_spoken_not_only_coloured(): void
    {
        $html = $this->stepper(
            '<x-ds::step status="complete" title="A" />'
            .'<x-ds::step status="current" title="B" />'
            .'<x-ds::step status="error" title="C" />'
            .'<x-ds::step status="upcoming" title="D" />'
        );

        // The text a screen reader gets: everything under the item except what
        // is aria-hidden, which is the marker's glyph or number.
        $x = $this->xpath($html);
        $spoken = array_map(
            fn (\DOMNode $li) => trim(preg_replace('/\s+/', ' ', implode('', array_map(
                fn (\DOMNode $t) => $t->nodeValue,
                iterator_to_array($x->query('.//text()[not(ancestor::*[@aria-hidden="true"])]', $li)),
            )))),
            iterator_to_array($x->query('//ol/li')),
        );

        $this->assertSame(['A, completed', 'B, current step', 'C, needs attention', 'D, not started'], $spoken);
    }

    #[Test]
    public function only_a_step_that_is_not_current_becomes_a_link(): void
    {
        $x = $this->xpath($this->stepper(
            '<x-ds::step status="complete" title="Account" href="/a" />'
            .'<x-ds::step status="current" title="Plan" href="/b" />'
        ));

        $links = $x->query('//a[@href]');
        $this->assertSame(1, $links->length);
        $this->assertSame('/a', $links->item(0)->getAttribute('href'));
    }

    #[Test]
    public function the_narrow_summary_counts_the_steps_and_names_the_current_one(): void
    {
        // Rendered with a title that needs escaping, so a double-escape shows.
        $html = Blade::render(
            '<x-ds::stepper><x-ds::step status="complete" title="A" /><x-ds::step status="current" :title="$t" />'
            .'<x-ds::step title="C" /><x-ds::step title="D" /></x-ds::stepper>',
            ['t' => 'Plan & "tier"'],
        );

        $summary = $this->xpath($html)->query('//div[@aria-hidden="true"]/p');
        $this->assertSame('Step 2 of 4', trim($summary->item(0)->textContent));
        $this->assertSame('Plan & "tier"', trim($summary->item(1)->textContent));
    }

    #[Test]
    public function with_no_current_step_the_summary_counts_what_is_done(): void
    {
        $done = $this->stepper('<x-ds::step status="complete" title="A" /><x-ds::step status="complete" title="B" />');
        $this->assertStringContainsString('All 2 steps complete', $done);

        $error = $this->stepper('<x-ds::step status="complete" title="A" /><x-ds::step status="error" title="B" />');
        $this->assertStringContainsString('Step 2 of 2', $error);
    }

    #[Test]
    public function a_vertical_stepper_has_no_summary(): void
    {
        $html = $this->stepper('<x-ds::step status="current" title="A" />', 'orientation="vertical"');

        $this->assertStringNotContainsString('Step 1 of 1', $html);
        $this->assertStringNotContainsString('sr-only @min-[40rem]', $html, 'a vertical step hid its title');
    }
}
