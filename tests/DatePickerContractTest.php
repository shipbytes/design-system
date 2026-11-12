<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * In 2.0 ds::date-picker draws the calendar ds::calendar draws (the 1.x
 * inline-Alpine grid is retired), so its markup is no longer pinned byte for
 * byte. What is pinned is what a consumer relies on: the props, the posted
 * names and values, the trigger and the popover's role, the label and the
 * error and help wiring, and that it renders its state before any script.
 */
final class DatePickerContractTest extends TestCase
{
    #[Test]
    public function one_day_posts_under_its_name_as_y_m_d(): void
    {
        $html = Blade::render('<x-ds::date-picker name="due" label="Due date" value="2026-09-30" min="2026-01-01" max="2026-12-31" />');

        $this->assertMatchesRegularExpression('/type="date"\s+name="due"\s+value="2026-09-30"\s+data-ds-cal-input\s+min="2026-01-01"\s+max="2026-12-31"/', $html);
        $this->assertSame(1, substr_count($html, 'data-ds-cal-input'));
        $this->assertStringNotContainsString('name="due_start"', $html);
    }

    #[Test]
    public function a_range_posts_start_and_end_as_two_fields(): void
    {
        $html = Blade::render('<x-ds::date-picker name="period" :range="true" :value="[\'2026-07-01\', \'2026-09-30\']" />');

        $this->assertMatchesRegularExpression('/name="period_start"\s+value="2026-07-01"/', $html);
        $this->assertMatchesRegularExpression('/name="period_end"\s+value="2026-09-30"/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="period"[\s>]/', $html);

        $empty = Blade::render('<x-ds::date-picker name="period" :range="true" />');
        $this->assertMatchesRegularExpression('/name="period_start"\s+value=""/', $empty);
        $this->assertMatchesRegularExpression('/name="period_end"\s+value=""/', $empty);
    }

    #[Test]
    public function a_date_time_value_posts_as_its_day(): void
    {
        $html = Blade::render('<x-ds::date-picker name="when" :value="\Illuminate\Support\Carbon::parse(\'2027-03-10 14:00\')" />');

        $this->assertMatchesRegularExpression('/name="when"\s+value="2027-03-10"/', $html);
        $this->assertMatchesRegularExpression('/data-day="2027-03-10"[^>]*data-selected/s', $html, 'opens on the value\'s month, chosen');
    }

    #[Test]
    public function the_trigger_opens_a_dialog_named_by_the_label(): void
    {
        $html = Blade::render('<x-ds::date-picker id="due" name="due" label="Due date" help="Soon." placeholder="Any day" />');

        $this->assertStringContainsString('<label id="due-label" for="due"', $html);
        $this->assertMatchesRegularExpression('/<button\s+type="button"\s+id="due"[^>]*data-ds-date-trigger[^>]*aria-haspopup="dialog"[^>]*:aria-expanded="dsOpen"[^>]*aria-labelledby="due-label due"[^>]*aria-describedby="due-help"/s', $html);
        $this->assertMatchesRegularExpression('/role="dialog"\s+aria-modal="false"\s+aria-labelledby="due-label"/', $html);
        $this->assertStringContainsString('>Any day</span>', $html);
        $this->assertStringContainsString('<p id="due-help"', $html);
    }

    #[Test]
    public function an_error_marks_the_field_and_replaces_the_help(): void
    {
        $html = Blade::render('<x-ds::date-picker id="start" name="start" label="Start" help="Soon." error="Pick a start date." />');

        $this->assertMatchesRegularExpression('/data-ds-date-trigger[^>]*aria-invalid="true"[^>]*aria-describedby="start-error"/s', $html);
        $this->assertStringContainsString('<p id="start-error"', $html);
        $this->assertStringNotContainsString('start-help', $html);
    }

    #[Test]
    public function disabled_still_posts_its_value(): void
    {
        $html = Blade::render('<x-ds::date-picker name="off" value="2026-09-01" :disabled="true" />');

        $this->assertMatchesRegularExpression('/data-ds-date-trigger[^>]*disabled/s', $html);
        $this->assertMatchesRegularExpression('/name="off"\s+value="2026-09-01"[^>]*readonly/s', $html, 'readonly, not disabled, so it posts');
    }

    #[Test]
    public function the_week_starts_on_monday_or_on_sunday(): void
    {
        $monday = Blade::render('<x-ds::date-picker name="d" value="2026-09-30" />');
        $sunday = Blade::render('<x-ds::date-picker name="d" value="2026-09-30" :weekStartsOn="0" />');

        $first = fn ($html) => preg_match('/role="columnheader"[^>]*>.*?<span class="sr-only">(\w+)/s', $html, $m) ? $m[1] : null;
        $this->assertSame('Monday', $first($monday));
        $this->assertSame('Sunday', $first($sunday));
    }

    #[Test]
    public function wire_model_binds_the_component_and_the_script_is_named(): void
    {
        $html = Blade::render('<x-ds::date-picker name="period" :range="true" wire:model.live="period" />');

        $this->assertMatchesRegularExpression('/x-modelable="dsValue"\s+wire:model.live="period"/', $html);
        $this->assertStringContainsString('dsCalendar is not registered: add &lt;x-ds::scripts /&gt;', $html);
    }
}
