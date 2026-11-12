<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * The chips the server renders, what they post, and the props it refuses.
 * Adding and removing is in test-behaviour.mjs.
 */
final class TagInputTest extends TestCase
{
    private function xpath(string $blade): \DOMXPath
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.Blade::render($blade));
        libxml_clear_errors();

        return new \DOMXPath($document);
    }

    #[Test]
    public function each_chip_posts_its_own_value_and_names_its_remove_button(): void
    {
        $x = $this->xpath('<x-ds::tag-input name="emails" label="Recipients" :value="[\'maya@example.com\', \' \', \'ada@example.com\']" />');

        $chips = $x->query('//ul[@data-ds-tag-list]/li[@data-ds-tag]');
        $this->assertSame(2, $chips->length, 'a blank value renders no chip');

        $posted = array_map(fn ($i) => $i->getAttribute('value'), iterator_to_array($x->query('//ul//input[@name="emails[]"]')));
        $this->assertSame(['maya@example.com', 'ada@example.com'], $posted);

        $this->assertSame('Remove maya@example.com', $x->query('//ul//button')->item(0)->getAttribute('aria-label'));
        $this->assertSame('button', $x->query('//ul//button')->item(0)->getAttribute('type'));

        $list = $x->query('//ul')->item(0);
        $this->assertSame('Recipients', trim($x->query('//*[@id="'.$list->getAttribute('aria-labelledby').'"]')->item(0)->textContent));
    }

    #[Test]
    public function an_empty_field_hides_its_list_and_posts_nothing(): void
    {
        $x = $this->xpath('<x-ds::tag-input name="tags" />');

        $this->assertTrue($x->query('//ul')->item(0)->hasAttribute('hidden'));
        // The template's input is inert until it is copied into the page.
        $this->assertSame(0, $x->query('//input[@name="tags[]"][not(ancestor::template)]')->length);
        $this->assertSame(1, $x->query('//template//input[@name="tags[]"]')->length);
    }

    #[Test]
    public function the_text_box_is_described_by_the_entry_message_and_the_help(): void
    {
        $x = $this->xpath('<x-ds::tag-input name="emails" validate="email" help="One per line." />');
        $text = $x->query('//input[@data-ds-tag-text]')->item(0);

        $ids = explode(' ', $text->getAttribute('aria-describedby'));
        $this->assertCount(2, $ids);
        $this->assertTrue($x->query('//*[@id="'.$ids[0].'"]')->item(0)->hasAttribute('aria-live'));
        $this->assertSame('One per line.', trim($x->query('//*[@id="'.$ids[1].'"]')->item(0)->textContent));
        $this->assertSame('email', $text->getAttribute('inputmode'));
        $this->assertFalse($text->hasAttribute('name'), 'the text box posts nothing; the chips do');
    }

    #[Test]
    public function disabled_disables_every_part_including_what_posts(): void
    {
        $x = $this->xpath('<x-ds::tag-input name="tags" :value="[\'a\', \'b\']" :disabled="true" />');

        $this->assertSame(5, $x->query('//*[@disabled][not(ancestor::template)]')->length);
    }

    #[Test]
    public function an_unknown_check_is_refused_rather_than_ignored(): void
    {
        $this->expectExceptionMessage("validate=\"emial\" is not a check it knows");
        Blade::render('<x-ds::tag-input name="t" validate="emial" />');
    }

    #[Test]
    public function a_separator_must_be_enter_or_one_character(): void
    {
        $this->expectExceptionMessage('separator "Space" is neither');
        Blade::render('<x-ds::tag-input name="t" :separators="[\'Space\']" />');
    }
}
