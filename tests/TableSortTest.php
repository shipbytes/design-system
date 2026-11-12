<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

/**
 * Sortable headers on ds::table. The component never sorts anything: it says
 * which column the rows are sorted by, and makes each sortable header a link
 * or a Livewire button that asks for an order.
 */
final class TableSortTest extends TestCase
{
    private const COLUMNS = "[
        ['label' => 'Report', 'key' => 'name', 'sortable' => true],
        'Status',
        ['label' => 'Total', 'key' => 'total', 'align' => 'right', 'sortable' => true],
    ]";

    private function render(string $attributes, string $columns = self::COLUMNS): string
    {
        return Blade::render("<x-ds::table {$attributes} :columns=\"{$columns}\">\n</x-ds::table>");
    }

    /** @return array<int, array{aria: ?string, control: ?string, href: ?string, click: ?string, label: string}> */
    private function headers(string $html): array
    {
        preg_match_all('/<th\b([^>]*)>(.*?)<\/th>/s', $html, $cells, PREG_SET_ORDER);

        return array_map(function ($cell) {
            preg_match('/aria-sort="([^"]+)"/', $cell[1], $aria);
            preg_match('/^<(a|button)\b/', $cell[2], $control);
            preg_match('/href="([^"]+)"/', $cell[2], $href);
            preg_match('/wire:click="([^"]+)"/', $cell[2], $click);

            return [
                'aria' => $aria[1] ?? null,
                'control' => $control[1] ?? null,
                'href' => isset($href[1]) ? html_entity_decode($href[1]) : null,
                'click' => isset($click[1]) ? html_entity_decode($click[1], ENT_QUOTES) : null,
                'label' => trim(strip_tags($cell[2])),
            ];
        }, $cells);
    }

    #[Test]
    public function only_the_sorted_column_carries_aria_sort(): void
    {
        $h = $this->headers($this->render('sort="total" direction="desc"'));

        $this->assertSame([null, null, 'descending'], array_column($h, 'aria'));
        $this->assertSame(1, substr_count($this->render('sort="name"'), 'aria-sort='));
        $this->assertStringContainsString('aria-sort="ascending"', $this->render('sort="name"'));
    }

    #[Test]
    public function sortable_headers_are_links_and_the_rest_are_text(): void
    {
        $h = $this->headers($this->render('sort="name"'));

        $this->assertSame(['a', null, 'a'], array_column($h, 'control'));
        $this->assertSame(['Report', 'Status', 'Total'], array_column($h, 'label'));
    }

    #[Test]
    public function by_default_the_link_is_the_current_url_with_sort_and_direction_and_no_page(): void
    {
        $this->app->instance('request', Request::create('http://localhost/reports?q=churn&page=3'));

        $h = $this->headers($this->render('sort="total" direction="asc"'));

        $this->assertSame('http://localhost/reports?q=churn&sort=name&direction=asc', $h[0]['href']);
        // The sorted column asks for the other way round.
        $this->assertSame('http://localhost/reports?q=churn&sort=total&direction=desc', $h[2]['href']);
    }

    #[Test]
    public function sort_url_as_a_pattern(): void
    {
        $h = $this->headers($this->render('sort="name" direction="desc" sortUrl="/reports?order={key}&dir={direction}"'));

        $this->assertSame('/reports?order=name&dir=asc', $h[0]['href']);
        $this->assertSame('/reports?order=total&dir=asc', $h[2]['href']);
    }

    #[Test]
    public function sort_url_as_a_closure(): void
    {
        $html = Blade::render(
            '<x-ds::table sort="name" :sortUrl="fn ($key, $direction) => \'/r/\'.$key.\'/\'.$direction" :columns="[[\'label\' => \'Report\', \'key\' => \'name\', \'sortable\' => true]]"></x-ds::table>'
        );

        $this->assertSame('/r/name/desc', $this->headers($html)[0]['href']);
    }

    #[Test]
    public function sort_action_makes_a_livewire_button_instead(): void
    {
        $h = $this->headers($this->render("sort=\"total\" sortAction=\"sortBy('{key}', '{direction}')\""));

        $this->assertSame('button', $h[0]['control']);
        $this->assertSame("sortBy('name', 'asc')", $h[0]['click']);
        $this->assertSame("sortBy('total', 'desc')", $h[2]['click']);
        $this->assertNull($h[0]['href']);
        $this->assertStringContainsString('type="button"', $this->render("sortAction=\"sortBy('{key}')\""));
    }

    #[Test]
    public function the_key_can_be_the_entry_s_array_key(): void
    {
        $h = $this->headers($this->render('sort="total"', "['total' => ['label' => 'Total', 'sortable' => true]]"));

        $this->assertSame('descending', $this->headers($this->render('sort="total" direction="desc"', "['total' => ['label' => 'Total', 'sortable' => true]]"))[0]['aria']);
        $this->assertSame('ascending', $h[0]['aria']);
    }

    #[Test]
    public function a_sortable_column_with_no_key_is_refused_by_name(): void
    {
        try {
            $this->render('', "[['label' => 'Total', 'sortable' => true]]");
            $message = null;
        } catch (Throwable $e) {
            $message = $e->getMessage();
        }

        $this->assertNotNull($message, 'a sortable column with no key rendered');
        $this->assertStringContainsString('column "Total" is sortable but has no key', $message);
    }

    #[Test]
    public function the_icon_says_the_direction_and_an_unsorted_column_shows_it_only_on_hover_and_focus(): void
    {
        $html = $this->render('sort="total" direction="asc"');

        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $html, $cells);
        [$name, , $total] = $cells[1];

        $this->assertStringContainsString('opacity-0 group-hover/sort:opacity-100 group-focus-visible/sort:opacity-100', $name);
        $this->assertStringNotContainsString('opacity-0', $total);
        // Right-aligned: the arrow goes on the inside of the label.
        $this->assertStringContainsString('flex-row-reverse', $total);
        $this->assertStringNotContainsString('flex-row-reverse', $name);
    }

    #[Test]
    public function an_unknown_sort_key_marks_nothing_and_a_bad_direction_reads_as_ascending(): void
    {
        $this->assertStringNotContainsString('aria-sort', $this->render('sort="missing"'));
        $this->assertStringContainsString('aria-sort="ascending"', $this->render('sort="name" direction="sideways"'));
    }
}
