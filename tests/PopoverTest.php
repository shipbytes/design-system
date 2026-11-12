<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use Throwable;
use PHPUnit\Framework\Attributes\Test;

/**
 * What ds::popover renders before Alpine has done anything: the panel's name
 * and role, its id, and whether the host owns the state. The behaviour is
 * driven in test-behaviour.mjs.
 */
final class PopoverTest extends TestCase
{
    /** @param  array<string, mixed>  $data */
    private function render(string $attributes, string $body = 'Body', array $data = []): string
    {
        return Blade::render(
            "<x-ds::popover {$attributes}>\n<x-slot:trigger><button type=\"button\">Open</button></x-slot:trigger>\n{$body}\n</x-ds::popover>",
            $data,
        );
    }

    #[Test]
    public function the_panel_is_a_named_dialog_hidden_until_alpine_shows_it(): void
    {
        $html = $this->render('label="Filters"');

        $this->assertMatchesRegularExpression('/role="dialog"[^>]*aria-label="Filters"/', $html);
        $this->assertStringContainsString('x-cloak', $html);
        $this->assertStringContainsString('x-show="dsOpen"', $html);
        $this->assertStringNotContainsString('aria-modal', $html, 'a popover is not modal and must not claim to be');
    }

    #[Test]
    public function labelledby_wins_over_label(): void
    {
        $html = $this->render('label="Ignored" labelledby="share-title"', '<h3 id="share-title">Share</h3>');

        $this->assertStringContainsString('aria-labelledby="share-title"', $html);
        $this->assertStringNotContainsString('aria-label="Ignored"', $html);
    }

    #[Test]
    public function a_quote_in_the_label_does_not_end_the_attribute(): void
    {
        $html = $this->render(':label="$name"', data: ['name' => 'Say "hi"']);

        $this->assertStringContainsString('aria-label="Say &quot;hi&quot;"', $html);
    }

    #[Test]
    public function an_id_names_the_panel_not_the_root(): void
    {
        $html = $this->render('label="x" id="filters-panel"');

        $this->assertSame(1, substr_count($html, 'id="filters-panel"'));
        $this->assertMatchesRegularExpression('/id="filters-panel" role="dialog"/', $html);
    }

    #[Test]
    public function the_default_id_is_stable_across_renders(): void
    {
        // Livewire keys a morph by id; a random one replaces the panel on
        // every response instead of patching it.
        preg_match('/id="(ds-popover-[0-9a-f]{8})"/', $this->render('label="Filters"'), $first);
        preg_match('/id="(ds-popover-[0-9a-f]{8})"/', $this->render('label="Filters"'), $second);

        $this->assertNotEmpty($first);
        $this->assertSame($first[1], $second[1]);
    }

    #[Test]
    public function self_scoped_by_default_and_bound_to_the_host_when_open_is_passed(): void
    {
        $this->assertStringNotContainsString('x-modelable', $this->render('label="x"'));

        $html = $this->render('label="x" open="filtersOpen"');
        $this->assertStringContainsString('x-modelable="dsOpen" x-model="filtersOpen"', $html);
    }

    #[Test]
    public function open_must_be_assignable(): void
    {
        // The view engine wraps what the component throws in a ViewException,
        // so the message is what is checked, as in OpenStateTest.
        try {
            $this->render("label=\"x\" open=\"mode === 'filters'\"");
            $message = null;
        } catch (Throwable $e) {
            $message = $e->getMessage();
        }

        $this->assertNotNull($message, 'a comparison was accepted as the open state');
        $this->assertStringContainsString('<x-ds::popover open="mode === \'filters\'"> is not assignable', $message);
    }

    #[Test]
    public function an_unknown_placement_or_width_falls_back_rather_than_rendering_nothing(): void
    {
        $html = $this->render('label="x" placement="sideways" width="huge"');

        $this->assertStringContainsString("placement: 'bottom-start'", $html);
        $this->assertStringContainsString('data-side="bottom"', $html);
        $this->assertStringContainsString('w-72', $html);
    }

    #[Test]
    public function the_side_is_on_the_panel_before_the_script_measures_it(): void
    {
        $this->assertStringContainsString('data-side="left"', $this->render('label="x" placement="left-end"'));
    }

    #[Test]
    public function not_dismissible_is_passed_to_the_behaviour(): void
    {
        $this->assertStringContainsString('dsPopover({&quot;dismissible&quot;:false})', $this->render('label="x" :dismissible="false"'));
    }
}
