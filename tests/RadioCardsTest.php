<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * What radio cards render with no script, which is all they have: one radio
 * per card, named by its title and meta, and the group's binding and
 * `required` on every radio. Choosing and the keyboard are in
 * test-behaviour.mjs.
 */
final class RadioCardsTest extends TestCase
{
    private function xpath(string $blade): \DOMXPath
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.Blade::render($blade));
        libxml_clear_errors();

        return new \DOMXPath($document);
    }

    private const PLANS = <<<'BLADE'
        <x-ds::radio-cards name="plan" label="Plan" help="Change at any time." :columns="3" class="mt-4">
            <x-ds::radio-card value="free" title="Free" />
            <x-ds::radio-card value="pro" title="Pro" description="For a small team." :checked="true">
                <x-slot:meta>$12 a month</x-slot:meta>
                Ten seats.
            </x-ds::radio-card>
            <x-ds::radio-card value="scale" title="Scale" :disabled="true" />
        </x-ds::radio-cards>
        BLADE;

    #[Test]
    public function every_card_is_a_label_around_one_radio_of_the_group(): void
    {
        $x = $this->xpath(self::PLANS);

        $radios = $x->query('//fieldset//label/span/input[@type="radio"]');
        $this->assertSame(3, $radios->length);
        foreach ($radios as $radio) {
            $this->assertSame('plan', $radio->getAttribute('name'));
            $this->assertFalse($radio->hasAttribute('data-ds-radio-card-input'), 'the hook was left in the page');
            $this->assertSame($radio->getAttribute('id'), $radio->parentNode->parentNode->getAttribute('for'));
        }
        $this->assertSame(['free', 'pro', 'scale'], array_map(fn ($r) => $r->getAttribute('value'), iterator_to_array($radios)));
        $this->assertTrue($radios->item(1)->hasAttribute('checked'));
        $this->assertTrue($radios->item(2)->hasAttribute('disabled'));
    }

    #[Test]
    public function a_card_is_named_by_title_and_meta_and_described_by_the_rest(): void
    {
        $x = $this->xpath(self::PLANS);
        $pro = $x->query('//input[@value="pro"]')->item(0);
        $text = fn (string $ids) => implode(' / ', array_map(
            fn ($id) => trim($x->query("//*[@id=\"{$id}\"]")->item(0)->textContent),
            explode(' ', $ids),
        ));

        $this->assertSame('Pro / $12 a month', $text($pro->getAttribute('aria-labelledby')));
        $this->assertSame('For a small team. / Ten seats.', $text($pro->getAttribute('aria-describedby')));

        $free = $x->query('//input[@value="free"]')->item(0);
        $this->assertSame('Free', $text($free->getAttribute('aria-labelledby')));
        $this->assertFalse($free->hasAttribute('aria-describedby'));
    }

    #[Test]
    public function ids_are_the_same_on_every_render(): void
    {
        $ids = fn () => array_map(fn ($r) => $r->getAttribute('id'), iterator_to_array($this->xpath(self::PLANS)->query('//input')));

        $this->assertSame($ids(), $ids());
        $this->assertCount(3, array_unique($ids()));
    }

    #[Test]
    public function the_group_is_a_named_radiogroup_and_columns_collapse_to_one(): void
    {
        $x = $this->xpath(self::PLANS);
        $group = $x->query('//fieldset')->item(0);

        $this->assertSame('radiogroup', $group->getAttribute('role'));
        $this->assertSame('Plan', trim($x->query('//*[@id="'.$group->getAttribute('aria-labelledby').'"]')->item(0)->textContent));
        $this->assertSame('Change at any time.', trim($x->query('//*[@id="'.$group->getAttribute('aria-describedby').'"]')->item(0)->textContent));
        $this->assertStringContainsString('mt-4', $group->getAttribute('class'));

        $grid = $x->query('//fieldset/div')->item(0)->getAttribute('class');
        $this->assertStringContainsString('grid-cols-1 sm:grid-cols-2 lg:grid-cols-3', $grid);
    }

    #[Test]
    public function the_binding_and_required_reach_every_radio_and_an_error_marks_the_group(): void
    {
        $x = $this->xpath(<<<'BLADE'
            <x-ds::radio-cards name="ship" label="Shipping" error="Choose one." required wire:model.live="ship">
                <x-ds::radio-card value="standard" title="Standard" />
                <x-ds::radio-card value="express" title="Express" />
            </x-ds::radio-cards>
            BLADE);

        foreach ($x->query('//input') as $radio) {
            $this->assertSame('ship', $radio->getAttribute('wire:model.live'));
            $this->assertTrue($radio->hasAttribute('required'));
        }

        $group = $x->query('//fieldset')->item(0);
        $this->assertFalse($group->hasAttribute('wire:model.live'));
        $this->assertFalse($group->hasAttribute('required'));
        $this->assertSame('true', $group->getAttribute('aria-invalid'));
        $this->assertSame('true', $group->getAttribute('aria-required'));
        $this->assertSame('Choose one.', trim($x->query('//*[@id="'.$group->getAttribute('aria-describedby').'"]')->item(0)->textContent));
    }

    #[Test]
    public function it_refuses_columns_out_of_range_and_a_card_with_no_name(): void
    {
        foreach ([
            '<x-ds::radio-cards name="p" label="P" :columns="5" />' => 'out of range',
            '<x-ds::radio-card value="a" title="A" />' => 'needs a `name`',
        ] as $blade => $message) {
            try {
                Blade::render($blade);
                $this->fail("{$blade} rendered");
            } catch (\Throwable $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }
}
