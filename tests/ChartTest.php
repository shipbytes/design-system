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
}
