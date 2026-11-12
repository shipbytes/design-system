<?php

declare(strict_types=1);

namespace Shipbytes\BladeUi\Tests;

/**
 * One specimen per component, exercising the props that change what is emitted.
 *
 * This list is the coverage. A variant that is not here is a variant no test
 * renders — so when a component grows a prop, it grows a specimen here in the
 * same commit.
 */
final class Specimens
{
    /** @return array<string, string> name => Blade source */
    public static function all(): array
    {
        return [
            'button/variants' => <<<'BLADE'
                <x-ds::button variant="primary">Save</x-ds::button>
                <x-ds::button variant="secondary">Cancel</x-ds::button>
                <x-ds::button variant="ghost">Dismiss</x-ds::button>
                <x-ds::button variant="danger">Delete</x-ds::button>
                BLADE,
            'button/tones' => <<<'BLADE'
                <x-ds::button variant="ghost" tone="danger">Remove</x-ds::button>
                <x-ds::button variant="secondary" tone="danger">Revoke</x-ds::button>
                <x-ds::button variant="ghost" size="sm" tone="danger">Remove</x-ds::button>
                <x-ds::button variant="primary" tone="danger">Ignored on primary</x-ds::button>
                <x-ds::button variant="ghost" tone="neutral">Neutral</x-ds::button>
                BLADE,
            'button/sizes' => <<<'BLADE'
                <x-ds::button size="sm">Small</x-ds::button>
                <x-ds::button size="md">Medium</x-ds::button>
                <x-ds::button size="lg">Large</x-ds::button>
                <x-ds::button size="fab" iconOnly aria-label="New">
                    <x-ds::icon name="plus" size="6" />
                </x-ds::button>
                BLADE,
            'button/states' => <<<'BLADE'
                <x-ds::button :loading="true">Saving</x-ds::button>
                <x-ds::button :disabled="true">Disabled</x-ds::button>
                <x-ds::button :pill="true">Pill</x-ds::button>
                <x-ds::button href="/somewhere">Link</x-ds::button>
                <x-ds::button iconOnly aria-label="Settings">
                    <x-ds::icon name="cog" />
                </x-ds::button>
                BLADE,

            /*
             * Pagination is a pair of views, not components, so these render
             * them the way Laravel does: through links() on a real paginator.
             * Page 4 of 16 gives every control at once, both arrows, the
             * ellipsis and numbered pages. The Livewire views are here so the
             * marker check covers them like everything else.
             */
            'pagination/links' => <<<'BLADE'
                @php
                    $paginator = new \Illuminate\Pagination\LengthAwarePaginator(range(1, 15), 237, 15, 4, ['path' => '/reports']);
                    $simple = new \Illuminate\Pagination\Paginator(range(1, 16), 15, 2, ['path' => '/reports']);
                @endphp
                {{ $paginator->links('ds::pagination') }}
                {{ $simple->links('ds::simple-pagination') }}
                BLADE,
            'pagination/livewire' => <<<'BLADE'
                @php
                    $paginator = new \Illuminate\Pagination\LengthAwarePaginator(range(1, 15), 237, 15, 4, ['path' => '/reports', 'pageName' => 'reports']);
                    $simple = new \Illuminate\Pagination\Paginator(range(1, 16), 15, 2, ['path' => '/reports']);
                @endphp
                {{ $paginator->links('ds::pagination-livewire') }}
                {{ $paginator->links('ds::pagination-livewire', ['scrollTo' => false]) }}
                {{ $simple->links('ds::simple-pagination-livewire') }}
                BLADE,

            'icon/sizes' => <<<'BLADE'
                <x-ds::icon name="check" size="3" />
                <x-ds::icon name="check" size="4" />
                <x-ds::icon name="check" size="4.5" />
                <x-ds::icon name="check" size="5" />
                <x-ds::icon name="check" size="6" />
                <x-ds::icon name="check" size="8" />
                BLADE,
            'icon/variants' => <<<'BLADE'
                <x-ds::icon name="check-circle" variant="outline" />
                <x-ds::icon name="check-circle" variant="solid" />
                <x-ds::icon name="check-circle" variant="mini" />
                <x-ds::icon name="check-circle" variant="micro" />
                <x-ds::icon name="x" label="Close" />
                BLADE,
            'icon/fallback' => <<<'BLADE'
                <x-ds::icon name="not-a-heroicon" fallback="question-mark-circle" />
                <x-ds::icon name="search" fallback="question-mark-circle" />
                BLADE,

            'badge/tones' => <<<'BLADE'
                <x-ds::badge tone="neutral">Neutral</x-ds::badge>
                <x-ds::badge tone="accent">Accent</x-ds::badge>
                <x-ds::badge tone="success">Success</x-ds::badge>
                <x-ds::badge tone="warning">Warning</x-ds::badge>
                <x-ds::badge tone="danger">Danger</x-ds::badge>
                BLADE,
            'badge/variants' => <<<'BLADE'
                <x-ds::badge variant="tint" tone="success">Tint</x-ds::badge>
                <x-ds::badge variant="solid" tone="success">Solid</x-ds::badge>
                <x-ds::badge variant="solid" tone="warning">Solid</x-ds::badge>
                <x-ds::badge variant="solid" tone="danger">Solid</x-ds::badge>
                <x-ds::badge variant="solid" tone="accent">Solid</x-ds::badge>
                <x-ds::badge variant="solid" tone="neutral">Solid</x-ds::badge>
                <x-ds::badge variant="outline">Outline</x-ds::badge>
                <x-ds::badge :dot="true" tone="success">Running</x-ds::badge>
                BLADE,

            'alert/tones' => <<<'BLADE'
                <x-ds::alert tone="accent" title="Heads up">Something to know.</x-ds::alert>
                <x-ds::alert tone="success" title="Saved">It worked.</x-ds::alert>
                <x-ds::alert tone="warning" title="Careful">This will expire.</x-ds::alert>
                <x-ds::alert tone="danger" title="Failed">It did not work.</x-ds::alert>
                <x-ds::alert>Single line, no title.</x-ds::alert>
                <x-ds::alert tone="warning" :dismissible="true">Dismissible.</x-ds::alert>
                BLADE,

            'input/all' => <<<'BLADE'
                <x-ds::input label="Name" name="name" help="Your full name." />
                <x-ds::input label="Email" name="email" error="That address is not valid." />
                <x-ds::input label="Search" name="q" icon="magnifying-glass" />
                <x-ds::input label="Locked" name="locked" :disabled="true" />
                <x-ds::input label="Email" name="email" value="ada@example.com" readonly />
                <x-ds::input as="textarea" label="Reference" name="ref" readonly>REF-0042</x-ds::input>
                <x-ds::input label="Readonly, invalid" name="ro" readonly error="Still wrong." />
                <x-ds::input as="textarea" label="Notes" name="notes" :rows="4" />
                <x-ds::input as="select" label="Plan" name="plan"><option>Free</option></x-ds::input>
                BLADE,
            'input/reveal' => <<<'BLADE'
                <x-ds::input type="password" name="password" label="Password" :reveal="true" autocomplete="current-password" />
                <x-ds::input type="password" name="password" label="Password" :reveal="true" error="That password is not right." />
                <x-ds::input type="password" name="password" label="Password" :reveal="true" :disabled="true" />
                <x-ds::input type="password" name="password" label="Password" icon="lock-closed" :reveal="true" />
                <x-ds::input type="text" name="name" label="Ignored off a password field" :reveal="true" />
                BLADE,
            'password-strength/all' => <<<'BLADE'
                <x-ds::input type="password" id="new-password" name="password" label="New password" :reveal="true" />
                <x-ds::password-strength for="new-password" />
                <x-ds::password-strength for="new-password" :minLength="12" :labels="['Too weak', 'Weak']" class="max-w-sm" />
                BLADE,

            'otp-input/all' => <<<'BLADE'
                <x-ds::otp-input name="code" label="Verification code" help="We sent it to your phone." />
                <x-ds::otp-input name="code" label="Verification code" value="4821" />
                <x-ds::otp-input name="pin" label="PIN" :length="4" :masked="true" :autofocus="true" />
                <x-ds::otp-input name="ref" label="Reference" :length="8" :numeric="false" value="AB12" />
                <x-ds::otp-input name="code" label="Verification code" value="123456" error="That code has expired." />
                <x-ds::otp-input name="code" label="Verification code" :disabled="true" />
                <x-ds::otp-input name="code" wire:model.live="code" aria-label="Code" />
                BLADE,

            'tag-input/all' => <<<'BLADE'
                <x-ds::tag-input name="tags" label="Tags" placeholder="Add a tag" />
                <x-ds::tag-input name="emails" label="Recipients" :value="['maya@example.com', 'ada@example.com']" validate="email" :max="5" help="Press Enter or comma after each address." />
                <x-ds::tag-input name="emails" label="Recipients" :value="['maya@']" validate="email" error="maya@ is not an email address." />
                <x-ds::tag-input name="tags" label="Tags" :value="['billing']" :separators="[';', 'Enter']" :unique="false" />
                <x-ds::tag-input name="tags" label="Locked" :value="['billing', 'security']" :disabled="true" />
                BLADE,

            'copy-button/all' => <<<'BLADE'
                <x-ds::copy-button value="sk_live_42" />
                <x-ds::copy-button target="api-key" label="Copy key" copiedLabel="Copied to clipboard" variant="primary" />
                <x-ds::copy-button target="#webhook-url" :iconOnly="true" label="Copy webhook URL" variant="ghost" size="sm" />
                <x-ds::copy-button value="x" size="lg" @copy-failed="$dispatch('notify', 'Could not copy')" />
                BLADE,

            'rating/all' => <<<'BLADE'
                <x-ds::rating name="score" label="How was your visit?" help="Optional." />
                <x-ds::rating name="score" label="Quality" :value="4" size="sm" />
                <x-ds::rating name="score" label="Value" :value="2" :max="10" size="lg" required />
                <x-ds::rating name="score" label="Support" error="Choose a rating." />
                <x-ds::rating name="score" label="Locked" :value="3" :disabled="true" />
                <x-ds::rating name="score" wire:model.live="score" aria-label="Score" />
                <x-ds::rating :readonly="true" :value="3.5" label="Average" />
                <x-ds::rating :readonly="true" :value="4.24" size="sm" />
                <x-ds::rating :readonly="true" :value="null" size="lg" />
                BLADE,

            'chip-group/all' => <<<'BLADE'
                <x-ds::chip-group name="status" label="Status">
                    <x-ds::chip value="open" :checked="true" :count="12">Open</x-ds::chip>
                    <x-ds::chip value="closed" :count="4">Closed</x-ds::chip>
                    <x-ds::chip value="spam" :disabled="true">Spam</x-ds::chip>
                </x-ds::chip-group>
                <x-ds::chip-group name="tags" label="Tags" :multiple="true" size="sm" :hideLabel="true" wire:model.live="tags">
                    <x-ds::chip value="billing" icon="credit-card" :checked="true">Billing</x-ds::chip>
                    <x-ds::chip value="bug" icon="bug-ant" :checked="true" :disabled="true">Bug</x-ds::chip>
                    <x-ds::chip value="sales">Sales</x-ds::chip>
                </x-ds::chip-group>
                <x-ds::chip-group label="Show" size="lg">
                    <x-ds::chip href="/tickets" :active="true" :count="16">All</x-ds::chip>
                    <x-ds::chip href="/tickets?mine=1" icon="user">Mine</x-ds::chip>
                    <x-ds::chip href="/tickets?archived=1" :disabled="true">Archived</x-ds::chip>
                </x-ds::chip-group>
                <x-ds::chip-group name="when" label="When" x-model="when">
                    <x-ds::chip value="today">Today</x-ds::chip>
                </x-ds::chip-group>
                BLADE,

            'radio-cards/all' => <<<'BLADE'
                <x-ds::radio-cards name="plan" label="Plan" help="Change or cancel at any time." :columns="3">
                    <x-ds::radio-card value="free" title="Free" description="For trying it out.">
                        <x-slot:meta>$0</x-slot:meta>
                    </x-ds::radio-card>
                    <x-ds::radio-card value="pro" title="Pro" description="For a small team." :checked="true">
                        <x-slot:icon><x-ds::icon name="rocket-launch" /></x-slot:icon>
                        <x-slot:meta>$12 a month</x-slot:meta>
                        Up to ten seats, priority support.
                    </x-ds::radio-card>
                    <x-ds::radio-card value="scale" title="Scale" :disabled="true" />
                </x-ds::radio-cards>
                <x-ds::radio-cards name="shipping" label="Shipping" error="Choose a shipping method." :columns="2" required wire:model.live="shipping">
                    <x-ds::radio-card value="standard" title="Standard" description="3 to 5 working days." />
                    <x-ds::radio-card value="express" title="Express" id="ship-express" />
                </x-ds::radio-cards>
                <x-ds::radio-cards name="size" label="Size" :columns="4">
                    <x-ds::radio-card value="s" title="Small" />
                </x-ds::radio-cards>
                <x-ds::radio-cards name="one" label="One" x-model="one">
                    <x-ds::radio-card value="a" title="A" class="shadow-raised" />
                </x-ds::radio-cards>
                BLADE,

            'segmented-control/all' => <<<'BLADE'
                <x-ds::segmented-control name="view" label="View">
                    <x-ds::segment value="list" icon="list-bullet" :checked="true">List</x-ds::segment>
                    <x-ds::segment value="board" icon="view-columns">Board</x-ds::segment>
                    <x-ds::segment value="archive" :disabled="true">Archive</x-ds::segment>
                </x-ds::segmented-control>
                <x-ds::segmented-control name="period" label="Period" size="sm" :hideLabel="true" :fullWidth="true" x-model="period">
                    <x-ds::segment value="day">Day</x-ds::segment>
                    <x-ds::segment value="week" :checked="true">Week</x-ds::segment>
                </x-ds::segmented-control>
                <x-ds::segmented-control name="layout" label="Layout" size="lg" wire:model.live="layout">
                    <x-ds::segment value="grid" icon="squares-2x2" :iconOnly="true">Grid</x-ds::segment>
                    <x-ds::segment value="rows" icon="table-cells" :iconOnly="true" :checked="true">Rows</x-ds::segment>
                </x-ds::segmented-control>
                <x-ds::segmented-control label="Show">
                    <x-ds::segment href="/tickets" :active="true">All</x-ds::segment>
                    <x-ds::segment href="/tickets?mine=1" icon="user">Mine</x-ds::segment>
                    <x-ds::segment href="/tickets?archived=1" :disabled="true">Archived</x-ds::segment>
                </x-ds::segmented-control>
                BLADE,

            'button-group/all' => <<<'BLADE'
                <x-ds::button-group label="Alignment">
                    <x-ds::button>Left</x-ds::button>
                    <x-ds::button>Center</x-ds::button>
                    <x-ds::button>Right</x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Pages" size="sm" variant="primary">
                    <x-ds::button iconOnly aria-label="Previous"><x-ds::icon name="chevron-left" /></x-ds::button>
                    <x-ds::button iconOnly aria-label="Next"><x-ds::icon name="chevron-right" /></x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Zoom" orientation="vertical" variant="ghost" size="lg">
                    <x-ds::button>In</x-ds::button>
                    <x-ds::button>Out</x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Deploy" variant="danger">
                    <x-ds::button>Roll back</x-ds::button>
                    <x-ds::button variant="secondary" tone="danger">Pause</x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Save options">
                    <x-ds::button>Save</x-ds::button>
                    <x-ds::dropdown>
                        <x-slot:trigger>
                            <x-ds::button iconOnly aria-label="More save options"><x-ds::icon name="chevron-down" /></x-ds::button>
                        </x-slot:trigger>
                        <x-ds::dropdown-item href="/draft">Save as draft</x-ds::dropdown-item>
                    </x-ds::dropdown>
                </x-ds::button-group>
                BLADE,

            'toolbar/all' => <<<'BLADE'
                <x-ds::toolbar label="Formatting">
                    <x-ds::toolbar-button icon="arrow-uturn-left" label="Undo" :iconOnly="true" :tooltip="true" />
                    <x-ds::toolbar-button icon="arrow-uturn-right" label="Redo" :iconOnly="true" :disabled="true" />
                    <x-ds::toolbar-separator />
                    <x-ds::toolbar-button icon="star" label="Star" :pressed="true" tooltip="Starred" />
                    <x-ds::toolbar-button label="Share" href="/share" />
                    <x-ds::toolbar-button label="Archived" href="/archive" :disabled="true" />
                </x-ds::toolbar>
                <x-ds::toolbar label="Canvas tools" variant="floating" size="sm" orientation="vertical">
                    <x-ds::toolbar-button icon="hand-raised" label="Pan" :iconOnly="true" :pressed="false" />
                    <x-ds::toolbar-separator />
                    <x-ds::toolbar-button icon="pencil" label="Draw" />
                </x-ds::toolbar>
                BLADE,

            'toggle-group/all' => <<<'BLADE'
                <x-ds::toggle-group label="Alignment" name="align">
                    <x-ds::toggle value="left" label="Left" icon="bars-3-bottom-left" :pressed="true" />
                    <x-ds::toggle value="center" label="Center" icon="bars-3" :iconOnly="true" :tooltip="true" />
                    <x-ds::toggle value="justify" label="Justify" :disabled="true" />
                </x-ds::toggle-group>
                <x-ds::toggle-group label="Text style" type="multiple" name="marks" size="sm" wire:model.live="marks">
                    <x-ds::toggle value="bold" label="Bold" icon="bold" :iconOnly="true" :pressed="true" />
                    <x-ds::toggle value="italic" label="Italic" icon="italic" :iconOnly="true" />
                </x-ds::toggle-group>
                <x-ds::toggle-group label="Filters" type="multiple" x-model="filters">
                    <x-ds::toggle value="mine" label="Mine" />
                </x-ds::toggle-group>
                <x-ds::toolbar label="Editor" size="sm">
                    <x-ds::toggle-group label="Marks" type="multiple">
                        <x-ds::toggle value="bold" label="Bold" />
                    </x-ds::toggle-group>
                </x-ds::toolbar>
                BLADE,

            'number-field/all' => <<<'BLADE'
                <x-ds::number-field name="seats" label="Seats" :value="3" :min="1" :max="10" help="Up to ten on this plan." />
                <x-ds::number-field name="seats" label="Seats" :value="1" :min="1" :max="10" />
                <x-ds::number-field name="seats" label="Seats" :value="10" :min="1" :max="10" error="This plan has ten seats." />
                <x-ds::number-field name="qty" label="Quantity" :value="2" :min="0" size="sm" />
                <x-ds::number-field name="weight" label="Weight (kg)" :value="1.5" :min="0" :step="0.5" />
                <x-ds::number-field name="offset" label="Offset" :value="-2" :min="-10" :max="10" step="any" />
                <x-ds::number-field name="seats" label="Locked" :value="3" :disabled="true" />
                <x-ds::number-field name="n" aria-label="Count" />
                BLADE,

            'panel/all' => <<<'BLADE'
                <x-ds::panel title="Recent" action="View all" actionHref="/all">
                    <x-ds::panel-row>A row</x-ds::panel-row>
                    <x-ds::panel-row>Another row</x-ds::panel-row>
                </x-ds::panel>
                <x-ds::panel title="Feature" subtitle="With a tile" icon="briefcase" iconTone="accent" body="plain">
                    Free-form content.
                </x-ds::panel>
                <x-ds::panel title="Warn" icon="exclamation-triangle" iconTone="warning" body="plain">Body</x-ds::panel>
                <x-ds::panel title="Good" icon="check-circle" iconTone="success" body="plain">Body</x-ds::panel>
                <x-ds::panel title="Bad" icon="x-circle" iconTone="danger" body="plain">Body</x-ds::panel>
                <x-ds::panel title="Plain" icon="cog" iconTone="neutral" body="plain">Body</x-ds::panel>
                BLADE,

            'stat-tile/all' => <<<'BLADE'
                <x-ds::stat-tile href="/revenue" label="Revenue" value="12400" :delta="12" />
                <x-ds::stat-tile label="Churn" value="2.1" :delta="-4" caption="vs last month" />
                <x-ds::stat-tile label="Open" value="18" />
                <x-ds::stat-tile label="Flat" value="7" :delta="0" />
                BLADE,

            /*
             * No `width` key here, deliberately. A column's width is a class
             * string the CONSUMER supplies, and it works in their app precisely
             * because they wrote it in their own scanned views. It has no rule in
             * a stylesheet compiled from THIS package alone — so using one here
             * would fail the class check for a reason that is not a bug. See
             * docs/components/table.md.
             */
            'table/all' => <<<'BLADE'
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
                    <x-ds::table-row>
                        <x-ds::table-cell>Churn by cohort</x-ds::table-cell>
                        <x-ds::table-cell align="center">—</x-ds::table-cell>
                        <x-ds::table-cell align="right">—</x-ds::table-cell>
                    </x-ds::table-row>
                </x-ds::table>
                BLADE,

            /*
             * Sortable headers: a link per sortable column, the sorted one
             * with aria-sort and its arrow, a right-aligned one with the
             * arrow inside; and the Livewire form, buttons with wire:click.
             */
            'table/sortable' => <<<'BLADE'
                <x-ds::table sort="total" direction="desc" sortUrl="/reports?sort={key}&direction={direction}" :columns="[
                    ['label' => 'Report', 'key' => 'name', 'sortable' => true],
                    'Status',
                    'total' => ['label' => 'Total', 'align' => 'right', 'sortable' => true],
                    // A width is the consumer's own class, which this
                    // package's CSS does not carry; the empty header is the
                    // point here.
                    ['label' => ''],
                ]">
                    <x-ds::table-row>
                        <x-ds::table-cell>Q3 revenue</x-ds::table-cell>
                        <x-ds::table-cell>Ready</x-ds::table-cell>
                        <x-ds::table-cell align="right">£1,200</x-ds::table-cell>
                        <x-ds::table-cell></x-ds::table-cell>
                    </x-ds::table-row>
                </x-ds::table>
                <x-ds::table sort="name" sortAction="sortBy('{key}')" :columns="[
                    ['label' => 'Report', 'key' => 'name', 'sortable' => true],
                    ['label' => 'Runs', 'key' => 'runs', 'align' => 'center', 'sortable' => true],
                ]">
                    <x-ds::table-row>
                        <x-ds::table-cell>Q3 revenue</x-ds::table-cell>
                        <x-ds::table-cell align="center">4</x-ds::table-cell>
                    </x-ds::table-row>
                </x-ds::table>
                BLADE,

            'nav-item/all' => <<<'BLADE'
                <x-ds::nav-item href="/" label="Dashboard" :active="true">
                    <x-slot:icon><x-ds::icon name="home" /></x-slot:icon>
                </x-ds::nav-item>
                <x-ds::nav-item href="/reports" label="Reports" badge="3">
                    <x-slot:icon><x-ds::icon name="chart-bar" /></x-slot:icon>
                </x-ds::nav-item>
                <x-ds::nav-item href="/team" label="Team" :chip="true" collapsedWhen="railCollapsed">
                    <x-slot:icon><x-ds::icon name="users" /></x-slot:icon>
                </x-ds::nav-item>
                BLADE,

            'sub-nav/all' => <<<'BLADE'
                <x-ds::sub-nav label="Reports">
                    <x-slot:icon><x-ds::icon name="chart-bar" /></x-slot:icon>
                    <x-ds::nav-item href="/reports/revenue" label="Revenue" />
                    <x-ds::nav-item href="/reports/churn" label="Churn" :active="true" />
                </x-ds::sub-nav>
                <x-ds::sub-nav label="Settings" :chip="true" collapsedWhen="railCollapsed">
                    <x-slot:icon><x-ds::icon name="cog" /></x-slot:icon>
                    <x-ds::nav-item href="/settings/profile" label="Profile" collapsedWhen="railCollapsed" />
                    <x-ds::nav-item href="/settings/billing" label="Billing" collapsedWhen="railCollapsed" />
                </x-ds::sub-nav>
                <x-ds::sub-nav label="Team" :open="true" href="/team">
                    <x-ds::nav-item href="/team/members" label="Members" />
                </x-ds::sub-nav>
                BLADE,

            'sheet/all' => <<<'BLADE'
                <x-ds::sheet open="sheetOpen" title="More">
                    <x-ds::sheet-item href="/a" label="Projects" description="All of them">
                        <x-slot:icon><x-ds::icon name="document-text" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                    <x-ds::sheet-item href="/b" label="Admin" tone="accent">
                        <x-slot:icon><x-ds::icon name="shield-check" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                    <x-ds::sheet-item label="Sign out" tone="danger" as="button">
                        <x-slot:icon><x-ds::icon name="logout" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                    <x-ds::sheet-item label="Soon" :disabled="true">
                        <x-slot:icon><x-ds::icon name="banknotes" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                </x-ds::sheet>
                BLADE,

            'bottom-nav/all' => <<<'BLADE'
                <x-ds::bottom-nav>
                    <x-ds::bottom-nav-item href="/" label="Home" :active="true">
                        <x-slot:icon><x-ds::icon name="home" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                    <x-ds::bottom-nav-item href="/reports" label="Reports">
                        <x-slot:icon><x-ds::icon name="chart-bar" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                    <x-ds::bottom-nav-item label="More">
                        <x-slot:icon><x-ds::icon name="menu" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                </x-ds::bottom-nav>
                BLADE,

            // The count is capped by the component, so the specimens pass raw
            // numbers: 3 draws "3", 12 draws "9+", and 0, null and '' draw
            // nothing at all. A numeric string is a count; any other string is
            // drawn as given.
            'bottom-nav/badges' => <<<'BLADE'
                <x-ds::bottom-nav>
                    <x-ds::bottom-nav-item href="/inbox" label="Inbox" :badge="3" :active="true">
                        <x-slot:icon><x-ds::icon name="inbox" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                    <x-ds::bottom-nav-item href="/support" label="Support" :badge="12">
                        <x-slot:icon><x-ds::icon name="lifebuoy" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                    <x-ds::bottom-nav-item href="/orders" label="Orders" badge="7">
                        <x-slot:icon><x-ds::icon name="shopping-bag" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                    <x-ds::bottom-nav-item href="/changes" label="Changes" badge="new">
                        <x-slot:icon><x-ds::icon name="sparkles" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                    <x-ds::bottom-nav-item href="/zero" label="Zero" :badge="0">
                        <x-slot:icon><x-ds::icon name="home" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                    <x-ds::bottom-nav-item href="/none" label="None" :badge="null">
                        <x-slot:icon><x-ds::icon name="home" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                    <x-ds::bottom-nav-item label="Empty" badge="">
                        <x-slot:icon><x-ds::icon name="home" size="6" /></x-slot:icon>
                    </x-ds::bottom-nav-item>
                </x-ds::bottom-nav>
                BLADE,

            'modal/all' => <<<'BLADE'
                <x-ds::modal open="a" title="Small" description="With a description" size="sm">
                    Body
                    <x-slot:footer><x-ds::button size="sm">OK</x-ds::button></x-slot:footer>
                </x-ds::modal>
                <x-ds::modal open="b" title="Medium" size="md">Body</x-ds::modal>
                <x-ds::modal open="c" title="Large" size="lg">Body</x-ds::modal>
                <x-ds::modal open="d" title="Extra" size="xl">Body</x-ds::modal>
                <x-ds::modal open="g" title="Wider" size="2xl">Body</x-ds::modal>
                <x-ds::modal open="h" title="Wider still" size="3xl">Body</x-ds::modal>
                <x-ds::modal open="i" title="Widest" size="4xl">Body</x-ds::modal>
                {{-- `full` is an ARBITRARY value on both this and the drawer, and an
                     arbitrary value with a stray space compiles to nothing at all
                     — so both are in the specimens where the class check sees them. --}}
                <x-ds::modal open="j" title="Near full screen" size="full">Body</x-ds::modal>
                <x-ds::modal open="e" title="Locked" :dismissible="false">Body</x-ds::modal>
                <x-ds::modal open="f">Untitled body only</x-ds::modal>
                BLADE,

            'avatar/all' => <<<'BLADE'
                <x-ds::avatar name="Ada Lovelace" size="xs" />
                <x-ds::avatar name="Ada Lovelace" size="sm" />
                <x-ds::avatar name="Ada Lovelace" size="md" />
                <x-ds::avatar name="Ada Lovelace" size="lg" />
                <x-ds::avatar name="Acme Ltd" :square="true" />
                <x-ds::avatar name="Ada Lovelace" src="/ada.jpg" />
                <x-ds::avatar name="Ada Lovelace" :decorative="true" />
                <x-ds::avatar />
                BLADE,

            'avatar-group/all' => <<<'BLADE'
                @php
                    $people = [
                        ['name' => 'Ada Lovelace', 'src' => '/ada.jpg'],
                        ['name' => 'Grace Hopper'],
                        ['name' => 'Alan Turing'],
                        ['name' => 'Acme Ltd', 'square' => true],
                    ];
                @endphp
                <x-ds::avatar-group :avatars="$people" label="Project members" />
                <x-ds::avatar-group :avatars="$people" :max="2" size="xs" />
                <x-ds::avatar-group :avatars="$people" :max="3" size="md" :total="12" href="/team" />
                <x-ds::avatar-group :avatars="array_slice($people, 0, 2)" size="lg" :total="40" />
                <x-ds::avatar-group :avatars="[]" :total="5" />
                BLADE,

            'progress/bar' => <<<'BLADE'
                <x-ds::progress label="Uploading" :value="42" />
                <x-ds::progress label="Uploading" :value="42" :showValue="true" size="sm" />
                <x-ds::progress label="Indexing" />
                <x-ds::progress label="Over" :value="140" tone="danger" :showValue="true" />
                <x-ds::progress label="Under" :value="-5" tone="success" />
                <x-ds::progress label="Files" :value="3" :max="12" :hideLabel="true">3 of 12 files</x-ds::progress>
                <x-ds::progress label="Accent" :value="10" tone="accent" />
                <x-ds::progress label="Warning" :value="90" tone="warning" />
                <span id="ext">Export</span>
                <x-ds::progress aria-labelledby="ext" :value="60" />
                BLADE,
            'progress/circle' => <<<'BLADE'
                <x-ds::progress label="Syncing" :value="64" variant="circle" :showValue="true" />
                <x-ds::progress label="Syncing" :value="64" variant="circle" size="sm" :showValue="true" tone="accent" />
                <x-ds::progress label="Waiting" variant="circle" size="sm" :hideLabel="true" />
                <x-ds::progress label="Waiting" variant="circle" tone="success" />
                <x-ds::progress label="Disk" :value="91" variant="circle" tone="warning" />
                <x-ds::progress label="Disk" :value="100" variant="circle" tone="danger" :hideLabel="true" />
                BLADE,

            'usage-meter/all' => <<<'BLADE'
                <x-ds::usage-meter label="AI credits" :used="12" :limit="50" unit="credits" />
                <x-ds::usage-meter label="AI credits" :used="42" :limit="50" unit="credits" />
                <x-ds::usage-meter label="Storage" :used="1200" :limit="1000" unit="MB" />
                <x-ds::usage-meter label="Seats" :used="10" :limit="10" />
                <x-ds::usage-meter label="Exports" :used="3" :limit="0" />
                <x-ds::usage-meter label="Builds" :used="30" :limit="40" :warnAt="50" :dangerAt="90" />
                @php
                    $storage = [
                        ['label' => 'Files', 'value' => 4.5, 'tone' => 'accent'],
                        ['label' => 'Backups', 'value' => 2, 'tone' => 'success'],
                        ['label' => 'Logs', 'value' => 1.25, 'tone' => 'warning'],
                        ['label' => 'Other', 'value' => 0.5],
                    ];
                    $overfull = [
                        ['label' => 'Files', 'value' => 9, 'tone' => 'accent'],
                        ['label' => 'Backups', 'value' => 3, 'tone' => 'danger'],
                    ];
                @endphp
                <x-ds::usage-meter label="Storage" :limit="10" unit="GB" :segments="$storage" />
                <x-ds::usage-meter label="Storage" :limit="10" unit="GB" :segments="$overfull" />
                BLADE,

            'stepper/horizontal' => <<<'BLADE'
                <x-ds::stepper label="Checkout progress">
                    <x-ds::step status="complete" title="Account" href="/checkout/account" />
                    <x-ds::step status="current" title="Plan" description="Pick what you need" />
                    <x-ds::step status="upcoming" title="Billing" href="/checkout/billing" />
                    <x-ds::step status="upcoming" title="Review" />
                </x-ds::stepper>
                <x-ds::stepper>
                    <x-ds::step status="complete" title="One" />
                    <x-ds::step status="error" title="Two" description="Card declined" />
                    <x-ds::step title="Three" />
                </x-ds::stepper>
                <x-ds::stepper>
                    <x-ds::step status="complete" title="One" />
                    <x-ds::step status="complete" title="Two" />
                </x-ds::stepper>
                BLADE,
            'stepper/vertical' => <<<'BLADE'
                <x-ds::stepper orientation="vertical" label="Setup">
                    <x-ds::step status="complete" title="Connect the repository" description="github.com/acme/api" href="/setup/repo" />
                    <x-ds::step status="error" title="Install the app" description="Permission was refused" />
                    <x-ds::step status="current" title="Configure" />
                    <x-ds::step status="upcoming" title="Deploy" />
                </x-ds::stepper>
                BLADE,

            'breadcrumb/all' => <<<'BLADE'
                <x-ds::breadcrumb>
                    <x-ds::breadcrumb-item href="/">Home</x-ds::breadcrumb-item>
                    <x-ds::breadcrumb-item href="/reports">Reports</x-ds::breadcrumb-item>
                    <x-ds::breadcrumb-item :current="true">Q3 revenue</x-ds::breadcrumb-item>
                </x-ds::breadcrumb>
                BLADE,

            'empty-state/all' => <<<'BLADE'
                <x-ds::empty-state title="No reports yet" description="Reports appear here once a project has run." icon="document-text">
                    <x-slot:action><x-ds::button size="sm">New report</x-ds::button></x-slot:action>
                </x-ds::empty-state>
                <x-ds::empty-state title="Nothing matched" description="Try a broader search." icon="magnifying-glass" tone="accent" />
                <x-ds::empty-state title="Import failed" icon="exclamation-circle" tone="danger" />
                <x-ds::empty-state title="Expiring soon" icon="exclamation-triangle" tone="warning" />
                <x-ds::empty-state title="All done" icon="check-circle" tone="success" />
                <x-ds::empty-state title="Bare" :bare="true" />
                BLADE,

            'skeleton/all' => <<<'BLADE'
                <x-ds::skeleton />
                <x-ds::skeleton variant="text" :lines="1" />
                <x-ds::skeleton variant="block" size="sm" />
                <x-ds::skeleton variant="block" size="md" />
                <x-ds::skeleton variant="block" size="lg" />
                <x-ds::skeleton variant="circle" size="sm" />
                <x-ds::skeleton variant="circle" size="md" />
                <x-ds::skeleton variant="circle" size="lg" />
                BLADE,

            'checkbox/all' => <<<'BLADE'
                <x-ds::checkbox name="terms" label="I agree to the terms" />
                <x-ds::checkbox name="news" label="Email me updates" help="At most one a month." :checked="true" />
                <x-ds::checkbox name="all" label="Select all" :indeterminate="true" />
                <x-ds::checkbox name="bad" label="Required" error="You must agree to continue." />
                <x-ds::checkbox name="off" label="Not available" :disabled="true" />
                <x-ds::checkbox name="offon" label="Locked on" :disabled="true" :checked="true" />
                BLADE,

            // Hidden label: the select column of a table, header and rows.
            'checkbox/hidden-label' => <<<'BLADE'
                <x-ds::table>
                    <thead>
                        <tr>
                            <th scope="col" class="px-4 py-3">
                                <x-ds::checkbox name="all" label="Select all" :hideLabel="true" :indeterminate="true" />
                            </th>
                            <th scope="col" class="px-4 py-3 text-left">Report</th>
                        </tr>
                    </thead>
                    <x-ds::table-row>
                        <x-ds::table-cell><x-ds::checkbox name="ids[]" value="1" label="Select Q3 revenue" :hideLabel="true" :checked="true" /></x-ds::table-cell>
                        <x-ds::table-cell>Q3 revenue</x-ds::table-cell>
                    </x-ds::table-row>
                    <x-ds::table-row>
                        <x-ds::table-cell><x-ds::checkbox name="ids[]" value="2" label="Select Churn" :hideLabel="true" :disabled="true" /></x-ds::table-cell>
                        <x-ds::table-cell>Churn</x-ds::table-cell>
                    </x-ds::table-row>
                </x-ds::table>
                <x-ds::checkbox name="x" label="Hidden, with an error" :hideLabel="true" error="Pick at least one." />
                BLADE,

            'radio/all' => <<<'BLADE'
                <x-ds::radio-group label="Billing period" help="You can change this later.">
                    <x-ds::radio name="period" value="monthly" label="Monthly" :checked="true" />
                    <x-ds::radio name="period" value="yearly" label="Yearly" help="Two months free." />
                    <x-ds::radio name="period" value="none" label="Not now" :disabled="true" />
                </x-ds::radio-group>
                <x-ds::radio-group label="Plan" error="Choose a plan to continue.">
                    <x-ds::radio name="plan" value="free" label="Free" />
                </x-ds::radio-group>
                BLADE,

            'tabs/panels' => <<<'BLADE'
                <x-ds::tabs label="Report sections">
                    <x-ds::tab controls="p1" :active="true">Overview</x-ds::tab>
                    <x-ds::tab controls="p2" count="12">Open</x-ds::tab>
                    <x-ds::tab controls="p3" :disabled="true">Archived</x-ds::tab>
                </x-ds::tabs>
                <x-ds::tab-panel id="p1" :active="true">Overview content</x-ds::tab-panel>
                <x-ds::tab-panel id="p2">Open content</x-ds::tab-panel>
                BLADE,
            'tabs/navigation' => <<<'BLADE'
                <x-ds::tabs label="Account" :navigation="true">
                    <x-ds::tab href="/profile" :active="true">Profile</x-ds::tab>
                    <x-ds::tab href="/billing" count="3">Billing</x-ds::tab>
                </x-ds::tabs>
                BLADE,

            'drawer/all' => <<<'BLADE'
                <x-ds::drawer open="a" title="Filters" side="right" size="md">
                    Body
                    <x-slot:footer><x-ds::button size="sm">Apply</x-ds::button></x-slot:footer>
                </x-ds::drawer>
                <x-ds::drawer open="b" title="Navigation" side="left" size="sm">Body</x-ds::drawer>
                <x-ds::drawer open="c" title="Wide" size="lg" :dismissible="false">Body</x-ds::drawer>
                <x-ds::drawer open="d" title="Wider" size="xl">Body</x-ds::drawer>
                <x-ds::drawer open="e" title="Widest" size="2xl">Body</x-ds::drawer>
                {{-- `full` is the one size here built from an ARBITRARY value, and
                     an arbitrary value with a stray space compiles to nothing at
                     all — so it is in the specimens where the class check can see
                     it, not just in the map. --}}
                <x-ds::drawer open="f" title="Near full width" size="full">Body</x-ds::drawer>
                BLADE,

            'tooltip/all' => <<<'BLADE'
                <x-ds::tooltip text="Delete this report" placement="top">
                    <x-ds::button size="sm" iconOnly aria-label="Delete">
                        <x-ds::icon name="trash" size="4" />
                    </x-ds::button>
                </x-ds::tooltip>
                <x-ds::tooltip text="Below" placement="bottom"><x-ds::button size="sm">B</x-ds::button></x-ds::tooltip>
                <x-ds::tooltip text="Left" placement="left"><x-ds::button size="sm">L</x-ds::button></x-ds::tooltip>
                <x-ds::tooltip text="Right" placement="right"><x-ds::button size="sm">R</x-ds::button></x-ds::tooltip>
                BLADE,

            'toast/all' => <<<'BLADE'
                <x-ds::toast-region position="bottom-right">
                    <x-ds::toast tone="success" title="Report exported" dismiss="show = false">
                        The CSV is in your downloads.
                    </x-ds::toast>
                    <x-ds::toast tone="danger" title="Export failed">Try a smaller range.</x-ds::toast>
                    <x-ds::toast tone="warning" title="Nearly full">92% of your quota.</x-ds::toast>
                    <x-ds::toast tone="accent" title="New version">Reload to update.</x-ds::toast>
                    <x-ds::toast>Saved.</x-ds::toast>
                    <x-ds::toast tone="success" title="With an action">
                        Report archived.
                        <x-slot:action><x-ds::button variant="ghost" size="sm">Undo</x-ds::button></x-slot:action>
                    </x-ds::toast>
                </x-ds::toast-region>
                <x-ds::toast-region position="top-right" />
                <x-ds::toast-region position="top-center" />
                <x-ds::toast-region position="bottom-center" />
                BLADE,

            'select/all' => <<<'BLADE'
                <x-ds::select
                    name="plan"
                    label="Plan"
                    :options="['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team']"
                    value="pro"
                    help="You can change this later."
                />
                <x-ds::select name="empty" label="Region" :options="['eu' => 'Europe']" placeholder="Choose a region…" />
                <x-ds::select name="bad" label="Currency" :options="['gbp' => 'GBP']" error="Pick a currency." />
                <x-ds::select name="off" label="Locked" :options="['a' => 'A']" value="a" :disabled="true" />
                BLADE,

            'switch/all' => <<<'BLADE'
                <x-ds::switch name="notify" label="Email notifications" help="At most one a month." />
                <x-ds::switch name="beta" label="Beta features" :checked="true" />
                <x-ds::switch name="locked" label="Not on this plan" :disabled="true" />
                <x-ds::switch name="nopost" label="Bound elsewhere" :submitUnchecked="false" />
                BLADE,

            'accordion/all' => <<<'BLADE'
                <x-ds::accordion open="billing">
                    <x-ds::accordion-item title="Billing" name="billing">Card ending 4242.</x-ds::accordion-item>
                    <x-ds::accordion-item title="Security" name="security">Two-factor is on.</x-ds::accordion-item>
                    <x-ds::accordion-item title="Notifications" name="notifications" as="h2">Weekly digest.</x-ds::accordion-item>
                </x-ds::accordion>
                <x-ds::accordion :multiple="true" :open="['a', 'b']">
                    <x-ds::accordion-item title="First" name="a">One.</x-ds::accordion-item>
                    <x-ds::accordion-item title="Second" name="b">Two.</x-ds::accordion-item>
                    <x-ds::accordion-item title="Third" name="c">Three.</x-ds::accordion-item>
                </x-ds::accordion>
                BLADE,

            'combobox/all' => <<<'BLADE'
                <x-ds::combobox
                    name="country"
                    label="Country"
                    :options="['gb' => 'United Kingdom', 'ie' => 'Ireland', 'fr' => 'France']"
                    value="gb"
                    help="Start typing to filter."
                />
                <x-ds::combobox
                    name="tags"
                    label="Tags"
                    :options="['a' => 'Accessibility', 'b' => 'Billing', 'c' => 'Compliance']"
                    :value="['a', 'c']"
                    :multiple="true"
                />
                <x-ds::combobox name="bad" label="Owner" :options="['x' => 'X']" error="Pick an owner." />
                <x-ds::combobox name="off" label="Locked" :options="['x' => 'X']" value="x" :disabled="true" />
                BLADE,

            'file-upload/all' => <<<'BLADE'
                <x-ds::file-upload name="avatar" label="Profile photo" accept="image/*" :maxSize="2097152" />
                <x-ds::file-upload name="attachments[]" label="Attachments" :multiple="true" help="Anything the reviewer should see." />
                <x-ds::file-upload name="bad" label="Contract" error="A contract is required." />
                <x-ds::file-upload name="off" label="Locked" :disabled="true" />
                <x-ds::file-upload name="plain" label="No previews" :preview="false" />
                BLADE,

            'date-picker/all' => <<<'BLADE'
                <x-ds::date-picker name="due" label="Due date" value="2026-09-30" help="Anything after today." />
                <x-ds::date-picker name="period" label="Reporting period" :range="true" :value="['2026-07-01', '2026-09-30']" />
                <x-ds::date-picker name="bounded" label="Within the year" min="2026-01-01" max="2026-12-31" />
                <x-ds::date-picker name="sunday" label="Week starts Sunday" :weekStartsOn="0" />
                <x-ds::date-picker name="bad" label="Start" error="Pick a start date." />
                <x-ds::date-picker name="off" label="Locked" :disabled="true" />
                BLADE,

            'dropdown/all' => <<<'BLADE'
                <x-ds::dropdown placement="bottom-end">
                    <x-slot:trigger><x-ds::button size="sm">Actions</x-ds::button></x-slot:trigger>
                    <x-ds::dropdown-item icon="pencil-square" href="/edit">Edit</x-ds::dropdown-item>
                    <x-ds::dropdown-item icon="archive-box" :disabled="true">Archive</x-ds::dropdown-item>
                    <x-ds::dropdown-item icon="trash" tone="danger" as="button">Delete</x-ds::dropdown-item>
                </x-ds::dropdown>
                <x-ds::dropdown open="menuOpen" placement="bottom-start">
                    <x-slot:trigger><x-ds::button size="sm">Start</x-ds::button></x-slot:trigger>
                    <x-ds::dropdown-item href="/a">A</x-ds::dropdown-item>
                </x-ds::dropdown>
                <x-ds::dropdown placement="top-start">
                    <x-slot:trigger><x-ds::button size="sm">Up</x-ds::button></x-slot:trigger>
                    <x-ds::dropdown-item href="/a">A</x-ds::dropdown-item>
                </x-ds::dropdown>
                <x-ds::dropdown placement="top-end">
                    <x-slot:trigger><x-ds::button size="sm">Up end</x-ds::button></x-slot:trigger>
                    <x-ds::dropdown-item href="/a">A</x-ds::dropdown-item>
                </x-ds::dropdown>
                BLADE,

            /*
             * Every placement once, so each fallback position class is
             * rendered and checked; every width; both ways of naming it;
             * host-owned state; not dismissible.
             */
            'popover/all' => <<<'BLADE'
                <x-ds::popover label="Filters">
                    <x-slot:trigger><x-ds::button size="sm">Filters</x-ds::button></x-slot:trigger>
                    <x-ds::checkbox name="failed" label="Only failed runs" />
                </x-ds::popover>
                <x-ds::popover labelledby="share-title" open="shareOpen" placement="bottom-end" width="lg">
                    <x-slot:trigger><x-ds::button size="sm">Share</x-ds::button></x-slot:trigger>
                    <h3 id="share-title">Share this report</h3>
                </x-ds::popover>
                <x-ds::popover label="Note" width="sm" :dismissible="false" placement="bottom">
                    <x-slot:trigger><x-ds::button size="sm">Note</x-ds::button></x-slot:trigger>
                    Body
                </x-ds::popover>
                <x-ds::popover label="Named" id="named-panel" placement="top-start"><x-slot:trigger><button type="button">T</button></x-slot:trigger> Body </x-ds::popover>
                <x-ds::popover label="t" placement="top"><x-slot:trigger><button type="button">T</button></x-slot:trigger> Body </x-ds::popover>
                <x-ds::popover label="t" placement="top-end"><x-slot:trigger><button type="button">T</button></x-slot:trigger> Body </x-ds::popover>
                <x-ds::popover label="t" placement="left-start"><x-slot:trigger><button type="button">T</button></x-slot:trigger> Body </x-ds::popover>
                <x-ds::popover label="t" placement="left"><x-slot:trigger><button type="button">T</button></x-slot:trigger> Body </x-ds::popover>
                <x-ds::popover label="t" placement="left-end"><x-slot:trigger><button type="button">T</button></x-slot:trigger> Body </x-ds::popover>
                <x-ds::popover label="t" placement="right-start"><x-slot:trigger><button type="button">T</button></x-slot:trigger> Body </x-ds::popover>
                <x-ds::popover label="t" placement="right"><x-slot:trigger><button type="button">T</button></x-slot:trigger> Body </x-ds::popover>
                <x-ds::popover label="t" placement="right-end"><x-slot:trigger><button type="button">T</button></x-slot:trigger> Body </x-ds::popover>
                BLADE,

            'hover-card/all' => <<<'BLADE'
                <p>
                    Assigned to
                    <x-ds::hover-card>
                        <x-slot:trigger><a href="/people/ada">Ada Lovelace</a></x-slot:trigger>
                        <span class="block font-medium text-fg">Ada Lovelace</span>
                        <span class="block text-meta text-fg-muted">Analyst, Engines team</span>
                        <a href="/people/ada" class="mt-2 block text-meta">View profile</a>
                    </x-ds::hover-card>
                    since Monday.
                </p>
                <x-ds::hover-card placement="top" width="sm">
                    <x-slot:trigger><a href="/r/42">Q3 revenue</a></x-slot:trigger>
                    Ready, £12,400
                </x-ds::hover-card>
                <x-ds::hover-card placement="right-end" width="lg" id="hc-named">
                    <x-slot:trigger><a href="/r/43">Churn</a></x-slot:trigger>
                    Running
                </x-ds::hover-card>
                BLADE,

            'context-menu/all' => <<<'BLADE'
                <x-ds::context-menu label="Report actions" class="rounded-panel border border-border p-4">
                    <p>Q3 revenue</p>
                    <x-slot:menu>
                        <x-ds::dropdown-item icon="pencil-square" href="/edit">Edit</x-ds::dropdown-item>
                        <x-ds::dropdown-item icon="archive-box" :disabled="true">Archive</x-ds::dropdown-item>
                        <x-ds::dropdown-item icon="trash" tone="danger" as="button">Delete</x-ds::dropdown-item>
                    </x-slot:menu>
                </x-ds::context-menu>
                <x-ds::context-menu :native="false">
                    <a href="/r/1">A link</a>
                    <x-slot:menu><x-ds::dropdown-item as="button">One</x-ds::dropdown-item></x-slot:menu>
                </x-ds::context-menu>
                <x-ds::context-menu>Empty menu</x-ds::context-menu>
                BLADE,

            'theme-switcher/all' => <<<'BLADE'
                <x-ds::theme-switcher />
                <x-ds::theme-switcher variant="button" />
                <x-ds::theme-switcher variant="button" size="sm" label="Appearance" storageKey="app-theme" />
                <x-ds::theme-switcher size="lg" :hideLabel="true" name="theme" :transition="false" />
                <x-ds::theme-switcher variant="nonsense" size="huge" />
                BLADE,

            'announcement-bar/all' => <<<'BLADE'
                <x-ds::announcement-bar>Scheduled maintenance on Saturday.</x-ds::announcement-bar>
                <x-ds::announcement-bar tone="accent" icon="sparkles" href="/changelog" actionLabel="See what changed">Exports are here.</x-ds::announcement-bar>
                <x-ds::announcement-bar tone="success" :dismissible="true" sticky>All systems normal.</x-ds::announcement-bar>
                <x-ds::announcement-bar tone="warning" dismissKey="trial-ends" nonce="abc123" label="Trial">
                    Your trial ends in 3 days.
                    <x-slot:action><x-ds::button size="sm" variant="secondary" data-ds-dismiss>Got it</x-ds::button></x-slot:action>
                </x-ds::announcement-bar>
                <x-ds::announcement-bar tone="danger" :urgent="true" :icon="false">Payments are failing.</x-ds::announcement-bar>
                <x-ds::announcement-bar tone="nonsense" :urgent="true">Unknown tone, urgent ignored.</x-ds::announcement-bar>
                BLADE,

            'confirm-button/all' => <<<'BLADE'
                <x-ds::confirm-button />
                <x-ds::confirm-button label="Remove" question="Remove this member?" confirmLabel="Yes, remove" cancelLabel="Keep" variant="ghost" size="sm" />
                <x-ds::confirm-button variant="secondary" size="lg" :timeout="0" :loading="true" type="button" wire:click="delete(4)" class="ml-auto" />
                <x-ds::confirm-button variant="primary" :timeout="2.5" name="intent" value="archive" label="Archive" />
                <x-ds::confirm-button variant="nonsense" size="huge" type="nonsense" />
                BLADE,

            'hold-button/all' => <<<'BLADE'
                <x-ds::hold-button />
                <x-ds::hold-button label="Hold to delete" armedLabel="Press again to delete" :duration="2000" type="submit" name="intent" value="delete" />
                <x-ds::hold-button tone="neutral" size="sm" label="Hold to archive" />
                <x-ds::hold-button size="lg" tone="nonsense" />
                BLADE,

            'confirm-popover/all' => <<<'BLADE'
                <x-ds::confirm-popover title="Delete this report?" description="Its 12 runs go with it.">
                    <x-slot:trigger><x-ds::button variant="danger" size="sm">Delete</x-ds::button></x-slot:trigger>
                </x-ds::confirm-popover>
                <x-ds::confirm-popover title="Delete project?" description="This cannot be undone." confirmText="acme-prod" :strict="false"
                    confirmPrompt="Enter :text below" confirmLabel="Delete project" :loading="true" width="lg" placement="top-end">
                    <x-slot:trigger><x-ds::button variant="secondary" tone="danger">Delete project</x-ds::button></x-slot:trigger>
                </x-ds::confirm-popover>
                <x-ds::confirm-popover tone="neutral" title="Revoke key?" confirmLabel="Revoke" cancelLabel="Keep" type="button" open="revokeOpen" wire:click="revoke">
                    <x-slot:trigger><x-ds::button size="sm">Revoke</x-ds::button></x-slot:trigger>
                </x-ds::confirm-popover>
                BLADE,

            'counter/all' => <<<'BLADE'
                <x-ds::counter :value="2847" />
                <x-ds::counter :value="1234.5" :format="['prefix' => '$', 'decimals' => 2]" size="xl" duration="dialog" />
                <x-ds::counter :value="18400" :format="['compact' => true, 'suffix' => ' views']" size="lg" :animateIn="true" />
                <x-ds::counter :value="-3.25" :format="['decimals' => 1, 'grouping' => false, 'locale' => 'de']" size="sm" duration="hint" />
                <x-ds::counter :value="12" ::value="count" aria-live="polite" />
                <x-ds::counter value="7" x-bind:value="$wire.total" :animateIn="true" size="md" duration="popover" />
                <x-ds::counter value="n/a" size="huge" duration="forever" />
                BLADE,

            'sparkline/all' => <<<'BLADE'
                <x-ds::sparkline :values="[3, 5, 4, 8, 6, 9]" />
                <x-ds::sparkline :values="[3, 5, null, 8, 6, 9]" variant="area" tone="success" width="lg" height="lg" :showLast="true" label="Signups" />
                <x-ds::sparkline :values="['Mon' => 3, 'Tue' => -2, 'Wed' => null, 'Thu' => 4]" variant="bar" series="3" width="sm" height="sm" :table="true" :format="['suffix' => '%']" />
                <x-ds::sparkline :values="[5, 5, 5]" tone="danger" width="full" :min="0" :max="10" summary="Steady at five." />
                <x-ds::sparkline :values="[7]" variant="area" tone="warning" />
                <x-ds::sparkline :values="[]" variant="bar" />
                <x-ds::sparkline :values="[-4, -1, -6]" variant="bar" tone="accent" />
                <x-ds::sparkline :values="[1, 2]" variant="nonsense" tone="nonsense" series="12" width="huge" height="huge" />
                BLADE,

            'sparkline/in-a-stat-tile' => <<<'BLADE'
                <x-ds::stat-tile label="Signups" value="1284" :delta="12">
                    <x-slot:caption><x-ds::sparkline :values="[3, 5, 4, 8, 6, 9]" tone="success" width="sm" height="sm" :showLast="true" /></x-slot:caption>
                </x-ds::stat-tile>
                <x-ds::stat-tile label="Revenue" value="$12.4K">
                    <x-slot:caption><x-ds::sparkline :values="[9, 7, 8, 11, 12]" variant="area" width="full" label="Revenue, last five weeks" /></x-slot:caption>
                </x-ds::stat-tile>
                BLADE,

            'gauge/all' => <<<'BLADE'
                <x-ds::gauge label="CPU" :value="72" :format="['suffix' => '%']" :thresholds="[
                    ['at' => 0, 'tone' => 'success'],
                    ['at' => 70, 'tone' => 'warning'],
                    ['at' => 90, 'tone' => 'danger', 'label' => 'Overloaded'],
                ]" />
                <x-ds::gauge label="Memory" :value="3.2" :max="8" :format="['suffix' => ' GB', 'decimals' => 1]" variant="ring" size="lg" tone="accent" />
                <x-ds::gauge label="Queue" :value="130" variant="ring" size="sm" :hideLabel="true" />
                <x-ds::gauge label="Battery" :value="-5" :min="0" :max="100" :showValue="false" tone="success" size="sm" />
                <x-ds::gauge aria-labelledby="g-heading" :value="null" />
                <x-ds::gauge label="Odd" :value="5" :min="10" :max="0" variant="nonsense" size="huge" tone="nonsense" :thresholds="[['at' => 'x'], ['at' => 2, 'tone' => 'nonsense']]" :format="'nonsense'" />
                BLADE,

            'activity-heatmap/all' => <<<'BLADE'
                <x-ds::activity-heatmap label="Contributions" from="2026-01-05" to="2026-03-11"
                    :data="['2026-03-03' => 12, '2026-03-04' => 1, '2026-02-27' => 4, '2026-03-10' => 7, '2026-01-01' => 50, 'not a date' => 3]" />
                <x-ds::activity-heatmap label="Deploys" to="2026-03-11" :weeks="4" :weekStartsOn="0" tone="success" :levels="3"
                    :data="['2026-03-02' => 2, '2026-03-05' => 9]" href="/deploys/{date}" unit="deploy" />
                <x-ds::activity-heatmap label="Runs" to="2026-03-11" :weeks="2" series="7" :levels="[1, 5, 10]" :unit="['run', 'runs']"
                    :showMonthLabels="false" :showWeekdayLabels="false" summary="Two weeks of runs." :data="['2026-03-09' => 11]" />
                <x-ds::activity-heatmap label="Empty" to="2026-03-11" :weeks="1" :showWeekdayLabels="false" tone="nonsense" />
                <x-ds::activity-heatmap label="Reversed" from="2026-03-11" to="2026-03-01" :showMonthLabels="false" :levels="9" />
                BLADE,

            'bar-chart/all' => <<<'BLADE'
                <x-ds::bar-chart label="Signups" :data="['Jan' => 12, 'Feb' => 18, 'Mar' => null, 'Apr' => 9]" />
                <x-ds::bar-chart label="Revenue and costs" :data="['Jan' => ['Revenue' => 12, 'Costs' => 8], 'Feb' => ['Revenue' => 18, 'Costs' => 10]]"
                    :format="['prefix' => '$', 'suffix' => 'K']" :showValues="true" :table="true" height="lg" :yTicks="3" />
                <x-ds::bar-chart :data="['W1' => [5, 3], 'W2' => [7, 2]]" :series="['Open', 'Closed']" :stacked="true" :showValues="true" height="sm" />
                <x-ds::bar-chart label="Views by page" orientation="horizontal" :showValues="true" tone="success"
                    :data="['Getting started with the API in ten minutes' => 3180, 'Pricing' => 4210]" />
                <x-ds::bar-chart label="Net" orientation="horizontal" :stacked="true" :data="['A' => [3, -2], 'B' => [-4, 1]]" :series="['In', 'Out']" :yTicks="[-5, 0, 5]" />
                <x-ds::bar-chart label="Zero" :data="['a' => 0, 'b' => 0]" :interactive="false" />
                <x-ds::bar-chart label="One" :data="['Only' => 4]" summary="Just the one." />
                <x-ds::bar-chart :data="[]" empty="No signups yet" />
                <x-ds::bar-chart :data="[1, 2]" orientation="nonsense" height="nonsense" tone="nonsense" :format="'nonsense'" />
                BLADE,

            'line-chart/all' => <<<'BLADE'
                <x-ds::line-chart label="Visits" :data="['Mon' => 320, 'Tue' => 410, 'Wed' => null, 'Thu' => 520, 'Fri' => 480]" :points="true" />
                <x-ds::line-chart label="New and returning" :data="['Mon' => [3, 1], 'Tue' => [4, 2], 'Wed' => [6, 3]]" :series="['New', 'Returning']"
                    curve="smooth" :area="true" :table="true" height="lg" :yTicks="4" />
                <x-ds::line-chart label="Regions" legend="end" :format="['suffix' => ' ms']" :interactive="false"
                    :data="['W1' => [210, 180, 260], 'W2' => [230, 170, 250]]" :series="['EU', 'US', 'APAC']" />
                <x-ds::line-chart :data="['a' => [1, 2, 3, 4, 5], 'b' => [2, 3, 4, 5, 6]]" :series="['1', '2', '3', '4', '5']" legend="end" xLabels="none" />
                <x-ds::line-chart label="Errors" tone="danger" :area="true" xLabels="2" height="sm" :data="[1.2, null, 2.4, null, 0.6, 0.9]" />
                <x-ds::line-chart label="One" :data="['Mon' => 5]" legend="bottom" />
                <x-ds::line-chart label="Flat" :data="['Mon' => 5, 'Tue' => 5]" summary="Steady." />
                <x-ds::line-chart :data="['Mon' => null]" />
                <x-ds::line-chart :data="[1, 2]" curve="nonsense" height="nonsense" legend="nonsense" xLabels="nonsense" tone="nonsense" />
                BLADE,

            'donut-chart/all' => <<<'BLADE'
                <x-ds::donut-chart label="Traffic by source" :data="['Direct' => 1200, 'Search' => 800, 'Social' => 400, 'Email' => 250]" />
                <x-ds::donut-chart label="Seats" size="sm" thickness="lg" legend="bottom" :table="true" :format="['suffix' => ' seats']" :data="['Free' => 70, 'Pro' => 25, 'Team' => 5]" />
                <x-ds::donut-chart label="Browsers" size="lg" thickness="sm" :maxSegments="3" otherLabel="Everything else" :interactive="false"
                    :data="['Chrome' => 640, 'Safari' => 190, 'Firefox' => 60, 'Edge' => 50]">
                    <x-slot:centre><span>65%</span></x-slot:centre>
                </x-ds::donut-chart>
                <x-ds::donut-chart label="One" legend="none" :showTotal="false" :data="['All' => 5]" summary="All of it." />
                <x-ds::donut-chart label="Tickets" legend="none" totalLabel="" :data="['Open' => 3, 'Closed' => 9]" />
                <x-ds::donut-chart :data="[]" empty="Nothing yet" />
                <x-ds::donut-chart :data="['a' => 1, 'b' => 2]" size="nonsense" thickness="nonsense" legend="nonsense" :maxSegments="99" />
                BLADE,

            'split-bar/all' => <<<'BLADE'
                <x-ds::split-bar label="Plans" :data="['Free' => 744, 'Pro' => 360, 'Team' => 96]" />
                <x-ds::split-bar label="Storage" :total="500" legend="inline" size="lg" :table="true" :format="['suffix' => ' GB']" :data="['Photos' => 150, 'Video' => 96, 'Mail' => 0.4]" />
                <x-ds::split-bar label="Tickets" size="sm" showValues="value" legend="none" :interactive="false"
                    :data="[['label' => 'Open', 'value' => 18, 'tone' => 'warning'], ['label' => 'Solved', 'value' => 41, 'series' => 3], ['label' => 'Blocked', 'value' => 0]]" />
                <x-ds::split-bar :data="['a' => 1]" showValues="percent" summary="All of it." />
                <x-ds::split-bar :data="[]" empty="Nothing yet" />
                <x-ds::split-bar :data="['a' => 0]" />
                <x-ds::split-bar :data="['a' => 1, 'b' => 2]" size="nonsense" legend="nonsense" showValues="nonsense" :total="'nonsense'" :format="'nonsense'" />
                BLADE,

            'waffle-chart/all' => <<<'BLADE'
                <x-ds::waffle-chart label="Answered" :data="37" tone="success" />
                <x-ds::waffle-chart label="Seats" :data="['Free' => 620, 'Pro' => 290, 'Team' => 0.5]" legend="below" shape="circle" size="lg" :table="true" :format="['suffix' => ' seats']" />
                <x-ds::waffle-chart label="Invited" :total="200" :rows="5" :columns="20" size="sm" legend="none" :interactive="false"
                    :data="[['label' => 'Accepted', 'value' => 112, 'series' => 4], ['label' => 'Declined', 'value' => 31, 'tone' => 'danger']]" />
                <x-ds::waffle-chart :data="0" summary="None yet." />
                <x-ds::waffle-chart :data="[]" empty="Nothing yet" />
                <x-ds::waffle-chart :data="12" :rows="99" :columns="-3" size="nonsense" shape="nonsense" legend="nonsense" tone="nonsense" :format="'nonsense'" />
                BLADE,

            'activity-rings/all' => <<<'BLADE'
                <x-ds::activity-rings label="Today" :rings="[['label' => 'Move', 'value' => 420, 'max' => 600, 'format' => ['suffix' => ' kcal']], ['label' => 'Exercise', 'value' => 25, 'max' => 30], ['label' => 'Stand', 'value' => 14, 'max' => 12, 'tone' => 'success']]">
                    <x-slot:centre><span>2/3</span></x-slot:centre>
                </x-ds::activity-rings>
                <x-ds::activity-rings label="Regions" size="lg" thickness="lg" legend="below" :table="true" :interactive="false" :rings="['North' => 82, 'South' => 64, 'East' => 0, 'West' => ['value' => 41, 'series' => 7]]" />
                <x-ds::activity-rings size="sm" thickness="sm" legend="none" :rings="['Done' => 99.5]" summary="Nearly done." />
                <x-ds::activity-rings :rings="[]" empty="No goals yet" />
                <x-ds::activity-rings :rings="['a' => 1]" size="nonsense" thickness="nonsense" legend="nonsense" :format="'nonsense'" />
                BLADE,

            'slope-chart/all' => <<<'BLADE'
                <x-ds::slope-chart label="Conversion" fromLabel="Q1" toLabel="Q2" :format="['suffix' => '%', 'decimals' => 1]"
                    :data="['Search' => [3.1, 4.2], 'Email' => [5.4, 5.1], 'Direct' => [4.0, 4.0]]" />
                <x-ds::slope-chart label="Satisfaction" fromLabel="Before" toLabel="After" colourBy="direction" highlight="Billing" height="lg" :table="true" :interactive="false"
                    :data="[['label' => 'Billing', 'from' => 54, 'to' => 71], ['label' => 'A team with a very long name indeed', 'from' => 68, 'to' => 59, 'tone' => 'warning'], ['label' => 'Mobile', 'from' => 66, 'to' => 66, 'series' => 5]]" />
                <x-ds::slope-chart fromLabel="2024" toLabel="2025" height="sm" :data="['Only' => [5, 5]]" summary="Unchanged." />
                <x-ds::slope-chart fromLabel="Then" toLabel="Now" colourBy="direction" :data="['a' => [1, 2], 'b' => [1, 2], 'c' => [1, 2], 'd' => [1, 2], 'e' => [1, 2], 'f' => [2, 1], 'g' => [2, 1], 'h' => [2, 1], 'i' => [2, 2], 'j' => [3, 1]]" />
                <x-ds::slope-chart fromLabel="Then" toLabel="Now" :data="[]" empty="Nothing to compare" />
                <x-ds::slope-chart fromLabel="Then" toLabel="Now" :data="['a' => [1, 2]]" height="nonsense" colourBy="nonsense" :highlight="'nobody'" :format="'nonsense'" />
                BLADE,

            'funnel-chart/all' => <<<'BLADE'
                <x-ds::funnel-chart label="Signups" :data="['Visits' => 12400, 'Sign-ups' => 3100, 'Activated' => 3500, 'Paid' => 310]" />
                <x-ds::funnel-chart label="Checkout" shape="funnel" showRate="both" height="lg" tone="success" :table="true" :format="['suffix' => ' carts']"
                    :data="[['label' => 'Cart', 'value' => 5200], ['label' => 'Payment', 'value' => 2600, 'tone' => 'warning'], ['label' => 'Done', 'value' => 0, 'series' => 3]]" />
                <x-ds::funnel-chart label="Across" orientation="horizontal" shape="funnel" showRate="total" height="sm" :interactive="false" :data="['A stage with a long name' => 9, 'B' => 4, 'C' => 1]" />
                <x-ds::funnel-chart orientation="horizontal" showRate="none" :data="['Only' => 5]" summary="One stage." />
                <x-ds::funnel-chart :data="[]" empty="Nothing yet" />
                <x-ds::funnel-chart :data="['a' => 0, 'b' => 0]" />
                <x-ds::funnel-chart :data="['a' => 2, 'b' => 1]" orientation="nonsense" shape="nonsense" showRate="nonsense" height="nonsense" tone="nonsense" :format="'nonsense'" />
                BLADE,

            'radar-chart/all' => <<<'BLADE'
                <x-ds::radar-chart label="Vendors" :axes="['Price', 'Support', 'Features', 'Reliability', 'Ease']"
                    :data="['Acme' => [8, 6, 9, 7, 5], 'Globex' => [5, 9, 6, 8, 8], 'Initech' => ['label' => 'Initech', 'values' => ['Price' => 6, 'Support' => 5, 'Features' => 4, 'Reliability' => 9, 'Ease' => 7], 'series' => 6], 'Umbrella' => [9, 3, 7, 5, 6]]" />
                <x-ds::radar-chart label="Skills" size="sm" shape="circle" :fill="false" legend="none" :rings="5" :table="false" :axes="['Design', 'Frontend', 'Backend']" :data="[['label' => 'Ada', 'values' => [90, 80, 60], 'tone' => 'success']]" />
                <x-ds::radar-chart label="Mixed units" size="lg" legend="below" :interactive="false" :max="['Score' => 100, 'Rating' => 5, 'A very long axis name indeed' => 1000, 'Uptime' => 100]"
                    :axes="['Score', 'Rating', 'A very long axis name indeed', 'Uptime']" :data="['This' => [80, 4, 600, 120], 'That' => [60, 4.5, 900, 99]]" :format="['decimals' => 1]" />
                <x-ds::radar-chart :axes="['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']" :max="10" :data="['One' => [1, 2, 3, 4, 5, 6, 7, 8]]" summary="Eight axes." />
                <x-ds::radar-chart :axes="['a', 'b', 'c']" :data="[]" empty="Nothing scored" />
                <x-ds::radar-chart :axes="['a', 'b', 'c']" :data="['x' => [0, 0, 0]]" :rings="99" shape="nonsense" legend="nonsense" size="nonsense" :format="'nonsense'" />
                BLADE,

            'treemap/all' => <<<'BLADE'
                <x-ds::treemap label="Storage" :format="['suffix' => ' GB']" :data="['Photos' => 150, 'Video' => 96, 'Documents' => 38, 'Mail' => 12, 'Backups' => 60, 'Empty' => 0]" />
                <x-ds::treemap label="Spend" height="lg" :ratio="3" showValues="percent" :minLabel="24" :format="['prefix' => '$', 'compact' => true]" :data="[
                    ['label' => 'Engineering', 'children' => ['Salaries' => 420000, 'Cloud' => ['value' => 120000, 'href' => '/spend/cloud'], 'Tools' => 30000, 'Refunds' => -5000]],
                    ['label' => 'Sales', 'series' => 5, 'children' => [['label' => 'Salaries', 'value' => 210000], ['label' => 'Travel', 'value' => 40000]]],
                    ['label' => 'Office', 'value' => 60000, 'tone' => 'neutral', 'href' => '/spend/office'],
                ]" />
                <x-ds::treemap label="By value" colourBy="value" tone="success" height="sm" showValues="none" :legend="false" :table="false" :interactive="false" :data="[50, 30, 20, 12, 8, 5, 3, 1]" />
                <x-ds::treemap colourBy="value" :data="['One' => 1]" summary="All of it." />
                <x-ds::treemap :data="[]" empty="Nothing stored" />
                <x-ds::treemap :data="['a' => 0, 'b' => -1]" />
                <x-ds::treemap :data="['a' => 2, 'b' => 1]" height="nonsense" :ratio="'nonsense'" colourBy="nonsense" showValues="nonsense" :minLabel="'nonsense'" tone="nonsense" :legend="false" :format="'nonsense'" />
                BLADE,

            'timeline/all' => <<<'BLADE'
                <x-ds::timeline label="Order history">
                    <x-ds::timeline-item title="Shipped" time="2026-11-12T10:42:00Z" icon="truck" tone="success">Two parcels.</x-ds::timeline-item>
                    <x-ds::timeline-item title="Payment failed" :time="new DateTimeImmutable('2026-11-10 09:31')" icon="exclamation-triangle" tone="danger">
                        Declined.
                        <x-slot:actions><x-ds::button size="sm" variant="secondary">Retry</x-ds::button></x-slot:actions>
                    </x-ds::timeline-item>
                    <x-ds::timeline-item title="Ada added a note" avatar="Ada Lovelace" time="Yesterday" datetime="2026-11-11" href="/notes/1" />
                    <x-ds::timeline-item title="Grace replied" :avatar="['name' => 'Grace Hopper', 'src' => '/grace.jpg']" time="2026-11-09" time-format="relative" />
                    <x-ds::timeline-item title="Placed" time="2026-11-09T08:00:00+05:30" time-format="j M, H:i" tone="accent" />
                    <x-ds::timeline-item tone="nonsense" time="">A body with no title.</x-ds::timeline-item>
                </x-ds::timeline>
                <x-ds::timeline :dense="true">
                    <x-ds::timeline-group label="Today">
                        <x-ds::timeline-item title="Deployed" time="10:42" tone="warning" />
                    </x-ds::timeline-group>
                    <x-ds::timeline-group label="Yesterday" :level="9">
                        <x-ds::timeline-item title="Built" />
                    </x-ds::timeline-group>
                </x-ds::timeline>
                BLADE,

            'tree/all' => <<<'BLADE'
                <x-ds::tree label="Files">
                    <x-ds::tree-item label="app" icon="folder" :open="true">
                        <x-ds::tree-item label="Http" icon="folder">
                            <x-ds::tree-item label="Kernel.php" icon="document" />
                        </x-ds::tree-item>
                        <x-ds::tree-item label="Models" icon="folder" :expandable="true" value="app/Models" />
                        <x-ds::tree-item label="routes.php" icon="document" :count="12" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="README.md" icon="document" :disabled="true">
                        <x-slot:badge><x-ds::badge tone="accent">New</x-ds::badge></x-slot:badge>
                    </x-ds::tree-item>
                </x-ds::tree>
                <x-ds::tree label="Documentation">
                    <x-ds::tree-item label="Guides" href="/docs/guides">
                        <x-ds::tree-item label="Install" href="/docs/guides/install" :selected="true" />
                        <x-ds::tree-item label="Upgrade" href="/docs/guides/upgrade" :disabled="true" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="API" href="/docs/api" />
                </x-ds::tree>
                <x-ds::tree label="Folder" selectable="single" name="folder">
                    <x-ds::tree-item label="Inbox" :count="3">
                        <x-ds::tree-item label="Receipts" value="receipts" :selected="true" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="Archive" :disabled="true" />
                </x-ds::tree>
                <x-ds::tree label="Permissions" selectable="multiple" name="perms" x-model="perms">
                    <x-ds::tree-item label="Billing" value="billing" :selected="true">
                        <x-ds::tree-item label="View invoices" value="billing.view" />
                        <x-ds::tree-item label="Refund" value="billing.refund" :disabled="true" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="Reports" value="reports">
                        <x-ds::tree-item label="Export" value="reports.export" :selected="true" />
                        <x-ds::tree-item label="Schedule" value="reports.schedule" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="Team" value="team" :expandable="true" />
                </x-ds::tree>
                <x-ds::tree label="Odd" selectable="nonsense"><x-ds::tree-item label="Only" /></x-ds::tree>
                BLADE,

            'filter-bar/all' => <<<'BLADE'
                <form method="GET" action="/invoices">
                    <x-ds::filter-bar :active="2" resetHref="/invoices">
                        <x-slot:search>
                            <x-ds::input type="search" name="q" label="Search" icon="magnifying-glass" value="acme" :clearable="true" />
                        </x-slot:search>
                        <x-ds::select name="status" label="Status" :options="['open' => 'Open', 'paid' => 'Paid']" value="open" />
                        <x-slot:actions><x-ds::button size="sm" variant="secondary">Export</x-ds::button></x-slot:actions>
                        <x-slot:applied>
                            <x-ds::filter-chip label="Status" value="Open" removeHref="/invoices?q=acme" />
                            <x-ds::filter-chip label="Search" value="acme" removeHref="/invoices?status=open" />
                        </x-slot:applied>
                    </x-ds::filter-bar>
                </form>
                <x-ds::filter-bar label="Ticket filters" :active="1" :reset="true" resetLabel="Reset" :collapsible="true">
                    <x-ds::chip-group name="state" label="State" :hideLabel="true" size="sm">
                        <x-ds::chip value="open" :checked="true">Open</x-ds::chip>
                        <x-ds::chip value="closed">Closed</x-ds::chip>
                    </x-ds::chip-group>
                </x-ds::filter-bar>
                <x-ds::filter-bar :active="0" resetHref="/x">
                    <x-ds::filter-chip value="Mine" />
                </x-ds::filter-bar>
                <x-ds::filter-bar>
                    <x-slot:search><x-ds::input type="search" name="q" label="Search" /></x-slot:search>
                </x-ds::filter-bar>
                BLADE,

            'input/clearable' => <<<'BLADE'
                <x-ds::input type="search" name="q" label="Search" icon="magnifying-glass" :clearable="true" value="invoices" />
                <x-ds::input name="code" label="Code" :clearable="true" placeholder="e.g. SAVE10" wire:model.live="code" />
                <x-ds::input name="locked" label="Locked" :clearable="true" :disabled="true" value="x" />
                <x-ds::input name="ro" label="Read only" :clearable="true" readonly value="x" />
                <x-ds::input type="password" name="pw" label="Password" :clearable="true" :reveal="true" />
                BLADE,

            'code-block/all' => <<<'BLADE'
                <x-ds::code-block language="PHP" filename="app/Models/Invoice.php" :lineNumbers="true" highlight="3,5-6"
                    :code="'<?php' . PHP_EOL . PHP_EOL . 'final class Invoice' . PHP_EOL . '{' . PHP_EOL . '    public function total(): int' . PHP_EOL . '    {' . PHP_EOL . '        return 42;' . PHP_EOL . '    }' . PHP_EOL . '}' . PHP_EOL" />
                <x-ds::code-block language="Shell" :wrap="true">composer require shipbytes/blade-ui && php artisan vendor:publish --tag=blade-ui-config</x-ds::code-block>
                <x-ds::code-block :copy="false" :maxHeight="2" :expandable="true" moreLabel="Show all" lessLabel="Show fewer" label="Example">
                one
                two
                three
                </x-ds::code-block>
                <x-ds::code-block filename="welcome.blade.php" :lineNumbers="true" highlight="2"
                    :html="'<pre class=&quot;shiki&quot;><code><span class=&quot;line&quot;><span style=&quot;color: var(--ds-code-comment)&quot;>{{-- a</span></span>' . PHP_EOL . '<span class=&quot;line&quot;><span style=&quot;color: var(--ds-code-comment)&quot;>comment --}}</span></span></code></pre>'" />
                <x-ds::code-block :html="'<span class=&quot;c&quot;>/* a' . PHP_EOL . ' b */</span>'" :maxHeight="0" highlight="nonsense,9-7" />
                BLADE,

            'kbd/all' => <<<'BLADE'
                <p>Press <x-ds::kbd>Esc</x-ds::kbd> to close.</p>
                <x-ds::kbd :keys="['Ctrl', 'Shift', 'P']" />
                <x-ds::kbd :keys="['⌘', 'K']" separator="" />
                <x-ds::kbd :keys="[]">Enter</x-ds::kbd>
                BLADE,

            'expandable-text/all' => <<<'BLADE'
                <x-ds::expandable-text>
                    A long description that runs over three lines in a narrow column, so the reader sees a fade and a Read more button.
                </x-ds::expandable-text>
                <x-ds::expandable-text :lines="1" moreLabel="More" lessLabel="Less" id="bio"><p>One</p><p>Two</p></x-ds::expandable-text>
                <x-ds::expandable-text :lines="0">Short.</x-ds::expandable-text>
                BLADE,

            'panel/collapsible' => <<<'BLADE'
                <x-ds::panel title="Billing" :collapsible="true" name="billing" action="Edit" actionHref="/billing">
                    <x-ds::panel-row>Card ending 4242</x-ds::panel-row>
                </x-ds::panel>
                <x-ds::panel title="Advanced" subtitle="Rarely changed" icon="cog" iconTone="neutral" :collapsible="true" :open="false" body="plain">
                    Free-form content.
                </x-ds::panel>
                <x-ds::panel title="Feature rows" icon="sparkles" :collapsible="true"><x-ds::panel-row>Row</x-ds::panel-row></x-ds::panel>
                <x-ds::panel title="List plain" :collapsible="true" :open="false" body="plain">Body</x-ds::panel>
                BLADE,

            'slider/single' => <<<'BLADE'
                <x-ds::slider name="volume" label="Volume" :value="40" />
                <x-ds::slider name="opacity" label="Opacity" :value="0.35" :min="0" :max="1" step="0.05" showValue="inline" :format="['suffix' => '%', 'decimals' => 2]" help="How see-through." />
                <x-ds::slider name="zoom" :value="150" :min="50" :max="200" step="any" showValue="tooltip" size="sm" aria-label="Zoom" />
                <x-ds::slider name="level" label="Level" :value="7" :max="10" :marks="[0, 5, 10]" :disabled="true" />
                <x-ds::slider name="score" label="Score" value="nonsense" error="Pick a score." wire:model="score" />
                BLADE,
            'slider/range' => <<<'BLADE'
                <x-ds::slider name="price" label="Price" :range="true" :value="[20, 80]" lowLabel="Minimum price" highLabel="Maximum price"
                    :minGap="10" showValue="inline" :format="['prefix' => '$']" :marks="[0 => 'Free', 50 => '$50', 100 => '$100', 400 => 'never']" />
                <x-ds::slider name="age" label="Age" :range="true" :value="['low' => 60, 'high' => 18]" :min="18" :max="99" lowLabel="Youngest" highLabel="Oldest"
                    lowName="age_min" highName="age_max" showValue="tooltip" size="sm" x-model="ages" />
                <x-ds::slider name="hours" label="Hours" :range="true" lowLabel="From" highLabel="To" :disabled="true" error="Not available." />
                BLADE,
            'time-picker/all' => <<<'BLADE'
                <x-ds::time-picker name="start" label="Start" value="09:30" />
                <x-ds::time-picker name="end" label="End" value="9:05:00" format="12" min="08:00" max="18:00" :step="30" :clearable="true" placeholder="Choose a time" help="Office hours." />
                <x-ds::time-picker name="alarm" label="Alarm" :value="\Illuminate\Support\Carbon::create(2026, 1, 1, 21, 45)" :step="2" size="sm" wire:model="alarm" />
                <x-ds::time-picker name="slot" label="Slot" value="25:00" error="Choose a slot." min="13:15" :step="60" />
                <x-ds::time-picker name="closed" label="Closed" value="12:00" :disabled="true" :clearable="true" />
                BLADE,
            'inline-edit/all' => <<<'BLADE'
                <h2><x-ds::inline-edit name="title" label="Title" value="Q3 roadmap" /></h2>
                <x-ds::inline-edit name="subtitle" label="Subtitle" placeholder="Add a subtitle" size="sm" submit="blur" wire:model="subtitle" />
                <x-ds::inline-edit name="notes" label="Notes" value="Two lines" :multiline="true" submit="buttons" />
                <x-ds::inline-edit name="name" label="Name" value="Ada &amp; co" action="/projects/1" method="PATCH" error="That name is taken." />
                <x-ds::inline-edit name="q" label="Query" value="draft" action="/search" method="GET" :saving="true" :disabled="true" />
                BLADE,
            'scroll-area/all' => <<<'BLADE'
                <x-ds::scroll-area maxHeight="10rem" label="Release notes"><p>Text only, so the region is the stop.</p></x-ds::scroll-area>
                <x-ds::scroll-area :height="240" orientation="horizontal" scrollbar="hidden-until-hover" label="Tags"><div class="flex w-max gap-2"><span>One</span></div></x-ds::scroll-area>
                <x-ds::scroll-area maxHeight="calc(100dvh - 4rem)" orientation="both" scrollbar="auto" :shadows="false"><a href="#a">A link, so the region is not a stop</a></x-ds::scroll-area>
                <x-ds::scroll-area maxHeight="50%" label="Form"><input type="hidden" name="x"><button type="button">Press</button></x-ds::scroll-area>
                BLADE,
            'command-palette/all' => <<<'BLADE'
                <x-ds::command-palette name="main" placeholder="Search or jump to…" @search="find($event.detail.query)">
                    <x-ds::command-group label="Go to">
                        <x-ds::command-item label="Invoices" href="/invoices" icon="document-text" keywords="bill receipt" shortcut="G+I" />
                        <x-ds::command-item label="Settings &amp; billing" href="/settings?tab=billing&from=palette" icon="cog" :keywords="['account', 'plan']" :disabled="true" wire:navigate />
                    </x-ds::command-group>
                    <x-ds::command-group label="Actions" id="actions">
                        <x-ds::command-item label="Archive invoice" action="archive" value="12" icon="archive-box" :shortcut="['⌘', 'Shift', 'A']">Moves it out of the inbox</x-ds::command-item>
                        <x-ds::command-item label="New invoice" action="create" />
                    </x-ds::command-group>
                    <x-ds::command-item label="Sign out" action="sign-out" id="sign-out" />
                    <x-slot:footer><x-ds::kbd>↵</x-ds::kbd> to open</x-slot:footer>
                </x-ds::command-palette>
                <x-ds::command-palette :shortcut="false" label="Search people" emptyText="Nobody by that name" :loading="true" :filter="false" id="people" />
                <x-ds::command-palette shortcut="/"><x-ds::command-item label="Only" /></x-ds::command-palette>
                BLADE,
            'sortable-list/all' => <<<'BLADE'
                <x-ds::sortable-list name="steps" label="Checklist" @reorder="save($event.detail.order)">
                    <x-ds::sortable-item value="1" label="Write the brief">Write the brief</x-ds::sortable-item>
                    <x-ds::sortable-item value="2">Review <strong>the draft</strong></x-ds::sortable-item>
                    <x-ds::sortable-item value="3" label="Ship" :disabled="true" wire:key="step-3">Ship it</x-ds::sortable-item>
                </x-ds::sortable-list>
                <x-ds::sortable-list label="Columns" :handle="false" :disabled="true" pickedText="Got :item" grabText="Move :item" moveUpText="Up: :item" moveDownText="Down: :item">
                    <x-ds::sortable-item value="name">Name</x-ds::sortable-item>
                </x-ds::sortable-list>
                <x-ds::sortable-list><x-ds::sortable-item value="solo">Unnamed, posting nothing</x-ds::sortable-item></x-ds::sortable-list>
                BLADE,
            'split/all' => <<<'BLADE'
                <x-ds::split name="editor" label="Resize sidebar" stackBelow="md" @resized="save($event.detail.sizes)">
                    <x-ds::split-pane size="25%" min="160" max="50%" :collapsible="true">Files</x-ds::split-pane>
                    <x-ds::split-pane>
                        <x-ds::split orientation="vertical" label="Resize console" stackBelow="sm">
                            <x-ds::split-pane>Editor</x-ds::split-pane>
                            <x-ds::split-pane size="12rem" min="4rem" :collapsible="true" id="console">Console</x-ds::split-pane>
                        </x-ds::split>
                    </x-ds::split-pane>
                    <x-ds::split-pane size="240px" min="nonsense" max="30%">Outline</x-ds::split-pane>
                </x-ds::split>
                <x-ds::split stackBelow="lg"><x-ds::split-pane :size="320">A</x-ds::split-pane><x-ds::split-pane>B</x-ds::split-pane></x-ds::split>
                <x-ds::split stackBelow="xl" orientation="nonsense"><x-ds::split-pane size="1.5em">C</x-ds::split-pane><x-ds::split-pane>D</x-ds::split-pane></x-ds::split>
                BLADE,

            // Not a visual component, but it renders inside a host's layout and
            // so inside a Livewire render too; this puts it through the markers
            // test with everything else.
            'scripts/include' => <<<'BLADE'
                <x-ds::scripts />
                <x-ds::scripts nonce="abc123" />
                <x-ds::scripts inline />
                <x-ds::theme-script />
                <x-ds::theme-script storageKey="app-theme" nonce="abc123" />
                BLADE,
        ];
    }
}
