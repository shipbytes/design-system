<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * Amounts of money as strings of digits, for ds::money-input's first paint
 * and for anywhere a host shows an amount the way the field does.
 *
 *   Money::format('1234.5', 'INR', 'en-IN')                  "1,234.50"
 *   Money::format('1234567.5', 'INR', 'en-IN', symbol: 'symbol')  "₹12,34,567.50"
 *   Money::format(123450, 'USD', 'en', minor: true)          "1,234.50"
 *
 * Never floating point: a float is read through its shortest decimal form
 * and rounded as digits, so 0.1 + 0.2 shows 0.30, and an amount past 2^53
 * keeps every digit. resources/js/shared/money.js is the same logic for the
 * browser, and the two are tested on the same cases (MoneyTest, test-js).
 *
 * With ext-intl the locale's grouping, separators and currency symbol come
 * from ICU's NumberFormatter, which is what the browser's Intl is built on.
 * Without it the plain fallback is what `en` would write: "," between groups
 * of three, "." before the decimals, and the currency's code (or one of a
 * few common symbols) in front.
 */
final class Money
{
    /**
     * Currencies whose minor unit is not hundredths (ISO 4217). Everything
     * else has two decimal places.
     */
    public const DECIMALS = [
        'BHD' => 3, 'IQD' => 3, 'JOD' => 3, 'KWD' => 3, 'LYD' => 3, 'OMR' => 3, 'TND' => 3,
        'BIF' => 0, 'CLP' => 0, 'DJF' => 0, 'GNF' => 0, 'ISK' => 0, 'JPY' => 0, 'KMF' => 0,
        'KRW' => 0, 'PYG' => 0, 'RWF' => 0, 'UGX' => 0, 'UYI' => 0, 'VND' => 0, 'VUV' => 0,
        'XAF' => 0, 'XOF' => 0, 'XPF' => 0,
    ];

    /** The fallback's symbols, when there is no ext-intl to ask. */
    private const SYMBOLS = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥', 'INR' => '₹', 'CNY' => '¥', 'KRW' => '₩'];

    /** Turned off by the tests, to render the fallback on a machine that has intl. */
    public static bool $intl = true;

    public static function decimals(string $currency): int
    {
        return self::DECIMALS[strtoupper($currency)] ?? 2;
    }

    /**
     * The amount written for display, without or with its currency.
     *
     * @param  string|int|float|null  $value  "1234.5", 1234.5, or with `minor` 123450
     * @param  'none'|'symbol'|'code'  $symbol
     */
    public static function format(
        string|int|float|null $value,
        string $currency,
        ?string $locale = null,
        ?int $decimals = null,
        bool $minor = false,
        string $symbol = 'none',
    ): string {
        $decimals ??= self::decimals($currency);
        $amount = self::round(self::given($value, $decimals, $minor), $decimals);
        $text = self::write($amount, self::shape($locale), $decimals, pad: true);
        if ($text === '' || $symbol === 'none') {
            return $text;
        }
        $sign = str_starts_with($text, '-') ? '-' : '';
        $unsigned = ltrim($text, '-');

        // The locale's own currency pattern, with the amount put where ICU
        // put its zero: "₹0.00" in en-IN, "0,00 €" in de, spacing included.
        return $sign.preg_replace('/\d(?:.*\d)?/u', str_replace('$', '\\$', $unsigned), self::template($currency, $locale, $symbol), 1);
    }

    /** The machine form, "1234.50", or minor units "123450"; '' for no amount. */
    public static function machine(string|int|float|null $value, string $currency, ?int $decimals = null, bool $minor = false, bool $givenMinor = false): string
    {
        $decimals ??= self::decimals($currency);

        return self::digits(self::round(self::given($value, $decimals, $givenMinor), $decimals), $decimals, $minor);
    }

    /**
     * The locale's decimal point, group separator and grouping.
     *
     * @return array{decimal: string, group: string, primary: int, secondary: int, min: int}
     */
    public static function shape(?string $locale = null): array
    {
        if (! self::hasIntl()) {
            return ['decimal' => '.', 'group' => ',', 'primary' => 3, 'secondary' => 3, 'min' => 1];
        }
        $f = new \NumberFormatter(self::icu($locale).'@numbers=latn', \NumberFormatter::DECIMAL);
        $primary = (int) $f->getAttribute(\NumberFormatter::GROUPING_SIZE);
        $secondary = (int) $f->getAttribute(\NumberFormatter::SECONDARY_GROUPING_SIZE);
        $group = (string) $f->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);

        return [
            'decimal' => (string) $f->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL),
            'group' => $group,
            'primary' => $primary,
            'secondary' => $secondary > 0 ? $secondary : $primary,
            'min' => str_contains((string) $f->format(1000), $group) ? 1 : 2,
        ];
    }

    /**
     * What sits before and after the amount: "$" before in en, "€" after
     * in de. 'code' writes the code where the symbol goes; 'none' nothing.
     *
     * @return array{prefix: string, suffix: string}
     */
    public static function affix(string $currency, ?string $locale = null, string $symbol = 'symbol'): array
    {
        if ($symbol === 'none') {
            return ['prefix' => '', 'suffix' => ''];
        }
        preg_match('/^(\D*)\d(?:.*\d)?(\D*)$/u', self::template($currency, $locale, $symbol), $m);
        // Spaces and direction marks around the symbol are the format's,
        // not the field's: the field draws its own gap. Byte-safe, unlike trim().
        $clean = fn (string $s) => preg_replace('/^[\s\x{00A0}\x{202F}\x{200E}\x{200F}\x{061C}]+|[\s\x{00A0}\x{202F}\x{200E}\x{200F}\x{061C}]+$/u', '', $s);

        return ['prefix' => $clean($m[1] ?? ''), 'suffix' => $clean($m[2] ?? '')];
    }

    /** Zero in the locale's currency format: the symbol or code where it goes. */
    private static function template(string $currency, ?string $locale, string $symbol): string
    {
        $currency = strtoupper($currency);
        if (! self::hasIntl()) {
            return ($symbol === 'code' ? $currency."\u{00A0}" : (self::SYMBOLS[$currency] ?? $currency."\u{00A0}")).'0';
        }
        $f = new \NumberFormatter(self::icu($locale).'@numbers=latn', \NumberFormatter::CURRENCY);
        if ($symbol === 'code') {
            $f->setPattern(str_replace('¤', '¤¤', (string) $f->getPattern()));
        }

        return (string) $f->formatCurrency(0, $currency);
    }

    /** ICU's spelling of a Laravel or BCP 47 locale: en_IN. */
    public static function icu(?string $locale): string
    {
        return str_replace('-', '_', $locale ?: (function_exists('app') ? (string) app()->getLocale() : 'en'));
    }

    /** The browser's spelling: en-IN. */
    public static function bcp47(?string $locale): string
    {
        return str_replace('_', '-', self::icu($locale));
    }

    private static function hasIntl(): bool
    {
        return self::$intl && class_exists(\NumberFormatter::class);
    }

    /*
     * The pieces below are shared/money.js, line for line; see its comments.
     * An amount is ['sign' => '-'|'', 'int' => '1234', 'frac' => '5'].
     */

    /** @return array{sign: string, int: string, frac: string}|null */
    public static function read(string $text, string $decimal = '.'): ?array
    {
        if (! preg_match('/\d/', $text, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $first = $m[0][1];
        $sign = preg_match('/[-−(]/u', substr($text, 0, $first)) ? '-' : '';
        if ($decimal !== '.' && $decimal !== ',') {
            $text = str_replace($decimal, '.', $text);
        }
        $body = preg_replace('/[^\d.,]/', '', substr($text, $first));
        preg_match_all('/[.,]/', $body, $marks, PREG_OFFSET_CAPTURE);
        $marks = $marks[0];
        $at = -1;
        if ($marks) {
            [$mark, $index] = end($marks);
            $after = strlen($body) - $index - 1;
            if (count(array_unique(array_column($marks, 0))) > 1) {
                $at = $index;
            } elseif (count($marks) === 1 && ($mark === $decimal || $after !== 3)) {
                $at = $index;
            }
        }
        $int = self::trim(preg_replace('/\D/', '', $at < 0 ? $body : substr($body, 0, $at)));
        $frac = $at < 0 ? '' : preg_replace('/\D/', '', substr($body, $at + 1));

        return ['sign' => $sign, 'int' => $int, 'frac' => $frac];
    }

    /** @return array{sign: string, int: string, frac: string}|null */
    public static function given(string|int|float|null $value, int $decimals, bool $minor = false): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_float($value)) {
            if (! is_finite($value)) {
                return null;
            }
            // PHP writes a float in its shortest round-trip form, 0.1 + 0.2
            // as 0.30000000000000004, and a large or tiny one with an E.
            $text = (string) $value;
            if (stripos($text, 'e') !== false) {
                $text = rtrim(rtrim(sprintf('%.20F', $value), '0'), '.');
            }
        } else {
            $text = (string) $value;
        }
        if ($minor) {
            if (! preg_match('/^\s*([-−]?)(\d+)\s*$/u', $text, $m)) {
                return null;
            }
            $digits = str_pad($m[2], $decimals + 1, '0', STR_PAD_LEFT);
            $cut = strlen($digits) - $decimals;

            return ['sign' => $m[1] !== '' ? '-' : '', 'int' => self::trim(substr($digits, 0, $cut)), 'frac' => substr($digits, $cut)];
        }

        return self::read($text);
    }

    /** @param array{sign: string, int: string, frac: string}|null $a */
    public static function round(?array $a, int $decimals): ?array
    {
        if ($a === null || strlen($a['frac']) <= $decimals) {
            return $a;
        }
        $kept = ($a['int'] === '' ? '0' : $a['int']).substr($a['frac'], 0, $decimals);
        $digits = $a['frac'][$decimals] >= '5' ? self::increment($kept) : $kept;
        $cut = strlen($digits) - $decimals;
        $int = self::trim(substr($digits, 0, $cut));

        return [...$a, 'int' => $int === '' ? '0' : $int, 'frac' => (string) substr($digits, $cut)];
    }

    /** @param array{sign: string, int: string, frac: string}|null $a */
    public static function digits(?array $a, int $decimals, bool $minor = false): string
    {
        if ($a === null || ($a['int'] === '' && $a['frac'] === '')) {
            return '';
        }
        $int = $a['int'] === '' ? '0' : $a['int'];
        $frac = substr(str_pad($a['frac'], $decimals, '0'), 0, $decimals);
        $sign = preg_match('/[1-9]/', $int.$frac) ? $a['sign'] : '';
        if ($minor) {
            return $sign.self::trim($int.$frac);
        }

        return $sign.$int.($decimals > 0 ? '.'.$frac : '');
    }

    /**
     * @param  array{sign: string, int: string, frac: string}|null  $a
     * @param  array{decimal: string, group: string, primary: int, secondary: int, min: int}  $s
     */
    public static function write(?array $a, array $s, int $decimals, bool $pad = false): string
    {
        if ($a === null || ($pad && $a['int'] === '' && $a['frac'] === '')) {
            return '';
        }
        $frac = substr($a['frac'], 0, $decimals);
        if ($pad && $decimals > 0) {
            $frac = str_pad($frac, $decimals, '0');
        }
        $int = $a['int'] !== '' ? $a['int'] : '0';
        $sign = $pad && ! preg_match('/[1-9]/', $int.$frac) ? '' : $a['sign'];

        return $sign.self::group($int, $s).($frac !== '' ? $s['decimal'].$frac : '');
    }

    /** @param array{decimal: string, group: string, primary: int, secondary: int, min: int} $s */
    public static function group(string $int, array $s): string
    {
        if ($s['primary'] <= 0 || $s['group'] === '' || strlen($int) < $s['primary'] + $s['min']) {
            return $int;
        }
        $out = substr($int, -$s['primary']);
        $rest = substr($int, 0, -$s['primary']);
        while ($rest !== '') {
            $out = substr($rest, -$s['secondary']).$s['group'].$out;
            $rest = strlen($rest) > $s['secondary'] ? substr($rest, 0, -$s['secondary']) : '';
        }

        return $out;
    }

    private static function trim(string $digits): string
    {
        return preg_replace('/^0+(?=\d)/', '', $digits);
    }

    private static function increment(string $digits): string
    {
        $i = strlen($digits) - 1;
        while ($i >= 0 && $digits[$i] === '9') {
            $digits[$i--] = '0';
        }

        return $i < 0 ? '1'.$digits : substr_replace($digits, (string) ((int) $digits[$i] + 1), $i, 1);
    }
}
