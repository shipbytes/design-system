<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * What ds::progress and ds::usage-meter tell a screen reader, and the numbers
 * they clamp before anything reaches the browser.
 *
 * Every assertion is on the parsed element: the value attributes are read
 * aloud, and an out-of-range aria-valuenow or a width past the track looks
 * fine in a string search and wrong everywhere else.
 */
final class ProgressAndMeterTest extends TestCase
{
    private function first(string $html, string $xpath): \DOMElement
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.$html);
        libxml_clear_errors();

        $node = (new \DOMXPath($document))->query($xpath)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $node, "nothing matches {$xpath}");

        return $node;
    }

    public static function clamped(): array
    {
        return [
            'in range' => [':value="42"', '42', '42'],
            'over max' => [':value="140"', '100', '100'],
            'under zero' => [':value="-5"', '0', '0'],
            'fraction' => [':value="12.5"', '12.5', '12.5'],
            'of a max' => [':value="3" :max="12"', '3', '25'],
            'over a max' => [':value="30" :max="12"', '12', '100'],
        ];
    }

    #[Test]
    #[DataProvider('clamped')]
    public function progress_clamps_in_php_and_drives_the_fill_from_one_property(string $props, string $now, string $percent): void
    {
        $bar = $this->first(Blade::render("<x-ds::progress label=\"Upload\" {$props} />"), '//*[@role="progressbar"]');

        $this->assertSame($now, $bar->getAttribute('aria-valuenow'));
        $this->assertSame('0', $bar->getAttribute('aria-valuemin'));
        $this->assertStringContainsString("--ds-progress: {$percent}", $bar->getAttribute('style'));
    }

    #[Test]
    public function an_indeterminate_progress_has_no_value_at_all(): void
    {
        // aria-valuenow present means "this far"; absent is how a progressbar
        // says "unknown". A 0 would announce "0 percent" for the whole wait.
        $bar = $this->first(Blade::render('<x-ds::progress label="Indexing" />'), '//*[@role="progressbar"]');

        $this->assertFalse($bar->hasAttribute('aria-valuenow'));
        $this->assertFalse($bar->hasAttribute('style'));
    }

    #[Test]
    public function the_label_is_the_name_whether_or_not_it_is_shown(): void
    {
        foreach (['', ':hideLabel="true"'] as $hide) {
            $html = Blade::render("<x-ds::progress label=\"Uploading\" :value=\"5\" {$hide} />");
            $this->assertSame('Uploading', $this->first($html, '//*[@role="progressbar"]')->getAttribute('aria-label'));
        }

        $this->assertStringNotContainsString(
            '>Uploading<',
            Blade::render('<x-ds::progress label="Uploading" :value="5" :hideLabel="true" />'),
            'hideLabel still printed the label',
        );
    }

    #[Test]
    public function aria_labelledby_replaces_the_label_rather_than_joining_it(): void
    {
        $bar = $this->first(Blade::render('<x-ds::progress aria-labelledby="t" :value="5" />'), '//*[@role="progressbar"]');

        $this->assertSame('t', $bar->getAttribute('aria-labelledby'));
        $this->assertFalse($bar->hasAttribute('aria-label'));
    }

    #[Test]
    public function a_progress_with_no_name_at_all_is_refused(): void
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionMessageMatches('/needs a label/');

        Blade::render('<x-ds::progress :value="5" />');
    }

    #[Test]
    public function the_slot_words_the_value_itself(): void
    {
        $html = Blade::render('<x-ds::progress label="Files" :value="3" :max="12">3 of 12 files</x-ds::progress>');

        $this->assertStringContainsString('3 of 12 files', $html);
        $this->assertStringNotContainsString('25%', $html);
    }

    public static function meterTones(): array
    {
        return [
            'below warn' => [':used="10" :limit="50"', 'bg-surface-inverse'],
            'at warn' => [':used="40" :limit="50"', 'bg-warning'],
            'at the limit' => [':used="50" :limit="50"', 'bg-danger'],
            'over' => [':used="70" :limit="50"', 'bg-danger'],
            'custom thresholds' => [':used="30" :limit="40" :warnAt="50" :dangerAt="90"', 'bg-warning'],
        ];
    }

    #[Test]
    #[DataProvider('meterTones')]
    public function the_meter_takes_its_tone_from_the_thresholds(string $props, string $fill): void
    {
        $html = Blade::render("<x-ds::usage-meter label=\"Credits\" {$props} />");

        $this->assertMatchesRegularExpression('/class="h-full shrink-0 '.preg_quote($fill, '/').'\b/', $html);
    }

    #[Test]
    public function over_the_limit_is_said_in_words_and_the_bar_stops_at_full(): void
    {
        $html = Blade::render('<x-ds::usage-meter label="Storage" :used="1200" :limit="1000" unit="MB" />');
        $meter = $this->first($html, '//*[@role="meter"]');

        // valuenow has to sit inside min/max; the words carry the overage.
        $this->assertSame('1000', $meter->getAttribute('aria-valuenow'));
        $this->assertSame('1000', $meter->getAttribute('aria-valuemax'));
        $this->assertSame('1,200 of 1,000 MB, 200 MB over the limit', $meter->getAttribute('aria-valuetext'));
        $this->assertStringContainsString('200 MB over the limit', strip_tags($html));
        $this->assertStringContainsString('width: 100%', $html);
        preg_match_all('/width: ([\d.]+)%/', $html, $widths);
        $this->assertLessThanOrEqual(100, max(array_map('floatval', $widths[1])), 'a fill is wider than its track');
    }

    #[Test]
    public function segments_scale_down_together_when_they_overflow(): void
    {
        $html = Blade::render(
            '<x-ds::usage-meter label="Storage" :limit="10" :segments="$s" />',
            ['s' => [['label' => 'A', 'value' => 9], ['label' => 'B', 'value' => 3]]],
        );

        preg_match_all('/width: ([\d.]+)%/', $html, $widths);

        $this->assertSame(['75', '25'], $widths[1], 'segments kept their proportions and filled the track exactly');
        $this->assertSame('12 of 10, 2 over the limit', $this->first($html, '//*[@role="meter"]')->getAttribute('aria-valuetext'));
    }

    #[Test]
    public function the_legend_is_text_a_screen_reader_can_reach(): void
    {
        $html = Blade::render(
            '<x-ds::usage-meter label="Seats" :limit="10" :segments="$s" />',
            ['s' => [['label' => 'Admins', 'value' => 2, 'tone' => 'accent'], ['label' => 'Members', 'value' => 5]]],
        );

        $legend = $this->first($html, '//ul');
        $this->assertFalse($legend->hasAttribute('aria-hidden'));
        $this->assertStringContainsString('Admins', $legend->textContent);
        $this->assertSame('7 of 10', $this->first($html, '//*[@role="meter"]')->getAttribute('aria-valuetext'));
    }

    #[Test]
    public function a_zero_limit_renders_rather_than_dividing_by_zero(): void
    {
        $meter = $this->first(Blade::render('<x-ds::usage-meter label="Exports" :used="3" :limit="0" />'), '//*[@role="meter"]');

        $this->assertSame('0', $meter->getAttribute('aria-valuenow'));
        $this->assertStringContainsString('over the limit', $meter->getAttribute('aria-valuetext'));
    }
}
