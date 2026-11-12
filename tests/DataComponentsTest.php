<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * What counter, sparkline, gauge and activity-heatmap say to a screen reader,
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
}
