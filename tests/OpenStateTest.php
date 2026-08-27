<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Throwable;
use Shipbytes\BladeUi\Support\OpenState;

/**
 * The `open` prop on modal, drawer and sheet is ASSIGNED to, not just read.
 *
 * A consumer read the old docblock's "Alpine expression" literally and drove
 * three drawers from one variable — `open="side === 'right'"`. The drawer
 * opened, and the close button, the backdrop and Escape all did nothing:
 * `side === 'right' = false` is not an assignment. HTTP 200, clean server log,
 * one "Invalid left-hand side in assignment" in a console nobody had open.
 *
 * These check both directions. The rejections matter; the ACCEPTANCES matter
 * more, because a guard that starts refusing valid expressions is worse than
 * the bug it replaced — the bug at least rendered.
 */
final class OpenStateTest extends TestCase
{
    public static function notAssignable(): array
    {
        return [
            'the reported case' => ["side === 'right'", '==='],
            'loose equality' => ["mode == 'filters'", '=='],
            'negated' => ["mode !== 'x'", '!=='],
            'loose negated' => ["mode != 'x'", '!='],
            'and' => ['a && b', '&&'],
            'or' => ['a || b', '||'],
            'less than' => ['count < 3', '<'],
            'greater than' => ['count > 3', '>'],
            'the operator inside a longer expression' => ["tab === 'one' && ready", '==='],
        ];
    }

    #[Test]
    #[DataProvider('notAssignable')]
    public function it_rejects_an_expression_that_cannot_be_assigned_to(string $open, string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);
        // The operator is named because "not assignable" on its own sends the
        // reader looking at the whole expression instead of the one token.
        $this->expectExceptionMessageMatches('/'.preg_quote($operator, '/').'/');

        OpenState::assertAssignable($open, 'ds::drawer');
    }

    public static function assignable(): array
    {
        return [
            'a plain property' => ['filtersOpen'],
            'a nested one' => ['show.right'],
            'bracket access' => ["panels['filters']"],
            // The false positive worth naming: `>` inside a string is part of a
            // key, not a comparison, and rejecting it would be the guard doing
            // more harm than the bug.
            'an operator inside a string key' => ["panels['a>b']"],
            'a double-quoted key' => ['panels["a && b"]'],
            'an index' => ['open[2]'],
            '$store' => ['$store.ui.drawerOpen'],
        ];
    }

    #[Test]
    #[DataProvider('assignable')]
    public function it_accepts_anything_that_can_be_assigned_to(string $open): void
    {
        OpenState::assertAssignable($open, 'ds::drawer');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function an_arrow_function_is_not_reported_as_a_comparison(): void
    {
        // `=>` contains `>`. Saying "you used a greater-than" about an arrow
        // function sends the reader hunting for a comparison that is not there,
        // so it is matched and skipped rather than half-recognised.
        OpenState::assertAssignable('items.find(i => i.open)', 'ds::drawer');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function the_guard_is_wired_into_all_three_components(): void
    {
        // The class being correct is worth nothing if a component forgets to
        // call it, which is the same class of omission as the missing gallery
        // sections. So this renders each one rather than trusting the source.
        foreach (['modal', 'drawer', 'sheet'] as $component) {
            $message = null;

            try {
                Blade::render('<x-ds::'.$component.' open="mode === \'x\'" title="t">body</x-ds::'.$component.'>');
            } catch (Throwable $e) {
                // Not InvalidArgumentException: Laravel's view engine catches
                // whatever a view throws and rethrows it as a ViewException,
                // carrying the original message. The message is what the reader
                // sees, so the message is what is asserted.
                //
                // Caught OUTSIDE the assertions on purpose — PHPUnit signals a
                // failed assertion by throwing, so asserting inside the catch
                // would have this block swallow its own failure report.
                $message = $e->getMessage();
            }

            $this->assertNotNull(
                $message,
                "ds::{$component} rendered a non-assignable `open` without complaining",
            );
            $this->assertStringContainsString('is not assignable', $message);
            $this->assertStringContainsString("ds::{$component}", $message);
        }
    }

    #[Test]
    public function the_working_form_still_renders(): void
    {
        // The other half. A guard that rejected everything would pass every test
        // above.
        $html = Blade::render('<x-ds::drawer open="show.right" title="Filters">body</x-ds::drawer>');

        $this->assertStringContainsString('show.right = false', $html);
    }
}
