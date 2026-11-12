<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

/**
 * What an avatar group says in place of the faces it draws, and how it
 * counts the ones it does not.
 */
final class AvatarGroupTest extends TestCase
{
    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<!doctype html><meta charset="utf-8">'.$html);
        libxml_clear_errors();

        return new \DOMXPath($document);
    }

    #[Test]
    public function the_avatar_group_speaks_one_sentence_and_hides_the_faces(): void
    {
        $html = Blade::render(
            '<x-ds::avatar-group label="Members" :max="2" :avatars="$a" />',
            ['a' => [['name' => 'Ada Lovelace'], ['name' => 'Grace Hopper'], ['name' => 'Alan Turing']]],
        );
        $x = $this->xpath($html);

        $group = $x->query('//*[@role="group"]')->item(0);
        $this->assertSame('Members', $group->getAttribute('aria-label'));
        $this->assertSame('Ada Lovelace, Grace Hopper, and 1 more', trim($x->query('//*[contains(@class, "sr-only")]')->item(0)->textContent));

        // Two avatars and the chip, all hidden: the sentence is the only thing read.
        $this->assertSame(3, $x->query('//*[@role="group"]/*[@aria-hidden="true"]')->length);
        $this->assertStringContainsString('+1', $html);
    }

    #[Test]
    public function total_counts_people_the_host_did_not_pass(): void
    {
        $html = Blade::render(
            '<x-ds::avatar-group :total="40" :avatars="$a" />',
            ['a' => [['name' => 'Ada Lovelace'], ['name' => 'Grace Hopper']]],
        );

        $this->assertStringContainsString('+38', $html);
        $this->assertStringContainsString('Ada Lovelace, Grace Hopper, and 38 more', $html);
    }

    #[Test]
    public function a_linked_chip_names_itself_and_leaves_the_sentence(): void
    {
        $html = Blade::render(
            '<x-ds::avatar-group :max="1" href="/team" :avatars="$a" />',
            ['a' => [['name' => 'Ada Lovelace'], ['name' => 'Grace Hopper']]],
        );
        $x = $this->xpath($html);

        $link = $x->query('//a[@href="/team"]')->item(0);
        $this->assertSame('1 more', $link->getAttribute('aria-label'));
        $this->assertSame('Ada Lovelace', trim($x->query('//*[contains(@class, "sr-only")]')->item(0)->textContent));
    }
}
