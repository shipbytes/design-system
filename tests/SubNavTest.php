<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

/**
 * What ds::sub-nav renders before Alpine boots: which sections are open, which
 * one contains the current page, and what the collapsed rail links to. Toggling
 * is driven in test-behaviour.mjs.
 */
final class SubNavTest extends TestCase
{
    private function render(string $attributes, bool $currentChild = false): string
    {
        $current = $currentChild ? ' :active="true"' : '';

        return Blade::render(<<<BLADE
            <x-ds::sub-nav label="Reports" {$attributes}>
                <x-slot:icon><x-ds::icon name="chart-bar" /></x-slot:icon>
                <x-ds::nav-item href="/reports/revenue?from=q3&to=q4" label="Revenue" />
                <x-ds::nav-item href="/reports/churn" label="Churn"{$current} />
            </x-ds::sub-nav>
            BLADE);
    }

    /** The button element, opening tag only. */
    private function button(string $html): string
    {
        preg_match('/<button\b[^>]*>/s', $html, $m);

        return $m[0] ?? '';
    }

    /** The element holding the pages, opening tag only. */
    private function group(string $html): string
    {
        preg_match('/<div\b[^>]*role="group"[^>]*>/s', $html, $m);

        return $m[0] ?? '';
    }

    #[Test]
    public function a_section_holding_the_current_page_renders_open(): void
    {
        $html = $this->render('', currentChild: true);

        $this->assertStringContainsString('aria-expanded="true"', $this->button($html));
        $this->assertStringNotContainsString('display: none', $this->group($html));
        $this->assertStringContainsString('dsSubNavOpen: true', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'), 'only the page carries aria-current, never the section');
    }

    #[Test]
    public function any_other_section_renders_closed_with_its_pages_hidden(): void
    {
        $html = $this->render('');

        $this->assertStringContainsString('aria-expanded="false"', $this->button($html));
        $this->assertStringContainsString('display: none', $this->group($html));
        $this->assertStringContainsString('dsSubNavOpen: false', $html);
    }

    #[Test]
    public function open_overrides_the_current_page(): void
    {
        $this->assertStringContainsString('aria-expanded="true"', $this->button($this->render(':open="true"')));
        $this->assertStringContainsString('aria-expanded="false"', $this->button($this->render(':open="false"', currentChild: true)));
    }

    #[Test]
    public function the_button_controls_a_group_named_by_it(): void
    {
        $html = $this->render('');
        preg_match('/aria-controls="([^"]+)"/', $this->button($html), $controls);
        preg_match('/\bid="([^"]+)"/', $this->button($html), $buttonId);

        $this->assertStringContainsString('type="button"', $this->button($html));
        $this->assertStringContainsString('id="'.$controls[1].'"', $this->group($html));
        $this->assertStringContainsString('aria-labelledby="'.$buttonId[1].'"', $this->group($html));
    }

    #[Test]
    public function containing_the_current_page_looks_different_from_being_it(): void
    {
        $holding = $this->button($this->render('', currentChild: true));
        $not = $this->button($this->render(''));

        $this->assertStringContainsString('text-fg ', $holding);
        $this->assertStringNotContainsString('shadow-raised', $holding, 'the raised card means "this is the page"');
        $this->assertStringContainsString('text-fg-body', $not);
    }

    #[Test]
    public function without_collapsed_when_there_is_no_collapsed_link(): void
    {
        $html = $this->render('');

        $this->assertStringNotContainsString('x-cloak', $html);
        $this->assertStringNotContainsString('title="Reports"', $html);
    }

    #[Test]
    public function collapsed_it_links_to_its_first_page(): void
    {
        $html = $this->render('collapsedWhen="railCollapsed"');

        $this->assertMatchesRegularExpression(
            '/<a\s+href="\/reports\/revenue\?from=q3&amp;to=q4"\s+title="Reports"\s+x-show="railCollapsed"\s+x-cloak/',
            $html,
            'the first page\'s href, decoded once and escaped once',
        );
        $this->assertStringContainsString('x-show="!(railCollapsed)"', $this->button($html));
        $this->assertStringContainsString('x-show="dsSubNavOpen &amp;&amp; !(railCollapsed)"', $this->group($html));
    }

    #[Test]
    public function href_overrides_the_collapsed_link(): void
    {
        $html = $this->render('collapsedWhen="railCollapsed" href="/reports"');

        $this->assertStringContainsString('<a
            href="/reports"', $html);
    }

    #[Test]
    public function collapsed_with_nowhere_to_link_is_refused(): void
    {
        try {
            Blade::render('<x-ds::sub-nav label="Empty" collapsedWhen="railCollapsed"></x-ds::sub-nav>');
            $this->fail('a collapsible section with no page and no href rendered');
        } catch (Throwable $e) {
            $this->assertStringContainsString('ds::sub-nav "Empty" has nowhere to link', $e->getMessage());
        }
    }

    #[Test]
    public function the_id_is_stable_across_renders(): void
    {
        preg_match('/aria-controls="([^"]+)"/', $this->render(''), $a);
        preg_match('/aria-controls="([^"]+)"/', $this->render(''), $b);

        $this->assertSame($a[1], $b[1]);
        $this->assertStringStartsWith('ds-sub-nav-', $a[1]);
    }

    #[Test]
    public function a_passed_id_names_the_section_and_its_parts(): void
    {
        $html = $this->render('id="rail-reports"');

        $this->assertMatchesRegularExpression('/<div x-data="[^"]*" id="rail-reports">/', $html);
        $this->assertStringContainsString('aria-controls="rail-reports-pages"', $this->button($html));
        $this->assertStringContainsString('id="rail-reports-button"', $this->button($html));
        $this->assertStringContainsString('id="rail-reports-pages"', $this->group($html));
    }
}
