<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * Sorting was added to ds::table in 2.0, and every table that does not ask for
 * it must render exactly as it did before: byte for byte, whitespace included.
 *
 * The fixture was written from the 1.x view, before `sortable`, `sort`,
 * `direction` or `sortUrl` existed. The cases are every way a table was
 * declared in the specimens, the gallery and the docs at that point: plain
 * labels, arrays, alignment, widths, an empty actions header, no `columns`.
 *
 * One edit since: the row's hover transition moved onto the motion tokens
 * behind motion-safe in 2.0 (the reduced-motion audit), the same 150ms at
 * rest. The fixture follows that class and nothing else; sorting is still
 * what this test guards.
 */
final class TableUnchangedTest extends TestCase
{
    /** @return array<string, string> */
    public static function cases(): array
    {
        return [
            'specimen' => <<<'BLADE'
                <x-ds::table :columns="[
                    'Report',
                    ['label' => 'Status'],
                    ['label' => 'Total', 'align' => 'right'],
                ]">
                    <x-ds::table-row>
                        <x-ds::table-cell>Q3 revenue</x-ds::table-cell>
                        <x-ds::table-cell :nowrap="true"><x-ds::badge tone="success">Ready</x-ds::badge></x-ds::table-cell>
                        <x-ds::table-cell align="right">£1,200</x-ds::table-cell>
                    </x-ds::table-row>
                </x-ds::table>
                BLADE,
            'widths, centre and an empty actions header' => <<<'BLADE'
                <x-ds::table :columns="[
                    'User',
                    ['label' => 'Role', 'width' => 'w-24'],
                    ['label' => 'Projects', 'align' => 'center', 'width' => 'w-24'],
                    ['label' => '', 'width' => 'w-14'],
                ]">
                    <x-ds::table-row :hover="false">
                        <x-ds::table-cell colspan="4" align="center">No users found.</x-ds::table-cell>
                    </x-ds::table-row>
                </x-ds::table>
                BLADE,
            'no columns, own thead' => <<<'BLADE'
                <x-ds::table class="mt-5">
                    <thead class="bg-surface-subtle"><tr><th scope="col">Report</th></tr></thead>
                    <x-ds::table-row><x-ds::table-cell>Q3 revenue</x-ds::table-cell></x-ds::table-row>
                </x-ds::table>
                BLADE,
            // Written without `sortable` for the fixture; now with it off and
            // the new props set, which must change nothing.
            'sortable passed as false' => <<<'BLADE'
                <x-ds::table sort="total" direction="desc" sortUrl="/x?s={key}" :columns="['Report', ['label' => 'Total', 'align' => 'right', 'key' => 'total', 'sortable' => false]]">
                    <x-ds::table-row><x-ds::table-cell>Q3 revenue</x-ds::table-cell></x-ds::table-row>
                </x-ds::table>
                BLADE,
        ];
    }

    #[Test]
    public function every_table_without_a_sortable_column_renders_exactly_as_before(): void
    {
        $fixture = __DIR__.'/Fixtures/table-1x.html';
        $rendered = '';

        foreach (self::cases() as $name => $blade) {
            $rendered .= "<!-- {$name} -->\n".Blade::render($blade)."\n";
        }

        $this->assertSame(file_get_contents($fixture), $rendered);
    }
}
