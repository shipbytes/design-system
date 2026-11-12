<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * What a segmented control renders before any script runs, which is all of it:
 * radios that post, the chosen face drawn by CSS, and a glide that is only an
 * enhancement. The sliding itself is in test-behaviour.mjs.
 */
final class SegmentedControlTest extends TestCase
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
    public function it_is_radios_in_a_fieldset_with_the_chosen_face_drawn_by_css(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::segmented-control name="view" label="View" x-model="view">
                <x-ds::segment value="list" :checked="true">List</x-ds::segment>
                <x-ds::segment value="board" :disabled="true">Board</x-ds::segment>
            </x-ds::segmented-control>
            BLADE);

        $this->assertSame('View', trim($x->query('//fieldset/legend')->item(0)->textContent));

        $inputs = $x->query('//fieldset//label/input');
        $this->assertSame(2, $inputs->length);
        foreach ($inputs as $input) {
            $this->assertSame('radio', $input->getAttribute('type'));
            $this->assertSame('view', $input->getAttribute('name'));
            $this->assertSame('view', $input->getAttribute('x-model'), 'the binding did not reach the input');
            $this->assertFalse($input->hasAttribute('data-ds-segment-input'), 'the binding hook was left in the page');
        }
        $this->assertTrue($inputs->item(0)->hasAttribute('checked'));
        $this->assertTrue($inputs->item(1)->hasAttribute('disabled'));

        $label = $x->query('//label')->item(0)->getAttribute('class');
        $this->assertStringContainsString('has-checked:bg-surface', $label, 'no chosen face before the script');
        $this->assertStringContainsString('group-data-[ds-glide-ready]/glide:has-checked:bg-transparent', $label);
    }

    #[Test]
    public function the_glide_is_a_plain_directive_with_no_behaviour_scope_so_a_missing_script_cannot_throw(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::segmented-control name="view" label="View" wire:model.live="view">
                <x-ds::segment value="list" :checked="true">List</x-ds::segment>
            </x-ds::segmented-control>
            BLADE);

        $track = $x->query('//*[@data-ds-glide]')->item(0);
        $this->assertSame('', $track->getAttribute('x-data'), 'the track asks for a behaviour, which throws without the script');
        $this->assertSame('$wire.view', $track->getAttribute('x-ds-glide'), 'the glide does not watch the Livewire property');
        $this->assertTrue($x->query('//*[@data-ds-glide-highlight]')->item(0)->hasAttribute('hidden'));
    }

    #[Test]
    public function links_are_a_nav_with_the_current_view_marked_and_drawn_chosen(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::segmented-control label="Show">
                <x-ds::segment href="/all" :active="true">All</x-ds::segment>
                <x-ds::segment href="/old" :disabled="true">Archived</x-ds::segment>
            </x-ds::segmented-control>
            BLADE);

        $links = $x->query('//nav//a');
        $this->assertSame('page', $links->item(0)->getAttribute('aria-current'));
        $this->assertStringContainsString('bg-surface', $links->item(0)->getAttribute('class'));
        $this->assertFalse($links->item(1)->hasAttribute('href'), 'a disabled link still navigates');
        $this->assertSame('true', $links->item(1)->getAttribute('aria-disabled'));
    }

    #[Test]
    public function mixing_links_and_inputs_throws(): void
    {
        $this->expectExceptionMessage('mixes link segments');

        Blade::render(<<<'BLADE'
            <x-ds::segmented-control name="v" label="View">
                <x-ds::segment value="a">A</x-ds::segment>
                <x-ds::segment href="/b">B</x-ds::segment>
            </x-ds::segmented-control>
            BLADE);
    }
}
