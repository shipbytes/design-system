<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Does each component render at all, and does it render markup rather than a
 * description of itself?
 *
 * Both failures here are silent in a browser: Blade emits an uncompiled
 * component tag as literal TEXT, and an error inside a view can come through as
 * text too. Neither throws, and neither looks like a failure in a log.
 */
final class RendersComponentsTest extends TestCase
{
    public static function specimens(): array
    {
        return array_map(fn ($blade) => [$blade], Specimens::all());
    }

    #[Test]
    #[DataProvider('specimens')]
    public function it_renders_markup(string $blade): void
    {
        $html = Blade::render($blade);

        $this->assertNotSame('', trim($html), 'rendered nothing at all');

        // The signature of the multi-line attribute-bag trap: Blade's tag parser
        // gives up and prints the tag. The page then shows the source of the
        // component where the component should be.
        $this->assertDoesNotMatchRegularExpression(
            '/<x-(ds::|dynamic-component)/',
            $html,
            'an uncompiled component tag came through as literal text',
        );

        // A rendered component is elements, not just words.
        $this->assertMatchesRegularExpression('/<[a-z]/i', $html, 'no HTML elements were emitted');
    }

    #[Test]
    public function every_built_component_appears_in_the_gallery_and_the_docs(): void
    {
        /*
         * Three lists have to agree, and nothing used to check that they did.
         *
         * Six components shipped with docs pages and screenshots and NO gallery
         * section, while docs/README said the gallery renders every component in
         * both themes. It was an oversight rather than a decision: one specimen
         * list was updated and the other was not, in silence.
         */
        $built = collect(glob(__DIR__.'/../resources/views/components/*.blade.php'))
            ->map(fn ($path) => basename($path, '.blade.php'));

        /*
         * `sheet` and `bottom-nav` are exempt from the GALLERY only, and the
         * reason is a media query rather than an oversight: both are
         * `lg:hidden`, and the gallery is a page a person opens on a desktop, so
         * at that viewport they render nothing at all. The documentation
         * screenshots do show them, because that script drives a 1000px
         * viewport — under `lg`, over `sm` — on purpose.
         *
         * The gallery's sheet section is hand-drawn markup for the same reason,
         * and is the one place in it that is not the real component.
         */
        $exempt = ['the gallery' => ['sheet', 'bottom-nav', 'bottom-nav-item']];

        $surfaces = [
            'the gallery' => __DIR__.'/../scripts/gallery.blade.php',
            'the docs screenshots' => __DIR__.'/../scripts/docs-specimens.blade.php',
        ];

        $missing = [];

        foreach ($surfaces as $where => $path) {
            $source = file_get_contents($path);

            foreach ($built as $component) {
                if (in_array($component, $exempt[$where] ?? [], true)) {
                    continue;
                }

                if (! preg_match('/<x-ds::'.preg_quote($component, '/').'[\s>\/]/', $source)) {
                    $missing[] = "{$component} appears in no specimen in {$where}";
                }
            }
        }

        $this->assertSame([], $missing, implode("\n", $missing));
    }

    #[Test]
    public function every_component_has_a_documentation_page(): void
    {
        // A component is not done until someone can read how to use it. The
        // page must also carry its screenshot, or the docs describe something
        // nobody can picture.
        $undocumented = [];

        foreach (glob(__DIR__.'/../resources/views/components/*.blade.php') as $path) {
            $component = basename($path, '.blade.php');
            $page = __DIR__."/../docs/components/{$component}.md";

            /*
             * Sub-components are covered by their parent's page, the same way
             * they are covered by their parent's spec. Written out rather than
             * derived from the name: `tab` belongs to `tabs` and `radio-group`
             * belongs to `radio`, neither of which any suffix rule gets right.
             * A new sub-component with no entry here fails, which is the point.
             */
            $parents = [
                'accordion-item' => 'accordion',
                'bottom-nav-item' => 'bottom-nav',
                'breadcrumb-item' => 'breadcrumb',
                'dropdown-item' => 'dropdown',
                'panel-row' => 'panel',
                'radio-group' => 'radio',
                'sheet-item' => 'sheet',
                'tab' => 'tabs',
                'tab-panel' => 'tabs',
                'table-cell' => 'table',
                'table-row' => 'table',
                'toast-region' => 'toast',
            ];

            if (isset($parents[$component])) {
                $this->assertFileExists(
                    __DIR__."/../docs/components/{$parents[$component]}.md",
                    "{$component} is covered by {$parents[$component]}, which has no docs page",
                );

                continue;
            }

            if (! is_file($page)) {
                $undocumented[] = "{$component}: no docs/components/{$component}.md";
                continue;
            }

            $body = file_get_contents($page);

            if (! str_contains($body, '../images/')) {
                $undocumented[] = "{$component}: its docs page has no screenshot";
            }

            if (! is_file(__DIR__."/../docs/images/{$component}.png") && ! str_contains($body, '../images/')) {
                $undocumented[] = "{$component}: docs/images/{$component}.png was never generated";
            }
        }

        $this->assertSame([], $undocumented, implode("\n", $undocumented));
    }

    #[Test]
    public function every_built_component_has_a_specimen(): void
    {
        $components = collect(glob(__DIR__.'/../resources/views/components/*.blade.php'))
            ->map(fn ($path) => basename($path, '.blade.php'))
            ->sort()
            ->values();

        $covered = implode(' ', Specimens::all());

        $missing = $components
            ->reject(fn ($name) => str_contains($covered, "x-ds::{$name} ")
                || str_contains($covered, "x-ds::{$name}>")
                || str_contains($covered, "x-ds::{$name}\n"))
            ->values()
            ->all();

        $this->assertSame(
            [],
            $missing,
            'components with no specimen, so nothing renders them: '.implode(', ', $missing),
        );
    }

    #[Test]
    public function no_specimen_passes_a_slot_no_component_renders(): void
    {
        /*
         * A named slot a component never outputs is DROPPED, in silence.
         *
         * This is not hypothetical: the table specimen was written against a
         * `<x-slot:head>` that does not exist — the real API is `:columns` — and
         * every other test passed, because markup still came out and every class
         * in it still had a rule. The header simply was not there.
         *
         * The same silence hides a typo'd slot name in a consumer's app.
         */
        $rendered = [];

        foreach (glob(__DIR__.'/../resources/views/components/*.blade.php') as $path) {
            $rendered[basename($path, '.blade.php')] = file_get_contents($path);
        }

        $unclaimed = [];

        foreach (Specimens::all() as $name => $blade) {
            preg_match_all('/<x-slot:([a-zA-Z][\w-]*)/', $blade, $slots);
            preg_match_all('/<x-ds::([a-z][\w-]*)/', $blade, $components);

            $available = implode(' ', array_map(
                fn ($component) => $rendered[$component] ?? '',
                array_unique($components[1]),
            ));

            foreach (array_unique($slots[1]) as $slot) {
                $variable = '$'.lcfirst(str_replace('-', '', ucwords($slot, '-')));

                if (! str_contains($available, $variable)) {
                    $unclaimed[] = "{$name}: <x-slot:{$slot}> — no component in it renders {$variable}";
                }
            }
        }

        $this->assertSame([], $unclaimed, implode("\n", $unclaimed));
    }

    #[Test]
    public function no_specimen_passes_a_slot_as_an_attribute(): void
    {
        /*
         * The mirror image of the test above, and just as silent.
         *
         * `nav-item` renders its icon from a SLOT — `$icon`. Written as an
         * attribute, `icon="home"`, Blade puts it in the attribute bag instead:
         * an anonymous component only turns DECLARED props into variables. So
         * `$icon` stays undefined, the @isset around it is false, and the row
         * renders with no icon and no complaint. The specimen, the docs and the
         * documentation screenshot were all wrong together.
         */
        $slotsOf = [];
        $propsOf = [];

        foreach (glob(__DIR__.'/../resources/views/components/*.blade.php') as $path) {
            $name = basename($path, '.blade.php');
            $source = file_get_contents($path);

            preg_match('/@props\(\[(.*?)^\]\)/ms', $source, $block);
            preg_match_all("/'([a-zA-Z][\w-]*)'\s*(?:=>|,)/", $block[1] ?? '', $declared);
            $propsOf[$name] = $declared[1];

            // Variables the template renders that are not props, minus Blade's
            // own. Those are the slots.
            preg_match_all('/\$([a-zA-Z][a-zA-Z0-9]*)/', $source, $used);
            $slotsOf[$name] = array_values(array_diff(
                array_unique($used[1]),
                $declared[1],
                ['attributes', 'slot', 'loop', 'errors', 'component', 'el', 'event', 'nextTick'],
            ));
        }

        $mistakes = [];

        foreach (Specimens::all() as $specimen => $blade) {
            preg_match_all('/<x-ds::([a-z][\w-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)/', $blade, $tags, PREG_SET_ORDER);

            foreach ($tags as [, $component, $attributes]) {
                foreach ($slotsOf[$component] ?? [] as $slot) {
                    if (in_array($slot, $propsOf[$component] ?? [], true)) {
                        continue;
                    }

                    if (preg_match('/(?<![:\w-])'.preg_quote($slot, '/').'\s*=/', $attributes)) {
                        $mistakes[] = "{$specimen}: <x-ds::{$component} {$slot}=\"…\"> — "
                            ."{$slot} is a SLOT, so the attribute is ignored and nothing renders";
                    }
                }
            }
        }

        $this->assertSame([], $mistakes, implode("\n", $mistakes));
    }

    #[Test]
    public function the_icon_component_resolves_a_v1_name_through_the_alias_map(): void
    {
        // A renamed v2 icon resolves to no component and renders NOTHING. The
        // alias map is the only thing between an old name and a blank space.
        $aliased = Blade::render('<x-ds::icon name="x" />');
        $direct = Blade::render('<x-ds::icon name="x-mark" />');

        $this->assertStringContainsString('<svg', $aliased, 'the v1 alias rendered no icon');
        $this->assertSame(
            $this->pathsOf($direct),
            $this->pathsOf($aliased),
            'the alias resolved to a different icon than the v2 name it maps to',
        );
    }

    #[Test]
    public function a_decorative_icon_is_hidden_and_a_labelled_one_is_not(): void
    {
        $decorative = $this->element(Blade::render('<x-ds::icon name="check" />'), '//svg');
        $labelled = $this->element(Blade::render('<x-ds::icon name="check" label="Done" />'), '//svg');

        $this->assertSame('true', $decorative->getAttribute('aria-hidden'));

        /*
         * Asserted on the PARSED element, not on the raw string, because the raw
         * string legitimately carries aria-hidden TWICE: blade-heroicons stamps
         * its own on every icon it renders, and ours is merged in ahead of it.
         * Duplicate attributes resolve to the FIRST occurrence, so a browser and
         * an assistive technology both see ours — which is the only reading that
         * matters, and the only one a string search gets wrong.
         *
         * If theirs ever wins, a screen reader skips the element entirely and
         * the accessible name is never announced, while the icon still looks
         * perfect.
         */
        $this->assertSame(
            'false',
            $labelled->getAttribute('aria-hidden'),
            'a labelled icon resolves to aria-hidden=true, so its name is never announced',
        );
        $this->assertSame('img', $labelled->getAttribute('role'));
        $this->assertSame('Done', $labelled->getAttribute('aria-label'));
    }

    #[Test]
    public function the_modal_names_itself_from_its_visible_title(): void
    {
        $html = Blade::render('<x-ds::modal open="x" title="Delete report?">Body</x-ds::modal>');

        $dialog = $this->element($html, '//*[@role="dialog"]');

        $labelledBy = $dialog->getAttribute('aria-labelledby');
        $this->assertNotSame('', $labelledBy, 'the dialog has no aria-labelledby');

        // Scoped to the dialog ELEMENT: the close button legitimately carries an
        // aria-label of its own, so searching the whole string for "aria-label="
        // finds it and reports a problem that is not there.
        $this->assertSame(
            '',
            $dialog->getAttribute('aria-label'),
            'the dialog uses aria-label, which is a second copy of the title that drifts',
        );

        $target = $this->element($html, '//*[@id="'.$labelledBy.'"]');
        $this->assertSame(
            'Delete report?',
            trim($target->textContent),
            'aria-labelledby resolves to something other than the visible title',
        );
    }

    #[Test]
    public function a_disabled_dropdown_item_is_not_a_link(): void
    {
        // A disabled <a> is still focusable and still followable by keyboard.
        $html = Blade::render(
            '<x-ds::dropdown><x-slot:trigger><button>t</button></x-slot:trigger>'
            .'<x-ds::dropdown-item href="/gone" :disabled="true">Archive</x-ds::dropdown-item>'
            .'</x-ds::dropdown>',
        );

        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringNotContainsString('href="/gone"', $html, 'a disabled item still navigates');
    }

    #[Test]
    public function the_alert_interrupts_only_for_danger(): void
    {
        // assertive interrupts whatever the screen reader is saying: right for a
        // failure, rude for a confirmation.
        $this->assertStringContainsString('role="alert"', Blade::render('<x-ds::alert tone="danger">x</x-ds::alert>'));

        foreach (['accent', 'success', 'warning'] as $tone) {
            $this->assertStringContainsString(
                'role="status"',
                Blade::render("<x-ds::alert tone=\"{$tone}\">x</x-ds::alert>"),
                "the {$tone} alert interrupts, which is only right for a failure",
            );
        }
    }

    /**
     * The first element matching an XPath, parsed as a browser would parse it.
     *
     * Assertions about attributes belong here rather than on the raw string: the
     * markup carries Alpine directives, duplicated attributes and whitespace
     * that a string search reads differently from every real consumer of it.
     */
    private function element(string $html, string $xpath): \DOMElement
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.$html);
        libxml_clear_errors();

        $found = (new \DOMXPath($document))->query($xpath);
        $this->assertNotFalse($found, "invalid xpath: {$xpath}");
        $this->assertGreaterThan(0, $found->length, "nothing matched {$xpath}");

        return $found->item(0);
    }

    /** The `d` attributes of every path, so two icons can be compared by shape. */
    private function pathsOf(string $svg): array
    {
        preg_match_all('/\sd="([^"]+)"/', $svg, $m);
        sort($m[1]);

        return $m[1];
    }
}
