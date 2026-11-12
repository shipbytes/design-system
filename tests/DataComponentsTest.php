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

    #[Test]
    public function a_split_bar_is_an_image_of_segments_whose_shares_add_to_a_hundred(): void
    {
        $html = Blade::render('<x-ds::split-bar label="Storage" :total="500" :table="true" :format="[\'suffix\' => \' GB\']" :data="[\'Photos\' => 150, \'Video\' => 96, \'Mail\' => 0.4, \'Empty\' => 0]" :interactive="false" />');

        $plot = $this->first($html, '//*[@role="img"]');
        $this->assertSame('Storage: 4 parts, 246.4 GB of 500 GB; the largest is Photos at 30%.', $plot->getAttribute('aria-label'));
        // A zero draws no segment; the rest of the track is one.
        $this->assertSame(4, $plot->childNodes->length - $this->xpath($html)->query('//*[@role="img"]/text()')->length);
        $this->assertStringContainsString('flex: 0 1 0.08%', $html);
        $rows = array_map(fn ($tr) => trim(preg_replace('/\s+/', ' ', $tr->textContent)), iterator_to_array($this->xpath($html)->query('//table//tbody/tr')));
        $this->assertSame(['Photos 150 GB 30%', 'Video 96 GB 19%', 'Mail 0.4 GB <1%', 'Empty 0 GB 0%', 'Remaining 253.6 GB 51%', 'Total 500 GB 100%'], $rows);
    }

    #[Test]
    public function a_split_bar_names_inline_what_fits_and_the_rest_below_by_container_query(): void
    {
        $html = Blade::render('<x-ds::split-bar legend="inline" :data="[\'Wide part\' => 90, \'Tiny\' => 10]" :interactive="false" />');

        $inline = $this->xpath($html)->query('//div[contains(@class, "text-meta")]/div/span');
        $this->assertSame(2, $inline->length);
        $fallback = $this->xpath($html)->query('//ul/li');
        $this->assertSame(2, $fallback->length);
        // Each part's two places are exact opposites, so it is named once.
        foreach ([0, 1] as $i) {
            $shown = preg_match('/@max-([a-z0-9]+):hidden/', $inline->item($i)->getAttribute('class'), $a);
            $this->assertSame(1, $shown);
            $this->assertStringContainsString('@'.$a[1].':hidden', $fallback->item($i)->getAttribute('class'));
        }
    }

    #[Test]
    public function a_split_bar_throws_on_a_negative_part(): void
    {
        $this->expectExceptionMessage('[Refunds] is -3');
        Blade::render('<x-ds::split-bar :data="[\'Sales\' => 5, \'Refunds\' => -3]" />');
    }

    #[Test]
    public function a_waffle_is_one_image_of_every_cell_with_one_mark_per_part(): void
    {
        $html = Blade::render('<x-ds::waffle-chart label="Seats" :data="[\'Free\' => 62, \'Pro\' => 30, \'Team\' => 8]" />');

        $plot = $this->first($html, '//*[@data-ds-chart-plot]');
        $this->assertSame('img', $plot->getAttribute('role'));
        $this->assertSame('Seats: 3 parts, total 100; the largest is Free at 62%.', $plot->getAttribute('aria-label'));
        $this->assertSame(100, $this->xpath($html)->query('//*[@data-ds-chart-plot]/span')->length);
        $this->assertSame(3, $this->xpath($html)->query('//*[@data-ds-chart-mark]')->length);
        $this->assertSame(29, $this->xpath($html)->query('//*[@data-ds-chart-piece="1"]')->length);
        // Nothing inside is announced: the cells carry no names of their own.
        $this->assertSame(3, $this->xpath($html)->query('//*[@data-ds-chart-plot]//*[@aria-label]')->length);
    }

    #[Test]
    public function a_waffle_of_one_number_is_a_share_of_a_hundred(): void
    {
        $plot = $this->first(Blade::render('<x-ds::waffle-chart label="Answered" :data="37" :interactive="false" />'), '//*[@role="img"]');

        $this->assertSame('Answered: 37 of 100 (37%).', $plot->getAttribute('aria-label'));
        $this->assertStringContainsString('repeat(10, max-content)', $plot->getAttribute('style'));
    }

    #[Test]
    public function activity_rings_say_every_ring_and_a_goal_passed_in_figures(): void
    {
        $html = Blade::render('<x-ds::activity-rings label="Today" :table="true" :rings="[[\'label\' => \'Move\', \'value\' => 420, \'max\' => 600, \'format\' => [\'suffix\' => \' kcal\']], [\'label\' => \'Stand\', \'value\' => 14, \'max\' => 12]]" />');

        $this->assertSame('Today: Move 420 kcal of 600 kcal (70%), Stand 14 of 12 (117%).', $this->first($html, '//svg')->getAttribute('aria-label'));
        // Past the goal is a full circle, no further.
        $this->assertSame(1, $this->xpath($html)->query('//circle[@stroke-linecap="round" and @stroke-dashoffset="0"]')->length);
        $this->assertSame('Stand: 14 of 12 (117%)', $this->xpath($html)->query('//*[@data-ds-chart-mark]')->item(1)->getAttribute('aria-label'));
        $this->assertSame('117%', trim($this->xpath($html)->query('//table//tbody/tr[2]/td[3]')->item(0)->textContent));
    }

    #[Test]
    public function activity_rings_stop_at_four(): void
    {
        $this->expectExceptionMessage('at most 4 rings, and was given 5');
        Blade::render('<x-ds::activity-rings :rings="[\'a\' => 1, \'b\' => 2, \'c\' => 3, \'d\' => 4, \'e\' => 5]" />');
    }

    #[Test]
    public function activity_rings_refuse_a_goal_of_nothing(): void
    {
        $this->expectExceptionMessage('[Move] has a max of 0');
        Blade::render('<x-ds::activity-rings :rings="[[\'label\' => \'Move\', \'value\' => 1, \'max\' => 0]]" />');
    }

    #[Test]
    public function a_slope_chart_writes_the_change_on_every_right_label_and_mutes_what_is_not_highlighted(): void
    {
        $html = Blade::render('<x-ds::slope-chart label="Price" fromLabel="2024" toLabel="2025" colourBy="direction" highlight="Oslo" :table="true" :data="[\'Oslo\' => [10, 22], \'Rome\' => [15, 11]]" />');

        $this->assertSame("Price: 2024 to 2025, 1 up and 1 down; the largest rise is Oslo (+12), the largest fall Rome (\u{2212}4).", $this->first($html, '//svg')->getAttribute('aria-label'));
        $right = $this->xpath($html)->query('//text[@x="100%" and @dx="10"]');
        $this->assertSame(['22+12Oslo', "11\u{2212}4Rome"], [trim($right->item(0)->textContent), trim($right->item(1)->textContent)]);
        $this->assertStringContainsString('text-success', $this->xpath($html)->query('//line[@x2="100%" and @stroke-linecap]')->item(1)->getAttribute('class'));
        $this->assertStringContainsString('text-fg-subtle', $this->xpath($html)->query('//line[@x2="100%" and @stroke-linecap]')->item(0)->getAttribute('class'));
        $this->assertSame('Rome, 2025: 11 (−4)', $this->xpath($html)->query('//*[@data-ds-chart-mark]')->item(3)->getAttribute('aria-label'));
        $this->assertSame(['Item', '2024', '2025', 'Change'], array_map(fn ($th) => $th->textContent, iterator_to_array($this->xpath($html)->query('//thead//th'))));
    }

    #[Test]
    public function a_slope_chart_needs_both_column_names(): void
    {
        $this->expectExceptionMessage('needs fromLabel and toLabel');
        Blade::render('<x-ds::slope-chart fromLabel="2024" :data="[\'a\' => [1, 2]]" />');
    }

    #[Test]
    public function a_slope_chart_by_series_stops_at_eight_items(): void
    {
        $this->expectExceptionMessage('Use colourBy="direction"');
        Blade::render('<x-ds::slope-chart fromLabel="a" toLabel="b" :data="array_fill_keys(range(1, 9), [1, 2])" />');
    }

    #[Test]
    public function a_funnel_writes_each_drop_between_stages_and_draws_a_stage_that_grows_as_it_is(): void
    {
        $html = Blade::render('<x-ds::funnel-chart label="Signups" :table="true" :data="[\'Visits\' => 1000, \'Trial\' => 400, \'Again\' => 500]" />');

        $this->assertSame('Signups: 3 stages, from Visits 1,000 to Again 500 (50% of the first); the largest drop is Visits to Trial (−60%).', $this->first($html, '//svg')->getAttribute('aria-label'));
        $drops = array_map(fn ($t) => $t->textContent, iterator_to_array($this->xpath($html)->query('//text[contains(@class, "font-medium") and not(tspan)]')));
        $this->assertSame(['−60%', '+25%'], $drops);
        // Scaled to the largest stage, not the first: the third bar is wider than the second.
        $widths = array_map(fn ($r) => (float) $r->getAttribute('width'), iterator_to_array($this->xpath($html)->query('//rect[@data-ds-chart-mark]')));
        $this->assertSame([100.0, 40.0, 50.0], $widths);
        $this->assertSame('Again: 500, 125% of Trial, 50% of Visits', $this->xpath($html)->query('//rect[@data-ds-chart-mark]')->item(2)->getAttribute('aria-label'));
        $this->assertSame(['Stage', 'Value', 'Of the stage before', 'Of the first'], array_map(fn ($th) => $th->textContent, iterator_to_array($this->xpath($html)->query('//thead//th'))));
    }

    #[Test]
    public function a_funnel_throws_on_a_negative_stage(): void
    {
        $this->expectExceptionMessage('[Trial] is -4');
        Blade::render('<x-ds::funnel-chart :data="[\'Visits\' => 10, \'Trial\' => -4]" />');
    }

    #[Test]
    public function a_radar_is_one_image_with_a_table_and_a_marker_and_dash_per_item(): void
    {
        $html = Blade::render('<x-ds::radar-chart label="Scores" :max="10" :axes="[\'Speed\', \'Price\', \'Support\']" :data="[\'Acme\' => [8, 6, 9], \'Globex\' => [\'Support\' => 6, \'Speed\' => 5, \'Price\' => 9]]" />');

        $this->assertSame('Scores. Acme: highest Support at 9, lowest Price at 6. Globex: highest Price at 9, lowest Speed at 5.', $this->first($html, '//svg')->getAttribute('aria-label'));
        $this->assertSame(['Axis', 'Acme', 'Globex'], array_map(fn ($th) => $th->textContent, iterator_to_array($this->xpath($html)->query('//thead//th'))));
        $this->assertSame('Price, Globex: 9', $this->xpath($html)->query('//*[@data-ds-chart-mark]')->item(4)->getAttribute('aria-label'));
        $this->assertNull($this->xpath($html)->query('//g[@data-ds-chart-series="0"]/polygon')->item(0)->getAttribute('stroke-dasharray') ?: null);
        $this->assertSame('6 4', $this->xpath($html)->query('//g[@data-ds-chart-series="1"]/polygon')->item(0)->getAttribute('stroke-dasharray'));
        // The rings are numbered once, up the first axis.
        $this->assertSame(['2.5', '5', '7.5', '10'], array_map(fn ($t) => $t->textContent, iterator_to_array($this->xpath($html)->query('//text[contains(@class, "stroke-surface")]'))));
    }

    #[Test]
    public function a_radar_refuses_two_axes_and_a_fifth_item(): void
    {
        try {
            Blade::render('<x-ds::radar-chart :axes="[\'a\', \'b\']" :data="[\'x\' => [1, 2]]" />');
            $this->fail('two axes drew');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('3 to 8 axes', $e->getMessage());
        }
        $this->expectExceptionMessage('at most 4 items');
        Blade::render('<x-ds::radar-chart :axes="[\'a\', \'b\', \'c\']" :data="array_fill_keys([\'p\', \'q\', \'r\', \'s\', \'t\'], [1, 2, 3])" />');
    }

    #[Test]
    public function a_treemap_lists_every_tile_and_the_parts_it_could_not_draw(): void
    {
        $html = Blade::render('<x-ds::treemap label="Spend" :data="[[\'label\' => \'Eng\', \'children\' => [\'Cloud\' => 30, \'Pens\' => 1, \'Refund\' => -5]], [\'label\' => \'Office\', \'value\' => 0]]" />');

        $this->assertSame('Spend: Eng 31 (100%); total 31. 2 tiles; the largest is Cloud in Eng at 97%. 2 of zero or less not drawn.', $this->first($html, '//svg')->getAttribute('aria-label'));
        $rows = array_map(fn ($tr) => array_map(fn ($c) => $c->textContent, iterator_to_array($tr->childNodes)), iterator_to_array($this->xpath($html)->query('//tbody/tr')));
        $rows = array_map(fn ($r) => array_values(array_filter(array_map('trim', $r), fn ($c) => $c !== '')), $rows);
        $this->assertSame([['Cloud', 'Eng', '30', '97%'], ['Pens', 'Eng', '1', '3%'], ['Refund', 'Eng', "\u{2212}5", 'not drawn'], ['Office', '—', '0', 'not drawn']], array_slice($rows, 0, 4));
        $this->assertSame(2, $this->xpath($html)->query('//*[@data-ds-chart-mark]')->length);
    }

    #[Test]
    public function a_treemap_nests_one_level_only(): void
    {
        $this->expectExceptionMessage('nests one level');
        Blade::render('<x-ds::treemap :data="[[\'label\' => \'a\', \'children\' => [[\'label\' => \'b\', \'children\' => [\'c\' => 1]]]]]" />');
    }

    #[Test]
    public function a_treemap_with_links_is_a_group_of_links_ringed_inside_their_tiles(): void
    {
        $html = Blade::render('<x-ds::treemap :interactive="false" :data="[[\'label\' => \'Home\', \'value\' => 3, \'href\' => \'/?a=1&b=2\'], [\'label\' => \'Blog\', \'value\' => 1]]" />');

        $this->assertSame('group', $this->first($html, '//svg')->getAttribute('role'));
        $link = $this->first($html, '//a');
        $this->assertSame('/?a=1&b=2', $link->getAttribute('href'));
        $this->assertSame('Home: 3 (75%)', $link->getAttribute('aria-label'));
        // Its ring is drawn inside the tile, transparent until the link has focus.
        $rings = $this->xpath($html)->query('//a//rect[@data-ds-ring]');
        $this->assertSame(2, $rings->length);
        $this->assertStringContainsString('group-focus-visible:stroke-focus-ring', $rings->item(1)->getAttribute('class'));
        $this->assertSame('img', $this->first($html, '//g[@aria-label="Blog: 1 (25%)"]')->getAttribute('role'));
    }
}
