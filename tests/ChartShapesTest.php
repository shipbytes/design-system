<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\ChartShapes;

/**
 * The funnel's rates, the radar's shapes and the treemap's squarified
 * layout, as exact values: Support\ChartShapes is a chart's geometry, so a
 * number that changes is a picture that changes.
 */
final class ChartShapesTest extends TestCase
{
    /** @param array<array{x: float, y: float, width: float, height: float}> $tiles */
    private function rounded(array $tiles): array
    {
        return array_map(fn ($t) => array_map(fn ($v) => round($v, 4), $t), $tiles);
    }

    #[Test]
    public function the_squarified_layout_is_the_papers_own_example(): void
    {
        // Bruls, Huizing and van Wijk's figure: 6, 6, 4, 3, 2, 2, 1 in a 6 × 4 box.
        $tiles = ChartShapes::squarify(['a' => 6, 'b' => 6, 'c' => 4, 'd' => 3, 'e' => 2, 'f' => 2, 'g' => 1], 6, 4);

        $this->assertSame([
            'a' => ['x' => 0.0, 'y' => 0.0, 'width' => 3.0, 'height' => 2.0],
            'b' => ['x' => 0.0, 'y' => 2.0, 'width' => 3.0, 'height' => 2.0],
            'c' => ['x' => 3.0, 'y' => 0.0, 'width' => 1.7143, 'height' => 2.3333],
            'd' => ['x' => 4.7143, 'y' => 0.0, 'width' => 1.2857, 'height' => 2.3333],
            'e' => ['x' => 3.0, 'y' => 2.3333, 'width' => 1.2, 'height' => 1.6667],
            'f' => ['x' => 4.2, 'y' => 2.3333, 'width' => 1.2, 'height' => 1.6667],
            'g' => ['x' => 5.4, 'y' => 2.3333, 'width' => 0.6, 'height' => 1.6667],
        ], $this->rounded($tiles));
    }

    #[Test]
    public function every_tile_has_the_area_its_value_earns(): void
    {
        $values = ['a' => 150, 'b' => 96, 'c' => 38, 'd' => 12, 'e' => 30, 'f' => 60, 'g' => 22, 'h' => 4];
        $tiles = ChartShapes::squarify($values, 512, 256);
        $perUnit = 512 * 256 / array_sum($values);

        foreach ($values as $key => $value) {
            $this->assertEqualsWithDelta($value * $perUnit, $tiles[$key]['width'] * $tiles[$key]['height'], 1e-6, "tile {$key}");
        }
    }

    #[Test]
    public function the_tiles_never_overlap_and_fill_the_box_exactly(): void
    {
        $tiles = array_values(ChartShapes::squarify([7, 1, 3, 3, 9, 0.5, 2, 2, 11, 4], 300, 180, 10, 20));

        $area = 0.0;
        foreach ($tiles as $i => $a) {
            $area += $a['width'] * $a['height'];
            $this->assertGreaterThanOrEqual(10 - 1e-9, $a['x']);
            $this->assertGreaterThanOrEqual(20 - 1e-9, $a['y']);
            $this->assertLessThanOrEqual(310 + 1e-9, $a['x'] + $a['width']);
            $this->assertLessThanOrEqual(200 + 1e-9, $a['y'] + $a['height']);
            foreach (array_slice($tiles, $i + 1) as $b) {
                $across = min($a['x'] + $a['width'], $b['x'] + $b['width']) - max($a['x'], $b['x']);
                $down = min($a['y'] + $a['height'], $b['y'] + $b['height']) - max($a['y'], $b['y']);
                $this->assertTrue($across <= 1e-9 || $down <= 1e-9, 'two tiles overlap');
            }
        }
        $this->assertEqualsWithDelta(300 * 180, $area, 1e-6);

        // The last tile ends on the box's corner exactly, not a rounding short of it.
        $right = max(array_map(fn ($t) => $t['x'] + $t['width'], $tiles));
        $bottom = max(array_map(fn ($t) => $t['y'] + $t['height'], $tiles));
        $this->assertSame([310.0, 200.0], [$right, $bottom]);
    }

    #[Test]
    public function equal_values_keep_the_order_they_were_given_in(): void
    {
        $tiles = ChartShapes::squarify(['north' => 5, 'south' => 5, 'east' => 5, 'west' => 5], 2, 2);

        $this->assertSame(['north', 'south', 'east', 'west'], array_keys($tiles));
        $this->assertSame([
            'north' => ['x' => 0.0, 'y' => 0.0, 'width' => 1.0, 'height' => 1.0],
            'south' => ['x' => 0.0, 'y' => 1.0, 'width' => 1.0, 'height' => 1.0],
            'east' => ['x' => 1.0, 'y' => 0.0, 'width' => 1.0, 'height' => 1.0],
            'west' => ['x' => 1.0, 'y' => 1.0, 'width' => 1.0, 'height' => 1.0],
        ], $this->rounded($tiles));
        // And the same input lays out the same way every time.
        $this->assertSame($tiles, ChartShapes::squarify(['north' => 5, 'south' => 5, 'east' => 5, 'west' => 5], 2, 2));
    }

    #[Test]
    public function zero_negative_and_non_numbers_are_dropped_from_the_layout_and_the_total(): void
    {
        $tiles = ChartShapes::squarify(['a' => 3, 'zero' => 0, 'b' => 1, 'minus' => -4, 'word' => 'x', 'gap' => null], 4, 1);

        $this->assertSame(['a', 'b'], array_keys($tiles));
        $this->assertSame(['x' => 0.0, 'y' => 0.0, 'width' => 3.0, 'height' => 1.0], $tiles['a']);
        $this->assertSame(['x' => 3.0, 'y' => 0.0, 'width' => 1.0, 'height' => 1.0], $tiles['b']);
        $this->assertSame([], ChartShapes::squarify(['a' => 0, 'b' => -1], 4, 1));
        $this->assertSame([], ChartShapes::squarify([], 4, 1));
    }

    #[Test]
    public function a_radar_scale_tops_out_on_a_round_number_per_ring(): void
    {
        $this->assertSame(100.0, ChartShapes::radarMax(95, 4));
        $this->assertSame(10.0, ChartShapes::radarMax(8.7, 4));
        $this->assertSame(10.0, ChartShapes::radarMax(10, 5));
        $this->assertSame(0.3, ChartShapes::radarMax(0.3, 3));
        $this->assertSame(4.0, ChartShapes::radarMax(0, 4));
    }

    #[Test]
    public function a_polygon_starts_at_twelve_and_runs_clockwise(): void
    {
        $this->assertSame('50,40 58.66,55 41.34,55', ChartShapes::polygon([10, 10, 10], 50, 50));
        $this->assertSame('50,40 70,50 50,55 50,50', ChartShapes::polygon([10, 20, 5, 0], 50, 50));
    }

    #[Test]
    public function an_axis_name_grows_away_from_the_centre(): void
    {
        $this->assertSame(['anchor' => 'middle', 'baseline' => 'auto'], ChartShapes::anchor(0));
        $this->assertSame(['anchor' => 'start', 'baseline' => 'central'], ChartShapes::anchor(90));
        $this->assertSame(['anchor' => 'start', 'baseline' => 'hanging'], ChartShapes::anchor(144));
        $this->assertSame(['anchor' => 'middle', 'baseline' => 'hanging'], ChartShapes::anchor(180));
        $this->assertSame(['anchor' => 'end', 'baseline' => 'central'], ChartShapes::anchor(270));
        $this->assertSame(['anchor' => 'end', 'baseline' => 'auto'], ChartShapes::anchor(300));
    }

    #[Test]
    public function each_series_has_a_marker_of_its_own(): void
    {
        $this->assertSame('M7 10a3 3 0 1 0 6 0a3 3 0 1 0 -6 0Z', ChartShapes::marker('circle', 10, 10, 6));
        $this->assertSame('M7 7h6v6h-6Z', ChartShapes::marker('square', 10, 10, 6));
        $this->assertSame('M10 6.25L13.75 13L6.25 13Z', ChartShapes::marker('triangle', 10, 10, 6));
        $this->assertSame('M10 5.95L14.05 10L10 14.05L5.95 10Z', ChartShapes::marker('diamond', 10, 10, 6));
        $this->assertCount(4, array_unique(ChartShapes::MARKERS));
        $this->assertCount(4, array_unique(ChartShapes::DASHES));
    }

    #[Test]
    public function a_funnel_rates_each_stage_against_the_one_before_and_the_first(): void
    {
        $rates = ChartShapes::rates([12400, 3100, 3500, 310, 0, 5]);

        $this->assertSame([null, '−75%', '+13%', '−91%', '−100%', null], array_column($rates, 'change'));
        $this->assertSame(1.0, $rates[0]['total']);
        $this->assertSame(0.025, $rates[3]['total']);
        // After a stage of nothing there is no step to take.
        $this->assertNull($rates[5]['step']);
        $this->assertSame([null, null], array_column(ChartShapes::rates([0, 4]), 'total'));
        $this->assertSame('±0%', ChartShapes::rates([5, 5])[1]['change']);
    }

    #[Test]
    public function a_rate_keeps_a_decimal_only_under_ten_percent(): void
    {
        $this->assertSame('2.5%', ChartShapes::percent(0.025));
        $this->assertSame('3%', ChartShapes::percent(0.03));
        $this->assertSame('38%', ChartShapes::percent(0.381));
        $this->assertSame('113%', ChartShapes::percent(1.129));
    }

    #[Test]
    public function a_funnel_fades_from_the_hue_to_its_lightest_step(): void
    {
        $this->assertSame([3, 2, 1], ChartShapes::ramp(3));
        $this->assertSame([3, 3, 2, 2, 1], ChartShapes::ramp(5));
        $this->assertSame([3], ChartShapes::ramp(1));
        $this->assertSame([], ChartShapes::ramp(0));
    }

    #[Test]
    public function a_funnel_is_read_as_first_to_last_and_its_largest_drop(): void
    {
        $stages = fn (array $v) => array_map(fn ($l, $n) => ['label' => $l, 'value' => (float) $n], array_keys($v), $v);

        $this->assertSame(
            'Signups: 4 stages, from Visits 12,400 to Paid 310 (2.5% of the first); the largest drop is Trial to Paid (−91%).',
            ChartShapes::funnel($stages(['Visits' => 12400, 'Sign-ups' => 3100, 'Trial' => 3500, 'Paid' => 310]), [], 'Signups'),
        );
        $this->assertSame(
            '2 stages, from A 4 to B 9 (225% of the first); no stage is smaller than the one before it.',
            ChartShapes::funnel($stages(['A' => 4, 'B' => 9])),
        );
        $this->assertSame('Signups: Visits 12.'.'', ChartShapes::funnel($stages(['Visits' => 12]), [], 'Signups'));
        $this->assertSame('No data.', ChartShapes::funnel([]));
    }
}
