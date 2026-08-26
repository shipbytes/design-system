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
                <x-ds::input as="textarea" label="Notes" name="notes" :rows="4" />
                <x-ds::input as="select" label="Plan" name="plan"><option>Free</option></x-ds::input>
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
                    <x-ds::table-row href="/row">
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

            'modal/all' => <<<'BLADE'
                <x-ds::modal open="a" title="Small" description="With a description" size="sm">
                    Body
                    <x-slot:footer><x-ds::button size="sm">OK</x-ds::button></x-slot:footer>
                </x-ds::modal>
                <x-ds::modal open="b" title="Medium" size="md">Body</x-ds::modal>
                <x-ds::modal open="c" title="Large" size="lg">Body</x-ds::modal>
                <x-ds::modal open="d" title="Extra" size="xl">Body</x-ds::modal>
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
        ];
    }
}
