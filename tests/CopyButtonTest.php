<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * What the copy button renders before any script runs, and the props it
 * refuses. Copying, the swap and the failure event are in test-behaviour.mjs.
 */
final class CopyButtonTest extends TestCase
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
    public function it_needs_exactly_one_source(): void
    {
        foreach (['<x-ds::copy-button />', '<x-ds::copy-button value="a" target="#b" />'] as $blade) {
            try {
                Blade::render($blade);
                $this->fail("{$blade} rendered");
            } catch (\Throwable $e) {
                $this->assertStringContainsString('exactly one of `value`', $e->getMessage());
            }
        }
    }

    #[Test]
    public function both_labels_share_one_cell_and_the_copied_one_starts_invisible(): void
    {
        $x = $this->xpath('<x-ds::copy-button value="k" />');

        $faces = $x->query('//button/span[contains(@class, "grid text-start")]/span');
        $this->assertSame(['Copy', 'Copied'], array_map(fn ($n) => trim($n->textContent), iterator_to_array($faces)));
        $this->assertStringNotContainsString('invisible', $faces->item(0)->getAttribute('class'));
        $this->assertStringContainsString('invisible', $faces->item(1)->getAttribute('class'));

        $this->assertSame('polite', $x->query('//span[@aria-live]')->item(0)->getAttribute('aria-live'));
        $this->assertNull($x->query('//button//*[@aria-live]')->item(0), 'the live region is inside the button, so it joins its name');
        $this->assertSame('button', $x->query('//button')->item(0)->getAttribute('type'));
    }

    #[Test]
    public function icon_only_is_named_by_the_label_and_the_host_attributes_reach_the_button(): void
    {
        $x = $this->xpath('<x-ds::copy-button target="#k" :iconOnly="true" label="Copy key" id="c" data-test="1" />');
        $button = $x->query('//button')->item(0);

        $this->assertSame('Copy key', $button->getAttribute('aria-label'));
        $this->assertSame('c', $button->getAttribute('id'));
        $this->assertSame('1', $button->getAttribute('data-test'));
        $this->assertNull($x->query('//button/span[contains(@class, "grid text-start")]')->item(0), 'an icon-only button renders a label');
        $this->assertStringContainsString('"target":"#k"', $x->query('//span[@x-data]')->item(0)->getAttribute('x-data'));
    }
}
