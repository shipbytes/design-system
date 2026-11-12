<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Code;

/**
 * ds::code-block's lines (Support\Code, held to exact strings) and what the
 * block renders. Copying, selecting and Show more are in test-behaviour.mjs.
 */
final class CodeTest extends TestCase
{
    #[Test]
    public function lines_are_escaped_and_one_trailing_newline_is_dropped(): void
    {
        $this->assertSame(['&lt;?php', '', '    echo &quot;a&quot;;', ''], Code::lines("<?php\r\n\n    echo \"a\";\n\n"));
    }

    #[Test]
    public function highlighted_html_is_unwrapped_and_balanced_per_line(): void
    {
        $html = '<pre class="phiki"><code><span class="c">/* one'."\n".'two */</span>'."\n".'<span class="k">x</span></code></pre>';

        $this->assertSame(
            ['<span class="c">/* one</span>', '<span class="c">two */</span>', '<span class="k">x</span>'],
            Code::htmlLines($html),
        );
    }

    #[Test]
    public function text_is_the_code_as_written(): void
    {
        $this->assertSame('if (a < b) {}', Code::text('<span class="k">if</span> (a &lt; b) {}'));
    }

    #[Test]
    public function ranges_take_a_list_spans_and_arrays_and_ignore_the_rest(): void
    {
        $this->assertSame([3, 7, 8, 9], array_keys(Code::ranges('3, 7-9')));
        $this->assertSame([4, 2], array_keys(Code::ranges([4, '2'])), 'a set: order does not matter');
        $this->assertSame([5, 7, 8, 9], array_keys(Code::ranges('5,9-7,x,0,-2')), 'a range written backwards still counts; 0 and garbage do not');
        $this->assertSame([], Code::ranges(null));
    }

    #[Test]
    public function the_slot_is_escaped_and_the_scroll_box_is_a_named_ltr_region(): void
    {
        $html = Blade::render('<x-ds::code-block language="HTML" :copy="false"><b>hi</b></x-ds::code-block>');

        $this->assertStringContainsString('&lt;b&gt;hi&lt;/b&gt;', $html);
        $this->assertMatchesRegularExpression('/<pre id="[^"]+" role="region" aria-label="HTML code" tabindex="0" dir="ltr"/', $html);
        $this->assertStringContainsString('<code', $html);
    }

    #[Test]
    public function highlighted_lines_are_marks_and_an_empty_line_holds_a_break(): void
    {
        $html = Blade::render('<x-ds::code-block :copy="false" highlight="2" :code="\'a\' . PHP_EOL . PHP_EOL . \'c\'" />');

        $this->assertMatchesRegularExpression('/<span class="[^"]*">a<\/span>\n<mark class="[^"]*"><br><\/mark>\n<span class="[^"]*">c<\/span>/', $html);
    }

    #[Test]
    public function line_numbers_are_unselectable_css_counters_and_never_text(): void
    {
        $html = Blade::render('<x-ds::code-block :copy="false" :lineNumbers="true" :code="\'a\'" />');

        $this->assertStringContainsString('before:select-none', $html);
        $this->assertStringContainsString('before:content-[counter(ds-line)]', $html);
        $this->assertDoesNotMatchRegularExpression('/>\s*1\s*</', $html);
    }

    #[Test]
    public function expandable_needs_a_limit_and_starts_with_its_button_hidden(): void
    {
        $plain = Blade::render('<x-ds::code-block :expandable="true" :code="\'a\'" />');
        $this->assertStringNotContainsString('dsClamp', $plain, 'no limit, nothing to expand');

        $held = Blade::render('<x-ds::code-block id="cb" :maxHeight="3" :expandable="true" :code="\'a\'" />');
        $this->assertStringContainsString('dsClamp', $held);
        $this->assertMatchesRegularExpression('/hidden="hidden" data-ds-clamp-toggle="data-ds-clamp-toggle" aria-expanded="false" aria-controls="cb-code"/', $held);
    }

    #[Test]
    public function code_and_html_together_throw(): void
    {
        $this->expectExceptionMessage('not both');
        Blade::render('<x-ds::code-block code="a" html="<b>a</b>" />');
    }

    #[Test]
    public function a_collapsible_panel_needs_a_title_and_no_header_slot(): void
    {
        foreach ([
            '<x-ds::panel :collapsible="true">x</x-ds::panel>',
            '<x-ds::panel title="T" :collapsible="true"><x-slot:header>h</x-slot:header>x</x-ds::panel>',
        ] as $blade) {
            try {
                Blade::render($blade);
                $this->fail("{$blade} rendered");
            } catch (\Throwable $e) {
                $this->assertStringContainsString('collapsible', $e->getMessage());
            }
        }
    }
}
