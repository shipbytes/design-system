<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Shipbytes\BladeUi\Support\Mention;

final class MentionTest extends TestCase
{
    private const PEOPLE = [
        ['id' => '1', 'label' => 'Maya'],
        ['id' => '2', 'label' => 'Maya Patel'],
        ['id' => '3', 'label' => 'Ade'],
    ];

    #[Test]
    public function the_template_is_replaced_in_one_pass(): void
    {
        $this->assertSame('@Maya Patel', Mention::token('@label', '@', 'Maya Patel', '2'));
        $this->assertSame('#[Fix login](42)', Mention::token('@[label](id)', '#', 'Fix login', '42'));
        // A label holding the words is not replaced again.
        $this->assertSame('@id label', Mention::token('@label', '@', 'id label', '9'));
    }

    #[Test]
    public function a_mention_is_there_only_as_a_whole(): void
    {
        $this->assertTrue(Mention::present('Hi @Maya, thanks', '@Maya'));
        $this->assertFalse(Mention::present('Hi @Mayan', '@Maya'));
        $this->assertFalse(Mention::present('mail x@Maya', '@Maya'));
        $this->assertTrue(Mention::present('(@[Maya](1))', '@[Maya](1)'));
    }

    #[Test]
    public function the_longest_match_wins_and_each_id_is_listed_once(): void
    {
        $found = Mention::found('@Maya Patel and @Ade, again @Ade', ['@' => self::PEOPLE], '@label');
        $this->assertSame([['id' => '2', 'text' => '@Maya Patel'], ['id' => '3', 'text' => '@Ade']], $found);

        $this->assertSame([['id' => '1', 'text' => '@[Maya](1)']], Mention::found('cc @[Maya](1)', ['@' => self::PEOPLE], '@[label](id)'));
        $this->assertSame([], Mention::found('@Mayan', ['@' => self::PEOPLE], '@label'));
    }

    #[Test]
    public function the_view_posts_the_ids_it_finds_beside_the_text(): void
    {
        $html = Blade::render('<x-ds::mention-input name="body" label="Comment" value="Thanks @Maya Patel" :items="$people" />', ['people' => self::PEOPLE]);
        $this->assertMatchesRegularExpression('/<input type="hidden" name="body_mentions\[\]" value="2" data-ds-mention-text="@Maya Patel"/', $html);
        $this->assertStringContainsString('>Thanks @Maya Patel</textarea>', $html);

        $html = Blade::render('<x-ds::mention-input name="post[body]" :items="$people" value="@Ade" :multiline="false" aria-label="Body" />', ['people' => self::PEOPLE]);
        $this->assertStringContainsString('name="post[body_mentions][]" value="3"', $html);
        $this->assertMatchesRegularExpression('/<input[^>]+name="post\[body\]" type="text" value="@Ade"/', $html);
    }
}
