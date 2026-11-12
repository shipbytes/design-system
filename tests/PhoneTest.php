<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Phone;

/**
 * Support\Phone reads a stored number as the browser does, and the field
 * sends only the countries it offers. A formatter and a length check, not
 * libphonenumber: nothing here says whether a number exists.
 */
final class PhoneTest extends TestCase
{
    #[Test]
    public function every_country_is_whole(): void
    {
        foreach (Phone::countries() as $iso => $c) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $iso);
            $this->assertNotSame('', $c['name'], $iso);
            $this->assertMatchesRegularExpression('/^\d{1,3}( \d{3})?$/', $c['dial'], $iso);
            $this->assertLessThanOrEqual($c['max'] ?: PHP_INT_MAX, $c['min'], $iso);
        }
        $this->assertGreaterThan(200, count(Phone::countries()));
    }

    #[Test]
    public function an_e164_number_chooses_its_country(): void
    {
        $this->assertSame(['country' => 'GB', 'national' => '7911123456'], Phone::parse('+447911123456', ['US', 'GB', 'IN'], 'US'));
        $this->assertSame(['country' => 'GB', 'national' => '7911123456'], Phone::parse('0044 7911 123456', ['US', 'GB'], 'US'));
        // A shared code: the field's own country if it is one, else the primary one.
        $this->assertSame('US', Phone::parse('+14155550123', ['CA', 'US'], 'IN')['country']);
        $this->assertSame('CA', Phone::parse('+14165550123', ['CA', 'US'], 'CA')['country']);
        // The longest code wins: +1 876 is Jamaica, not the US.
        $this->assertSame(['country' => 'JM', 'national' => '5551234'], Phone::parse('+18765551234', ['US', 'JM'], 'US'));
        // Not offered: no number, rather than a wrong country.
        $this->assertSame(['country' => 'US', 'national' => ''], Phone::parse('+919876543210', ['US'], 'US'));
    }

    #[Test]
    public function the_trunk_zero_is_dropped_where_e164_drops_it(): void
    {
        $this->assertSame('+447911123456', Phone::e164('GB', '07911 123456'));
        $this->assertSame('+390612345678', Phone::e164('IT', '06 1234 5678'));
        $this->assertSame('', Phone::e164('GB', ''));
        $this->assertSame(['country' => 'GB', 'national' => '7911123456'], Phone::parse('07911 123456', ['GB'], 'GB'));
    }

    #[Test]
    public function masks_write_the_national_number(): void
    {
        $this->assertSame('98765 43210', Phone::mask('9876543210', '##### #####'));
        $this->assertSame('(415) 555-0123', Phone::mask('4155550123', Phone::countries()['US']['mask']));
        $this->assertSame('(415) 5', Phone::mask('4155', '(###) ###-####'));
        $this->assertSame('123456', Phone::mask('123456', null));
        $this->assertSame('🇮🇳', Phone::flag('in'));
    }

    #[Test]
    public function the_field_posts_e164_and_sends_only_its_countries(): void
    {
        $html = Blade::render('<x-ds::phone-input name="phone" label="Phone" value="+447911123456" :countries="[\'GB\', \'IE\']" />');

        $this->assertMatchesRegularExpression('/type="hidden"[^>]*value="\+447911123456"/', $html);
        $this->assertStringContainsString('value="7911 123456"', $html);
        $this->assertStringContainsString('name="phone[number]"', $html);
        $this->assertStringContainsString('name="phone[country]"', $html);
        $this->assertStringContainsString('autocomplete="tel-national"', $html);
        $this->assertStringContainsString('autocomplete="tel-country-code"', $html);
        $this->assertSame(2, substr_count($html, 'data-ds-dial='));
        $this->assertMatchesRegularExpression('/<option[^>]*value="GB"[^>]*selected/s', $html);
        $this->assertStringNotContainsString('data-ds-phone-search', $html, 'no search field for two countries');
    }

    #[Test]
    public function an_unknown_country_is_an_error_not_a_guess(): void
    {
        $this->expectException(\Throwable::class);
        Blade::render('<x-ds::phone-input name="phone" :countries="[\'XX\']" />');
    }
}
