<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * What a chip group renders with no script, which is all it ever has: real
 * inputs that post, links that navigate, and the binding where it binds.
 * Choosing and the check's width are in test-behaviour.mjs.
 */
final class ChipGroupTest extends TestCase
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
    public function single_is_radios_under_one_name_in_a_fieldset_named_by_its_legend(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::chip-group name="status" label="Status">
                <x-ds::chip value="open" :checked="true">Open</x-ds::chip>
                <x-ds::chip value="closed">Closed</x-ds::chip>
            </x-ds::chip-group>
            BLADE);

        $this->assertSame('Status', trim($x->query('//fieldset/legend')->item(0)->textContent));

        $inputs = $x->query('//fieldset//label/input');
        $this->assertSame(2, $inputs->length);
        foreach ($inputs as $input) {
            $this->assertSame('radio', $input->getAttribute('type'));
            $this->assertSame('status', $input->getAttribute('name'));
            $this->assertFalse($input->hasAttribute('data-ds-chip-input'), 'the binding hook was left in the page');
        }
        $this->assertTrue($inputs->item(0)->hasAttribute('checked'));
        $this->assertFalse($inputs->item(1)->hasAttribute('checked'));
        $this->assertSame('Closed', trim($x->query('//label')->item(1)->textContent));
    }

    #[Test]
    public function multiple_is_checkboxes_posting_an_array_and_the_binding_reaches_each_one(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::chip-group name="tags" label="Tags" :multiple="true" wire:model.live="tags" class="mt-4">
                <x-ds::chip value="billing" :checked="true">Billing</x-ds::chip>
                <x-ds::chip value="bug" :disabled="true">Bug</x-ds::chip>
            </x-ds::chip-group>
            BLADE);

        $inputs = $x->query('//input');
        foreach ($inputs as $input) {
            $this->assertSame('checkbox', $input->getAttribute('type'));
            $this->assertSame('tags[]', $input->getAttribute('name'));
            $this->assertSame('tags', $input->getAttribute('wire:model.live'));
        }
        $this->assertTrue($inputs->item(1)->hasAttribute('disabled'));

        $fieldset = $x->query('//fieldset')->item(0);
        $this->assertFalse($fieldset->hasAttribute('wire:model.live'), 'the binding stayed on the fieldset, where it binds nothing');
        $this->assertStringContainsString('mt-4', $fieldset->getAttribute('class'));
    }

    #[Test]
    public function link_chips_are_a_nav_and_the_active_one_is_current(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::chip-group label="Show">
                <x-ds::chip href="/t" :active="true" :count="16">All</x-ds::chip>
                <x-ds::chip href="/t?mine=1">Mine</x-ds::chip>
                <x-ds::chip href="/t?old=1" :disabled="true">Archived</x-ds::chip>
            </x-ds::chip-group>
            BLADE);

        $nav = $x->query('//nav')->item(0);
        $this->assertSame('Show', trim($x->query('//*[@id="'.$nav->getAttribute('aria-labelledby').'"]')->item(0)->textContent));
        $this->assertSame(0, $x->query('//input|//fieldset')->length);

        $links = $x->query('//nav//a');
        $this->assertSame('true', $links->item(0)->getAttribute('aria-current'));
        $this->assertFalse($links->item(1)->hasAttribute('aria-current'));
        $this->assertFalse($links->item(2)->hasAttribute('href'), 'a disabled link chip can still be followed');
        $this->assertSame('true', $links->item(2)->getAttribute('aria-disabled'));
    }

    #[Test]
    public function it_refuses_a_group_that_mixes_links_and_inputs(): void
    {
        $this->expectExceptionMessage('mixes link chips');

        Blade::render(<<<'BLADE'
            <x-ds::chip-group name="s" label="Status">
                <x-ds::chip value="open">Open</x-ds::chip>
                <x-ds::chip href="/all">All</x-ds::chip>
            </x-ds::chip-group>
            BLADE);
    }

    #[Test]
    public function an_input_chip_needs_a_name_and_a_value(): void
    {
        foreach ([
            '<x-ds::chip value="a">A</x-ds::chip>' => 'needs a `name`',
            '<x-ds::chip-group name="s" label="S"><x-ds::chip>A</x-ds::chip></x-ds::chip-group>' => 'needs a `value`',
        ] as $blade => $message) {
            try {
                Blade::render($blade);
                $this->fail("{$blade} rendered");
            } catch (\Throwable $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }
}
