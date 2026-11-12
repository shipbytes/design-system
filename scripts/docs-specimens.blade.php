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

{{-- ─────────────────────────── swap-button
     Each pair is one button at rest and the same button swapped, drawn so
     by the server (`swapped`, never reverting), so the picture shows both
     faces at the one width the wider reserves. --}}
<div data-shot="swap-button" class="shot">
    @foreach ($themes as $t)
        <div class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col items-start gap-4">
                <div class="flex items-center gap-2">
                    <x-ds::swap-button variant="primary" :revert="0">
                        <x-ds::icon name="plus" /> Follow
                        <x-slot:swapped><x-ds::icon name="check" /> Following</x-slot:swapped>
                    </x-ds::swap-button>
                    <x-ds::swap-button variant="secondary" :revert="0" :swapped="true">
                        <x-ds::icon name="plus" /> Follow
                        <x-slot:swapped><x-ds::icon name="check" /> Following</x-slot:swapped>
                    </x-ds::swap-button>
                </div>
                <div class="flex items-center gap-2">
                    <x-ds::swap-button event="docs-never" variant="secondary">Save changes<x-slot:swapped><x-ds::icon name="check" /> Saved</x-slot:swapped></x-ds::swap-button>
                    <x-ds::swap-button event="docs-never" variant="secondary" :revert="0" :swapped="true">Save changes<x-slot:swapped><x-ds::icon name="check" /> Saved</x-slot:swapped></x-ds::swap-button>
                </div>
                <div class="flex items-center gap-2">
                    <x-ds::swap-button variant="ghost" size="sm" :revert="0"><x-ds::icon name="star" /> Star<x-slot:swapped><x-ds::icon name="star" variant="solid" class="text-warning" /> Starred</x-slot:swapped></x-ds::swap-button>
                    <x-ds::swap-button variant="ghost" size="sm" :revert="0" :swapped="true"><x-ds::icon name="star" /> Star<x-slot:swapped><x-ds::icon name="star" variant="solid" class="text-warning" /> Starred</x-slot:swapped></x-ds::swap-button>
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

{{-- ─────────────────────────── button-group-expanding
     The pressed and current buttons, which stay open; hover and keyboard
     focus open the others the same way. --}}
<div data-shot="button-group-expanding" class="shot">
    @foreach ($themes as $t)
        <div class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col items-start gap-4">
                <x-ds::button-group label="Text style" expanding class="w-72">
                    <x-ds::button icon="bold" aria-pressed="true">Bold</x-ds::button>
                    <x-ds::button icon="italic" aria-pressed="false">Italic</x-ds::button>
                    <x-ds::button icon="underline" aria-pressed="false">Underline</x-ds::button>
                    <x-ds::button icon="strikethrough" aria-pressed="false">Strikethrough</x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Views" expanding variant="ghost" class="w-72">
                    <x-ds::button icon="home" href="#home" aria-current="page">Home</x-ds::button>
                    <x-ds::button icon="inbox" href="#inbox">Inbox</x-ds::button>
                    <x-ds::button icon="calendar" href="#calendar">Calendar</x-ds::button>
                    <x-ds::button icon="cog-6-tooth" href="#settings">Settings</x-ds::button>
                </x-ds::button-group>
                <x-ds::button-group label="Reply" expanding variant="primary" size="sm" class="w-72">
                    <x-ds::button icon="arrow-uturn-left" aria-pressed="true">Reply</x-ds::button>
                    <x-ds::button icon="arrow-uturn-right">Forward</x-ds::button>
                    <x-ds::button icon="archive-box">Archive</x-ds::button>
                </x-ds::button-group>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── icon-swap
     Each control twice: at rest, and in the state its button says it is in
     (aria-expanded, aria-pressed, data-state), which is all the swap reads. --}}
<div data-shot="icon-swap" class="shot">
    @foreach ($themes as $t)
        <div class="pane bg-surface-sunken {{ $t }}">
            <div class="grid grid-cols-[auto_auto_auto] items-center justify-start gap-x-3 gap-y-3 text-meta text-fg-body">
                @foreach ([false, true] as $on)
                    <span>{{ $on ? 'On' : 'At rest' }}</span>
                    <div class="flex items-center gap-2">
                        <x-ds::button variant="secondary" iconOnly aria-label="Menu" aria-expanded="{{ $on ? 'true' : 'false' }}"><x-ds::icon-swap preset="menu" /></x-ds::button>
                        <x-ds::button variant="secondary" iconOnly aria-label="Menu" aria-expanded="{{ $on ? 'true' : 'false' }}"><x-ds::icon-swap from="bars-3" to="x-mark" /></x-ds::button>
                        <x-ds::button variant="secondary" iconOnly aria-label="Play" aria-pressed="{{ $on ? 'true' : 'false' }}"><x-ds::icon-swap from="play" to="pause" preset="rotate" variant="solid" size="4" /></x-ds::button>
                        <x-ds::button variant="secondary" iconOnly aria-label="Add" data-state="{{ $on ? 'open' : 'closed' }}"><x-ds::icon-swap from="plus" to="x-mark" preset="rotate" /></x-ds::button>
                        <x-ds::button variant="secondary" iconOnly aria-label="Dark mode" aria-pressed="{{ $on ? 'true' : 'false' }}"><x-ds::icon-swap from="sun" to="moon" preset="flip" /></x-ds::button>
                    </div>
                    <x-ds::button variant="secondary" size="sm" aria-expanded="{{ $on ? 'true' : 'false' }}">Filters <x-ds::icon-swap from="chevron-down" preset="rotate" size="4" class="text-fg-muted" /></x-ds::button>
                @endforeach
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

{{-- ─────────────────────────── table-sortable
     Sorted by Total, descending. Report is sortable too, and shows its arrow
     only under the pointer or keyboard focus, which one image of two panes
     cannot hold in both. --}}
<div data-shot="table-sortable" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::table sort="total" direction="desc" sortUrl="#{key}-{direction}" :columns="[
                ['label' => 'Report', 'key' => 'name', 'sortable' => true],
                ['label' => 'Status', 'width' => 'w-32'],
                ['label' => 'Total', 'key' => 'total', 'align' => 'right', 'sortable' => true],
            ]">
                <x-ds::table-row>
                    <x-ds::table-cell>Q3 revenue</x-ds::table-cell>
                    <x-ds::table-cell :nowrap="true"><x-ds::badge tone="success">Ready</x-ds::badge></x-ds::table-cell>
                    <x-ds::table-cell align="right" class="tabular-nums">£12,400</x-ds::table-cell>
                </x-ds::table-row>
                <x-ds::table-row>
                    <x-ds::table-cell>Churn by cohort</x-ds::table-cell>
                    <x-ds::table-cell :nowrap="true"><x-ds::badge tone="warning">Running</x-ds::badge></x-ds::table-cell>
                    <x-ds::table-cell align="right" class="tabular-nums">£3,150</x-ds::table-cell>
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

{{-- ─────────────────────────── sub-nav
     The same rail twice: expanded, with the current page's section open and
     another closed, and collapsed to icons, where each section is a link. --}}
<div data-shot="sub-nav" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }} flex items-start gap-4">
            @foreach ([false, true] as $collapsed)
                <div x-data="{ railCollapsed: @js($collapsed) }" @class(['rounded-panel border border-border bg-surface p-2', 'w-56' => ! $collapsed])>
                    <x-ds::nav-item href="#" label="Dashboard" collapsedWhen="railCollapsed">
                        <x-slot:icon><x-ds::icon name="home" /></x-slot:icon>
                    </x-ds::nav-item>
                    <x-ds::sub-nav label="Reports" collapsedWhen="railCollapsed">
                        <x-slot:icon><x-ds::icon name="chart-bar" /></x-slot:icon>
                        <x-ds::nav-item href="#" label="Revenue" collapsedWhen="railCollapsed" />
                        <x-ds::nav-item href="#" label="Churn" :active="true" collapsedWhen="railCollapsed" />
                        <x-ds::nav-item href="#" label="Usage" collapsedWhen="railCollapsed" />
                    </x-ds::sub-nav>
                    <x-ds::sub-nav label="Settings" collapsedWhen="railCollapsed">
                        <x-slot:icon><x-ds::icon name="cog" /></x-slot:icon>
                        <x-ds::nav-item href="#" label="Profile" collapsedWhen="railCollapsed" />
                        <x-ds::nav-item href="#" label="Billing" collapsedWhen="railCollapsed" />
                    </x-ds::sub-nav>
                    <x-ds::nav-item href="#" label="Team" collapsedWhen="railCollapsed">
                        <x-slot:icon><x-ds::icon name="users" /></x-slot:icon>
                    </x-ds::nav-item>
                </div>
            @endforeach
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

{{-- ─────────────────────────── counter --}}
{{-- At rest a counter is plain formatted text, so the picture is the formats:
     the roll itself is proved in the behaviour suite. --}}
<div data-shot="counter" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <div class="text-body font-medium text-fg-muted">Revenue</div>
                    <x-ds::counter :value="12480.5" :format="['prefix' => '$', 'decimals' => 2]" size="xl" class="mt-1 font-semibold text-fg" />
                </div>
                <div class="rounded-panel border border-border bg-surface p-4">
                    <div class="text-body font-medium text-fg-muted">Page views</div>
                    <x-ds::counter :value="1840000" :format="['compact' => true]" size="xl" class="mt-1 font-semibold text-fg" />
                </div>
                <p class="col-span-2 rounded-panel border border-border bg-surface p-4 text-body text-fg-body">
                    <x-ds::counter :value="342" class="font-semibold text-fg" /> people are online, a change of
                    <x-ds::counter :value="-3.5" :format="['decimals' => 1, 'suffix' => '%']" class="font-semibold text-fg" /> since this morning.
                </p>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── sparkline --}}
<div data-shot="sparkline" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="grid grid-cols-2 gap-3">
                <x-ds::stat-tile label="Signups" value="1284" :delta="12">
                    <x-slot:caption><x-ds::sparkline :values="[3, 5, 4, 8, 6, 9, 8, 12]" tone="success" width="lg" :showLast="true" label="Signups, last eight weeks" /></x-slot:caption>
                </x-ds::stat-tile>
                <x-ds::stat-tile label="Refunds" value="18" :delta="-4">
                    <x-slot:caption><x-ds::sparkline :values="[9, 7, 8, 5, 6, 4, 5, 3]" variant="area" width="lg" label="Refunds, last eight weeks" /></x-slot:caption>
                </x-ds::stat-tile>
                <div class="col-span-2 flex flex-col gap-3 rounded-panel border border-border bg-surface p-4 text-body text-fg-body">
                    <div class="flex items-center justify-between gap-4"><span>Response time</span><x-ds::sparkline :values="[220, 240, 210, 260, 300, 280, 250, 230, 225, 215]" :showLast="true" width="lg" /></div>
                    <div class="flex items-center justify-between gap-4"><span>Errors, with a gap</span><x-ds::sparkline :values="[4, 6, 5, null, null, 3, 7, 6, 9, 8]" variant="area" tone="danger" width="lg" /></div>
                    <div class="flex items-center justify-between gap-4"><span>Net change</span><x-ds::sparkline :values="[3, 5, -2, 4, -4, 6, 2, -1, 5, 7]" variant="bar" tone="accent" width="lg" /></div>
                    <div class="flex items-center justify-between gap-4"><span>Series 1, 2 and 3</span>
                        <span class="flex gap-3">
                            <x-ds::sparkline :values="[2, 4, 3, 5, 6]" series="1" width="sm" />
                            <x-ds::sparkline :values="[5, 3, 4, 2, 3]" series="2" width="sm" />
                            <x-ds::sparkline :values="[1, 2, 4, 3, 5]" series="3" width="sm" />
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── gauge --}}
@php
    $load = [
        ['at' => 0, 'tone' => 'success'],
        ['at' => 70, 'tone' => 'warning'],
        ['at' => 90, 'tone' => 'danger', 'label' => 'Overloaded'],
    ];
@endphp
<div data-shot="gauge" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-wrap items-end justify-around gap-6 rounded-panel border border-border bg-surface p-5">
                <x-ds::gauge label="CPU" :value="42" :format="['suffix' => '%']" :thresholds="$load" />
                <x-ds::gauge label="Memory" :value="76" :format="['suffix' => '%']" :thresholds="$load" />
                <x-ds::gauge label="Disk" :value="94" :format="['suffix' => '%']" :thresholds="$load" variant="ring" />
                <x-ds::gauge label="Score" :value="8.4" :max="10" :format="['decimals' => 1]" tone="accent" variant="ring" size="sm" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── activity heatmap --}}
@php
    // Deterministic, so the image changes only when the component does: a
    // working-week rhythm, quieter weekends, a holiday gap and a busy spell.
    $activity = [];
    $day = \Illuminate\Support\Carbon::parse('2025-03-10');
    for ($i = 0; $i < 365; $i++, $day->addDay()) {
        $weekend = $day->isWeekend();
        $n = ($i * 37 + intdiv($i, 7) * 11) % 13;
        $count = $weekend ? ($n > 9 ? $n - 9 : 0) : ($n < 3 ? 0 : $n - 2);
        if ($i > 280 && $i < 300) $count = 0;
        if ($i > 320 && $i < 334 && ! $weekend) $count += 8;
        if ($count) $activity[$day->format('Y-m-d')] = $count;
    }
@endphp
<div data-shot="activity-heatmap" class="shot shot-widest">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-6 rounded-panel border border-border bg-surface p-5">
                <x-ds::activity-heatmap label="Contributions in the last year" to="2026-03-08" :data="$activity" />
                <div class="flex flex-wrap items-start gap-10">
                    <x-ds::activity-heatmap label="Deploys" to="2026-03-08" :weeks="16" series="3" :levels="3" unit="deploy" :data="$activity" />
                    <x-ds::activity-heatmap label="Incidents" to="2026-03-08" :weeks="16" tone="danger" :weekStartsOn="0" unit="incident" :data="$activity" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── bar-chart --}}
{{-- Wide, with a 320px box beside the full one: a chart's text keeps its
     size as the box narrows, and the image shows what gives way instead. --}}
@php
    $money = ['prefix' => '$', 'suffix' => 'K'];
    $months = [
        'Jan' => ['Revenue' => 12, 'Costs' => 8], 'Feb' => ['Revenue' => 18, 'Costs' => 10],
        'Mar' => ['Revenue' => 15, 'Costs' => 11], 'Apr' => ['Revenue' => 22, 'Costs' => 12],
        'May' => ['Revenue' => 26, 'Costs' => 14], 'Jun' => ['Revenue' => 24, 'Costs' => 15],
    ];
    $pages = ['Pricing' => 4210, 'Getting started with the API' => 3180, 'Changelog' => 1920, 'Billing and invoices' => 1340, 'Status' => 610];
@endphp
<div data-shot="bar-chart" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::bar-chart label="Revenue and costs by month" :data="$months" :format="$money" />
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-80 shrink-0 rounded-panel border border-border bg-surface p-4">
                        <x-ds::bar-chart label="Views by page" :data="$pages" orientation="horizontal" height="sm" :showValues="true" :interactive="false" />
                    </div>
                    <div class="min-w-0 flex-1 rounded-panel border border-border bg-surface p-4">
                        <x-ds::bar-chart label="Tickets by week" :data="['W1' => [5, 3], 'W2' => [7, 2], 'W3' => [4, 6], 'W4' => [8, 4]]" :series="['Open', 'Closed']" :stacked="true" height="sm" :showValues="true" />
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── line-chart --}}
<div data-shot="line-chart" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::line-chart label="Visits, new and returning" curve="smooth"
                        :data="['1 Nov' => [320, 150], '2 Nov' => [410, 190], '3 Nov' => [380, 170], '4 Nov' => [520, 260], '5 Nov' => [610, 300], '6 Nov' => [480, 230], '7 Nov' => [450, 210], '8 Nov' => [700, 330], '9 Nov' => [660, 310], '10 Nov' => [720, 350], '11 Nov' => [690, 330], '12 Nov' => [810, 400]]"
                        :series="['New', 'Returning']" data-shot-hover />
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-80 shrink-0 rounded-panel border border-border bg-surface p-4">
                        <x-ds::line-chart label="Error rate" :area="true" tone="danger" height="sm" :points="true" :format="['suffix' => '%', 'decimals' => 1]"
                            :data="['Mon' => 1.2, 'Tue' => 0.8, 'Wed' => null, 'Thu' => 2.4, 'Fri' => 1.9, 'Sat' => 0.6, 'Sun' => 0.9]" :interactive="false" />
                    </div>
                    <div class="min-w-0 flex-1 rounded-panel border border-border bg-surface p-4">
                        <x-ds::line-chart label="Response time by region" legend="end" height="sm" :format="['suffix' => ' ms']" :interactive="false"
                            :data="['W1' => [210, 180, 260], 'W2' => [230, 170, 250], 'W3' => [220, 190, 300], 'W4' => [200, 175, 280]]" :series="['EU', 'US', 'APAC']" />
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── donut-chart --}}
<div data-shot="donut-chart" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::donut-chart label="Traffic by source" :data="['Direct' => 1200, 'Search' => 800, 'Social' => 400, 'Email' => 250]" />
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-80 shrink-0 rounded-panel border border-border bg-surface p-4">
                        <x-ds::donut-chart label="Seats by plan" size="sm" legend="bottom" :data="['Free' => 70, 'Pro' => 25, 'Team' => 5]" :format="['suffix' => ' seats']" :interactive="false" />
                    </div>
                    <div class="min-w-0 flex-1 rounded-panel border border-border bg-surface p-4">
                        <x-ds::donut-chart label="Browsers" size="sm" thickness="sm" :maxSegments="4" :interactive="false"
                            :data="['Chrome' => 640, 'Safari' => 190, 'Firefox' => 60, 'Edge' => 50, 'Samsung' => 30, 'Opera' => 12]">
                            <x-slot:centre><span class="text-body font-semibold text-fg">65%</span><span class="text-meta text-fg-muted">Chrome</span></x-slot:centre>
                        </x-ds::donut-chart>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── split-bar --}}
<div data-shot="split-bar" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-5 rounded-panel border border-border bg-surface p-4">
                    <x-ds::split-bar label="Plan mix" :data="['Free' => 744, 'Pro' => 360, 'Team' => 84, 'Enterprise' => 12]" :format="['suffix' => ' seats']" />
                    <x-ds::split-bar label="Storage" legend="inline" :total="500" size="lg" :format="['suffix' => ' GB']"
                        :data="['Photos' => 150, 'Video' => 96, 'Documents' => 38, 'Mail' => 2]" data-shot-hover />
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-80 shrink-0 rounded-panel border border-border bg-surface p-4">
                        <x-ds::split-bar label="Tickets by status" size="sm" showValues="value" :interactive="false"
                            :data="[['label' => 'Open', 'value' => 18], ['label' => 'Pending', 'value' => 7], ['label' => 'Solved', 'value' => 41], ['label' => 'Blocked', 'value' => 0]]" />
                    </div>
                    <div class="min-w-0 flex-1 rounded-panel border border-border bg-surface p-4">
                        <x-ds::split-bar label="Storage, narrow" legend="inline" :total="500" showValues="percent" :interactive="false"
                            :data="['Photos' => 150, 'Video' => 96, 'Documents' => 38, 'Mail' => 2]" />
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── waffle-chart --}}
<div data-shot="waffle-chart" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::waffle-chart label="Seats by plan" :data="['Free' => 620, 'Pro' => 290, 'Team' => 74, 'Enterprise' => 16]" :format="['suffix' => ' seats']" data-shot-hover />
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-80 shrink-0 rounded-panel border border-border bg-surface p-4">
                        <x-ds::waffle-chart label="Survey answered" :data="37" size="sm" tone="accent" legend="below" :interactive="false" />
                    </div>
                    <div class="min-w-0 flex-1 rounded-panel border border-border bg-surface p-4">
                        <x-ds::waffle-chart label="Of 200 invited" :total="200" :rows="5" :columns="20" shape="circle" size="sm" legend="below" :interactive="false"
                            :data="['Accepted' => 112, 'Declined' => 31]" />
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── activity-rings --}}
<div data-shot="activity-rings" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::activity-rings label="Today" :rings="[
                        ['label' => 'Move', 'value' => 420, 'max' => 600, 'format' => ['suffix' => ' kcal']],
                        ['label' => 'Exercise', 'value' => 25, 'max' => 30, 'format' => ['suffix' => ' min']],
                        ['label' => 'Stand', 'value' => 14, 'max' => 12, 'format' => ['suffix' => ' h']],
                    ]" data-shot-hover>
                        <x-slot:centre><span class="text-title font-semibold text-fg">2/3</span><span class="text-meta text-fg-muted">goals</span></x-slot:centre>
                    </x-ds::activity-rings>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-80 shrink-0 rounded-panel border border-border bg-surface p-4">
                        <x-ds::activity-rings label="Sprint" size="sm" legend="below" :interactive="false" :format="['suffix' => ' pts']"
                            :rings="[['label' => 'Done', 'value' => 34, 'max' => 50], ['label' => 'Reviewed', 'value' => 21, 'max' => 50]]" />
                    </div>
                    <div class="min-w-0 flex-1 rounded-panel border border-border bg-surface p-4">
                        <x-ds::activity-rings label="Quarter's target by region" size="sm" thickness="lg" :interactive="false"
                            :rings="['North' => 82, 'South' => 64, 'East' => 97, 'West' => 41]" />
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── slope-chart --}}
<div data-shot="slope-chart" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::slope-chart label="Conversion by channel" fromLabel="Q1" toLabel="Q2" :format="['suffix' => '%', 'decimals' => 1]" height="sm"
                        :data="['Search' => [3.1, 4.2], 'Email' => [5.4, 5.1], 'Social' => [1.2, 2.6], 'Direct' => [4.0, 4.0], 'Referral' => [2.2, 1.6]]" data-shot-hover />
                </div>
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::slope-chart label="Satisfaction by team" fromLabel="Before the redesign" toLabel="After" colourBy="direction" :highlight="['Billing', 'Onboarding']" height="sm" :interactive="false"
                        :data="['Support' => [72, 75], 'Billing' => [54, 71], 'Onboarding' => [68, 59], 'Reporting and analytics' => [61, 62], 'Integrations' => [80, 80], 'Mobile' => [66, 64]]" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── funnel-chart --}}
<div data-shot="funnel-chart" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::funnel-chart label="Signups this month" showRate="both" height="sm" :interactive="false"
                        :data="['Visits' => 12400, 'Sign-ups' => 3100, 'Activated' => 3500, 'Trial' => 1200, 'Paid' => 310]" />
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-56 shrink-0 rounded-panel border border-border bg-surface p-4">
                        <x-ds::funnel-chart label="Trial to paid" shape="funnel" height="sm" tone="success" :interactive="false"
                            :data="['Trial' => 1200, 'Card added' => 640, 'Paid' => 310]" />
                    </div>
                    <div class="min-w-0 flex-1 rounded-panel border border-border bg-surface p-4">
                        <x-ds::funnel-chart label="Checkout" orientation="horizontal" shape="funnel" showRate="both"
                            :data="['Cart' => 5200, 'Shipping' => 3900, 'Payment' => 2600, 'Done' => 2100]" data-shot-hover />
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── radar-chart --}}
<div data-shot="radar-chart" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::radar-chart label="Vendor scores" size="sm" :axes="['Price', 'Support', 'Features', 'Reliability', 'Ease of use', 'Integrations']"
                        :data="['Acme' => [8, 6, 9, 7, 5, 8], 'Globex' => [5, 9, 6, 8, 8, 4], 'Initech' => [6, 5, 4, 9, 7, 6]]" data-shot-hover />
                </div>
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1 rounded-panel border border-border bg-surface p-4">
                        <x-ds::radar-chart label="Skills" size="sm" shape="circle" :fill="false" :interactive="false"
                            :axes="['Design', 'Frontend', 'Backend', 'Ops', 'Writing']" :data="['Ada' => [90, 80, 60, 40, 85]]" />
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── treemap --}}
<div data-shot="treemap" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::treemap label="Spend by team" height="sm" :ratio="3" :format="['prefix' => '$', 'compact' => true]" :data="[
                        ['label' => 'Engineering', 'children' => ['Salaries' => 420000, 'Cloud' => 120000, 'Tools' => 30000, 'Hardware' => 18000]],
                        ['label' => 'Sales', 'children' => ['Salaries' => 210000, 'Travel' => 40000, 'Events' => 25000]],
                        ['label' => 'Marketing', 'children' => ['Ads' => 90000, 'Content' => 30000, 'Swag' => 2000]],
                        ['label' => 'Office', 'value' => 60000],
                    ]" data-shot-hover />
                </div>
                <div class="rounded-panel border border-border bg-surface p-4">
                    <x-ds::treemap label="Storage" colourBy="value" height="sm" :ratio="3.5" showValues="percent" :interactive="false" :format="['suffix' => ' GB']"
                        :data="['Photos' => 150, 'Video' => 96, 'Backups' => 60, 'Documents' => 38, 'Music' => 30, 'Apps' => 22, 'Mail' => 12, 'Other' => 3]" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── timeline --}}
<div data-shot="timeline" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex items-start gap-3">
                <div class="min-w-0 flex-1 rounded-panel border border-border bg-surface p-4">
                    <x-ds::timeline label="Order 1042">
                        <x-ds::timeline-item title="Shipped" time="2026-11-12T10:42:00Z" icon="truck" tone="success">Two parcels, tracked.</x-ds::timeline-item>
                        <x-ds::timeline-item title="Payment failed" time="2026-11-10T09:31:00Z" icon="exclamation-triangle" tone="danger">
                            The card was declined, and the customer was emailed a link to pay another way.
                            <x-slot:actions><x-ds::button size="sm" variant="secondary">Retry payment</x-ds::button></x-slot:actions>
                        </x-ds::timeline-item>
                        <x-ds::timeline-item title="Ada Lovelace added a note" avatar="Ada Lovelace" time="2026-11-10T09:12:00Z" href="#">Gift wrap both items.</x-ds::timeline-item>
                        <x-ds::timeline-item title="Order placed" time="2026-11-10T09:05:00Z" icon="shopping-cart" />
                    </x-ds::timeline>
                </div>
                <div class="w-64 shrink-0 rounded-panel border border-border bg-surface p-4">
                    <x-ds::timeline label="Deploys" :dense="true">
                        <x-ds::timeline-group label="Today">
                            <x-ds::timeline-item title="v2.4.1 live" time="10:42" tone="success" />
                            <x-ds::timeline-item title="Build started" time="10:31" tone="accent" />
                        </x-ds::timeline-group>
                        <x-ds::timeline-group label="Yesterday">
                            <x-ds::timeline-item title="Rolled back" time="16:05" tone="warning" />
                            <x-ds::timeline-item title="v2.4.0 live" time="15:50" />
                        </x-ds::timeline-group>
                    </x-ds::timeline>
                </div>
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

{{-- ─────────────────────────── context-menu
     Opened the way a right-click opens it, by build-docs.mjs, at a point on
     the second row. --}}
<div data-shot="context-menu" class="shot shot-tall">
    @foreach ($themes as $t)
        <div class="pane bg-surface-sunken {{ $t }}">
            <x-ds::context-menu label="Report actions" data-shot-region class="overflow-hidden rounded-panel border border-border bg-surface">
                @foreach ([['Q3 revenue', 'Ready'], ['Churn by cohort', 'Running'], ['Weekly signups', 'Ready']] as [$name, $status])
                    <div class="flex items-center justify-between border-b border-divider px-4 py-3 text-body last:border-b-0" tabindex="0">
                        <span class="font-medium text-fg">{{ $name }}</span>
                        <span class="text-meta text-fg-muted">{{ $status }}</span>
                    </div>
                @endforeach
                <x-slot:menu>
                    <x-ds::dropdown-item icon="pencil-square" href="#">Edit report</x-ds::dropdown-item>
                    <x-ds::dropdown-item icon="document-duplicate" href="#">Duplicate</x-ds::dropdown-item>
                    <x-ds::dropdown-item icon="archive-box" :disabled="true">Archive</x-ds::dropdown-item>
                    <x-ds::dropdown-item icon="trash" tone="danger" as="button">Delete</x-ds::dropdown-item>
                </x-slot:menu>
            </x-ds::context-menu>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── theme-switcher
     Both variants as the server draws them, on "system": the docs browser has
     nothing stored. --}}
<div data-shot="theme-switcher" class="shot">
    @foreach ($themes as $t)
        <div class="pane bg-surface-sunken {{ $t }}">
            <div class="flex items-end gap-6">
                <x-ds::theme-switcher storageKey="docs-shot-theme" />
                <x-ds::theme-switcher variant="button" storageKey="docs-shot-theme" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── announcement-bar
     Four tones over a stand-in for the app's header, so the image shows the
     bar ABOVE the chrome, which is where it goes. The close controls are the
     real ones, shown because the script is on this page. --}}
<div data-shot="announcement-bar" class="shot shot-wide">
    @foreach ($themes as $t)
        <div class="pane bg-surface-sunken {{ $t }}" style="padding: 0">
            <x-ds::announcement-bar dismissible href="#status">Scheduled maintenance on Saturday, 02:00 to 03:00 UTC.</x-ds::announcement-bar>
            <div class="flex h-12 items-center gap-3 border-b border-border bg-surface px-4 text-body font-semibold text-fg">Acme <span class="font-normal text-fg-muted">Reports</span></div>
            <div class="flex flex-col gap-3 p-6">
                <x-ds::announcement-bar tone="accent" icon="sparkles">Exports are here: CSV and PDF from every report.
                    <x-slot:action><x-ds::button size="sm" variant="secondary">Try it</x-ds::button></x-slot:action>
                </x-ds::announcement-bar>
                <x-ds::announcement-bar tone="warning" dismissible>Your trial ends in 3 days.</x-ds::announcement-bar>
                <x-ds::announcement-bar tone="danger" href="#status" actionLabel="Status page">Card payments are failing. We are on it.</x-ds::announcement-bar>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── confirm-button
     At rest, and asking: the second row is pressed by build-docs.mjs, and
     never times out, so the photograph cannot catch it putting itself away.
     End-aligned rows, the way a table's action cell holds them. --}}
<div data-shot="confirm-button" class="shot">
    @foreach ($themes as $t)
        <div class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-3 rounded-control border border-border bg-surface px-3 py-2">
                    <span class="text-body text-fg">Q3 revenue</span>
                    <x-ds::confirm-button type="button" size="sm" variant="ghost" />
                </div>
                <div class="flex items-center justify-between gap-3 rounded-control border border-border bg-surface px-3 py-2" data-shot-press>
                    <span class="text-body text-fg">Q2 revenue</span>
                    <x-ds::confirm-button type="button" size="sm" variant="ghost" question="Delete this report?" :timeout="0" />
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-ds::confirm-button type="button" label="Remove member" confirmLabel="Remove" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── hold-button
     At rest, part held (the fill set as the script would set it, since a
     photograph cannot hold a button down), armed by a press that did not hold
     (pressed by build-docs.mjs, the way a screen reader would), and neutral. --}}
<div data-shot="hold-button" class="shot">
    @foreach ($themes as $t)
        <div class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col items-start gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <x-ds::hold-button label="Hold to delete" />
                    <x-ds::hold-button label="Hold to delete" style="--ds-hold-rest: 38%" />
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ds::hold-button label="Hold to delete" data-shot-press />
                    <x-ds::hold-button label="Hold to archive" tone="neutral" size="sm" style="--ds-hold-rest: 62%" />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── confirm-popover
     Open from the host's `shown`, as the popover's image is, with
     type-to-confirm. Opened at load, so it takes no focus. --}}
<div data-shot="confirm-popover" class="shot shot-tall">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex justify-center">
                <x-ds::confirm-popover open="shown" placement="bottom" type="button"
                    title="Delete acme-prod?" description="Its 12 reports and every run go with it."
                    confirmLabel="Delete project" confirmText="acme-prod">
                    <x-slot:trigger>
                        <x-ds::button variant="secondary" tone="danger" size="sm">Delete project</x-ds::button>
                    </x-slot:trigger>
                </x-ds::confirm-popover>
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

{{-- ─────────────────────────── user-menu
     Open from the server's state, the way a host's `open` holds it. --}}
<div data-shot="user-menu" class="shot shot-calendar">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex items-start justify-between gap-4">
                <x-ds::user-menu open="shown" align="start" name="Ada Lovelace" email="ada@example.com" plan="Pro" signOutAction="#sign-out" class="w-56">
                    <div role="group" aria-label="Account">
                        <x-ds::dropdown-item href="#profile" icon="user-circle">Profile</x-ds::dropdown-item>
                        <x-ds::dropdown-item href="#settings" icon="cog-6-tooth">Settings</x-ds::dropdown-item>
                        <x-ds::dropdown-item href="#billing" icon="credit-card">Billing</x-ds::dropdown-item>
                    </div>
                    <div role="group" aria-label="Help">
                        <x-ds::dropdown-item href="#docs" icon="book-open">Documentation</x-ds::dropdown-item>
                    </div>
                    <x-slot:theme><x-ds::theme-switcher variant="menu" storageKey="docs-shot-theme" /></x-slot:theme>
                </x-ds::user-menu>
                <x-ds::user-menu name="Grace Hopper" email="grace@example.com" :compact="true" signOutAction="#sign-out" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── team-switcher
     Open from the server's state; Globex is the current team. --}}
<div data-shot="team-switcher" class="shot shot-calendar">
    @foreach ($themes as $t)
        @php
            $docsTeams = [
                ['id' => 1, 'name' => 'Acme Inc', 'meta' => 'Pro plan · 12 members'],
                ['id' => 2, 'name' => 'Globex', 'meta' => 'Team plan · 48 members'],
                ['id' => 3, 'name' => 'Initech', 'meta' => 'Free'],
                ['id' => 4, 'name' => 'Umbrella Labs', 'meta' => 'Pro plan · 3 members', 'initials' => 'UL'],
            ];
        @endphp
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex items-start gap-4">
                <div class="w-60">
                    <x-ds::team-switcher open="shown" :teams="$docsTeams" current="2" name="team">
                        <x-slot:footer><x-ds::button variant="ghost" size="sm" icon="plus">Create team</x-ds::button></x-slot:footer>
                    </x-ds::team-switcher>
                </div>
                <x-ds::team-switcher :teams="$docsTeams" current="2" name="team" :compact="true" class="ml-auto" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── money-input --}}
<div data-shot="money-input" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex max-w-sm flex-col gap-3.5">
                <x-ds::money-input name="m1{{ $loop->index }}" label="Price" currency="USD" locale="en" value="1249.5" help="Before tax." />
                <x-ds::money-input name="m2{{ $loop->index }}" label="Budget" currency="INR" locale="en-IN" value="1234567.5" />
                <x-ds::money-input name="m3{{ $loop->index }}" label="Payout" :currencies="['EUR', 'GBP', 'JPY']" currency="EUR" locale="de" value="1234.5" />
                <x-ds::money-input name="m4{{ $loop->index }}" label="Deposit" currency="KWD" locale="en" value="0.5" min="1" error="Enter at least 1.000." />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── phone-input --}}
<div data-shot="phone-input" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex max-w-sm flex-col gap-3.5">
                <x-ds::phone-input name="p1{{ $loop->index }}" label="Mobile" value="+447911123456" :flags="true" help="We text the sign-in code here." />
                <x-ds::phone-input name="p2{{ $loop->index }}" label="Phone" value="+919876543210" :preferred="['IN', 'US', 'GB']" />
                <x-ds::phone-input name="p3{{ $loop->index }}" label="Office" country="US" placeholder="(415) 555-0123" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── shortcut-input --}}
<div data-shot="shortcut-input" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex max-w-sm flex-col gap-3.5">
                <x-ds::shortcut-input name="s1{{ $loop->index }}" label="Open search" value="mod+k" />
                <x-ds::shortcut-input name="s2{{ $loop->index }}" label="Toggle sidebar" value="mod+shift+b" help="Works anywhere in the app." />
                <x-ds::shortcut-input name="s3{{ $loop->index }}" label="Next item" />
                <p class="text-body text-fg-body">Search with <x-ds::kbd shortcut="mod+k" />, anywhere.</p>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── mention-input
     Typed into by build-docs ("@ma"), so the image shows the list under the trigger. --}}
<div data-shot="mention-input" class="shot shot-calendar">
    @foreach ($themes as $t)
        @php
            $docsPeople = [
                ['id' => 7, 'label' => 'Maya Patel', 'meta' => 'Design', 'initials' => 'MP'],
                ['id' => 8, 'label' => 'Omar Malik', 'meta' => 'Support', 'initials' => 'OM'],
                ['id' => 9, 'label' => 'Sam Marsh', 'meta' => 'Engineering', 'initials' => 'SM'],
                ['id' => 10, 'label' => 'Ade Okafor', 'meta' => 'Finance', 'initials' => 'AO'],
            ];
        @endphp
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="max-w-sm">
                <x-ds::mention-input name="mn{{ $loop->index }}" label="Comment" :items="$docsPeople" value="Can you check these, " data-shot-type="@ma" help="Type @ to mention someone." />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── color-picker
     Opened by build-docs (a press on the swatch), so the image shows the panel. --}}
<div data-shot="color-picker" class="shot shot-colour">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="max-w-xs">
                <x-ds::color-picker
                    name="cp{{ $loop->index }}"
                    label="Brand colour"
                    value="#2f6fb2"
                    :alpha="true"
                    :swatches="['#0f172a' => 'Ink', '#2563eb' => 'Blue', '#16a34a' => 'Green', '#d97706' => 'Amber', '#dc2626' => 'Red', '#7c3aed' => 'Violet']"
                />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── color-picker-contrast
     Closed: the contrast line under the field, and presets on their own. --}}
<div data-shot="color-picker-contrast" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex max-w-sm flex-col gap-5">
                <x-ds::color-picker name="cpc{{ $loop->index }}" label="Button colour" value="#2563eb" contrastWith="#ffffff" help="Checked against white text." />
                <x-ds::color-picker
                    name="cpp{{ $loop->index }}"
                    label="Label colour"
                    :presetsOnly="true"
                    value="#2563eb"
                    :swatches="['#64748b' => 'Grey', '#2563eb' => 'Blue', '#16a34a' => 'Green', '#d97706' => 'Amber', '#dc2626' => 'Red', '#7c3aed' => 'Violet', '#fde047' => 'Yellow']"
                    help="Shown on the card."
                />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── input-expanding
     Collapsed, and open because it holds text: both from CSS. --}}
<div data-shot="input-expanding" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-5">
                <form role="search" class="flex items-center gap-3 rounded-panel border border-border bg-surface px-3 py-2">
                    <span class="text-body font-medium text-fg">Invoices</span>
                    <span class="ml-auto"></span>
                    <x-ds::input name="e1{{ $loop->index }}" label="Search invoices" :expanding="true" />
                    <x-ds::button variant="primary" size="sm">New</x-ds::button>
                </form>
                <form role="search" class="flex items-center gap-3 rounded-panel border border-border bg-surface px-3 py-2">
                    <span class="text-body font-medium text-fg">Invoices</span>
                    <span class="ml-auto"></span>
                    <x-ds::input name="e2{{ $loop->index }}" label="Search invoices" :expanding="true" :clearable="true" value="acme" />
                    <x-ds::button variant="primary" size="sm">New</x-ds::button>
                </form>
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

{{-- ─────────────────────────── tree --}}
<div data-shot="tree" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="grid grid-cols-2 gap-4">
                <x-ds::tree label="Files">
                    <x-ds::tree-item label="app" icon="folder" :open="true">
                        <x-ds::tree-item label="Http" icon="folder" :open="true">
                            <x-ds::tree-item label="Kernel.php" icon="document" />
                        </x-ds::tree-item>
                        <x-ds::tree-item label="Models" icon="folder" :count="8" />
                        <x-ds::tree-item label="helpers.php" icon="document" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="config" icon="folder">
                        <x-ds::tree-item label="app.php" icon="document" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="routes" icon="folder">
                        <x-ds::tree-item label="web.php" icon="document" :selected="false" />
                    </x-ds::tree-item>
                </x-ds::tree>
                <x-ds::tree label="Permissions" selectable="multiple" name="perms">
                    <x-ds::tree-item label="Billing" :open="true">
                        <x-ds::tree-item label="View invoices" :selected="true" />
                        <x-ds::tree-item label="Refund" />
                        <x-ds::tree-item label="Void" :disabled="true" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="Reports" :selected="true" :open="true">
                        <x-ds::tree-item label="Export" />
                        <x-ds::tree-item label="Schedule" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="Team">
                        <x-ds::tree-item label="Invite" />
                    </x-ds::tree-item>
                </x-ds::tree>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── filter-bar --}}
<div data-shot="filter-bar" class="shot shot-wide">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::filter-bar :active="2" :reset="true">
                <x-slot:search>
                    <x-ds::input type="search" name="q" aria-label="Search invoices" icon="magnifying-glass" :clearable="true" value="acme" />
                </x-slot:search>
                <x-ds::input as="select" name="status" aria-label="Status"><option>Any status</option><option selected>Open</option><option>Paid</option></x-ds::input>
                <x-slot:applied>
                    <x-ds::filter-chip label="Status" value="Open" />
                    <x-ds::filter-chip label="Owner" value="Ada Lovelace" />
                </x-slot:applied>
                <x-slot:actions>
                    <x-ds::button variant="secondary" size="sm">Export</x-ds::button>
                </x-slot:actions>
            </x-ds::filter-bar>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── code-block --}}
<div data-shot="code-block" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3">
                <x-ds::code-block language="PHP" filename="app/Models/Invoice.php" :lineNumbers="true" highlight="5"
                    :code="implode(PHP_EOL, ['final class Invoice extends Model', '{', '    public function total(): int', '    {', '        return $this->lines->sum(\'amount\');', '    }', '}'])" />
                <x-ds::code-block language="Bash" :code="'composer require shipbytes/blade-ui'" />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── kbd --}}
<div data-shot="kbd" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-3 text-body text-fg-body">
                <p>Press <x-ds::kbd>/</x-ds::kbd> to search, <x-ds::kbd>Esc</x-ds::kbd> to close.</p>
                <p>Open the palette with <x-ds::kbd :keys="['Ctrl', 'K']" />, or <x-ds::kbd :keys="['⌘', 'K']" separator="" /> on a Mac.</p>
                <h3 class="text-section text-fg">Save with <x-ds::kbd :keys="['Ctrl', 'S']" /></h3>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── expandable-text --}}
<div data-shot="expandable-text" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::panel title="Ticket #1042" body="plain">
                <x-ds::expandable-text>
                    The invoice for September was charged twice. Both charges show on the card statement, on the first and the second of the month, for the same amount. The receipt in the app shows only one of them. Could you refund the second charge and tell me whether the October invoice will be affected as well? I would also like to know why it happened, so that it does not happen again next month.
                </x-ds::expandable-text>
            </x-ds::panel>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── panel-collapsible --}}
<div data-shot="panel-collapsible" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-4">
                <x-ds::panel title="Billing" name="billing-{{ $t }}" :collapsible="true" action="Manage" actionHref="#">
                    <x-ds::panel-row>Card ending 4242</x-ds::panel-row>
                    <x-ds::panel-row>Next invoice on 1 October</x-ds::panel-row>
                </x-ds::panel>
                <x-ds::panel title="Advanced" name="advanced-{{ $t }}" :collapsible="true" :open="false" body="plain">
                    Webhooks and API access.
                </x-ds::panel>
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

{{-- ─────────────────────────── slider --}}
<div data-shot="slider" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-6">
                <x-ds::slider name="vol{{ $loop->index }}" label="Volume" :value="40" showValue="inline" :format="['suffix' => '%']" />
                <x-ds::slider
                    name="price{{ $loop->index }}"
                    label="Price"
                    :range="true"
                    :value="[20, 80]"
                    :max="100"
                    :step="5"
                    lowLabel="Minimum price"
                    highLabel="Maximum price"
                    showValue="inline"
                    :format="['prefix' => '$']"
                    :marks="[0, 25, 50, 75, 100]"
                    help="Per night, before tax."
                />
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── slider-histogram --}}
<div data-shot="slider-histogram" class="shot">
    @foreach ($themes as $t)
        @php
            $docsPrices = [];
            foreach (range(0, 159) as $i) {
                $docsPrices[] = (int) round(40 + 260 * (($i * 37 % 101) / 101) ** 1.8 + ($i % 7) * 6);
            }
        @endphp
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::slider
                name="night{{ $loop->index }}"
                label="Price per night"
                :range="true"
                :value="[60, 180]"
                :max="400"
                step="10"
                lowLabel="Minimum price"
                highLabel="Maximum price"
                showValue="inline"
                :format="['prefix' => '$']"
                :histogram="$docsPrices"
                :buckets="40"
                countLabel=":count stay|:count stays"
                :inputs="true"
            />
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── time-picker --}}
{{-- Opened by build-docs (a click on the field), so the image shows the list. --}}
<div data-shot="time-picker" class="shot shot-calendar">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::time-picker
                name="tp{{ $loop->index }}"
                label="Opens"
                value="09:30"
                format="12"
                min="08:00"
                max="18:00"
                :step="30"
                :clearable="true"
                help="Shop hours, local time."
            />
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── inline-edit --}}
{{-- The second one is opened by build-docs; buttons mode keeps it open when the other pane takes focus. --}}
<div data-shot="inline-edit" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="flex flex-col gap-4">
                <h3 class="text-section text-fg"><x-ds::inline-edit name="t{{ $loop->index }}" label="Title" value="Q3 roadmap" /></h3>
                <div data-shot-press class="text-body text-fg-body">
                    <x-ds::inline-edit name="d{{ $loop->index }}" label="Owner" value="Platform team" submit="buttons" />
                </div>
                <div class="text-body text-fg-body">
                    <x-ds::inline-edit name="s{{ $loop->index }}" label="Slug" value="q3 roadmap" error="Use letters, numbers and dashes." />
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── scroll-area --}}
{{-- Scrolled part way by build-docs, so both edge shadows show. --}}
<div data-shot="scroll-area" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::scroll-area :maxHeight="176" label="Release notes" class="rounded-panel border border-border bg-surface">
                <div class="flex flex-col gap-3 p-4 text-body text-fg-body">
                    <p class="text-label text-fg">2.0.0</p>
                    <p>Slider, time picker, inline edit and scroll area.</p>
                    <p>Charts read out their values and move by keyboard.</p>
                    <p>The tree keeps its open folders across a morph.</p>
                    <p>Code blocks clamp long files behind Show more.</p>
                    <p>Overlays stay on screen near the window's edge.</p>
                    <p>The theme switcher remembers the reader's choice.</p>
                    <p>Every component has an accessibility contract.</p>
                </div>
            </x-ds::scroll-area>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── sortable-list --}}
{{-- A row part way through a drag, operated by build-docs with pointer
     events on the second row's handle in each pane. --}}
<div data-shot="sortable-list" class="shot">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <x-ds::sortable-list name="steps{{ $loop->index }}" label="Release checklist">
                <x-ds::sortable-item value="1" label="Write the changelog">Write the changelog</x-ds::sortable-item>
                <x-ds::sortable-item value="2" label="Tag the release" data-shot-drag>
                    <span class="text-fg">Tag the release</span>
                    <span class="block text-meta text-fg-muted">v2.0.0 on next-2.0</span>
                </x-ds::sortable-item>
                <x-ds::sortable-item value="3" label="Rebuild the docs">Rebuild the docs</x-ds::sortable-item>
                <x-ds::sortable-item value="4" label="Announce it" :disabled="true">Announce it <span class="text-meta text-fg-muted">(after the rest)</span></x-ds::sortable-item>
            </x-ds::sortable-list>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── split --}}
<div data-shot="split" class="shot shot-tall">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }}">
            <div class="h-full overflow-hidden rounded-panel border border-border bg-surface">
                <x-ds::split label="Resize the file list">
                    <x-ds::split-pane size="32%" min="120">
                        <div class="flex flex-col gap-1 p-3 text-body text-fg-body">
                            <span class="text-label text-fg">Files</span>
                            <span>app/</span><span>config/</span><span>routes/web.php</span>
                        </div>
                    </x-ds::split-pane>
                    <x-ds::split-pane>
                        <x-ds::split orientation="vertical" label="Resize the console">
                            <x-ds::split-pane>
                                <pre class="p-3 font-mono text-meta text-fg-body">Route::get('/', fn () =&gt; view('home'));</pre>
                            </x-ds::split-pane>
                            <x-ds::split-pane size="38%" min="48" :collapsible="true">
                                <div class="bg-surface-subtle p-3 font-mono text-meta text-fg-muted h-full">$ php artisan test<br>Tests: 42 passed</div>
                            </x-ds::split-pane>
                        </x-ds::split>
                    </x-ds::split-pane>
                </x-ds::split>
            </div>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── command-palette --}}
{{-- Opened by build-docs (the open event naming each pane's palette) with
     "in" typed, so the image shows the filter and its ranking. Last on the
     page: opening it locks the body's scroll, as a modal does. --}}
<div data-shot="command-palette" class="shot shot-calendar">
    @foreach ($themes as $t)
        <div x-data="{ shown: true }" class="pane bg-surface-sunken {{ $t }} relative [transform:translateZ(0)]">
            <x-ds::command-palette name="docs{{ $loop->index }}" data-shot-name="docs{{ $loop->index }}">
                <x-ds::command-group label="Go to">
                    <x-ds::command-item label="Invoices" href="#invoices" icon="document-text" shortcut="G+I" />
                    <x-ds::command-item label="Customers" href="#customers" icon="users" shortcut="G+C" />
                    <x-ds::command-item label="Settings" href="#settings" icon="cog" />
                </x-ds::command-group>
                <x-ds::command-group label="Actions">
                    <x-ds::command-item label="New invoice" action="create" icon="plus" :shortcut="['⌘', 'N']" />
                    <x-ds::command-item label="Archive invoice" action="archive" icon="archive-box">Moves it out of the inbox</x-ds::command-item>
                    <x-ds::command-item label="Delete customer" action="delete" icon="trash" :disabled="true" />
                </x-ds::command-group>
                <x-slot:footer>
                    <span class="flex items-center gap-1.5"><x-ds::kbd>↑</x-ds::kbd><x-ds::kbd>↓</x-ds::kbd> to move</span>
                    <span class="flex items-center gap-1.5"><x-ds::kbd>↵</x-ds::kbd> to open</span>
                    <span class="flex items-center gap-1.5"><x-ds::kbd>Esc</x-ds::kbd> to close</span>
                </x-slot:footer>
            </x-ds::command-palette>
        </div>
    @endforeach
</div>

{{-- ─────────────────────────── In situ --}}
