<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * The markup a toolbar and a toggle group render from PHP: roles, names,
 * states and what posts. The keyboard and the highlight are in
 * test-behaviour.mjs.
 */
final class ToolbarTest extends TestCase
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
    public function a_toolbar_is_named_oriented_and_its_buttons_are_items(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::toolbar label="Formatting" orientation="vertical" size="sm">
                <x-ds::toolbar-button icon="trash" label="Delete" :iconOnly="true" />
                <x-ds::toolbar-separator />
                <x-ds::toolbar-button label="Star" :pressed="true" />
                <x-ds::toolbar-button label="Share" href="/share" :disabled="true" />
            </x-ds::toolbar>
            BLADE);

        $bar = $x->query('//*[@role="toolbar"]')->item(0);
        $this->assertSame('Formatting', $bar->getAttribute('aria-label'));
        $this->assertSame('vertical', $bar->getAttribute('aria-orientation'));
        $this->assertStringContainsString('dsToolbar', $bar->getAttribute('x-data'));

        $items = $x->query('//*[@data-ds-toolbar-item]');
        $this->assertSame(3, $items->length);
        $this->assertSame('Delete', trim($items->item(0)->textContent), 'an icon-only button lost its name');
        $this->assertStringContainsString('size-control-sm', $items->item(0)->getAttribute('class'), 'the toolbar size did not reach the button');
        $this->assertFalse($items->item(0)->hasAttribute('aria-pressed'), 'a plain button claims a pressed state');
        $this->assertSame('true', $items->item(1)->getAttribute('aria-pressed'));
        $this->assertSame('a', $items->item(2)->nodeName);
        $this->assertFalse($items->item(2)->hasAttribute('href'));

        $rule = $x->query('//*[@role="separator"]')->item(0);
        $this->assertSame('horizontal', $rule->getAttribute('aria-orientation'), 'a separator runs across the toolbar');
    }

    #[Test]
    public function a_link_cannot_be_pressed(): void
    {
        $this->expectExceptionMessage('a link has no pressed state');
        Blade::render('<x-ds::toolbar label="t"><x-ds::toolbar-button label="x" href="/x" :pressed="false" /></x-ds::toolbar>');
    }

    #[Test]
    public function single_is_a_radiogroup_and_only_the_pressed_value_posts(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::toggle-group label="Alignment" name="align">
                <x-ds::toggle value="left" label="Left" :pressed="true" />
                <x-ds::toggle value="right" label="Right" />
            </x-ds::toggle-group>
            BLADE);

        $this->assertSame('radiogroup', $x->query('//*[@data-ds-toggle-group]')->item(0)->getAttribute('role'));

        $toggles = $x->query('//button[@data-ds-toggle]');
        $this->assertSame('radio', $toggles->item(0)->getAttribute('role'));
        $this->assertSame('true', $toggles->item(0)->getAttribute('aria-checked'));
        $this->assertSame('false', $toggles->item(1)->getAttribute('aria-checked'));

        $inputs = $x->query('//button[@data-ds-toggle]/input[@type="hidden"]');
        $this->assertSame('align', $inputs->item(0)->getAttribute('name'));
        $this->assertFalse($inputs->item(0)->hasAttribute('disabled'));
        $this->assertTrue($inputs->item(1)->hasAttribute('disabled'), 'an unpressed toggle posts');
    }

    #[Test]
    public function multiple_is_pressed_buttons_posting_an_array_and_the_binding_sits_on_the_root(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::toggle-group label="Marks" type="multiple" name="marks" wire:model.live="marks">
                <x-ds::toggle value="bold" label="Bold" :pressed="true" :disabled="true" />
                <x-ds::toggle value="italic" label="Italic" :pressed="true" />
            </x-ds::toggle-group>
            BLADE);

        $root = $x->query('//*[@data-ds-toggle-group]')->item(0);
        $this->assertSame('group', $root->getAttribute('role'));
        $this->assertSame('dsValue', $root->getAttribute('x-modelable'));
        $this->assertSame('marks', $root->getAttribute('wire:model.live'));

        $toggles = $x->query('//button[@data-ds-toggle]');
        $this->assertSame('true', $toggles->item(1)->getAttribute('aria-pressed'));
        $this->assertFalse($toggles->item(1)->hasAttribute('role'));

        $inputs = $x->query('//button[@data-ds-toggle]/input');
        $this->assertSame('marks[]', $inputs->item(1)->getAttribute('name'));
        $this->assertTrue($inputs->item(0)->hasAttribute('disabled'), 'a disabled toggle posts');
        $this->assertFalse($inputs->item(1)->hasAttribute('disabled'));
    }

    #[Test]
    public function a_toggle_group_without_a_name_posts_nothing_and_an_unknown_type_throws(): void
    {
        $html = Blade::render('<x-ds::toggle-group label="t" type="multiple"><x-ds::toggle value="a" label="A" /></x-ds::toggle-group>');
        $this->assertStringNotContainsString('<input', $html);

        $this->expectExceptionMessage('`type` is single or multiple');
        Blade::render('<x-ds::toggle-group label="t" type="many"><x-ds::toggle value="a" label="A" /></x-ds::toggle-group>');
    }
}
