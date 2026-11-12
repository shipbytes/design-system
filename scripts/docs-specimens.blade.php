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

{{-- The package's script, where a host's layout puts it: before Alpine, which
     build-docs.mjs appends after everything here. Inline, because the page is
     opened from disk and has no route to fetch it from. --}}
<x-ds::scripts inline />


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
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-ds::button variant="secondary" size="sm">Save photo</x-ds::button>
                <x-ds::button variant="ghost" size="sm" tone="danger">Remove</x-ds::button>
                <x-ds::button variant="secondary" tone="danger">Revoke access</x-ds::button>
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

{{-- ─────────────────────────── avatar group --}}
{{-- On the panel AND on the sunken ground, because the ring is the surface
     colour and has to look deliberate on both. --}}
@php
    $team = [
        ['name' => 'Ada Lovelace'],
        ['name' => 'Grace Hopper'],
        ['name' => 'Alan Turing'],
        ['name' => 'Katherine Johnson'],
        ['name' => 'Edsger Dijkstra'],
        ['name' => 'Barbara Liskov'],
    ];
@endphp
<div data-shot="avatar-group" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="rounded-panel border border-border bg-surface p-4">
                <div class="flex flex-wrap items-center gap-6">
                    <x-ds::avatar-group :avatars="$team" :max="4" label="Project members" />
                    <x-ds::avatar-group :avatars="$team" :max="3" size="md" :total="24" />
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-6 px-4">
                <x-ds::avatar-group :avatars="$team" :max="5" size="xs" />
                <x-ds::avatar-group :avatars="array_slice($team, 0, 3)" size="lg" />
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
                <x-ds::input name="a5" label="Email" value="ada@example.com" readonly help="The address this reset link was sent to." />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── otp-input --}}
<div data-shot="otp-input" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-5">
                <x-ds::otp-input name="otp{{ $loop->index }}" label="Verification code" value="4821" help="We sent a six-digit code to your phone." />
                <x-ds::otp-input name="pin{{ $loop->index }}" label="PIN" :length="4" value="12" :masked="true" />
                <x-ds::otp-input name="bad{{ $loop->index }}" label="Verification code" value="123456" error="That code has expired. Request a new one." />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── tag-input --}}
<div data-shot="tag-input" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-5">
                <x-ds::tag-input name="to{{ $loop->index }}" label="Invite by email" :value="['maya@example.com', 'ada@example.com', 'grace@example.com']" validate="email" placeholder="Add an address" help="Press Enter or comma after each address." />
                <x-ds::tag-input name="tags{{ $loop->index }}" label="Tags" :value="['billing', 'urgent']" placeholder="Add a tag" error="Choose at most two tags." />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── copy-button
     The second button is pressed by build-docs.mjs, so the picture shows the
     copied face beside the resting one, at the same width. --}}
<div data-shot="copy-button" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-2">
                    <code id="docs-key-{{ $loop->index }}" class="rounded-control border border-border bg-surface px-3 py-2 font-mono text-meta text-fg-body">sk_live_51HxQ2c4f9A</code>
                    <x-ds::copy-button target="docs-key-{{ $loop->index }}" />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ds::copy-button value="https://example.com/invite/7f3a" label="Copy link" />
                    <x-ds::copy-button value="https://example.com/invite/7f3a" label="Copy link" data-shot-press />
                    <x-ds::copy-button value="https://example.com/invite/7f3a" variant="primary" label="Copy invite link" />
                    <x-ds::copy-button value="https://example.com/hooks/42" :iconOnly="true" variant="ghost" label="Copy webhook URL" />
                    <x-ds::copy-button value="42" size="sm" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── rating --}}
<div data-shot="rating" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-5">
                <x-ds::rating name="visit{{ $loop->index }}" label="How was your visit?" :value="4" help="Shown to the host after your stay." />
                <x-ds::rating name="clean{{ $loop->index }}" label="Cleanliness" size="sm" error="Choose a rating, or pick No rating." />
                <div class="flex flex-wrap items-end gap-8">
                    <x-ds::rating :readonly="true" :value="4.5" label="Average, 128 reviews" />
                    <x-ds::rating :readonly="true" :value="3.2" size="sm" />
                    <x-ds::rating :readonly="true" :value="null" size="sm" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── chip-group --}}
<div data-shot="chip-group" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-5">
                <x-ds::chip-group name="status{{ $loop->index }}" label="Status">
                    <x-ds::chip value="open" :checked="true" :count="12">Open</x-ds::chip>
                    <x-ds::chip value="pending" :count="5">Pending</x-ds::chip>
                    <x-ds::chip value="closed" :count="48">Closed</x-ds::chip>
                </x-ds::chip-group>
                <x-ds::chip-group name="labels{{ $loop->index }}" label="Labels" :multiple="true">
                    <x-ds::chip value="billing" icon="credit-card" :checked="true">Billing</x-ds::chip>
                    <x-ds::chip value="bug" icon="bug-ant" :checked="true">Bug</x-ds::chip>
                    <x-ds::chip value="sales" icon="banknotes">Sales</x-ds::chip>
                    <x-ds::chip value="legal" icon="scale" :disabled="true">Legal</x-ds::chip>
                </x-ds::chip-group>
                <x-ds::chip-group label="Show" size="sm">
                    <x-ds::chip href="#all" :active="true">All tickets</x-ds::chip>
                    <x-ds::chip href="#mine">Assigned to me</x-ds::chip>
                    <x-ds::chip href="#unassigned">Unassigned</x-ds::chip>
                </x-ds::chip-group>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── radio-cards --}}
<div data-shot="radio-cards" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-6">
                <x-ds::radio-cards name="plan{{ $loop->index }}" label="Plan" help="Change or cancel at any time." :columns="3">
                    <x-ds::radio-card value="free" title="Free" description="For trying it out.">
                        <x-slot:meta>$0</x-slot:meta>
                    </x-ds::radio-card>
                    <x-ds::radio-card value="pro" title="Pro" description="For a small team." :checked="true">
                        <x-slot:meta>$12 a month</x-slot:meta>
                        Ten seats, priority support.
                    </x-ds::radio-card>
                    <x-ds::radio-card value="scale" title="Scale" description="Talk to us first." :disabled="true">
                        <x-slot:meta>Custom</x-slot:meta>
                    </x-ds::radio-card>
                </x-ds::radio-cards>
                <x-ds::radio-cards name="ship{{ $loop->index }}" label="Shipping" :columns="2" error="Choose how it should be sent.">
                    <x-ds::radio-card value="standard" title="Standard" description="3 to 5 working days.">
                        <x-slot:icon><x-ds::icon name="truck" size="5" /></x-slot:icon>
                        <x-slot:meta>Free</x-slot:meta>
                    </x-ds::radio-card>
                    <x-ds::radio-card value="express" title="Express" description="Next working day.">
                        <x-slot:icon><x-ds::icon name="bolt" size="5" /></x-slot:icon>
                        <x-slot:meta>$9</x-slot:meta>
                    </x-ds::radio-card>
                </x-ds::radio-cards>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── segmented-control --}}
<div data-shot="segmented-control" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col items-start gap-5">
                <x-ds::segmented-control name="view{{ $loop->index }}" label="View">
                    <x-ds::segment value="list" icon="list-bullet" :checked="true">List</x-ds::segment>
                    <x-ds::segment value="board" icon="view-columns">Board</x-ds::segment>
                    <x-ds::segment value="calendar" icon="calendar-days">Calendar</x-ds::segment>
                </x-ds::segmented-control>
                <div class="flex flex-wrap items-center gap-4">
                    <x-ds::segmented-control name="period{{ $loop->index }}" label="Period" :hideLabel="true" size="sm">
                        <x-ds::segment value="day">Day</x-ds::segment>
                        <x-ds::segment value="week" :checked="true">Week</x-ds::segment>
                        <x-ds::segment value="month">Month</x-ds::segment>
                        <x-ds::segment value="year" :disabled="true">Year</x-ds::segment>
                    </x-ds::segmented-control>
                    <x-ds::segmented-control name="layout{{ $loop->index }}" label="Layout" :hideLabel="true" size="sm">
                        <x-ds::segment value="grid" icon="squares-2x2" :iconOnly="true" :checked="true">Grid</x-ds::segment>
                        <x-ds::segment value="rows" icon="table-cells" :iconOnly="true">Rows</x-ds::segment>
                    </x-ds::segmented-control>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── button-group --}}
<div data-shot="button-group" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col items-start gap-4">
                <x-ds::button-group label="Alignment">
                    <x-ds::button>Left</x-ds::button>
                    <x-ds::button>Center</x-ds::button>
                    <x-ds::button>Right</x-ds::button>
                </x-ds::button-group>
                <div class="flex flex-wrap items-center gap-4">
                    <x-ds::button-group label="Save options" variant="primary">
                        <x-ds::button>Save changes</x-ds::button>
                        <x-ds::dropdown>
                            <x-slot:trigger>
                                <x-ds::button iconOnly aria-label="More save options"><x-ds::icon name="chevron-down" /></x-ds::button>
                            </x-slot:trigger>
                            <x-ds::dropdown-item href="#button-group">Save as draft</x-ds::dropdown-item>
                        </x-ds::dropdown>
                    </x-ds::button-group>
                    <x-ds::button-group label="Pages" size="sm">
                        <x-ds::button iconOnly aria-label="Previous page"><x-ds::icon name="chevron-left" /></x-ds::button>
                        <x-ds::button>Page 2 of 9</x-ds::button>
                        <x-ds::button iconOnly aria-label="Next page"><x-ds::icon name="chevron-right" /></x-ds::button>
                    </x-ds::button-group>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── toolbar --}}
<div data-shot="toolbar" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col items-start gap-5">
                <x-ds::toolbar label="Formatting">
                    <x-ds::toolbar-button icon="arrow-uturn-left" label="Undo" :iconOnly="true" />
                    <x-ds::toolbar-button icon="arrow-uturn-right" label="Redo" :iconOnly="true" :disabled="true" />
                    <x-ds::toolbar-separator />
                    <x-ds::toggle-group label="Text style" type="multiple">
                        <x-ds::toggle value="bold" label="Bold" icon="bold" :iconOnly="true" :pressed="true" />
                        <x-ds::toggle value="italic" label="Italic" icon="italic" :iconOnly="true" data-shot-hover />
                        <x-ds::toggle value="underline" label="Underline" icon="underline" :iconOnly="true" />
                    </x-ds::toggle-group>
                    <x-ds::toolbar-separator />
                    <x-ds::toolbar-button icon="link" label="Link" />
                </x-ds::toolbar>
                <x-ds::toolbar label="3 selected" variant="floating">
                    <span class="px-2 text-meta font-medium text-fg-muted">3 selected</span>
                    <x-ds::toolbar-separator />
                    <x-ds::toolbar-button icon="archive-box" label="Archive" />
                    <x-ds::toolbar-button icon="tag" label="Label" />
                    <x-ds::toolbar-button icon="trash" label="Delete" />
                </x-ds::toolbar>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── toggle-group --}}
<div data-shot="toggle-group" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col items-start gap-4">
                <x-ds::toggle-group label="Alignment" name="align{{ $loop->index }}">
                    <x-ds::toggle value="left" label="Left" icon="bars-3-bottom-left" :iconOnly="true" :pressed="true" />
                    <x-ds::toggle value="center" label="Center" icon="bars-3" :iconOnly="true" />
                    <x-ds::toggle value="right" label="Right" icon="bars-3-bottom-right" :iconOnly="true" />
                </x-ds::toggle-group>
                <x-ds::toggle-group label="Show" type="multiple" name="show{{ $loop->index }}" size="sm">
                    <x-ds::toggle value="open" label="Open" :pressed="true" />
                    <x-ds::toggle value="mine" label="Assigned to me" :pressed="true" />
                    <x-ds::toggle value="flagged" label="Flagged" />
                    <x-ds::toggle value="spam" label="Spam" :disabled="true" />
                </x-ds::toggle-group>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── number-field --}}
<div data-shot="number-field" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-5">
                <x-ds::number-field name="seats{{ $loop->index }}" label="Seats" :value="3" :min="1" :max="10" help="Up to ten on this plan." class="max-w-48" />
                <x-ds::number-field name="max{{ $loop->index }}" label="Seats" :value="10" :min="1" :max="10" error="This plan has ten seats. Upgrade for more." class="max-w-48" />
                <div class="flex items-center gap-3">
                    <span class="text-body text-fg-body">Quantity</span>
                    <x-ds::number-field name="qty{{ $loop->index }}" aria-label="Quantity" :value="2" :min="0" size="sm" class="w-28" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── password-strength --}}
{{-- Typed into by the screenshot script, so the meter shows the level the
     component's own script scored, and the first field is revealed with its
     own button. --}}
<div data-shot="password-strength" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-5">
                <div>
                    <x-ds::input type="password" id="d-pw-a{{ $loop->index }}" name="pa{{ $loop->index }}" label="New password" :reveal="true" data-shot-type="Sunflower7" />
                    <x-ds::password-strength for="d-pw-a{{ $loop->index }}" />
                </div>
                <div>
                    <x-ds::input type="password" id="d-pw-b{{ $loop->index }}" name="pb{{ $loop->index }}" label="New password" :reveal="true" data-shot-type="correct horse battery staple" />
                    <x-ds::password-strength for="d-pw-b{{ $loop->index }}" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── pagination --}}
{{-- Wide: the count and the controls share one row from the sm breakpoint up,
     and at 500px a side they would collide. --}}
<div data-shot="pagination" class="shot shot-wide">
    @php
        $pages = (new \Illuminate\Pagination\LengthAwarePaginator(range(1, 15), 237, 15, 8, ['path' => '/reports']))->onEachSide(1);
        $simple = new \Illuminate\Pagination\Paginator(range(1, 16), 15, 2, ['path' => '/reports']);
    @endphp
    @foreach ($themes as $t)
        <div class="pane bg-surface-sunken {{ $t }}">
            {{ $pages->links('ds::pagination') }}
            <div class="mt-5">{{ $simple->links('ds::simple-pagination') }}</div>
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
            {{-- hideLabel: the row already names the thing, so the label is the
                 accessible name only. --}}
            <x-ds::table class="mt-5">
                <thead class="bg-surface-subtle">
                    <tr>
                        <th scope="col" class="w-12 px-4 py-3"><x-ds::checkbox name="c5{{ $t }}" label="Select all" :hideLabel="true" /></th>
                        <th scope="col" class="px-4 py-3 text-left text-overline uppercase text-fg-muted">Report</th>
                    </tr>
                </thead>
                <x-ds::table-row>
                    <x-ds::table-cell><x-ds::checkbox name="c6{{ $t }}" label="Select Q3 revenue" :hideLabel="true" :checked="true" /></x-ds::table-cell>
                    <x-ds::table-cell>Q3 revenue</x-ds::table-cell>
                </x-ds::table-row>
            </x-ds::table>
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
                {{-- Zero is grey and unsigned. It rendered a green +0% until v1.1,
                     which is the sort of thing only a picture shows. --}}
                <x-ds::stat-tile label="Open tasks" value="31" :delta="0" />
                <x-ds::stat-tile label="Page views" value="2847" caption="Total public views" />
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

{{-- ─────────────────────────── progress --}}
<div data-shot="progress" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-5 rounded-panel border border-border bg-surface p-4">
                <x-ds::progress label="Uploading report.pdf" :value="64" :showValue="true" />
                <x-ds::progress label="Importing contacts" :value="3" :max="12" tone="accent">3 of 12 files</x-ds::progress>
                <x-ds::progress label="Preparing the export" size="sm" />
                <div class="flex flex-wrap items-center gap-5">
                    <x-ds::progress label="Synced" :value="100" variant="circle" tone="success" :showValue="true" :hideLabel="true" />
                    <x-ds::progress label="Disk" :value="91" variant="circle" tone="warning" :showValue="true" :hideLabel="true" />
                    <x-ds::progress label="Building" :value="40" variant="circle" size="sm" :showValue="true" />
                    <x-ds::progress label="Waiting" variant="circle" size="sm" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── usage meter --}}
@php
    $storage = [
        ['label' => 'Files', 'value' => 4.5, 'tone' => 'accent'],
        ['label' => 'Backups', 'value' => 2, 'tone' => 'success'],
        ['label' => 'Logs', 'value' => 1.25, 'tone' => 'warning'],
    ];
@endphp
<div data-shot="usage-meter" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-5 rounded-panel border border-border bg-surface p-4">
                <x-ds::usage-meter label="AI credits" :used="18" :limit="50" unit="credits" />
                <x-ds::usage-meter label="Team seats" :used="9" :limit="10" unit="seats" />
                <x-ds::usage-meter label="Storage" :limit="10" unit="GB" :segments="$storage" />
                <x-ds::usage-meter label="Emails this month" :used="5240" :limit="5000" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── stepper --}}
{{-- The widest shot, because the stepper collapses below 40rem of ITS OWN
     width: at 900px a pane the horizontal one has room to show every title,
     and the narrow box beside the vertical one shows what it collapses to. --}}
<div data-shot="stepper" class="shot shot-widest">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="rounded-panel border border-border bg-surface p-5">
                <x-ds::stepper label="Checkout progress">
                    <x-ds::step status="complete" title="Account" description="ada@example.com" href="#account" />
                    <x-ds::step status="current" title="Plan" description="Pick what you need" />
                    <x-ds::step status="upcoming" title="Billing" />
                    <x-ds::step status="upcoming" title="Review" />
                </x-ds::stepper>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="rounded-panel border border-border bg-surface p-5">
                    <x-ds::stepper orientation="vertical" label="Setup">
                        <x-ds::step status="complete" title="Connect the repository" description="github.com/acme/api" />
                        <x-ds::step status="error" title="Install the app" description="Permission was refused" />
                        <x-ds::step status="upcoming" title="Deploy" />
                    </x-ds::stepper>
                </div>
                <div class="max-w-80 rounded-panel border border-border bg-surface p-5">
                    <x-ds::stepper label="Checkout progress">
                        <x-ds::step status="complete" title="Account" />
                        <x-ds::step status="current" title="Plan" />
                        <x-ds::step status="upcoming" title="Billing" />
                        <x-ds::step status="upcoming" title="Review" />
                    </x-ds::stepper>
                </div>
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

{{-- ─────────────────────────── popover
     Open from the host's `shown`, which is true from the start, so it takes
     no focus and both panes hold one open at once. --}}
<div data-shot="popover" class="shot shot-tall">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex justify-center">
                <x-ds::popover open="shown" labelledby="pop-title-{{ $loop->index }}" placement="bottom">
                    <x-slot:trigger>
                        <x-ds::button variant="secondary" size="sm">
                            Filters <x-ds::icon name="chevron-down" variant="mini" size="4" />
                        </x-ds::button>
                    </x-slot:trigger>
                    <h3 id="pop-title-{{ $loop->index }}" class="text-section font-semibold text-fg">Filter runs</h3>
                    <div class="mt-3 flex flex-col gap-2">
                        <x-ds::checkbox name="pf{{ $loop->index }}" label="Only failed runs" :checked="true" />
                        <x-ds::checkbox name="pa{{ $loop->index }}" label="Include archived" />
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <x-ds::button variant="ghost" size="sm">Reset</x-ds::button>
                        <x-ds::button size="sm">Apply</x-ds::button>
                    </div>
                </x-ds::popover>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── hover-card
     Opened the way a reader opens it, by resting the pointer on the name:
     build-docs.mjs operates it and waits out the delay. --}}
<div data-shot="hover-card" class="shot shot-tall">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <p class="text-body text-fg-body">
                Assigned to
                <x-ds::hover-card>
                    <x-slot:trigger>
                        <a href="#" data-shot-hover class="font-medium text-fg underline decoration-border-strong underline-offset-4">Ada Lovelace</a>
                    </x-slot:trigger>
                    <span class="flex items-center gap-3">
                        <x-ds::avatar name="Ada Lovelace" />
                        <span class="min-w-0">
                            <span class="block font-medium text-fg">Ada Lovelace</span>
                            <span class="block text-meta text-fg-muted">Analyst, Engines team</span>
                        </span>
                    </span>
                    <span class="mt-3 block text-meta">Owns the quarterly revenue and churn reports. Usually replies within a day.</span>
                </x-ds::hover-card>
                since Monday.
            </p>
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

{{-- ─────────────────────────── modal, wide --}}
<div data-shot="modal-wide" class="shot shot-wide shot-overlay">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }} relative [transform:translateZ(0)]">
            <x-ds::modal open="shown" size="3xl" title="invoice-2026-08.csv" description="First 3 of 1,204 rows">
                <x-ds::table :columns="[
                    ['label' => 'Reference', 'width' => 'w-32'],
                    'Account',
                    ['label' => 'Amount', 'align' => 'right', 'width' => 'w-32'],
                ]">
                    @foreach ([
                        ['INV-4821', 'Northwind Trading', '1,240.00'],
                        ['INV-4822', 'Contoso Logistics', '318.50'],
                        ['INV-4823', 'Fabrikam Industrial', '9,700.25'],
                    ] as [$ref, $account, $amount])
                        <x-ds::table-row>
                            <x-ds::table-cell :nowrap="true" class="font-medium text-fg">{{ $ref }}</x-ds::table-cell>
                            <x-ds::table-cell>{{ $account }}</x-ds::table-cell>
                            <x-ds::table-cell align="right" class="tabular-nums">{{ $amount }}</x-ds::table-cell>
                        </x-ds::table-row>
                    @endforeach
                </x-ds::table>
                <x-slot:footer>
                    <x-ds::button variant="secondary" size="sm">Close</x-ds::button>
                    <x-ds::button size="sm">Import</x-ds::button>
                </x-slot:footer>
            </x-ds::modal>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── drawer, wide --}}
<div data-shot="drawer-wide" class="shot shot-widest shot-overlay">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }} relative [transform:translateZ(0)]">
            <x-ds::drawer open="shown" title="Run #4821" side="right" size="2xl">
                <x-ds::panel title="Summary">
                    @foreach ([['Started', '26 Aug 2026, 09:14'], ['Duration', '4m 08s'], ['Steps', '31 of 31']] as [$k, $v])
                        <x-ds::panel-row>
                            <span class="w-32 shrink-0 text-fg-muted">{{ $k }}</span>
                            <span class="min-w-0 flex-1 text-fg">{{ $v }}</span>
                        </x-ds::panel-row>
                    @endforeach
                    <x-ds::panel-row>
                        <span class="w-32 shrink-0 text-fg-muted">Result</span>
                        <span class="min-w-0 flex-1"><x-ds::badge tone="success">Passed</x-ds::badge></span>
                    </x-ds::panel-row>
                </x-ds::panel>
                <x-slot:footer>
                    <x-ds::button variant="secondary" size="sm">Download log</x-ds::button>
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
                <x-ds::bottom-nav-item href="#" label="Inbox" :badge="3">
                    <x-slot:icon><x-ds::icon name="inbox" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
                <x-ds::bottom-nav-item href="#" label="Support" :badge="12">
                    <x-slot:icon><x-ds::icon name="lifebuoy" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
                <x-ds::bottom-nav-item href="#" label="Reports">
                    <x-slot:icon><x-ds::icon name="chart-bar" size="6" /></x-slot:icon>
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