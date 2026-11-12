<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * ds::mention-input's text on the server: what a chosen row writes into the
 * field, and which rows a value already mentions, so the page renders
 * `name_mentions[]` for a value it is given back (old input after a failed
 * validation, a saved draft).
 *
 * dsMention does the same in the browser (behaviours/mention.js): the same
 * template and the same rule for "still there". MentionTest holds this side.
 */
final class Mention
{
    /**
     * The text a row inserts. In the template, "@" is the trigger that was
     * typed, "label" the row's label and "id" its id, replaced in one pass:
     * "@label" writes "@Maya Patel", "@[label](id)" writes "@[Maya Patel](42)",
     * a token a server can parse back.
     */
    public static function token(string $template, string $trigger, string $label, string $id): string
    {
        return preg_replace_callback('/@|\blabel\b|\bid\b/', fn ($m) => match ($m[0]) {
            '@' => $trigger,
            'label' => $label,
            default => $id,
        }, $template);
    }

    /**
     * Whether `text` stands in `value` as a whole: not inside a longer word
     * at either end. "@Maya" is in "Hi @Maya, …" and not in "@Mayan".
     */
    public static function present(string $value, string $text): bool
    {
        return $text !== '' && preg_match('/(?<![\p{L}\p{N}_])'.preg_quote($text, '/').'(?![\p{L}\p{N}_])/u', $value) === 1;
    }

    /**
     * The rows `value` mentions, as [id, text] in the order they first
     * appear, each id once. Longer texts are matched first and taken out, so
     * "@Maya Patel" is not also "@Maya".
     *
     * @param  array<string, list<array{id: string, label: string}>>  $lists  trigger => rows
     * @return list<array{id: string, text: string}>
     */
    public static function found(string $value, array $lists, string $template): array
    {
        $candidates = [];
        foreach ($lists as $trigger => $rows) {
            foreach ($rows as $row) {
                $candidates[] = ['id' => $row['id'], 'text' => self::token($template, (string) $trigger, $row['label'], $row['id'])];
            }
        }
        usort($candidates, fn ($a, $b) => mb_strlen($b['text']) <=> mb_strlen($a['text']));

        $hits = [];
        foreach ($candidates as $c) {
            $pattern = '/(?<![\p{L}\p{N}_])'.preg_quote($c['text'], '/').'(?![\p{L}\p{N}_])/u';
            if (preg_match($pattern, $value, $m, PREG_OFFSET_CAPTURE) === 1) {
                $hits[] = $c + ['at' => $m[0][1]];
                $value = preg_replace($pattern, str_repeat("\0", strlen($c['text'])), $value);
            }
        }
        usort($hits, fn ($a, $b) => $a['at'] <=> $b['at']);

        $seen = [];
        $out = [];
        foreach ($hits as $hit) {
            if (! isset($seen[$hit['id']])) {
                $seen[$hit['id']] = true;
                $out[] = ['id' => $hit['id'], 'text' => $hit['text']];
            }
        }

        return $out;
    }
}
