<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Support;

/**
 * The structure of a ds::tree, written into markup that is already rendered.
 *
 * A component's slot renders before the component does, so a tree item does
 * not know, while it renders, how deep it is or how many siblings it has.
 * Its parent does, once its own slot is in hand. So every row is rendered at
 * level 1, one of one, and each parent rewrites the rows it holds: its
 * direct children (still at level 1 in its slot) are numbered among
 * themselves, and every row below it goes one level down. The tree's root
 * numbers the top level and moves nothing. By the time the root has
 * rendered, every row says where it is.
 *
 * The rows write those three attributes together and in this order, which is
 * what lets a parent find them: see tree-item.blade.php. TreeTest holds it.
 */
final class Tree
{
    private const PLACE = '/aria-level="(\d+)" aria-setsize="(\d+)" aria-posinset="(\d+)"/';

    /** A parent's children: numbered among themselves, and everything one level down. */
    public static function nest(string $html): string
    {
        return self::number($html, 1);
    }

    /** The root's top level: numbered, and nothing moved. */
    public static function top(string $html): string
    {
        return self::number($html, 0);
    }

    /**
     * The row that holds the tree's one tab stop before the script runs: the
     * current page, else the selected item, else the first row. A row that is
     * a link needs no stop of its own, links being focusable already.
     */
    public static function stop(string $html): string
    {
        $rows = '/<(?:div|a) role="treeitem"[^>]*>/';

        foreach (['aria-current="page"', 'aria-selected="true"', ''] as $want) {
            $done = false;
            $html = preg_replace_callback($rows, function (array $m) use ($want, &$done) {
                if ($done || ($want !== '' && ! str_contains($m[0], $want))) {
                    return $m[0];
                }
                $done = true;

                return str_replace('tabindex="-1"', 'tabindex="0"', $m[0]);
            }, $html);

            if ($done) {
                return $html;
            }
        }

        return $html;
    }

    /** The value of the selected row in a single-selection tree, or null. */
    public static function selected(string $html): ?string
    {
        if (! preg_match('/data-ds-tree-value="([^"]*)" '.substr(self::PLACE, 1, -1).' aria-selected="true"/', $html, $m)) {
            return null;
        }

        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * What a parent's box shows in a checkbox tree, from the leaves below it:
     * true when every leaf is checked, false when none is, mixed between.
     * Null when it has no leaves at all, and so is a leaf itself.
     */
    public static function checked(string $html): ?string
    {
        $total = preg_match_all('/data-ds-tree-check="leaf(?:-disabled)?"/', $html);
        if ($total === 0) {
            return null;
        }
        $on = preg_match_all('/data-ds-tree-check="leaf(?:-disabled)?" aria-checked="true"/', $html);

        return match (true) {
            $on === 0 => 'false',
            $on === $total => 'true',
            default => 'mixed',
        };
    }

    /**
     * A checked parent's leaves, all checked: their box and the hidden input
     * that posts them. Disabled leaves keep what they have, as a disabled
     * checkbox keeps its state when the reader presses everything around it.
     */
    public static function checkAll(string $html): string
    {
        return str_replace(
            ['data-ds-tree-check="leaf" aria-checked="false"', 'data-ds-tree-input disabled'],
            ['data-ds-tree-check="leaf" aria-checked="true"', 'data-ds-tree-input'],
            $html,
        );
    }

    /**
     * Every parent's box in a checkbox tree, from the leaves under it, for
     * the whole tree at once. Done at the root, where every row knows its
     * level: a parent's leaves are the rows after it that are deeper, up to
     * the next row that is not. A parent cannot settle this for itself,
     * because a checked ancestor may check its leaves after it rendered.
     */
    public static function derive(string $html): string
    {
        if (! str_contains($html, 'data-ds-tree-check="parent"')) {
            return $html;
        }

        preg_match_all('/<(?:div|a) role="treeitem"[^>]*>/', $html, $found, PREG_OFFSET_CAPTURE);
        $rows = array_map(function (array $m) {
            preg_match('/aria-level="(\d+)"/', $m[0], $level);
            preg_match('/data-ds-tree-check="([a-z-]+)" aria-checked="(true|false|mixed)"/', $m[0], $check);

            return ['tag' => $m[0], 'at' => $m[1], 'level' => (int) ($level[1] ?? 1), 'kind' => $check[1] ?? null, 'checked' => $check[2] ?? null];
        }, $found[0]);

        $states = [];
        foreach ($rows as $i => $row) {
            if ($row['kind'] !== 'parent') {
                continue;
            }
            $total = $on = 0;
            for ($j = $i + 1; $j < count($rows) && $rows[$j]['level'] > $row['level']; $j++) {
                if (str_starts_with((string) $rows[$j]['kind'], 'leaf')) {
                    $total++;
                    $on += $rows[$j]['checked'] === 'true' ? 1 : 0;
                }
            }
            if ($total > 0) {
                $states[$i] = $on === 0 ? 'false' : ($on === $total ? 'true' : 'mixed');
            }
        }

        // From the end, so the offsets before each change still hold.
        foreach (array_reverse($states, true) as $i => $state) {
            $tag = preg_replace('/(data-ds-tree-check="parent") aria-checked="[a-z]+"/', '$1 aria-checked="'.$state.'"', $rows[$i]['tag']);
            $html = substr_replace($html, $tag, $rows[$i]['at'], strlen($rows[$i]['tag']));
        }

        return $html;
    }

    private static function number(string $html, int $down): string
    {
        preg_match_all(self::PLACE, $html, $all);
        $size = count(array_filter($all[1], fn (string $level) => $level === '1'));
        $at = 0;

        return preg_replace_callback(self::PLACE, function (array $m) use ($down, $size, &$at) {
            $level = (int) $m[1];
            [$setsize, $posinset] = $level === 1 ? [$size, ++$at] : [$m[2], $m[3]];

            return sprintf('aria-level="%d" aria-setsize="%d" aria-posinset="%d"', $level + $down, $setsize, $posinset);
        }, $html);
    }
}
