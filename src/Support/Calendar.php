<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

use Carbon\CarbonImmutable;

/**
 * The month grid ds::calendar and ds::date-picker's popover share, drawn on
 * the server: which days a month shows, which of them are chosen, disabled,
 * today or marked, and the built-in date ranges.
 *
 * resources/js/shared/dates.js is the same arithmetic for the browser, and
 * both are held to tests/Fixtures/dates.json (CalendarTest, test-js), so the
 * month PHP draws and the month dsCalendar redraws are the same month. Days
 * are Y-m-d strings throughout, compared as strings; gmmktime is used for
 * arithmetic only, where no timezone can move a day.
 */
final class Calendar
{
    public const TONES = ['neutral', 'accent', 'success', 'warning', 'danger'];

    /** The built-in ranges, in the order they are listed. */
    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last7' => 'Last 7 days',
        'last30' => 'Last 30 days',
        'thisMonth' => 'This month',
        'lastMonth' => 'Last month',
        'thisYear' => 'This year',
    ];

    /** Every month draws six rows, the most a month needs, so the script can redraw any month into them. */
    public const ROWS = 6;

    /** Y-m-d from a DateTimeInterface or a string that starts with one; anything else is null. */
    public static function iso(mixed $date): ?string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}/', $date) ? substr($date, 0, 10) : null;
    }

    public static function key(int $year, int $month, int $day): string
    {
        return gmdate('Y-m-d', gmmktime(0, 0, 0, $month, $day, $year));
    }

    /** @return array{int, int, int} */
    public static function parts(string $iso): array
    {
        return [(int) substr($iso, 0, 4), (int) substr($iso, 5, 2), (int) substr($iso, 8, 2)];
    }

    public static function addDays(string $iso, int $days): string
    {
        [$y, $m, $d] = self::parts($iso);

        return self::key($y, $m, $d + $days);
    }

    /** Held to the last day of a shorter month, as shared/dates.js is. */
    public static function addMonths(string $iso, int $months): string
    {
        [$y, $m, $d] = self::parts($iso);

        return self::key($y, $m + $months, min($d, self::monthLength($y, $m + $months)));
    }

    public static function monthLength(int $year, int $month): int
    {
        return (int) gmdate('t', gmmktime(0, 0, 0, $month, 1, $year));
    }

    /** 0 = Sunday … 6 = Saturday. */
    public static function weekday(string $iso): int
    {
        [$y, $m, $d] = self::parts($iso);

        return (int) gmdate('w', gmmktime(0, 0, 0, $m, $d, $y));
    }

    /** @return list<list<string>> the weeks that show a month, outside days included */
    public static function weeks(int $year, int $month, int $weekStart): array
    {
        $first = self::key($year, $month, 1);
        $day = self::addDays($first, -((self::weekday($first) - $weekStart + 7) % 7));
        $rows = [];

        while ($rows === [] || substr($day, 0, 7) === substr($first, 0, 7)) {
            $row = [];
            for ($i = 0; $i < 7; $i++, $day = self::addDays($day, 1)) {
                $row[] = $day;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** @param  list<string>  $row */
    public static function isoWeek(array $row): int
    {
        foreach ($row as $iso) {
            if (self::weekday($iso) === 4) {
                [$y, $m, $d] = self::parts($iso);

                return (int) gmdate('W', gmmktime(0, 0, 0, $m, $d, $y));
            }
        }

        return 0;
    }

    public static function days(string $from, string $to): int
    {
        [$a, $b, $c] = self::parts($from);
        [$x, $y, $z] = self::parts($to);

        return (int) round((gmmktime(0, 0, 0, $y, $z, $x) - gmmktime(0, 0, 0, $b, $c, $a)) / 86400);
    }

    /** @return array<string, array{string, string}> the built-in ranges, from the host's today */
    public static function presets(string $today): array
    {
        [$y, $m] = self::parts($today);
        $lastMonth = self::addMonths(self::key($y, $m, 1), -1);

        return [
            'today' => [$today, $today],
            'yesterday' => [self::addDays($today, -1), self::addDays($today, -1)],
            'last7' => [self::addDays($today, -6), $today],
            'last30' => [self::addDays($today, -29), $today],
            'thisMonth' => [self::key($y, $m, 1), $today],
            'lastMonth' => [$lastMonth, self::key($y, $m, 0)],
            'thisYear' => [self::key($y, 1, 1), $today],
        ];
    }

    /**
     * The presets a picker lists: `true` is the built-in set, an array is
     * `label => [from, to]` worked out by the host. A built-in one carries
     * its key and no dates, because the script works those out from the
     * same today; the host's carry their dates.
     *
     * @return list<array{key: ?string, label: string, from: string, to: string}>
     */
    public static function presetList(mixed $presets, string $today): array
    {
        if ($presets === true) {
            $ranges = self::presets($today);

            return array_map(
                fn (string $key) => ['key' => $key, 'label' => self::PRESETS[$key], 'from' => $ranges[$key][0], 'to' => $ranges[$key][1]],
                array_keys(self::PRESETS),
            );
        }

        $list = [];
        foreach (is_array($presets) ? $presets : [] as $label => $range) {
            $from = self::iso(is_array($range) ? ($range[0] ?? null) : null);
            $to = self::iso(is_array($range) ? ($range[1] ?? null) : null);
            if ($from !== null && $to !== null) {
                $list[] = ['key' => null, 'label' => (string) $label, 'from' => min($from, $to), 'to' => max($from, $to)];
            }
        }

        return $list;
    }

    /** The locale's first day of the week, 0 = Sunday … 6 = Saturday; Monday when it has none. */
    public static function weekStart(mixed $weekStart, string $locale): int
    {
        if (is_numeric($weekStart)) {
            return ((int) $weekStart % 7 + 7) % 7;
        }
        $first = CarbonImmutable::parse('2024-01-01')->locale($locale)->getTranslationMessage('first_day_of_week');

        return is_int($first) ? $first : 1;
    }

    /**
     * Day and month names in the locale, from Carbon. Long weekday names are
     * by weekday (0 = Sunday); short ones in the order the columns show them.
     *
     * @return array{short: list<string>, long: list<string>, months: list<string>}
     */
    public static function names(string $locale, int $weekStart): array
    {
        // 2024-01-07 is a Sunday.
        $day = fn (int $w) => CarbonImmutable::parse('2024-01-07')->addDays($w)->locale($locale);

        return [
            'short' => array_map(fn ($i) => $day(($i + $weekStart) % 7)->isoFormat('dd'), range(0, 6)),
            'long' => array_map(fn ($w) => $day($w)->isoFormat('dddd'), range(0, 6)),
            'months' => array_map(fn ($m) => CarbonImmutable::create(2024, $m, 1)->locale($locale)->isoFormat('MMMM'), range(1, 12)),
        ];
    }

    /**
     * A day's marker: a tone draws a dot, a positive count draws the number.
     * Anything else is dropped rather than drawn as something it is not.
     *
     * @return array<string, string|int>
     */
    public static function events(mixed $events): array
    {
        $marks = [];
        foreach (is_iterable($events) ? $events : [] as $date => $mark) {
            $iso = self::iso($date);
            if ($iso === null) {
                continue;
            }
            if (is_string($mark) && in_array($mark, self::TONES, true)) {
                $marks[$iso] = $mark;
            } elseif (is_numeric($mark) && (int) $mark > 0) {
                $marks[$iso] = (int) $mark;
            }
        }

        return $marks;
    }

    /**
     * Everything the grid partial draws, for `count` months from the one
     * `cursor` falls in. The script is handed the same options and redraws
     * the same cells, so every rule here has its twin in behaviours/calendar.js.
     *
     * @param  array{
     *     cursor: string, count: int, weekStart: int, names: array, mode: string,
     *     value: list<string>, min: ?string, max: ?string, disabledDates: list<string>,
     *     disabledDays: list<int>, today: ?string, events: array<string, string|int>,
     *     outside: bool, words: array<string, string>,
     * }  $o
     * @return array{months: list<array>, stop: ?string}
     */
    public static function grid(array $o): array
    {
        $value = $o['value'];
        $range = $o['mode'] === 'range';
        [$from, $to] = $range && count($value) === 2 ? [min($value), max($value)] : [null, null];
        $off = fn (string $iso) => ($o['min'] && $iso < $o['min']) || ($o['max'] && $iso > $o['max'])
            || in_array($iso, $o['disabledDates'], true) || in_array(self::weekday($iso), $o['disabledDays'], true);

        $months = [];
        $visible = [];
        for ($i = 0; $i < $o['count']; $i++) {
            [$y, $m] = self::parts(self::addMonths($o['cursor'], $i));
            $weeks = self::weeks($y, $m, $o['weekStart']);
            // Padded to six with the weeks that follow, hidden: room the
            // script can redraw a six-week month into.
            while (count($weeks) < self::ROWS) {
                $last = end($weeks)[6];
                $weeks[] = array_map(fn ($n) => self::addDays($last, $n), range(1, 7));
            }
            $shown = count(self::weeks($y, $m, $o['weekStart']));

            $rows = [];
            foreach ($weeks as $r => $week) {
                $cells = [];
                foreach ($week as $iso) {
                    $outside = substr($iso, 5, 2) !== sprintf('%02d', $m);
                    if (! $outside) {
                        $visible[] = $iso;
                    }
                    $mark = $o['events'][$iso] ?? null;
                    $cells[] = [
                        'iso' => $iso,
                        'day' => (int) substr($iso, 8, 2),
                        'outside' => $outside,
                        'hidden' => $outside && ! $o['outside'],
                        'disabled' => $off($iso),
                        'selected' => in_array($iso, $value, true),
                        'between' => $from !== null && $iso > $from && $iso < $to,
                        'today' => $iso === $o['today'],
                        'tone' => is_string($mark) ? $mark : null,
                        'count' => is_int($mark) ? $mark : null,
                        'label' => self::label($iso, $o['names'], $mark, $o['words']),
                    ];
                }
                $rows[] = ['week' => self::isoWeek($week), 'hidden' => $r >= $shown, 'cells' => $cells];
            }

            $months[] = ['year' => $y, 'month' => $m, 'label' => $o['names']['months'][$m - 1].' '.$y, 'rows' => $rows];
        }

        // One tab stop in the grid: a chosen day in view, else today, else
        // the first day of the first month. dsCalendar picks the same way.
        $stop = null;
        foreach ([...$value, $o['today']] as $candidate) {
            if ($candidate !== null && in_array($candidate, $visible, true)) {
                $stop = $candidate;
                break;
            }
        }

        return ['months' => $months, 'stop' => $stop ?? ($visible[0] ?? null)];
    }

    /** "Thursday 17 September 2026", and the marker in words: a dot or a number says nothing on its own. */
    public static function label(string $iso, array $names, string|int|null $mark, array $words): string
    {
        [$y, $m, $d] = self::parts($iso);
        $label = $names['long'][self::weekday($iso)].' '.$d.' '.$names['months'][$m - 1].' '.$y;

        return match (true) {
            is_int($mark) => $label.', '.str_replace(':count', (string) $mark, $words[$mark === 1 ? 'event' : 'events']),
            is_string($mark) => $label.', '.$words['marked'],
            default => $label,
        };
    }
}
