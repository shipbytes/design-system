<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * Phone numbers for ds::phone-input: the country data in
 * resources/data/countries.php, an E.164 number split into a country and a
 * national number, and the simple masks the field draws with. The browser
 * does the same in resources/js/behaviours/phone-input.js, so the first
 * paint and the typing agree.
 *
 * This is a sanity check and a formatter, not libphonenumber: it knows each
 * country's code, a common way of writing its numbers, and roughly how many
 * digits they have. Whether a number exists is the server's question.
 */
final class Phone
{
    /**
     * Where several countries share a code, the one a pasted "+1…" or a
     * stored "+44…" is taken to be, unless the field's own country shares it.
     * The E.164 number is the same whichever is chosen.
     */
    public const PRIMARY = [
        '1' => 'US', '7' => 'RU', '39' => 'IT', '44' => 'GB', '47' => 'NO', '61' => 'AU',
        '212' => 'MA', '262' => 'RE', '358' => 'FI', '590' => 'GP', '599' => 'CW',
    ];

    /** Countries whose national numbers keep their leading 0 in E.164. */
    public const KEEPS_ZERO = ['CI', 'IT', 'SM', 'VA'];

    /** @var array<string, array{iso: string, name: string, dial: string, digits: string, mask: ?string, min: int, max: int, zero: bool}>|null */
    private static ?array $all = null;

    /** @return array<string, array{iso: string, name: string, dial: string, digits: string, mask: ?string, min: int, max: int, zero: bool}> */
    public static function countries(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }
        self::$all = [];
        foreach (require __DIR__.'/../../resources/data/countries.php' as $iso => $row) {
            [$name, $dial, $mask] = $row;
            $length = $mask === null ? 0 : substr_count($mask, '#');
            self::$all[$iso] = [
                'iso' => $iso,
                'name' => $name,
                'dial' => $dial,
                'digits' => str_replace(' ', '', $dial),
                'mask' => $mask,
                'min' => $row[3] ?? $length,
                'max' => $row[4] ?? $length,
                'zero' => in_array($iso, self::KEEPS_ZERO, true),
            ];
        }

        return self::$all;
    }

    /** The emoji flag: two regional indicator letters. */
    public static function flag(string $iso): string
    {
        return implode('', array_map(fn ($c) => mb_chr(0x1F1A5 + ord($c)), str_split(strtoupper($iso))));
    }

    /**
     * The country and national number in an E.164 number, among `$in`
     * (ISO codes, in order). A number with no "+" is a national number of
     * `$prefer`. The longest code wins; among countries sharing it, `$prefer`,
     * then the primary one, then the first.
     *
     * @param  list<string>  $in
     * @return array{country: string, national: string}
     */
    public static function parse(?string $value, array $in, string $prefer): array
    {
        $value = trim((string) $value);
        $digits = preg_replace('/\D/', '', $value);
        if (! preg_match('/^(\+|00)/', $value)) {
            return ['country' => $prefer, 'national' => self::national($digits, $prefer)];
        }
        if (str_starts_with($value, '00')) {
            $digits = substr($digits, 2);
        }
        $all = self::countries();
        $best = null;
        foreach ($in as $iso) {
            $code = $all[$iso]['digits'];
            if (! str_starts_with($digits, $code)) {
                continue;
            }
            $better = $best === null
                || strlen($code) > strlen($all[$best]['digits'])
                || (strlen($code) === strlen($all[$best]['digits']) && self::rank($iso, $prefer) < self::rank($best, $prefer));
            if ($better) {
                $best = $iso;
            }
        }
        if ($best === null) {
            return ['country' => $prefer, 'national' => ''];
        }

        return ['country' => $best, 'national' => substr($digits, strlen($all[$best]['digits']))];
    }

    private static function rank(string $iso, string $prefer): int
    {
        return $iso === $prefer ? 0 : (in_array($iso, self::PRIMARY, true) ? 1 : 2);
    }

    /** The national significant number: the trunk 0 dropped, where E.164 drops it. */
    public static function national(string $digits, string $iso): string
    {
        $country = self::countries()[$iso] ?? null;

        return $country && ! $country['zero'] && str_starts_with($digits, '0') ? substr($digits, 1) : $digits;
    }

    /** "+919876543210", or '' for no number. */
    public static function e164(string $iso, string $national): string
    {
        $national = self::national(preg_replace('/\D/', '', $national), $iso);

        return $national === '' ? '' : '+'.self::countries()[$iso]['digits'].$national;
    }

    /** Digits through a mask: "9876543210", "##### #####" is "98765 43210"; digits past it follow as they are. */
    public static function mask(string $digits, ?string $mask): string
    {
        if ($mask === null || $digits === '') {
            return $digits;
        }
        $out = '';
        $at = 0;
        foreach (str_split($mask) as $ch) {
            if ($at >= strlen($digits)) {
                break;
            }
            $out .= $ch === '#' ? $digits[$at++] : $ch;
        }

        return $out.substr($digits, $at);
    }
}
