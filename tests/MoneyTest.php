<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Money;

/**
 * Amounts are digits, never floats, on the server as in the browser: these
 * are the cases scripts/test-js.mjs puts to resources/js/shared/money.js, so
 * the first paint and the typing agree.
 */
final class MoneyTest extends TestCase
{
    protected function tearDown(): void
    {
        Money::$intl = true;
        parent::tearDown();
    }

    /** @return array<string, array{string|int|float, int, bool, bool, string}> */
    public static function amounts(): array
    {
        return [
            '0.1 + 0.2 is 0.30' => [0.1 + 0.2, 2, false, false, '0.30'],
            'a twenty-digit amount keeps every digit' => ['12345678901234567890.129', 2, false, false, '12345678901234567890.13'],
            'and in minor units' => ['12345678901234567890.129', 2, true, false, '1234567890123456789013'],
            'minor units read back' => [123450, 2, false, true, '1234.50'],
            'rounding carries through the units' => ['9.995', 2, false, false, '10.00'],
            'half away from zero, below zero too' => ['-1.005', 2, false, false, '-1.01'],
            'no negative zero' => ['-0.001', 2, false, false, '0.00'],
            'no places for JPY' => ['1234', 0, false, false, '1234'],
            'three for KWD' => ['1.2345', 3, false, false, '1.235'],
            'a float past 1e21 is digits' => [1e21, 2, false, false, '1000000000000000000000.00'],
            'and a tiny one' => [0.0000001, 8, false, false, '0.00000010'],
            'a paste with a symbol and grouping' => ['$1,234.56', 2, false, false, '1234.56'],
            'one comma and three digits after is grouping' => ['1,234', 2, false, false, '1234.00'],
            'one comma and one digit after is the point' => ['1,5', 2, false, false, '1.50'],
            'accounting brackets are a minus' => ['(12.00)', 2, false, false, '-12.00'],
        ];
    }

    #[Test]
    #[DataProvider('amounts')]
    public function the_machine_form_is_exact(string|int|float $value, int $decimals, bool $minor, bool $givenMinor, string $want): void
    {
        $this->assertSame($want, Money::machine($value, 'USD', $decimals, $minor, $givenMinor));
    }

    #[Test]
    public function places_come_from_the_currency(): void
    {
        $this->assertSame(2, Money::decimals('usd'));
        $this->assertSame(0, Money::decimals('JPY'));
        $this->assertSame(3, Money::decimals('KWD'));
        $this->assertSame('1235', Money::machine('1234.5', 'JPY'));
        $this->assertSame('', Money::machine(null, 'USD'));
        $this->assertSame('', Money::machine('abc', 'USD'));
    }

    #[Test]
    public function it_formats_with_intl(): void
    {
        if (! extension_loaded('intl')) {
            $this->markTestSkipped('ext-intl is not loaded');
        }
        $this->assertSame('12,34,567.50', Money::format('1234567.5', 'INR', 'en-IN'));
        $this->assertSame('₹12,34,567.50', Money::format('1234567.5', 'INR', 'en-IN', symbol: 'symbol'));
        // ICU puts a no-break space between the amount and the symbol.
        $this->assertSame("-1.234,50\u{00A0}€", Money::format('-1234.5', 'EUR', 'de', symbol: 'symbol'));
        $this->assertSame('$1,234.50', Money::format('1234.5', 'USD', 'en', symbol: 'symbol'));
        $this->assertSame('¥1,235', Money::format('1234.5', 'JPY', 'en', symbol: 'symbol'));
        $this->assertSame(['decimal' => ',', 'group' => '.'], array_intersect_key(Money::shape('de'), ['decimal' => 1, 'group' => 1]));
    }

    #[Test]
    public function without_intl_it_writes_en_style(): void
    {
        Money::$intl = false;

        $this->assertSame('1,234,567.50', Money::format('1234567.5', 'INR', 'en-IN'));
        $this->assertSame('₹1,234,567.50', Money::format('1234567.5', 'INR', 'en-IN', symbol: 'symbol'));
        $this->assertSame("CHF\u{00A0}12.00", Money::format('12', 'CHF', 'de-CH', symbol: 'symbol'));
        $this->assertSame('-€1,234.50', Money::format('-1234.5', 'EUR', 'de', symbol: 'symbol'));
        $this->assertSame('0.30', Money::format(0.1 + 0.2, 'USD'));
    }

    #[Test]
    public function the_field_posts_digits_and_shows_the_locale(): void
    {
        $html = Blade::render('<x-ds::money-input name="budget" label="Budget" currency="INR" locale="en-IN" value="1234567.5" />');

        $this->assertMatchesRegularExpression('/type="hidden"[^>]*value="1234567.50"/', $html);
        $this->assertStringContainsString('value="12,34,567.50"', $html);
        $this->assertStringContainsString('inputmode="decimal"', $html);
        $this->assertStringContainsString('name="budget"', $html);
    }

    #[Test]
    public function minor_units_go_in_and_out(): void
    {
        $html = Blade::render('<x-ds::money-input name="cents" :value="1999" :minor="true" />');

        $this->assertMatchesRegularExpression('/type="hidden"[^>]*value="1999"/', $html);
        $this->assertStringContainsString('value="19.99"', $html);
    }
}
