{{--
    Specimen source for the DOCUMENTATION screenshots.

    Every block renders the same markup TWICE — once on the light ground, once
    inside a `.dark` — so one screenshot carries both themes and the two can
    never drift apart in the docs.

    Separate from gallery.blade.php on purpose: the gallery is a review surface
    that shows every state at once, and a documentation image wants one clear
    representative example instead.

    Every pane carries `x-data="{ shown: true }"`, and the overlays take
    `open="shown"` — a real state variable, because a real host always has one
    somewhere above an overlay. `open="true"` looks like it would work and does
    not: the close handlers compile to `true = false`, which throws. Without it Alpine never initialises the element, never
    strips its `x-cloak`, and the modal, drawer and sheet photograph as empty
    boxes — which is precisely what a consumer would see if they dropped one
    into a page with no Alpine scope around it.

    Rendered by scripts/build-docs.mjs. Not shipped.
--}}

@php
    // Each entry: the shot's id, and the height its pane needs. A `fixed`
    // overlay is boxed by a transformed ancestor, which becomes the containing
    // block for it — so the real component renders inside the pane rather than
    // over the whole page.
    $themes = ['', 'dark'];
@endphp

{{-- ─────────────────────────── button --}}
<div data-shot="button" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-wrap items-center gap-2">
                <x-ds::button>Save changes</x-ds::button>
                <x-ds::button variant="secondary">Cancel</x-ds::button>
                <x-ds::button variant="ghost">Dismiss</x-ds::button>
                <x-ds::button variant="danger">Delete</x-ds::button>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-ds::button size="sm">Small</x-ds::button>
                <x-ds::button :loading="true">Saving</x-ds::button>
                <x-ds::button :disabled="true">Disabled</x-ds::button>
                <x-ds::button iconOnly aria-label="Settings"><x-ds::icon name="cog" /></x-ds::button>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── badge --}}
<div data-shot="badge" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-wrap items-center gap-2">
                <x-ds::badge tone="neutral">Draft</x-ds::badge>
                <x-ds::badge tone="accent">Beta</x-ds::badge>
                <x-ds::badge tone="success" :dot="true">Running</x-ds::badge>
                <x-ds::badge tone="warning">Expiring</x-ds::badge>
                <x-ds::badge tone="danger">Failed</x-ds::badge>
                <x-ds::badge variant="outline">Internal</x-ds::badge>
                <x-ds::badge variant="solid" tone="accent">New</x-ds::badge>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── icon --}}
<div data-shot="icon" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-wrap items-end gap-4 text-fg">
                <x-ds::icon name="document-text" size="4" />
                <x-ds::icon name="document-text" size="5" />
                <x-ds::icon name="document-text" size="6" />
                <x-ds::icon name="document-text" size="8" />
                <x-ds::icon name="check-circle" variant="solid" size="6" class="text-success" />
                <x-ds::icon name="exclamation-triangle" variant="mini" size="6" class="text-warning" />
                <x-ds::icon name="trash" size="6" class="text-danger" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── avatar --}}
<div data-shot="avatar" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-wrap items-center gap-3">
                <x-ds::avatar name="Ada Lovelace" size="xs" />
                <x-ds::avatar name="Ada Lovelace" size="sm" />
                <x-ds::avatar name="Ada Lovelace" size="md" />
                <x-ds::avatar name="Grace Hopper" size="lg" />
                <x-ds::avatar name="Acme Holdings" :square="true" size="lg" />
                <x-ds::avatar size="lg" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── alert --}}
<div data-shot="alert" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-2.5">
                <x-ds::alert tone="success" title="Export ready">Your file stays available for 24 hours.</x-ds::alert>
                <x-ds::alert tone="warning" title="Your plan expires in 3 days">Renew to keep unlimited exports.</x-ds::alert>
                <x-ds::alert tone="danger" title="Import failed">We couldn't read that file.</x-ds::alert>
                <x-ds::alert>Template switching is always free.</x-ds::alert>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── input --}}
<div data-shot="input" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3.5">
                <x-ds::input name="a1" label="Full name" placeholder="Ada Lovelace" />
                <x-ds::input name="a2" label="Search" icon="magnifying-glass" placeholder="Find a report" />
                <x-ds::input name="a3" label="Email" value="not-an-email" error="That address is not valid." />
                <x-ds::input name="a4" label="Notes" help="Visible to your team." />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── select --}}
{{-- Opened by the screenshot script, so the image shows the list rather than
     just the trigger. Everything else about it is the component's own doing. --}}
<div data-shot="select" class="shot shot-tall">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::select name="s{{ $loop->index }}" label="Plan" :options="['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team']" value="pro" help="You can change this later." />
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── checkbox --}}
<div data-shot="checkbox" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <x-ds::checkbox name="c1" label="I agree to the terms" />
                <x-ds::checkbox name="c2" label="Email me updates" help="At most one a month." :checked="true" />
                <x-ds::checkbox name="c3" label="Required" error="You must agree to continue." />
                <x-ds::checkbox name="c4" label="Not available on this plan" :disabled="true" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── radio --}}
<div data-shot="radio" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::radio-group label="Billing period" help="You can change this later.">
                <x-ds::radio name="r{{ $loop->index }}" value="m" label="Monthly" :checked="true" />
                <x-ds::radio name="r{{ $loop->index }}" value="y" label="Yearly" help="Two months free." />
                <x-ds::radio name="r{{ $loop->index }}" value="n" label="Not now" :disabled="true" />
            </x-ds::radio-group>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── panel --}}
<div data-shot="panel" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-4">
                <x-ds::panel title="Recent reports" action="View all" actionHref="#">
                    <x-ds::panel-row>Q3 revenue</x-ds::panel-row>
                    <x-ds::panel-row>Churn by cohort</x-ds::panel-row>
                </x-ds::panel>
                <x-ds::panel title="Billing" subtitle="Card ending 4242" icon="credit-card" iconTone="accent" body="plain">
                    Your next invoice is due on 1 October.
                </x-ds::panel>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── stat-tile --}}
<div data-shot="stat-tile" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="grid grid-cols-2 gap-3">
                <x-ds::stat-tile label="Projects" value="12" :delta="18" href="#" />
                <x-ds::stat-tile label="Collaborators" value="4" :delta="-9" caption="vs last week" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── table --}}
<div data-shot="table" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::table :columns="[
                'Report',
                ['label' => 'Status', 'width' => 'w-32'],
                ['label' => 'Total', 'align' => 'right'],
            ]">
                <x-ds::table-row>
                    <x-ds::table-cell>Q3 revenue</x-ds::table-cell>
                    <x-ds::table-cell :nowrap="true"><x-ds::badge tone="success">Ready</x-ds::badge></x-ds::table-cell>
                    <x-ds::table-cell align="right">£12,400</x-ds::table-cell>
                </x-ds::table-row>
                <x-ds::table-row>
                    <x-ds::table-cell>Churn by cohort</x-ds::table-cell>
                    <x-ds::table-cell :nowrap="true"><x-ds::badge tone="warning">Running</x-ds::badge></x-ds::table-cell>
                    <x-ds::table-cell align="right">&mdash;</x-ds::table-cell>
                </x-ds::table-row>
            </x-ds::table>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── tabs --}}
<div data-shot="tabs" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::tabs label="Report sections">
                <x-ds::tab controls="d-p1-{{ $loop->index }}" :active="true">Overview</x-ds::tab>
                <x-ds::tab controls="d-p2-{{ $loop->index }}" count="12">Open</x-ds::tab>
                <x-ds::tab controls="d-p3-{{ $loop->index }}" count="4">Flagged</x-ds::tab>
                <x-ds::tab controls="d-p4-{{ $loop->index }}" :disabled="true">Archived</x-ds::tab>
            </x-ds::tabs>
            <x-ds::tab-panel id="d-p1-{{ $loop->index }}" :active="true" class="text-body text-fg-body">
                The panel the selected tab controls.
            </x-ds::tab-panel>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── breadcrumb --}}
<div data-shot="breadcrumb" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::breadcrumb>
                <x-ds::breadcrumb-item href="#">Home</x-ds::breadcrumb-item>
                <x-ds::breadcrumb-item href="#">Reports</x-ds::breadcrumb-item>
                <x-ds::breadcrumb-item href="#">2026</x-ds::breadcrumb-item>
                <x-ds::breadcrumb-item :current="true">Q3 revenue</x-ds::breadcrumb-item>
            </x-ds::breadcrumb>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── nav-item --}}
<div data-shot="nav-item" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="max-w-56 rounded-panel border border-border bg-surface p-2">
                <x-ds::nav-item href="#" label="Dashboard" :active="true">
                    <x-slot:icon><x-ds::icon name="home" /></x-slot:icon>
                </x-ds::nav-item>
                <x-ds::nav-item href="#" label="Reports">
                    <x-slot:icon><x-ds::icon name="chart-bar" /></x-slot:icon>
                </x-ds::nav-item>
                <x-ds::nav-item href="#" label="Team" badge="3">
                    <x-slot:icon><x-ds::icon name="users" /></x-slot:icon>
                </x-ds::nav-item>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── empty-state --}}
<div data-shot="empty-state" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::empty-state title="No reports yet" description="Reports appear here once a project has run for a full day." icon="document-text">
                <x-slot:action><x-ds::button size="sm">New report</x-ds::button></x-slot:action>
            </x-ds::empty-state>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── skeleton --}}
<div data-shot="skeleton" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="rounded-control border border-border bg-surface p-4">
                <div class="flex items-center gap-3">
                    <x-ds::skeleton variant="circle" size="md" class="w-auto" />
                    <x-ds::skeleton :lines="2" />
                </div>
                <div class="mt-4"><x-ds::skeleton variant="block" size="sm" /></div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── dropdown --}}
<div data-shot="dropdown" class="shot shot-tall">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex justify-center">
                <x-ds::dropdown open="shown" placement="bottom-start">
                    <x-slot:trigger>
                        <x-ds::button variant="secondary" size="sm">
                            Actions <x-ds::icon name="chevron-down" variant="mini" size="4" />
                        </x-ds::button>
                    </x-slot:trigger>
                    <x-ds::dropdown-item icon="pencil-square" href="#">Edit report</x-ds::dropdown-item>
                    <x-ds::dropdown-item icon="document-duplicate" href="#">Duplicate</x-ds::dropdown-item>
                    <x-ds::dropdown-item icon="archive-box" :disabled="true">Archive</x-ds::dropdown-item>
                    <x-ds::dropdown-item icon="trash" tone="danger" as="button">Delete</x-ds::dropdown-item>
                </x-ds::dropdown>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── tooltip --}}
<div data-shot="tooltip" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex justify-center py-8">
                <x-ds::tooltip text="Delete this report permanently" placement="top">
                    <x-ds::button variant="secondary" size="sm" iconOnly aria-label="Delete">
                        <x-ds::icon name="trash" size="4" />
                    </x-ds::button>
                </x-ds::tooltip>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── toast --}}
<div data-shot="toast" class="shot shot-overlay">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }} relative [transform:translateZ(0)]">
            <x-ds::toast-region position="bottom-right">
                <x-ds::toast tone="success" title="Report exported" dismiss="0">The CSV is in your downloads.</x-ds::toast>
                <x-ds::toast tone="danger" title="Export failed">Try a smaller date range.</x-ds::toast>
                <x-ds::toast>Draft saved.</x-ds::toast>
            </x-ds::toast-region>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── modal --}}
<div data-shot="modal" class="shot shot-overlay">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }} relative [transform:translateZ(0)]">
            <x-ds::modal open="shown" size="sm" title="Delete report?" description="This cannot be undone.">
                The report and every export made from it are removed immediately.
                <x-slot:footer>
                    <x-ds::button variant="ghost" size="sm">Cancel</x-ds::button>
                    <x-ds::button variant="danger" size="sm">Delete report</x-ds::button>
                </x-slot:footer>
            </x-ds::modal>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── drawer --}}
<div data-shot="drawer" class="shot shot-overlay">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }} relative [transform:translateZ(0)]">
            <x-ds::drawer open="shown" title="Filters" side="right" size="sm">
                <div class="flex flex-col gap-3">
                    <x-ds::checkbox name="dr{{ $loop->index }}a" label="Only failed runs" />
                    <x-ds::checkbox name="dr{{ $loop->index }}b" label="Include archived" :checked="true" />
                </div>
                <x-slot:footer>
                    <x-ds::button variant="ghost" size="sm">Reset</x-ds::button>
                    <x-ds::button size="sm">Apply</x-ds::button>
                </x-slot:footer>
            </x-ds::drawer>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── sheet --}}
<div data-shot="sheet" class="shot shot-overlay">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }} relative [transform:translateZ(0)]">
            <x-ds::sheet open="shown" title="More">
                <div class="px-3">
                    <x-ds::sheet-item href="#" label="Projects" description="View and manage every project">
                        <x-slot:icon><x-ds::icon name="document-text" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                    <x-ds::sheet-item href="#" label="Admin panel" tone="accent">
                        <x-slot:icon><x-ds::icon name="shield-check" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                    <x-ds::sheet-item label="Sign out" tone="danger" as="button">
                        <x-slot:icon><x-ds::icon name="logout" size="5" /></x-slot:icon>
                    </x-ds::sheet-item>
                </div>
            </x-ds::sheet>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── bottom-nav --}}
<div data-shot="bottom-nav" class="shot shot-short">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }} relative [transform:translateZ(0)]">
            <x-ds::bottom-nav>
                <x-ds::bottom-nav-item href="#" label="Home" :active="true">
                    <x-slot:icon><x-ds::icon name="home" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
                <x-ds::bottom-nav-item href="#" label="Reports">
                    <x-slot:icon><x-ds::icon name="chart-bar" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
                <x-ds::bottom-nav-item href="#" label="Team">
                    <x-slot:icon><x-ds::icon name="users" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
                <x-ds::bottom-nav-item label="More">
                    <x-slot:icon><x-ds::icon name="menu" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
            </x-ds::bottom-nav>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── switch --}}
<div data-shot="switch" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-4 rounded-control border border-border bg-surface p-4">
                <x-ds::switch name="sw{{ $loop->index }}a" label="Email notifications" help="At most one a month." :checked="true" />
                <x-ds::switch name="sw{{ $loop->index }}b" label="Beta features" help="Things that may change without warning." />
                <x-ds::switch name="sw{{ $loop->index }}c" label="Not on this plan" :disabled="true" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── accordion --}}
<div data-shot="accordion" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::accordion open="billing">
                <x-ds::accordion-item title="Billing" name="billing">
                    Your card ending 4242 is charged on the first of each month.
                </x-ds::accordion-item>
                <x-ds::accordion-item title="Security" name="security">Two-factor is on.</x-ds::accordion-item>
                <x-ds::accordion-item title="Notifications" name="notifications">Weekly digest.</x-ds::accordion-item>
            </x-ds::accordion>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── combobox --}}
<div data-shot="combobox" class="shot shot-calendar">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::combobox
                name="cb{{ $loop->index }}"
                label="Tags"
                :options="['a11y' => 'Accessibility', 'billing' => 'Billing', 'compliance' => 'Compliance', 'design' => 'Design', 'infra' => 'Infrastructure']"
                :value="['a11y', 'billing']"
                :multiple="true"
                help="Start typing to filter."
            />
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── file-upload --}}
<div data-shot="file-upload" class="shot shot-calendar">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::file-upload
                name="fu{{ $loop->index }}[]"
                label="Attachments"
                :multiple="true"
                accept="image/*,.pdf"
                :maxSize="5242880"
            />
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── date-picker --}}
<div data-shot="date-picker" class="shot shot-calendar">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::date-picker
                name="dp{{ $loop->index }}"
                label="Reporting period"
                :range="true"
                :value="['2026-09-07', '2026-09-19']"
            />
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── In situ --}}