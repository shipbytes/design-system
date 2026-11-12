<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * What counter, sparkline, gauge, activity-heatmap, the three charts and the
 * timeline say to a screen reader,
 * on the parsed element. The geometry under them is Support\Chart's, tested
 * with exact strings in ChartTest; this is the contract each view builds on it.
 */
final class DataComponentsTest extends TestCase
{
    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.$html);
        libxml_clear_errors();

        return new \DOMXPath($document);
    }

    private function first(string $html, string $query): \DOMElement
    {
        $node = $this->xpath($html)->query($query)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $node, "nothing matches {$query}");

        return $node;
    }

    #[Test]
    public function a_counter_is_its_formatted_number_from_php_read_once(): void
    {
        $html = Blade::render('<x-ds::counter :value="-1200" :format="[\'prefix\' => \'$\']" />');

        $this->assertSame("\u{2212}\$1,200", trim($this->first($html, '//*[@data-ds-counter-text]')->textContent));
        $this->assertSame('true', $this->first($html, '//*[@data-ds-counter-face]')->getAttribute('aria-hidden'));
        $this->assertStringContainsString('x-ds-roll', $html);
        $this->assertStringNotContainsString('dsRoll', $html);
    }

    #[Test]
    public function a_counter_bound_to_alpine_goes_through_ds_roll_and_drops_the_attribute(): void
    {
        $root = $this->first(Blade::render('<x-ds::counter ::value="count" />'), '//*[@data-ds-counter]');

        $this->assertStringContainsString('dsRoll', $root->getAttribute('x-data'));
        $this->assertStringContainsString('(count)', $root->getAttribute('x-data'));
        $this->assertFalse($root->hasAttribute(':value'));
    }

    #[Test]
    public function a_sparkline_is_an_image_named_by_its_summary(): void
    {
        $svg = $this->first(Blade::render('<x-ds::sparkline :values="[12, 8, 30]" label="Signups" />'), '//svg');

        $this->assertSame('img', $svg->getAttribute('role'));
        $this->assertSame('Signups: up from 12 to 30 (low 8, high 30).', $svg->getAttribute('aria-label'));
        $this->assertSame('none', $svg->getAttribute('preserveaspectratio'), 'the HTML parser lowercases it');
    }

    #[Test]
    public function a_sparkline_table_is_a_div_with_every_value(): void
    {
        $html = Blade::render('<x-ds::sparkline :values="[\'Mon\' => 3, \'Tue\' => null]" label="Visits" :table="true" />');

        $this->assertSame('div', $this->first($html, '/html/body/*[1]')->tagName);
        $this->assertSame(2, $this->xpath($html)->query('//table//tbody/tr')->length);
        $this->assertSame('—', trim($this->first($html, '//table//tbody/tr[2]/td')->textContent));
    }

    #[Test]
    public function a_gauge_says_its_band_and_a_reading_past_the_range_in_words(): void
    {
        $bands = "[['at' => 0, 'tone' => 'success'], ['at' => 90, 'tone' => 'danger']]";
        $meter = $this->first(Blade::render('<x-ds::gauge label="Disk" :value="120" :format="[\'suffix\' => \'%\']" :thresholds="'.$bands.'" />'), '//*[@role="meter"]');

        $this->assertSame('Disk', $meter->getAttribute('aria-label'));
        $this->assertSame('100', $meter->getAttribute('aria-valuenow'));
        $this->assertSame('120%, above the maximum of 100%, Critical', $meter->getAttribute('aria-valuetext'));
    }

    #[Test]
    public function a_gauge_with_no_reading_says_so(): void
    {
        $meter = $this->first(Blade::render('<x-ds::gauge label="Latency" />'), '//*[@role="meter"]');

        $this->assertFalse($meter->hasAttribute('aria-valuenow'));
        $this->assertSame('No reading', $meter->getAttribute('aria-valuetext'));
    }

    #[Test]
    public function a_gauge_without_a_name_throws(): void
    {
        $this->expectExceptionMessage('ds::gauge needs a label');
        Blade::render('<x-ds::gauge :value="3" />');
    }

    #[Test]
    public function a_heatmap_without_a_label_throws(): void
    {
        $this->expectExceptionMessage('ds::activity-heatmap needs a label');
        Blade::render('<x-ds::activity-heatmap :data="[]" />');
    }

    #[Test]
    public function a_heatmap_is_weeks_of_days_each_saying_its_count(): void
    {
        $html = Blade::render('<x-ds::activity-heatmap label="Deploys" unit="deploy" from="2026-03-02" to="2026-03-15" :data="[\'2026-03-03\' => 2, \'2026-03-14\' => 1]" />');
        $x = $this->xpath($html);

        $this->assertSame(2, $x->query('//tbody/tr')->length, 'one row per week');
        $this->assertSame('2 deploys on 3 March 2026', trim($this->first($html, '//td[contains(., "3 March 2026")]')->getAttribute('title')));
        $this->assertSame('1 deploy on 14 March 2026', trim($this->first($html, '//td[contains(., "14 March 2026")]')->textContent));
        $this->assertStringStartsWith('Deploys. 3 deploys from 2 March 2026 to 15 March 2026; the busiest day was 3 March 2026, with 2.', trim($this->first($html, '//caption')->textContent));
        $this->assertSame('true', $this->first($html, '//*[contains(., "Less")][contains(@class, "justify-end")]')->getAttribute('aria-hidden'));
    }

    #[Test]
    public function a_heatmap_links_only_the_days_with_a_count(): void
    {
        $html = Blade::render('<x-ds::activity-heatmap label="Commits" from="2026-03-02" to="2026-03-08" :data="[\'2026-03-04\' => 5]" href="/day/{date}" />');
        $links = $this->xpath($html)->query('//a');

        $this->assertSame(1, $links->length);
        $this->assertSame('/day/2026-03-04', $links->item(0)->getAttribute('href'));
    }

    #[Test]
    public function a_bar_chart_is_an_image_named_by_its_ranking_and_enhanced_only_when_interactive(): void
    {
        $html = Blade::render('<x-ds::bar-chart label="Revenue" :data="[\'Jan\' => 12, \'Feb\' => 18, \'Mar\' => 9]" />');
        $svg = $this->first($html, '//svg[@data-ds-chart-plot]');

        $this->assertSame('img', $svg->getAttribute('role'));
        $this->assertSame('Revenue: highest Feb at 18, lowest Mar at 9.', $svg->getAttribute('aria-label'));
        $this->assertSame('Feb: 18', $this->first($html, '//rect[@data-ds-at="1"]')->getAttribute('aria-label'));
        $this->assertTrue($this->first($html, '//*[@x-ds-chart]')->hasAttribute('x-data'));
        $this->assertSame('true', $this->first($html, '//*[@data-ds-chart-readout]')->getAttribute('aria-hidden'));

        $static = Blade::render('<x-ds::bar-chart :data="[1, 2]" :interactive="false" />');
        $this->assertStringNotContainsString('x-ds-chart', $static);
        $this->assertStringNotContainsString('data-ds-chart-readout', $static);
        $this->assertStringNotContainsString('tabindex', $static);
    }

    #[Test]
    public function a_chart_with_several_series_has_a_legend_and_a_table_with_a_column_each(): void
    {
        $html = Blade::render('<x-ds::bar-chart label="Money" :table="true" :data="[\'Jan\' => [\'Revenue\' => 12, \'Costs\' => 8]]" />');
        $headers = array_map(fn ($th) => trim($th->textContent), iterator_to_array($this->xpath($html)->query('//table//th[@scope="col"]')));

        $this->assertSame(['Category', 'Revenue', 'Costs'], $headers);
        $this->assertSame('Money', trim($this->first($html, '//table/caption')->textContent));
        $this->assertSame(2, $this->xpath($html)->query('//ul[@role="list"]/li')->length);
        $this->assertTrue($this->first($html, '//*[@x-ds-chart]')->hasAttribute('data-ds-chart-toggles'));
    }

    #[Test]
    public function a_chart_refuses_a_ninth_series(): void
    {
        $this->expectExceptionMessage('at most 8 series');
        Blade::render('<x-ds::line-chart :data="[\'a\' => [1, 2, 3, 4, 5, 6, 7, 8, 9]]" />');
    }

    #[Test]
    public function an_empty_chart_is_the_empty_state(): void
    {
        $html = Blade::render('<x-ds::line-chart :data="[\'Mon\' => null]" empty="No visits yet" />');

        $this->assertStringContainsString('No visits yet', $html);
        $this->assertStringNotContainsString('<svg data-ds-chart-plot', $html);
        $this->assertStringNotContainsString('x-ds-chart', $html);
    }

    #[Test]
    public function a_line_chart_breaks_at_a_gap_and_names_its_lines_at_their_ends(): void
    {
        $html = Blade::render('<x-ds::line-chart label="Visits" :data="[\'Mon\' => [3, 1], \'Tue\' => [null, 2], \'Wed\' => [6, 3]]" :series="[\'New\', \'Returning\']" />');
        $paths = $this->xpath($html)->query('//g[@data-ds-chart-series="0"]//path[@fill="none"]');

        $this->assertSame(2, substr_count($paths->item(0)->getAttribute('d'), 'M'));
        $this->assertSame(['New', 'Returning'], array_map(fn ($t) => trim($t->textContent), iterator_to_array($this->xpath($html)->query('//text[@data-ds-chart-series]'))));
        $this->assertStringStartsWith('Visits. New: up from 3 to 6', $this->first($html, '//svg[@data-ds-chart-plot]')->getAttribute('aria-label'));
    }

    #[Test]
    public function a_donut_folds_the_tail_into_other_and_its_shares_add_to_a_hundred(): void
    {
        $html = Blade::render('<x-ds::donut-chart label="Browsers" :maxSegments="3" :table="true" :data="[\'Chrome\' => 1, \'Safari\' => 1, \'Firefox\' => 1, \'Edge\' => 1]" />');
        $cells = array_map(fn ($td) => trim($td->textContent), iterator_to_array($this->xpath($html)->query('//table/tbody/tr/td[2]')));

        $this->assertSame(['25%', '25%', '50%', '100%'], $cells);
        $this->assertSame('Other: 2 (50%)', $this->xpath($html)->query('//circle[@data-ds-chart-mark]')->item(2)->getAttribute('aria-label'));
        $this->assertSame('Browsers: 3 parts, total 4; the largest is Other at 50%.', $this->first($html, '//svg[@data-ds-chart-plot]')->getAttribute('aria-label'));
    }

    #[Test]
    public function a_donut_throws_on_a_part_of_nothing(): void
    {
        $this->expectExceptionMessage('[Search] is 0');
        Blade::render('<x-ds::donut-chart :data="[\'Direct\' => 5, \'Search\' => 0]" />');
    }

    #[Test]
    public function a_timeline_is_an_ordered_list_with_time_elements_and_hidden_markers(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ds::timeline label="History">
                <x-ds::timeline-group label="Today" :level="4">
                    <x-ds::timeline-item title="Shipped" time="2026-11-12T10:42:00Z" icon="truck" tone="success" />
                    <x-ds::timeline-item title="Noted" time="Yesterday" datetime="2026-11-11" avatar="Ada Lovelace" />
                    <x-ds::timeline-item title="Placed" time="10:00" />
                </x-ds::timeline-group>
            </x-ds::timeline>
            BLADE);

        $this->assertSame('History', $this->first($html, '/html/body/ol')->getAttribute('aria-label'));
        $this->assertSame('Today', trim($this->first($html, '//ol/li/h4')->textContent));
        $this->assertSame(3, $this->xpath($html)->query('//ol/li/ol/li')->length);
        $this->assertSame('2026-11-12T10:42:00+00:00', $this->first($html, '//time')->getAttribute('datetime'));
        $this->assertSame('2026-11-11', $this->xpath($html)->query('//time')->item(1)->getAttribute('datetime'));
        $this->assertSame(2, $this->xpath($html)->query('//time')->length);
        foreach ($this->xpath($html)->query('//ol/li/ol/li/span[1] | //ol/li/ol/li/span[2]') as $marker) {
            $this->assertSame('true', $marker->getAttribute('aria-hidden'));
        }
    }
}
