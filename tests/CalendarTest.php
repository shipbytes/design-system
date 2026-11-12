<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Calendar;

/**
 * The calendar arithmetic on the server. Every case is read from
 * tests/Fixtures/dates.json, which test-js.mjs holds shared/dates.js to as
 * well, so the month PHP draws and the month dsCalendar redraws cannot drift
 * apart: month and year boundaries and leap days on both sides.
 */
final class CalendarTest extends TestCase
{
    private function fixture(string $key): array
    {
        return json_decode(file_get_contents(__DIR__.'/Fixtures/dates.json'), true, flags: JSON_THROW_ON_ERROR)[$key];
    }

    #[Test]
    public function days_and_months_add_across_every_boundary(): void
    {
        foreach ($this->fixture('addDays') as [$day, $by, $want]) {
            $this->assertSame($want, Calendar::addDays($day, $by), "{$day} {$by} days");
        }
        foreach ($this->fixture('addMonths') as [$day, $by, $want]) {
            $this->assertSame($want, Calendar::addMonths($day, $by), "{$day} {$by} months");
        }
    }

    #[Test]
    public function a_month_shows_the_weeks_it_spans_from_any_first_weekday(): void
    {
        foreach ($this->fixture('weeks') as [$year, $month, $start, $first, $last, $rows]) {
            $weeks = Calendar::weeks($year, $month, $start);
            $this->assertSame([$first, $last, $rows], [$weeks[0][0], end($weeks)[6], count($weeks)], "{$year}-{$month} from {$start}");
        }
        foreach ($this->fixture('isoWeek') as [$from, $start, $want]) {
            $row = array_map(fn ($i) => Calendar::addDays($from, $i), range(0, 6));
            $this->assertSame($want, Calendar::isoWeek($row), "the week from {$from}");
        }
    }

    #[Test]
    public function the_built_in_ranges_count_from_the_hosts_today(): void
    {
        foreach ($this->fixture('presets') as [$today, $want]) {
            $this->assertSame($want, Calendar::presets($today), "presets on {$today}");
        }
    }

    #[Test]
    public function a_hosts_presets_are_read_low_to_high_and_bad_ones_dropped(): void
    {
        $list = Calendar::presetList(['Q3' => ['2026-09-30', new \DateTimeImmutable('2026-07-01')], 'Broken' => ['nope', '2026-01-01'], 'Single' => '2026-01-01'], '2026-09-14');

        $this->assertSame([['key' => null, 'label' => 'Q3', 'from' => '2026-07-01', 'to' => '2026-09-30']], $list);
        $this->assertSame(['today', 'yesterday', 'last7', 'last30', 'thisMonth', 'lastMonth', 'thisYear'], array_column(Calendar::presetList(true, '2026-09-14'), 'key'));
    }

    #[Test]
    public function the_week_starts_where_the_locale_says_unless_told(): void
    {
        $this->assertSame(0, Calendar::weekStart(null, 'en_US'));
        $this->assertSame(1, Calendar::weekStart(null, 'de'));
        $this->assertSame(6, Calendar::weekStart(null, 'ar'));
        $this->assertSame(1, Calendar::weekStart(null, 'he'), 'a locale with no answer starts on Monday');
        $this->assertSame(0, Calendar::weekStart('0', 'de'));
    }

    #[Test]
    public function markers_are_a_tone_or_a_positive_count_and_nothing_else(): void
    {
        $this->assertSame(
            ['2026-09-01' => 'success', '2026-09-02' => 3],
            Calendar::events(['2026-09-01' => 'success', '2026-09-02' => '3', '2026-09-03' => 'purple', '2026-09-04' => 0, 'soon' => 'accent']),
        );
    }

    #[Test]
    public function the_calendar_renders_its_state_before_any_script(): void
    {
        $html = Blade::render('<x-ds::calendar name="day" value="2026-09-10" today="2026-09-14" min="2026-09-05" :disabledDays="[0]" :events="[\'2026-09-03\' => 2]" />');

        $this->assertMatchesRegularExpression('/data-day="2026-09-10"[^>]*tabindex="0"[^>]*data-selected/s', $html);
        $this->assertMatchesRegularExpression('/data-day="2026-09-14"[^>]*data-today aria-current="date"/s', $html);
        $this->assertMatchesRegularExpression('/data-day="2026-09-04"[^>]*aria-disabled="true"/s', $html, 'before min');
        $this->assertMatchesRegularExpression('/data-day="2026-09-13"[^>]*aria-disabled="true"/s', $html, 'a Sunday');
        $this->assertStringContainsString('aria-label="Thursday 3 September 2026, 2 events"', $html);
        $this->assertMatchesRegularExpression('/type="date"\s+name="day"\s+value="2026-09-10"/', $html, 'posts without the script');
        $this->assertSame(6 * 7, substr_count($html, 'data-ds-cal-day'), 'six rows a month, the room any month needs');
    }

    #[Test]
    public function each_mode_posts_under_its_own_names(): void
    {
        $range = Blade::render('<x-ds::calendar name="stay" :range="true" :value="[\'2026-09-20\', \'2026-09-08\']" />');
        $this->assertMatchesRegularExpression('/name="stay_start"\s+value="2026-09-08"/', $range);
        $this->assertMatchesRegularExpression('/name="stay_end"\s+value="2026-09-20"/', $range);

        $many = Blade::render('<x-ds::calendar name="days" :multiple="true" :value="[\'2026-09-15\', \'2026-09-01\']" />');
        $this->assertSame(3, substr_count($many, 'name="days[]"'), 'one a day, and one empty to add a day with');
    }

    #[Test]
    public function the_picker_marks_the_preset_it_equals(): void
    {
        $html = Blade::render('<x-ds::date-picker name="period" :range="true" :presets="true" today="2026-09-14" :value="[\'2026-08-01\', \'2026-08-31\']" />');

        $this->assertMatchesRegularExpression('/data-ds-cal-preset="lastMonth"\s+aria-pressed="true"/', $html);
        $this->assertSame(1, substr_count($html, 'aria-pressed="true"'));
        $this->assertMatchesRegularExpression('/name="period_start"\s+value="2026-08-01"/', $html);
        $this->assertMatchesRegularExpression('/name="period_end"\s+value="2026-08-31"/', $html);
    }
}
