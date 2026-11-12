<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The arithmetic of ds::code-block: code to lines, a line-range string to a
 * set, and highlighted HTML to lines that each close every tag they open.
 *
 * Kept out of the view because each piece is easy to get almost right. A
 * highlighter's token can run across a newline (a block comment is one span
 * over three lines), and splitting its HTML on "\n" leaves a line that opens a
 * span and never closes it, so the next line, and its number and its
 * highlight, render inside the last one. CodeTest holds the cases.
 */
final class Code
{
    /**
     * Plain code as escaped lines, ready to print. Exactly as given: no tab
     * expanded, no indent removed. One trailing newline is dropped, because a
     * file read from disk ends with one and it is not an empty last line.
     *
     * @return list<string>
     */
    public static function lines(string $code): array
    {
        $code = self::chomp($code);

        return array_map(fn (string $line) => e($line), preg_split('/\r\n|\r|\n/', $code));
    }

    /**
     * Highlighted HTML as lines, each one balanced: a tag still open at the
     * end of a line is closed there and opened again, with the same
     * attributes, at the start of the next.
     *
     * A wrapper a highlighter adds around its output, its own `pre` and
     * `code`, is taken off, so its background and padding do not land inside
     * this component's. Everything else is printed as given, unescaped: it is
     * the host's highlighter's HTML, and the host vouches for it.
     *
     * @return list<string>
     */
    public static function htmlLines(string $html): array
    {
        $html = self::chomp(self::unwrap($html));

        $lines = [];
        /** @var list<array{name: string, open: string}> $open */
        $open = [];

        foreach (preg_split('/\r\n|\r|\n/', $html) as $raw) {
            $line = implode('', array_column($open, 'open'));

            $offset = 0;
            preg_match_all('/<(\/?)([a-zA-Z][a-zA-Z0-9-]*)\b[^>]*?(\/?)>/', $raw, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
            foreach ($tags as $tag) {
                [$whole, $position] = $tag[0];
                $line .= substr($raw, $offset, $position - $offset).$whole;
                $offset = $position + strlen($whole);

                $name = strtolower($tag[2][0]);
                if ($tag[3][0] === '/' || in_array($name, ['br', 'img', 'wbr', 'hr', 'input'], true)) {
                    continue;
                }
                if ($tag[1][0] === '/') {
                    // The innermost open tag of this name. A stray closing tag
                    // with none open is printed as it came and changes nothing.
                    for ($i = count($open) - 1; $i >= 0; $i--) {
                        if ($open[$i]['name'] === $name) {
                            array_splice($open, $i, 1);
                            break;
                        }
                    }
                } else {
                    $open[] = ['name' => $name, 'open' => $whole];
                }
            }
            $line .= substr($raw, $offset);

            foreach (array_reverse($open) as $tag) {
                $line .= '</'.$tag['name'].'>';
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * The text a reader copies: what the HTML says, with its tags gone and
     * its entities read.
     */
    public static function text(string $html): string
    {
        return self::chomp(html_entity_decode(strip_tags(self::unwrap($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * "3,7-9" as the set {3, 7, 8, 9}. Spaces are allowed, a reversed range
     * counts the same as the right way round, and anything that is not a
     * number or a range is ignored rather than thrown on: a wrong highlight
     * is a wrong picture, never a broken page.
     *
     * @return array<int, true>
     */
    public static function ranges(string|array|int|null $spec): array
    {
        if ($spec === null || $spec === '' || $spec === []) {
            return [];
        }

        $parts = is_array($spec) ? $spec : explode(',', (string) $spec);
        $set = [];

        foreach ($parts as $part) {
            // Lines count from 1: a 0 or a negative names no line.
            if (is_int($part)) {
                if ($part >= 1) {
                    $set[$part] = true;
                }

                continue;
            }
            if (! preg_match('/^\s*(\d+)\s*(?:-\s*(\d+)\s*)?$/', (string) $part, $m)) {
                continue;
            }
            $from = (int) $m[1];
            $to = isset($m[2]) ? (int) $m[2] : $from;
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            $from = max(1, $from);
            // A range of a million lines in a typo would otherwise be a million
            // array entries for a block of twenty.
            for ($n = $from; $n <= min($to, $from + 10000); $n++) {
                $set[$n] = true;
            }
        }

        return $set;
    }

    private static function unwrap(string $html): string
    {
        $html = trim($html, "\r\n");

        if (preg_match('/^\s*<pre\b[^>]*>(.*)<\/pre>\s*$/is', $html, $m)) {
            $html = $m[1];
        }
        if (preg_match('/^\s*<code\b[^>]*>(.*)<\/code>\s*$/is', $html, $m)) {
            $html = $m[1];
        }

        return $html;
    }

    private static function chomp(string $code): string
    {
        return preg_replace('/(\r\n|\r|\n)$/', '', $code, 1);
    }
}
