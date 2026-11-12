<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Chart;

/**
 * The geometry every chart draws, as exact strings. A chart is static SVG
 * computed here, so a path that changes is a picture that changes: these
 * make that a decision rather than a side effect.
 */
final class ChartTest extends TestCase
{
    #[Test]
    public function coordinates_print_short_and_never_as_minus_zero(): void
    {
        $this->assertSame('3.33', Chart::n(10 / 3));
        $this->assertSame('20', Chart::n(20.0));
        $this->assertSame('0', Chart::n(-0.001));
        $this->assertSame('-1.5', Chart::n(-1.5));
    }

    #[Test]
    public function values_scale_into_the_box_with_y_pointing_down(): void
    {
        $this->assertSame([[0.0, 20.0], [50.0, 10.0], [100.0, 0.0]], Chart::points([0, 5, 10], 100, 20));
        $this->assertSame('M0 20L50 10L100 0', Chart::line(Chart::points([0, 5, 10], 100, 20)));
        $this->assertSame('M0 20L0 20L50 10L100 0L100 20Z', Chart::area(Chart::points([0, 5, 10], 100, 20), 20));
    }

    #[Test]
    public function a_gap_lifts_the_pen_and_a_lone_value_is_a_dot(): void
    {
        $points = Chart::points([1, null, 3, 4, null, 2], 50, 10);

        $this->assertNull($points[1]);
        $this->assertSame('M0 10h0M20 3.33L30 0M50 6.67h0', Chart::line($points));
        // A lone value has no area; only the run of two is filled.
        $this->assertSame('M20 10L20 3.33L30 0L30 10Z', Chart::area($points, 10));
        $this->assertSame('', Chart::line(Chart::points([], 50, 10)));
    }

    #[Test]
    public function one_value_and_equal_values_sit_on_the_middle_line(): void
    {
        $this->assertSame([[50.0, 10.0]], Chart::points([7], 100, 20));
        $this->assertSame([[0.0, 10.0], [50.0, 10.0], [100.0, 10.0]], Chart::points([3, 3, 3], 100, 20));
    }

    #[Test]
    public function overrides_set_the_domain_and_out_of_range_values_are_clamped(): void
    {
        $this->assertSame([[0.0, 10.0], [100.0, 0.0]], Chart::points([5, 50], 100, 20, min: 0, max: 10));
        $this->assertSame([0.0, 10.0], Chart::domain([2, 3], 0, 10));
        $this->assertSame([-4.0, 0.0], Chart::domain([-4, -1], zero: true));
        $this->assertSame([0.0, 0.0], Chart::domain([]));
    }

    #[Test]
    public function bars_stand_on_zero_and_hang_from_it_when_negative(): void
    {
        $this->assertSame([
            ['x' => 1.0, 'y' => 4.0, 'width' => 8.0, 'height' => 4.0],
            ['x' => 11.0, 'y' => 8.0, 'width' => 8.0, 'height' => 2.0],
            null,
            ['x' => 31.0, 'y' => 0.0, 'width' => 8.0, 'height' => 8.0],
        ], Chart::bars([2, -1, null, 4], 40, 10, gap: 2));

        $this->assertSame(8.0, Chart::baseline(10, -1, 4));
        $this->assertSame(10.0, Chart::baseline(10, 2, 5), 'all positive: zero is the floor of the box');
    }

    #[Test]
    public function arcs_run_clockwise_from_twelve_o_clock(): void
    {
        $this->assertSame('M10 50A40 40 0 0 1 90 50', Chart::arc(50, 50, 40, -90, 90));
        $this->assertSame('M10 50A40 40 0 0 1 50 10', Chart::arc(50, 50, 40, -90, 0));
        $this->assertSame('M50 10A40 40 0 1 1 10 50', Chart::arc(50, 50, 40, 0, 270), 'past half a turn takes the large arc');
        // A full turn is two halves: an arc whose ends meet draws nothing.
        $this->assertSame('M18 2A16 16 0 0 1 18 34A16 16 0 0 1 18 2', Chart::arc(18, 18, 16, 0, 360));
        $this->assertEqualsWithDelta(40 * M_PI, Chart::arcLength(40, 180), 1e-9);
    }

    #[Test]
    public function ticks_are_round_numbers_that_cover_the_range(): void
    {
        $this->assertSame([0.0, 20.0, 40.0, 60.0, 80.0, 100.0], Chart::ticks(0, 97));
        $this->assertSame([-5.0, 0.0, 5.0, 10.0], Chart::ticks(-3.2, 7.9));
        $this->assertSame([0.0, 0.05, 0.1], Chart::ticks(0.01, 0.07, 4));
        $this->assertSame([4.0], Chart::ticks(4, 4));
    }

    #[Test]
    public function numbers_format_with_grouping_and_trimmed_decimals(): void
    {
        $this->assertSame('1,234.5', Chart::format(1234.5));
        $this->assertSame('1,234', Chart::format(1234));
        $this->assertSame('1234567', Chart::format(1234567, ['grouping' => false]));
        $this->assertSame('$1,234.0', Chart::format(1234, ['decimals' => 1, 'prefix' => '$']));
        $this->assertSame('72%', Chart::format(72, ['suffix' => '%']));
        $this->assertSame('—', Chart::format(null));
        $this->assertSame('—', Chart::format('n/a'));
    }

    #[Test]
    public function rounding_is_half_away_from_zero_as_in_the_browser(): void
    {
        // ICU's default is half-even, which would print "2" where Intl prints "3".
        $this->assertSame('3', Chart::format(2.5, ['decimals' => 0]));
        $this->assertSame('−3', Chart::format(-2.5, ['decimals' => 0]));
    }

    #[Test]
    public function a_negative_takes_a_real_minus_before_the_prefix_and_zero_takes_none(): void
    {
        $this->assertSame('−$1,200', Chart::format(-1200, ['prefix' => '$']));
        $this->assertSame('0', Chart::format(-0.001));
    }

    #[Test]
    public function compact_rolls_over_to_the_next_unit_after_rounding(): void
    {
        $this->assertSame('1.2K', Chart::format(1234, ['compact' => true]));
        $this->assertSame('12.4K', Chart::format(12400, ['compact' => true]));
        $this->assertSame('1.5M', Chart::format(1500000, ['compact' => true]));
        $this->assertSame('999', Chart::format(999, ['compact' => true]));
        $this->assertSame('1M', Chart::format(999950, ['compact' => true]), 'not "1,000K"');
        $this->assertSame('−1.2K', Chart::format(-1200, ['compact' => true]));
    }

    #[Test]
    public function the_locale_sets_the_separators_where_intl_is_present(): void
    {
        if (! extension_loaded('intl')) {
            $this->assertSame('1,234,567.89', Chart::format(1234567.891, ['locale' => 'de']));
            $this->assertSame('en', Chart::formatOptions(['locale' => 'de'])['locale']);

            return;
        }

        $this->assertSame('1.234.567,89', Chart::format(1234567.891, ['locale' => 'de']));
        $this->assertSame('de-CH', Chart::formatOptions(['locale' => 'de_CH'])['locale']);
    }

    #[Test]
    public function the_options_handed_to_the_browser_are_intl_number_formats_own(): void
    {
        $this->assertSame([
            'locale' => 'en',
            'minimumFractionDigits' => 0,
            'maximumFractionDigits' => 1,
            'useGrouping' => true,
            'prefix' => '',
            'suffix' => '',
            'compact' => true,
        ], Chart::formatOptions(['compact' => true]));

        $this->assertSame(2, Chart::formatOptions(['decimals' => 2])['minimumFractionDigits']);
    }

    #[Test]
    public function the_summary_says_trend_latest_and_range(): void
    {
        $this->assertSame('Signups: up from 12 to 30 (low 8, high 30).', Chart::summary([12, 8, 30], [], 'Signups'));
        $this->assertSame('Down from 30 to 12 (low 8, high 30).', Chart::summary([30, 8, 12]));
        $this->assertSame('Level at 3 (low 3, high 9).', Chart::summary([3, 9, 3]));
        $this->assertSame('Flat at 7.', Chart::summary([7, 7]));
        $this->assertSame('X: 5.', Chart::summary([5], [], 'X'));
        $this->assertSame('No data.', Chart::summary([]));
        $this->assertSame('Load: no data.', Chart::summary([null, null], [], 'Load'));
        $this->assertSame('Up from $1.2K to $3K (low $1.2K, high $3K).', Chart::summary([1200, null, 3000], ['prefix' => '$', 'compact' => true]));
    }

    #[Test]
    public function the_trend_is_first_present_value_to_last(): void
    {
        $this->assertSame('up', Chart::trend([null, 1, 5, 2]));
        $this->assertSame('down', Chart::trend([4, null, 1]));
        $this->assertSame('level', Chart::trend([2, 9, 2]));
        $this->assertSame('none', Chart::trend([3]));
    }

    #[Test]
    public function a_series_slot_wins_over_a_tone_and_nonsense_falls_back(): void
    {
        $this->assertSame('text-accent', Chart::colour('accent'));
        $this->assertSame('text-series-3', Chart::colour('accent', 3));
        $this->assertSame('text-series-8', Chart::colour(null, '8'));
        $this->assertSame('text-fg-muted', Chart::colour('nonsense', 9));
        $this->assertSame('text-success', Chart::colour(null, 2.5, 'success'));
        $this->assertNull(Chart::series(0));
    }

    #[Test]
    public function the_data_table_is_a_real_table_and_escapes_what_it_is_given(): void
    {
        $html = view('ds::partials.chart-table', [
            'caption' => 'Signups <b>',
            'headers' => ['Day', 'Signups'],
            'rows' => [['Mon', '12'], ['Tue', '1,200']],
        ])->render();

        $this->assertStringContainsString('<caption>Signups &lt;b&gt;</caption>', $html);
        $this->assertSame(2, substr_count($html, '<th scope="row">'));
        $this->assertStringContainsString('<td>1,200</td>', $html);
    }

    #[Test]
    public function quantile_thresholds_give_each_level_a_share_of_the_busy_days(): void
    {
        $this->assertSame([1.0, 3.0, 5.0, 7.0], Chart::thresholds([0, 1, 2, 3, 4, 5, 6, 7, 8]));
        $this->assertSame([1.0, 10.0], Chart::thresholds([1, 10, 100], 3));
        // Equal counts merge thresholds rather than inventing levels.
        $this->assertSame([5.0], Chart::thresholds([5, 5, 5, 5]));
        $this->assertSame([], Chart::thresholds([0, 0, null]));
    }

    #[Test]
    public function given_thresholds_are_sorted_kept_positive_and_capped_at_four(): void
    {
        $this->assertSame([1.0, 4.0, 8.0, 12.0], Chart::thresholds([], [8, 1, 4, 'x', 0, 12, 20]));
    }

    #[Test]
    public function a_count_reaches_the_highest_threshold_it_meets_and_nothing_is_zero(): void
    {
        $t = [1.0, 3.0, 5.0, 7.0];

        $this->assertSame(0, Chart::level(0, $t));
        $this->assertSame(0, Chart::level(null, $t));
        $this->assertSame(1, Chart::level(2, $t));
        $this->assertSame(4, Chart::level(7, $t));
        $this->assertSame(4, Chart::level(100, $t));
        $this->assertSame(0, Chart::level(1, [2.0, 4.0]), 'under a given first threshold is level 0');
    }

    #[Test]
    public function fewer_levels_spread_across_the_five_steps_keeping_both_ends(): void
    {
        $this->assertSame([0, 1, 2, 3, 4], Chart::steps(5));
        $this->assertSame([0, 1, 3, 4], Chart::steps(4));
        $this->assertSame([0, 2, 4], Chart::steps(3));
        $this->assertSame([0, 4], Chart::steps(2));
        $this->assertSame([0, 1, 2, 3, 4], Chart::steps(9));
    }

    #[Test]
    public function a_smooth_line_passes_through_every_point_and_flattens_at_a_turn(): void
    {
        // A peak's tangent is level, so the curve tops out AT the point.
        $this->assertSame(
            'M0 10C3.33 5 6.67 0 10 0C13.33 0 16.67 5 20 5C23.33 5 26.67 5 30 5C33.33 5 36.67 7.5 40 10',
            Chart::line([[0, 10], [10, 0], [20, 5], [30, 5], [40, 10]], smooth: true),
        );
        $this->assertSame('M0 10L0 10C3.33 5 6.67 0 10 0C13.33 0 16.67 2.5 20 5L20 10Z', Chart::area([[0, 10], [10, 0], [20, 5]], 10, smooth: true));
        // Two points have no bend to make, and a gap still lifts the pen.
        $this->assertSame('M0 0L10 10', Chart::line([[0, 0], [10, 10]], smooth: true));
        $this->assertSame('M0 0h0M20 5L30 0', Chart::line([[0, 0], null, [20, 5], [30, 0]], smooth: true));
    }

    #[Test]
    public function a_smooth_line_never_overshoots_its_neighbours(): void
    {
        // A step: Catmull-Rom would dip below 0 and rise above 10 here.
        $path = Chart::line([[0, 10], [10, 10], [20, 0], [30, 0]], smooth: true);
        preg_match_all('/-?\d+(?:\.\d+)?/', $path, $numbers);
        $ys = array_map('floatval', array_values(array_filter($numbers[0], fn ($k) => $k % 2 === 1, ARRAY_FILTER_USE_KEY)));

        $this->assertGreaterThanOrEqual(0, min($ys));
        $this->assertLessThanOrEqual(10, max($ys));
    }

    #[Test]
    public function a_dataset_reads_every_shape_a_chart_takes(): void
    {
        $this->assertSame(
            ['labels' => ['Jan', 'Feb'], 'series' => ['Signups'], 'rows' => [[12.0, null]]],
            Chart::dataset(['Jan' => 12, 'Feb' => 'n/a'], 'Signups'),
        );
        $this->assertSame(
            ['labels' => ['Jan', 'Feb'], 'series' => ['Revenue', 'Costs'], 'rows' => [[12.0, null], [8.0, 3.0]]],
            Chart::dataset(['Jan' => ['Revenue' => 12, 'Costs' => 8], 'Feb' => ['Costs' => 3]]),
        );
        $this->assertSame(
            ['labels' => ['Jan', 'Feb'], 'series' => ['A', 'B'], 'rows' => [[12.0, 1.0], [8.0, null]]],
            Chart::dataset(['Jan' => [12, 8], 'Feb' => [1]], ['A', 'B']),
        );
        $this->assertSame(['1', '2', '3'], Chart::dataset([3, 4, 5])['labels']);
        $this->assertSame(['Series 1', 'Series 2'], Chart::dataset(['Q1' => [1, 2]])['series']);
    }

    #[Test]
    public function a_ninth_series_is_refused(): void
    {
        $this->expectExceptionMessage('at most 8 series');
        Chart::dataset(['Q1' => range(1, 9)]);
    }

    #[Test]
    public function the_value_axis_starts_and_ends_on_a_round_tick(): void
    {
        $this->assertSame(['min' => 0.0, 'max' => 100.0, 'ticks' => [0.0, 20.0, 40.0, 60.0, 80.0, 100.0]], Chart::scale([3, 97], zero: true));
        $this->assertSame(['min' => -10.0, 'max' => 30.0, 'ticks' => [-10.0, 0.0, 10.0, 20.0, 30.0]], Chart::scale([-4, 28], zero: true));
        $this->assertSame([0.0, 1.0], Chart::scale([0, 0], zero: true)['ticks'], 'all zero: an axis to stand on, not a division by nothing');
        $this->assertSame(['min' => 6.0, 'max' => 8.0, 'ticks' => [6.0, 6.5, 7.0, 7.5, 8.0]], Chart::scale([7, 7]));
        $this->assertSame(['min' => -3.0, 'max' => 12.0, 'ticks' => [0.0, 5.0, 10.0]], Chart::scale([-3, 12], true, [10, 0, 5]));
        $this->assertSame([0.0, 50.0, 100.0], Chart::scale([3, 97], true, 3)['ticks']);
    }

    #[Test]
    public function stacks_grow_up_from_zero_and_negatives_hang_below_it(): void
    {
        $this->assertSame([
            'segments' => [[[0.0, 1.0], [0.0, -2.0], null], [[1.0, 4.0], [-2.0, -3.0], [0.0, 4.0]]],
            'totals' => [4.0, 4.0, -3.0],
        ], Chart::stack([[1, -2, null], [3, -1, 4]]));
    }

    #[Test]
    public function percentages_are_whole_and_add_up_to_a_hundred(): void
    {
        $this->assertSame([34, 33, 33], Chart::percentages([1, 1, 1]));
        $this->assertSame([45, 30, 15, 10], Chart::percentages([1200, 800, 400, 250]));
        $this->assertSame(100, array_sum(Chart::percentages([7, 7, 7, 7, 7, 7])));
    }

    #[Test]
    public function text_is_estimated_and_cut_to_a_width_with_an_ellipsis(): void
    {
        $this->assertEqualsWithDelta(48.72, Chart::textWidth('Revenue'), 0.001);
        $this->assertSame('Northern Eur…', Chart::truncate('Northern Europe and Scandinavia', 80));
        $this->assertSame('Asia', Chart::truncate('Asia', 80));
    }

    #[Test]
    public function crowded_labels_drop_in_halves_and_the_first_always_stays(): void
    {
        // Twelve labels needing 30px each, beside a 40px gutter: all twelve
        // from 400px, every other one below it, and that fits anywhere.
        $this->assertSame([null, 400.0, null, 400.0, null, 400.0, null, 400.0, null, 400.0, null, 400.0], Chart::thinning(12, 30, 40, 12));
        // Thirty points on a line: the halvings step down to a quarter, then
        // an eighth, which is what the narrowest box keeps.
        $thirty = Chart::thinning(30, 40, 40, 29);
        $this->assertSame([null, 1200.0, 620.0, 1200.0, 330.0], array_slice($thirty, 0, 5));
        $this->assertNull($thirty[8]);
    }

    #[Test]
    public function a_chart_of_categories_is_read_as_its_highest_and_lowest(): void
    {
        $this->assertSame('Revenue: highest Europe at 30, lowest Asia at 8.', Chart::ranked([30, 8, 12], ['Europe', 'Asia', 'Americas'], [], 'Revenue'));
        $this->assertSame('All 3 at 7.', Chart::ranked([7, 7, 7], ['a', 'b', 'c']));
        $this->assertSame('Plans: Pro at $9.', Chart::ranked([null, 9], ['Free', 'Pro'], ['prefix' => '$'], 'Plans'));
        $this->assertSame('No data.', Chart::ranked([], []));
    }

    #[Test]
    public function units_are_shared_by_largest_remainder_and_always_add_up(): void
    {
        $this->assertSame([4, 3, 3], Chart::apportion([1, 1, 1], 10));
        $this->assertSame([33, 33, 34], Chart::percentages([1, 1, 1.0001]));
        $this->assertSame([0, 0], Chart::apportion([0, 0], 10));
        $this->assertSame(10, array_sum(Chart::apportion([0.3, 2.2, 7.5], 10)));
    }

    #[Test]
    public function a_waffle_counts_cells_against_its_whole_and_never_drops_a_part(): void
    {
        $this->assertSame(['parts' => [37], 'empty' => 63], Chart::cells([37], 100, 100.0));
        $this->assertSame(['parts' => [66, 33, 1], 'empty' => 0], Chart::cells([62, 30.5, 0.2], 100));
        // 0.2 of 100 rounds to no cell; it takes one from the largest.
        $this->assertSame(['parts' => [1, 9], 'empty' => 0], Chart::cells([0.2, 99.8], 10));
        $this->assertSame(['parts' => [56, 16], 'empty' => 28], Chart::cells([112, 31], 100, 200.0));
        $this->assertSame(['parts' => [0], 'empty' => 100], Chart::cells([0], 100, 100.0));
    }

    #[Test]
    public function rows_read_every_shape_a_chart_of_parts_takes(): void
    {
        $this->assertSame(
            [['label' => 'Free', 'value' => 70, 'tone' => null, 'series' => null, 'format' => null]],
            Chart::rows(['Free' => 70]),
        );
        $this->assertSame(
            [['label' => 'Pro', 'value' => 25, 'tone' => 'success', 'series' => 3, 'format' => null]],
            Chart::rows([['label' => 'Pro', 'value' => 25, 'tone' => 'success', 'series' => 3]]),
        );
        $this->assertSame(
            [['label' => 'Oslo', 'from' => 12, 'to' => 18, 'tone' => null, 'series' => null, 'format' => null]],
            Chart::rows(['Oslo' => [12, 18]], ['from', 'to']),
        );
        $this->assertSame('2', Chart::rows([5, 6])[1]['label']);
        $this->assertNull(Chart::rows(['Move' => ['value' => 4]], ['value', 'max'])[0]['max']);
    }

    #[Test]
    public function the_parts_of_a_whole_are_read_as_the_gist_or_every_part(): void
    {
        $parts = [['label' => 'Free', 'value' => 744.0], ['label' => 'Pro', 'value' => 360.0], ['label' => 'Team', 'value' => 96.0]];

        $this->assertSame('Plans: 3 parts, total 1,200; the largest is Free at 62%.', Chart::whole($parts, [62, 30, 8], [], 'Plans'));
        $this->assertSame('Plans: Free 744 (62%), Pro 360 (30%), Team 96 (8%); total 1,200.', Chart::whole($parts, [62, 30, 8], [], 'Plans', list: true));
        $this->assertSame('3 parts, 1,200 of 2,000; the largest is Free at 37%.', Chart::whole($parts, [37, 18, 5], [], null, false, 2000));
        $this->assertSame('Disk: Used 5 GB (<1%); 5 GB of 900 GB.', Chart::whole([['label' => 'Used', 'value' => 5.0]], ['<1'], ['suffix' => ' GB'], 'Disk', false, 900));
        $this->assertSame('No data.', Chart::whole([], []));
    }

    #[Test]
    public function stacked_labels_are_spread_apart_and_kept_in_the_box(): void
    {
        $this->assertSame([0 => 10.0, 1 => 26.0, 2 => 60.0, 3 => 44.0], Chart::spread([10, 12, 50, 49], 16, 0, 60));
        $this->assertSame(['a' => 5.0, 'b' => 40.0], Chart::spread(['a' => 5, 'b' => 40], 16, 0, 60));
        // Too many for the box: they overflow at the foot, never the head.
        $this->assertSame([0.0, 16.0, 32.0], Chart::spread([0, 1, 2], 16, 0, 20));
    }

    #[Test]
    public function rings_share_a_width_that_narrows_to_leave_a_hole(): void
    {
        $this->assertSame(['width' => 10.0, 'radii' => [44.0]], Chart::rings(1, 10));
        $four = Chart::rings(4, 13);
        $this->assertSame('6.75', Chart::n($four['width']));
        $this->assertSame(['45.63', '36.88', '28.13', '19.38'], array_map(fn ($r) => Chart::n($r), $four['radii']));
        // The innermost ring's inner edge is the hole asked for.
        $this->assertSame('16', Chart::n(end($four['radii']) - $four['width'] / 2));
    }

    #[Test]
    public function a_progress_ring_ends_exactly_at_its_share_caps_and_all(): void
    {
        // Half of a 251.33 circle, less a width for the two half-caps.
        $this->assertSame(['dash' => '115.66 251.33', 'offset' => '-5'], Chart::ring(40, 0.5, 10));
        $this->assertSame(['dash' => '251.33 251.33', 'offset' => '0'], Chart::ring(40, 1.2, 10));
        $this->assertSame(['dash' => '0.01 251.33', 'offset' => '-5'], Chart::ring(40, 0.01, 10));
        $this->assertNull(Chart::ring(40, 0, 10));
    }

    #[Test]
    public function a_change_is_always_signed(): void
    {
        $this->assertSame('+4', Chart::change(10, 14));
        $this->assertSame("\u{2212}\$4", Chart::change(14, 10, ['prefix' => '$']));
        $this->assertSame('±0', Chart::change(5, 5.001));
        $this->assertSame('+1.5%', Chart::change(2, 3.5, ['suffix' => '%']));
        $this->assertSame(['up', 'down', 'flat'], [Chart::direction(1, 2), Chart::direction(2, 1), Chart::direction(2, 2)]);
    }

    #[Test]
    public function a_before_and_after_is_read_as_its_directions_and_largest_moves(): void
    {
        $items = [
            ['label' => 'Oslo', 'from' => 10.0, 'to' => 22.0],
            ['label' => 'Rome', 'from' => 15.0, 'to' => 11.0],
            ['label' => 'Lima', 'from' => 9.0, 'to' => 12.0],
            ['label' => 'Kyiv', 'from' => 8.0, 'to' => 8.0],
        ];

        $this->assertSame(
            "Price: 2024 to 2025, 2 up, 1 down and 1 flat; the largest rise is Oslo (+12), the largest fall Rome (\u{2212}4).",
            Chart::slope($items, '2024', '2025', [], 'Price'),
        );
        $this->assertSame('Then to now, all 2 flat.', Chart::slope([['label' => 'a', 'from' => 1.0, 'to' => 1.0], ['label' => 'b', 'from' => 2.0, 'to' => 2.0]], 'Then', 'now'));
        $this->assertSame("Q1 to Q2, 1 up and 1 down; the largest rise is a (+1), the largest fall b (\u{2212}1).", Chart::slope([['label' => 'a', 'from' => 1.0, 'to' => 2.0], ['label' => 'b', 'from' => 2.0, 'to' => 1.0]], 'Q1', 'Q2'));
        $this->assertSame('Price: Oslo up from $10 to $22 (+$12), 2024 to 2025.', Chart::slope([$items[0]], '2024', '2025', ['prefix' => '$'], 'Price'));
        $this->assertSame('No data.', Chart::slope([], 'a', 'b'));
    }

    #[Test]
    public function the_stand_in_for_a_hidden_label_shows_exactly_when_it_hides(): void
    {
        $this->assertSame(['', 'hidden'], [Chart::below(null), Chart::above(null)]);
        $this->assertSame(['@max-xs:hidden', '@xs:hidden'], [Chart::below(300), Chart::above(300)]);
        $this->assertSame(['hidden', ''], [Chart::below(INF), Chart::above(INF)]);
    }
}
