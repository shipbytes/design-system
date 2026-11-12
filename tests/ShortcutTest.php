<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Shortcut;

/**
 * Support\Shortcut writes a stored shortcut as resources/js/shared/shortcut.js
 * does (scripts/test-js.mjs holds the same answers), and ds::kbd and
 * ds::shortcut-input draw it from PHP before any script runs.
 */
final class ShortcutTest extends TestCase
{
    #[Test]
    public function it_normalises_the_string(): void
    {
        $this->assertSame('mod+shift+k', Shortcut::normalise('Shift+Mod+K'));
        $this->assertSame('meta+alt+p', Shortcut::normalise('cmd + option + p'));
        $this->assertSame('ctrl+/', Shortcut::normalise('control+/'));
        $this->assertSame('', Shortcut::normalise(''));
        $this->assertSame('', Shortcut::normalise(null));
    }

    #[Test]
    public function it_draws_words_off_a_mac_and_symbols_on_one(): void
    {
        $this->assertSame(['Ctrl', 'Shift', 'K'], Shortcut::keys('mod+shift+k'));
        $this->assertSame(['⌘', '⇧', 'K'], Shortcut::keys('mod+shift+k', true));
        $this->assertSame(['⌥', '↑'], Shortcut::keys('alt+arrowup', true));
        $this->assertSame('Control+Shift+K', Shortcut::words('mod+shift+k'));
        $this->assertSame('Command+Option+Up Arrow', Shortcut::words('mod+alt+arrowup', true));
        $this->assertSame(['Ctrl', 'Space'], Shortcut::keys('mod+space'));
        $this->assertSame(['F2'], Shortcut::keys('f2'));
    }

    #[Test]
    public function kbd_draws_a_stored_shortcut_with_words_to_read(): void
    {
        $html = Blade::render('<x-ds::kbd shortcut="Shift+Mod+K" />');

        $this->assertStringContainsString('data-ds-shortcut="mod+shift+k"', $html);
        $this->assertStringContainsString('x-ds-shortcut', $html);
        $this->assertStringContainsString('<span class="sr-only" data-ds-kbd-words>Control+Shift+K</span>', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertSame(4, substr_count($html, '<kbd'), 'an outer kbd and one per key');

        $empty = Blade::render('<x-ds::kbd shortcut="" />');
        $this->assertStringContainsString('hidden', $empty);
        $this->assertStringContainsString('data-ds-key=', $empty);
    }

    #[Test]
    public function kbd_without_the_prop_is_unchanged(): void
    {
        $this->assertStringNotContainsString('x-ds-shortcut', Blade::render('<x-ds::kbd :keys="[\'Ctrl\', \'K\']" />'));
        $this->assertStringNotContainsString('x-ds-shortcut', Blade::render('<x-ds::kbd>K</x-ds::kbd>'));
    }

    #[Test]
    public function the_field_holds_the_string_with_no_script(): void
    {
        $html = Blade::render('<x-ds::shortcut-input name="search" label="Open search" value="Shift+Mod+K" />');

        $this->assertMatchesRegularExpression('/<input[^>]*type="text"[^>]*name="search"[^>]*value="mod\+shift\+k"/s', $html);
        $this->assertStringContainsString('data-ds-shortcut-record', $html);
        $this->assertStringContainsString('role="status"', $html);
        // The browser's own shortcuts are refused by default.
        $this->assertStringContainsString('mod+w', $html);
    }
}
