<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Tree;

/**
 * What a tree renders before any script runs: the structure counted into
 * already-rendered rows (Support\Tree, held to its exact strings, because the
 * rows are found by them), the one tab stop, the selection and what posts.
 * The keys, opening and choosing are in test-behaviour.mjs.
 */
final class TreeTest extends TestCase
{
    private const ROW = 'role="treeitem" data-ds-tree-item data-ds-tree-value="%s" aria-level="1" aria-setsize="1" aria-posinset="1"';

    private function xpath(string $blade): \DOMXPath
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.Blade::render($blade));
        libxml_clear_errors();

        return new \DOMXPath($document);
    }

    /** @return array<string, string> value => "level/setsize/posinset" */
    private function places(\DOMXPath $x): array
    {
        $out = [];
        foreach ($x->query('//*[@role="treeitem"]') as $row) {
            $out[$row->getAttribute('data-ds-tree-value')] = $row->getAttribute('aria-level').'/'.$row->getAttribute('aria-setsize').'/'.$row->getAttribute('aria-posinset');
        }

        return $out;
    }

    #[Test]
    public function nest_numbers_the_direct_children_and_moves_everything_one_level_down(): void
    {
        $html = '<a aria-level="1" aria-setsize="1" aria-posinset="1">'
            .'<b aria-level="2" aria-setsize="3" aria-posinset="2">'
            .'<c aria-level="1" aria-setsize="1" aria-posinset="1">';

        $this->assertSame(
            '<a aria-level="2" aria-setsize="2" aria-posinset="1">'
            .'<b aria-level="3" aria-setsize="3" aria-posinset="2">'
            .'<c aria-level="2" aria-setsize="2" aria-posinset="2">',
            Tree::nest($html),
        );
    }

    #[Test]
    public function top_numbers_the_first_level_and_moves_nothing(): void
    {
        $this->assertSame(
            '<a aria-level="1" aria-setsize="2" aria-posinset="1"><b aria-level="2" aria-setsize="1" aria-posinset="1"><c aria-level="1" aria-setsize="2" aria-posinset="2">',
            Tree::top('<a aria-level="1" aria-setsize="1" aria-posinset="1"><b aria-level="2" aria-setsize="1" aria-posinset="1"><c aria-level="1" aria-setsize="1" aria-posinset="1">'),
        );
    }

    #[Test]
    public function the_stop_goes_to_the_current_page_then_the_selection_then_the_first_row(): void
    {
        $row = fn (string $extra) => '<div role="treeitem" '.$extra.' tabindex="-1">';

        $this->assertSame(
            $row('a').'<div role="treeitem" aria-selected="true" tabindex="0">',
            Tree::stop($row('a').$row('aria-selected="true"')),
        );
        $this->assertSame(
            '<div role="treeitem" a tabindex="0">'.$row('b'),
            Tree::stop($row('a').$row('b')),
        );
        // A current page that is a link has no tabindex of its own to change.
        $this->assertSame(
            '<a role="treeitem" aria-current="page">'.$row('b'),
            Tree::stop('<a role="treeitem" aria-current="page">'.$row('b')),
        );
    }

    #[Test]
    public function selected_reads_the_value_off_the_selected_row(): void
    {
        $html = sprintf(self::ROW, 'a').' aria-selected="false"'.sprintf(self::ROW, 'b &amp; c').' aria-selected="true"';

        $this->assertSame('b & c', Tree::selected($html));
        $this->assertNull(Tree::selected(sprintf(self::ROW, 'a').' aria-selected="false"'));
    }

    #[Test]
    public function checked_is_all_some_or_none_of_the_leaves(): void
    {
        $leaf = fn (string $on, string $kind = 'leaf') => 'data-ds-tree-check="'.$kind.'" aria-checked="'.$on.'"';

        $this->assertSame('true', Tree::checked($leaf('true').$leaf('true')));
        $this->assertSame('mixed', Tree::checked($leaf('true').$leaf('false', 'leaf-disabled')));
        $this->assertSame('false', Tree::checked($leaf('false')));
        $this->assertNull(Tree::checked('no leaves here'));
    }

    #[Test]
    public function check_all_checks_and_enables_the_enabled_leaves_only(): void
    {
        $this->assertSame(
            'data-ds-tree-check="leaf" aria-checked="true" data-ds-tree-input data-ds-tree-check="leaf-disabled" aria-checked="false"',
            Tree::checkAll('data-ds-tree-check="leaf" aria-checked="false" data-ds-tree-input disabled data-ds-tree-check="leaf-disabled" aria-checked="false"'),
        );
    }

    #[Test]
    public function every_row_says_where_it_is_before_any_script_runs(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::tree label="Files">
                <x-ds::tree-item label="app">
                    <x-ds::tree-item label="Http">
                        <x-ds::tree-item label="Kernel.php" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="helpers.php" />
                </x-ds::tree-item>
                <x-ds::tree-item label="composer.json" />
            </x-ds::tree>
            BLADE);

        $this->assertSame(
            ['app' => '1/2/1', 'Http' => '2/2/1', 'Kernel.php' => '3/1/1', 'helpers.php' => '2/2/2', 'composer.json' => '1/2/2'],
            $this->places($x),
        );
        $this->assertSame('Files', $x->query('//ul[@role="tree"]')->item(0)->getAttribute('aria-label'));
        $this->assertSame(2, $x->query('//ul[@role="group"]')->length);
        $this->assertSame(['app'], array_map(fn ($r) => $r->getAttribute('data-ds-tree-value'), iterator_to_array($x->query('//*[@role="treeitem"][@tabindex="0"]'))));
        $this->assertSame(['none'], array_values(array_unique(array_map(fn ($li) => $li->getAttribute('role'), iterator_to_array($x->query('//li'))))));
    }

    #[Test]
    public function a_navigation_tree_opens_down_to_the_current_page(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::tree label="Docs">
                <x-ds::tree-item label="Guides" href="/guides">
                    <x-ds::tree-item label="Install" href="/install" :selected="true" />
                </x-ds::tree-item>
                <x-ds::tree-item label="Reference" href="/reference">
                    <x-ds::tree-item label="Props" href="/props" />
                </x-ds::tree-item>
            </x-ds::tree>
            BLADE);

        $guides = $x->query('//a[@data-ds-tree-value="Guides"]')->item(0);
        $this->assertSame('true', $guides->getAttribute('aria-expanded'));
        $this->assertSame('false', $x->query('//a[@data-ds-tree-value="Reference"]')->item(0)->getAttribute('aria-expanded'));
        $this->assertSame('page', $x->query('//a[@data-ds-tree-value="Install"]')->item(0)->getAttribute('aria-current'));
        $this->assertSame(1, $x->query('//ul[@role="group"][@hidden]')->length);
        $this->assertSame(0, $x->query('//a[@tabindex]')->length, 'links keep their own Tab stops until the script runs');
    }

    #[Test]
    public function a_single_selection_posts_its_value_and_opens_around_it(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::tree label="Folder" selectable="single" name="folder">
                <x-ds::tree-item label="Inbox">
                    <x-ds::tree-item label="Receipts" value="r&d" :selected="true" />
                </x-ds::tree-item>
                <x-ds::tree-item label="Archive" :selected="true" :disabled="true" />
            </x-ds::tree>
            BLADE);

        $input = $x->query('//input[@name="folder"]')->item(0);
        $this->assertSame('r&d', $input->getAttribute('value'));
        $this->assertSame('true', $x->query('//*[@data-ds-tree-value="Inbox"]')->item(0)->getAttribute('aria-expanded'));
        $this->assertSame('false', $x->query('//*[@data-ds-tree-value="Archive"]')->item(0)->getAttribute('aria-selected'), 'a disabled row is never selected');
        $this->assertSame('0', $x->query('//*[@data-ds-tree-value="r&d"]')->item(0)->getAttribute('tabindex'));
    }

    #[Test]
    public function a_checkbox_tree_works_out_its_parents_and_posts_only_checked_enabled_leaves(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::tree label="Perms" selectable="multiple" name="perms">
                <x-ds::tree-item label="Billing" value="billing">
                    <x-ds::tree-item label="View" value="view" :selected="true" />
                    <x-ds::tree-item label="Void" value="void" :disabled="true" />
                </x-ds::tree-item>
                <x-ds::tree-item label="All" value="all" :selected="true">
                    <x-ds::tree-item label="Group" value="group">
                        <x-ds::tree-item label="Deep" value="deep" :selected="false" />
                    </x-ds::tree-item>
                </x-ds::tree-item>
            </x-ds::tree>
            BLADE);

        $state = fn (string $v) => $x->query("//*[@data-ds-tree-value=\"{$v}\"]")->item(0)->getAttribute('aria-checked');
        $this->assertSame(['mixed', 'true', 'true', 'true'], [$state('billing'), $state('all'), $state('group'), $state('deep')]);
        $this->assertSame('true', $x->query('//ul[@role="tree"]')->item(0)->getAttribute('aria-multiselectable'));

        $posting = array_map(fn ($i) => $i->getAttribute('value'), iterator_to_array($x->query('//input[@name="perms[]"][not(@disabled)]')));
        $this->assertSame(['view', 'deep'], $posting);
        $this->assertSame(0, $x->query('//input[@value="void"]')->length, 'a disabled leaf has no input at all');
    }

    #[Test]
    public function a_bound_tree_puts_the_model_on_the_root_beside_x_modelable(): void
    {
        $html = Blade::render('<x-ds::tree label="T" selectable="multiple" name="t" x-model="picked"><x-ds::tree-item label="a" /></x-ds::tree>');

        $this->assertMatchesRegularExpression('/<div\s+x-data="[^"]*dsTree[^"]*"\s+x-modelable="dsValue" x-model="picked"/', $html);
    }

    #[Test]
    public function it_refuses_a_link_in_a_selectable_tree_and_a_model_with_nothing_to_choose(): void
    {
        foreach ([
            ['<x-ds::tree label="T" selectable="single"><x-ds::tree-item label="a" href="/a" /></x-ds::tree>', 'has an `href` in a tree that is selectable'],
            ['<x-ds::tree label="T" wire:model="x"><x-ds::tree-item label="a" /></x-ds::tree>', 'nothing to choose'],
        ] as [$blade, $says]) {
            try {
                Blade::render($blade);
                $this->fail("{$blade} rendered");
            } catch (\Throwable $e) {
                $this->assertStringContainsString($says, $e->getMessage());
            }
        }
    }
}
