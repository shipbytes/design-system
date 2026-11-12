{{--
    Specimen source for the component gallery.

    This renders the REAL components, not a drawing of them. The gallery is
    assembled from this file's output, so a broken component shows up broken —
    which is the only kind of gallery worth reviewing against.

    Chrome lives in scripts/build-gallery.mjs. This file is specimens only.
--}}

@php
    $plate = 'ds-plate';
    $row = 'flex flex-wrap items-center gap-3';
@endphp

{{-- The package's script, as a host's layout includes it. The gallery loads no
     Alpine on purpose (see the select section), so here it registers nothing;
     it is included so the page carries what a consumer's does, and so a
     gallery that ever does load Alpine gets the behaviours with it. Inline,
     because dist/gallery.html is a file opened from disk, with no route to
     fetch the script from: the case the prop exists for. --}}
<x-ds::scripts inline />

{{-- ─────────────────────────────────────────── Button --}}
<section class="ds-spec" id="button">
    <header class="ds-spec-head">
        <h2>Button</h2>
        <p>Four variants, three sizes. The app writes this eight different ways today; every one of them was trying to be this.</p>
    </header>

    <div class="ds-note">Hover, focus and active are live — interact with them. <em>Disabled</em> and <em>loading</em> are prop-driven and shown below.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Variants <span>&middot; size md</span></div>
        <div class="{{ $row }}">
            <x-ds::button variant="primary">Export</x-ds::button>
            <x-ds::button variant="secondary">Duplicate</x-ds::button>
            <x-ds::button variant="ghost">Cancel</x-ds::button>
            <x-ds::button variant="danger">Delete</x-ds::button>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Danger tone <span>&middot; ghost and secondary only &middot; a quiet destructive control beside an ordinary one</span></div>
        <div class="{{ $row }}">
            <x-ds::button variant="secondary" size="sm">Save photo</x-ds::button>
            <x-ds::button variant="ghost" size="sm" tone="danger">Remove</x-ds::button>
            <x-ds::button variant="secondary" tone="danger">Revoke access</x-ds::button>
            <x-ds::button variant="ghost" tone="danger">
                <x-ds::icon name="trash" />
                Delete draft
            </x-ds::button>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Sizes <span>&middot; sm 32px &middot; md 36px at the sm breakpoint, 44px below it &middot; lg 44px</span></div>
        <div class="{{ $row }}">
            <x-ds::button size="sm">Small</x-ds::button>
            <x-ds::button size="md">Medium</x-ds::button>
            <x-ds::button size="lg">Large</x-ds::button>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">States</div>
        <div class="{{ $row }}">
            <x-ds::button>Default</x-ds::button>
            <x-ds::button :disabled="true">Disabled</x-ds::button>
            <x-ds::button :loading="true">Saving</x-ds::button>
            <x-ds::button variant="secondary" :loading="true">Working</x-ds::button>
            <x-ds::button variant="danger" :loading="true">Deleting</x-ds::button>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">With icons, as a link, and icon-only</div>
        <div class="{{ $row }}">
            <x-ds::button>
                <x-ds::icon name="plus" />
                Create project
            </x-ds::button>
            <x-ds::button variant="secondary">
                Export
                <x-ds::icon name="arrow-down-tray" />
            </x-ds::button>
            <x-ds::button href="#button" variant="ghost">A link that looks like a button</x-ds::button>
            <x-ds::button icon-only aria-label="More actions" variant="secondary">
                <x-ds::icon name="ellipsis-vertical" />
            </x-ds::button>
            <x-ds::button icon-only pill aria-label="Create" size="lg">
                <x-ds::icon name="plus" size="6" />
            </x-ds::button>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Badge --}}
<section class="ds-spec" id="badge">
    <header class="ds-spec-head">
        <h2>Badge</h2>
        <p>Five tones, three variants. Replaces roughly fifty hand-written badges across nine colour families.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Tint <span>&middot; the default &mdash; a badge annotates, it does not act</span></div>
        <div class="{{ $row }}">
            <x-ds::badge tone="neutral">Draft</x-ds::badge>
            <x-ds::badge tone="accent">Admin</x-ds::badge>
            <x-ds::badge tone="success">Verified</x-ds::badge>
            <x-ds::badge tone="warning">Unverified</x-ds::badge>
            <x-ds::badge tone="danger">Blocked</x-ds::badge>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Solid and outline</div>
        <div class="{{ $row }}">
            <x-ds::badge tone="neutral" variant="solid">Solid</x-ds::badge>
            <x-ds::badge tone="accent" variant="solid">Accent</x-ds::badge>
            <x-ds::badge tone="success" variant="solid">Live</x-ds::badge>
            <x-ds::badge tone="danger" variant="solid">Failed</x-ds::badge>
            <x-ds::badge variant="outline">Outline</x-ds::badge>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">With a status dot <span>&middot; decorative &mdash; the label already says the state</span></div>
        <div class="{{ $row }}">
            <x-ds::badge tone="success" :dot="true">Published</x-ds::badge>
            <x-ds::badge tone="warning" :dot="true">Pending</x-ds::badge>
            <x-ds::badge tone="danger" :dot="true">Errored</x-ds::badge>
            <x-ds::badge tone="neutral" :dot="true">Idle</x-ds::badge>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Icon --}}
<section class="ds-spec" id="icon">
    <header class="ds-spec-head">
        <h2>Icon</h2>
        <p>Heroicons v2, with the v1 names the app still uses aliased so they resolve instead of rendering nothing.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Sizes <span>&middot; 4 is the dashboard default</span></div>
        <div class="flex flex-wrap items-end gap-5 text-fg">
            @foreach ([3, 4, 5, 6, 8] as $s)
                <span class="flex flex-col items-center gap-2">
                    <x-ds::icon name="document-text" :size="$s" />
                    <span class="ds-tick">{{ $s }}</span>
                </span>
            @endforeach
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Variants</div>
        <div class="flex flex-wrap items-center gap-5 text-fg">
            @foreach (['outline' => 'outline 24', 'solid' => 'solid 24', 'mini' => 'mini 20', 'micro' => 'micro 16'] as $v => $caption)
                <span class="flex flex-col items-center gap-2">
                    <x-ds::icon name="star" :variant="$v" size="6" />
                    <span class="ds-tick">{{ $caption }}</span>
                </span>
            @endforeach
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">v1 names still resolve <span>&middot; v2 renamed 33 of the names this app uses</span></div>
        <div class="flex flex-wrap items-center gap-5 text-fg">
            @foreach (['x' => 'x-mark', 'search' => 'magnifying-glass', 'mail' => 'envelope', 'cog' => 'cog-6-tooth', 'dots-vertical' => 'ellipsis-vertical', 'location-marker' => 'map-pin'] as $old => $new)
                <span class="flex flex-col items-center gap-2">
                    <x-ds::icon :name="$old" size="5" />
                    <span class="ds-tick"><code>{{ $old }}</code> &rarr; {{ $new }}</span>
                </span>
            @endforeach
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Input --}}
<section class="ds-spec" id="input">
    <header class="ds-spec-head">
        <h2>Input</h2>
        <p>A Catalyst two-layer field. In the app this is a ~40-line class string pasted at roughly forty sites.</p>
    </header>

    <div class="ds-note">Focus the fields. The ring is drawn <em>inward</em>, so the box never changes size and nothing below it shifts &mdash; a plain outline would.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">States</div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ds::input label="Project name" name="a" placeholder="Q3 site redesign" help="Shown on the dashboard card and in the shared link." />
            <x-ds::input label="Project name" name="b" value="Q3 site redesign" />
            <x-ds::input label="Project name" name="c" value="ab" error="Name must be at least 3 characters." />
            <x-ds::input label="Locked while exporting" name="d" value="Senior Product Designer" :disabled="true" />
            <x-ds::input label="Email (readonly: focusable, copyable, submitted)" name="d2" value="ada@example.com" readonly />
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Types <span>&middot; the select shares the shell exactly; only the caret differs</span></div>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-ds::input label="Search" name="e" icon="magnifying-glass" placeholder="Search by name or email…" />
            <x-ds::input label="Sort by" as="select" name="f">
                <option>Last updated</option>
                <option>Title</option>
            </x-ds::input>
            <x-ds::input label="Summary" as="textarea" name="g" rows="2">Product designer with nine years shipping data-heavy tools.</x-ds::input>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Help is replaced by the error <span>&middot; never stacked &mdash; two lines of guidance under one field is one too many</span></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ds::input label="Email" type="email" name="h" value="amara@example.com" help="We only use this for password resets." />
            <x-ds::input label="Email" type="email" name="i" value="amara@" error="That does not look like an email address." />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── OTP input --}}
<section class="ds-spec" id="otp-input">
    <header class="ds-spec-head">
        <h2>OTP input</h2>
        <p>A one-time code, one box per character, posted as <em>one</em> field. Every box is drawn from the input&rsquo;s own recipe.</p>
    </header>

    <div class="ds-note">The boxes carry no name. A hidden input carries the joined code, so the form posts <code>code=482193</code> and nothing else. Typing, pasting and the browser&rsquo;s autofill need the package&rsquo;s script; the gallery loads none, so what is here is the server&rsquo;s render.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">States <span>&middot; rendered from <code>value</code> before any script runs</span></div>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-ds::otp-input name="g-otp-a" label="Verification code" help="We sent a six-digit code to your phone." />
            <x-ds::otp-input name="g-otp-b" label="Verification code" value="4821" />
            <x-ds::otp-input name="g-otp-c" label="Verification code" value="123456" error="That code has expired. Request a new one." />
            <x-ds::otp-input name="g-otp-d" label="Verification code" :disabled="true" />
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Modes <span>&middot; a masked PIN, and a code that takes letters</span></div>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-ds::otp-input name="g-otp-e" label="PIN" :length="4" value="12" :masked="true" />
            <x-ds::otp-input name="g-otp-f" label="Reference" :length="8" :numeric="false" value="AB12" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Copy button --}}
<section class="ds-spec" id="copy-button">
    <header class="ds-spec-head">
        <h2>Copy button</h2>
        <p>A ds::button that copies a value and confirms in place. Both labels sit in one cell from the first paint, so the button is as wide as &ldquo;Copied&rdquo; before anything is pressed and never jumps.</p>
    </header>

    <div class="ds-note">Copying needs the package&rsquo;s script; the gallery loads none, so these are the server&rsquo;s render, at rest. A refused clipboard changes nothing on the button and dispatches <code>copy-failed</code> instead.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Variants and sizes <span>&middot; <code>secondary</code> by default</span></div>
        <div class="{{ $row }} px-4">
            <x-ds::copy-button value="sk_live_42" />
            <x-ds::copy-button value="sk_live_42" variant="primary" label="Copy invite link" />
            <x-ds::copy-button value="sk_live_42" variant="ghost" label="Copy key" />
            <x-ds::copy-button value="sk_live_42" size="sm" />
            <x-ds::copy-button value="sk_live_42" size="lg" />
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Icon only, and beside what it copies <span>&middot; <code>target</code> reads the element at the moment of the press</span></div>
        <div class="{{ $row }} px-4">
            <code id="g-copy-key" class="rounded-control border border-border bg-surface px-3 py-2 font-mono text-meta text-fg-body">sk_live_51HxQ2c4f9A</code>
            <x-ds::copy-button target="g-copy-key" :iconOnly="true" label="Copy key" />
            <x-ds::copy-button target="g-copy-key" :iconOnly="true" variant="ghost" size="sm" label="Copy key" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Rating --}}
<section class="ds-spec" id="rating">
    <header class="ds-spec-head">
        <h2>Rating</h2>
        <p>Stars that are radios underneath: one name, one value posted, the arrow keys and the form&rsquo;s required check for free. Hovering previews &ldquo;up to here&rdquo; and leaving restores the choice, all in CSS. Readonly is one image, named &ldquo;4.5 out of 5&rdquo;.</p>
    </header>

    <div class="ds-note">No script at all. The faint cross after the stars is &ldquo;No rating&rdquo;, the way back once a star is chosen; it is transparent while it is itself the choice, and omitted when the rating is <code>required</code>.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Choosing <span>&middot; <code>sm</code>, <code>md</code>, <code>lg</code></span></div>
        <div class="{{ $row }} px-4 items-end">
            <x-ds::rating name="g-r1" label="Small" size="sm" :value="3" />
            <x-ds::rating name="g-r2" label="Medium" :value="4" />
            <x-ds::rating name="g-r3" label="Large" size="lg" />
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">States <span>&middot; help, error, disabled, required, ten stars</span></div>
        <div class="flex flex-col gap-5 px-4">
            <x-ds::rating name="g-r4" label="Cleanliness" :value="2" help="Shown to the host after your stay." />
            <x-ds::rating name="g-r5" label="Location" error="Choose a rating." />
            <x-ds::rating name="g-r6" label="Locked" :value="3" :disabled="true" />
            <x-ds::rating name="g-r7" label="Required: no way back to none" required />
            <x-ds::rating name="g-r8" label="Out of ten" :max="10" :value="7" size="sm" />
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Readonly <span>&middot; to the nearest half, named as drawn</span></div>
        <div class="{{ $row }} px-4 items-end">
            <x-ds::rating :readonly="true" :value="4.5" label="Average" />
            <x-ds::rating :readonly="true" :value="3.24" />
            <x-ds::rating :readonly="true" :value="1" size="sm" />
            <x-ds::rating :readonly="true" :value="null" size="lg" label="No reviews yet" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Chip group --}}
<section class="ds-spec" id="chip-group">
    <header class="ds-spec-head">
        <h2>Chip group</h2>
        <p>Pressable filters. Each chip is a real radio or checkbox under a pill, so the group posts with the form and binds with <code>wire:model</code> on the group. Or each is a link, for filtering by URL. A chosen chip opens a check before its words, by a width transition, so the row slides rather than jumps.</p>
    </header>

    <div class="ds-note">No script. <code>ds::badge</code> is the static pill; this is the one you press. A group is all inputs or all links, never both, and says so when the view renders.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">One of (radios) <span>&middot; with counts</span></div>
        <div class="px-4">
            <x-ds::chip-group name="g-c1" label="Status" :hideLabel="true">
                <x-ds::chip value="open" :checked="true" :count="12">Open</x-ds::chip>
                <x-ds::chip value="pending" :count="5">Pending</x-ds::chip>
                <x-ds::chip value="closed" :count="48">Closed</x-ds::chip>
                <x-ds::chip value="spam" :disabled="true">Spam</x-ds::chip>
            </x-ds::chip-group>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Any of (checkboxes) <span>&middot; <code>sm</code>, <code>md</code>, <code>lg</code>, with icons</span></div>
        <div class="flex flex-col gap-4 px-4">
            @foreach (['sm', 'md', 'lg'] as $s)
                <x-ds::chip-group name="g-c2{{ $s }}" label="Labels, {{ $s }}" :multiple="true" :size="$s" :hideLabel="true">
                    <x-ds::chip value="billing" icon="credit-card" :checked="true">Billing</x-ds::chip>
                    <x-ds::chip value="bug" icon="bug-ant">Bug</x-ds::chip>
                    <x-ds::chip value="sales" icon="banknotes" :checked="true">Sales</x-ds::chip>
                    <x-ds::chip value="legal" icon="scale" :checked="true" :disabled="true">Legal</x-ds::chip>
                </x-ds::chip-group>
            @endforeach
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Links <span>&middot; <code>href</code> and <code>active</code>, a nav with <code>aria-current</code></span></div>
        <div class="px-4">
            <x-ds::chip-group label="Show">
                <x-ds::chip href="#chip-group" :active="true" :count="16">All</x-ds::chip>
                <x-ds::chip href="#chip-group">Assigned to me</x-ds::chip>
                <x-ds::chip href="#chip-group" :disabled="true">Archived</x-ds::chip>
            </x-ds::chip-group>
        </div>
    </div>
</section>

<section class="ds-spec" id="segmented-control">
    <header class="ds-spec-head">
        <h2>Segmented control</h2>
        <p>One of a few views or modes, side by side in a track. Each segment is a real radio, so the control posts with the form, takes the arrow keys and binds with <code>wire:model</code> on the control. Or each is a link, for switching views by URL.</p>
    </header>

    <div class="ds-note">No script needed. With the package's script and Alpine, one highlight slides between segments; this page loads no Alpine, so what you see is the face CSS draws on the chosen segment, which is also what every page shows before the script runs.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Radios <span>&middot; <code>sm</code>, <code>md</code>, <code>lg</code>, with icons, one disabled</span></div>
        <div class="flex flex-col items-start gap-4 px-4">
            @foreach (['sm', 'md', 'lg'] as $s)
                <x-ds::segmented-control name="g-sc{{ $s }}" label="View, {{ $s }}" :size="$s" :hideLabel="true">
                    <x-ds::segment value="list" icon="list-bullet" :checked="true">List</x-ds::segment>
                    <x-ds::segment value="board" icon="view-columns">Board</x-ds::segment>
                    <x-ds::segment value="calendar" icon="calendar-days" :disabled="true">Calendar</x-ds::segment>
                </x-ds::segmented-control>
            @endforeach
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Labelled, full width, icon only <span>&middot; <code>fullWidth</code>, <code>iconOnly</code></span></div>
        <div class="flex flex-col gap-4 px-4">
            <x-ds::segmented-control name="g-sc-period" label="Period" :fullWidth="true">
                <x-ds::segment value="day">Day</x-ds::segment>
                <x-ds::segment value="week" :checked="true">Week</x-ds::segment>
                <x-ds::segment value="month">Month</x-ds::segment>
                <x-ds::segment value="year">Year</x-ds::segment>
            </x-ds::segmented-control>
            <x-ds::segmented-control name="g-sc-layout" label="Layout" :hideLabel="true" size="sm">
                <x-ds::segment value="grid" icon="squares-2x2" :iconOnly="true" :checked="true">Grid</x-ds::segment>
                <x-ds::segment value="rows" icon="table-cells" :iconOnly="true">Rows</x-ds::segment>
            </x-ds::segmented-control>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Links <span>&middot; <code>href</code> and <code>active</code>, a nav with <code>aria-current</code></span></div>
        <div class="px-4">
            <x-ds::segmented-control label="Report" :hideLabel="true">
                <x-ds::segment href="#segmented-control" :active="true">Overview</x-ds::segment>
                <x-ds::segment href="#segmented-control">Traffic</x-ds::segment>
                <x-ds::segment href="#segmented-control" :disabled="true">Revenue</x-ds::segment>
            </x-ds::segmented-control>
        </div>
    </div>
</section>

<section class="ds-spec" id="button-group">
    <header class="ds-spec-head">
        <h2>Button group</h2>
        <p>Related buttons joined into one shape: the inner corners square off and neighbouring borders become one line. The group sets the size and variant of the buttons inside it, and any button can still set its own.</p>
    </header>

    <div class="ds-note">No script. A split button is a group whose last member is a <code>ds::dropdown</code> with a button as its trigger; the dropdown itself needs Alpine, the group does not.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Outlined <span>&middot; <code>secondary</code>, the default, at <code>sm</code>, <code>md</code>, <code>lg</code></span></div>
        <div class="flex flex-col items-start gap-4 px-4">
            @foreach (['sm', 'md', 'lg'] as $s)
                <x-ds::button-group label="Alignment, {{ $s }}" :size="$s">
                    <x-ds::button>Left</x-ds::button>
                    <x-ds::button>Center</x-ds::button>
                    <x-ds::button>Right</x-ds::button>
                </x-ds::button-group>
            @endforeach
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Split button and filled <span>&middot; <code>primary</code>, <code>danger</code>, parted by a hairline</span></div>
        <div class="{{ $row }} px-4">
            <x-ds::button-group label="Save options" variant="primary">
                <x-ds::button>Save changes</x-ds::button>
                <x-ds::dropdown>
                    <x-slot:trigger>
                        <x-ds::button iconOnly aria-label="More save options"><x-ds::icon name="chevron-down" /></x-ds::button>
                    </x-slot:trigger>
                    <x-ds::dropdown-item href="#button-group">Save as draft</x-ds::dropdown-item>
                </x-ds::dropdown>
            </x-ds::button-group>
            <x-ds::button-group label="Remove" variant="danger" size="sm">
                <x-ds::button>Delete</x-ds::button>
                <x-ds::button>Delete all</x-ds::button>
            </x-ds::button-group>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Vertical, icon only <span>&middot; <code>orientation="vertical"</code></span></div>
        <div class="{{ $row }} items-start px-4">
            <x-ds::button-group label="Zoom" orientation="vertical" size="sm">
                <x-ds::button iconOnly aria-label="Zoom in"><x-ds::icon name="plus" /></x-ds::button>
                <x-ds::button iconOnly aria-label="Zoom out"><x-ds::icon name="minus" /></x-ds::button>
            </x-ds::button-group>
            <x-ds::button-group label="Pages" size="sm">
                <x-ds::button iconOnly aria-label="Previous page"><x-ds::icon name="chevron-left" /></x-ds::button>
                <x-ds::button>Page 2 of 9</x-ds::button>
                <x-ds::button iconOnly aria-label="Next page"><x-ds::icon name="chevron-right" /></x-ds::button>
            </x-ds::button-group>
        </div>
    </div>
</section>

<section class="ds-spec" id="toolbar">
    <header class="ds-spec-head">
        <h2>Toolbar</h2>
        <p>A row of tools that is one stop in the tab order: the arrow keys move between its buttons, Home and End jump to the ends. With the script, a highlight follows the pointer and the keyboard from button to button, and darkens while one is pressed.</p>
    </header>

    <div class="ds-note">Needs Alpine and the package's script for the keyboard and the highlight. This page loads no Alpine, so the buttons show their static faces; the pressed ones are drawn from PHP.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Muted <span>&middot; <code>md</code>, with a separator and a toggle group</span></div>
        <div class="px-4">
            <x-ds::toolbar label="Formatting">
                <x-ds::toolbar-button icon="arrow-uturn-left" label="Undo" :iconOnly="true" />
                <x-ds::toolbar-button icon="arrow-uturn-right" label="Redo" :iconOnly="true" :disabled="true" />
                <x-ds::toolbar-separator />
                <x-ds::toggle-group label="Text style" type="multiple">
                    <x-ds::toggle value="bold" icon="bold" label="Bold" :iconOnly="true" :pressed="true" />
                    <x-ds::toggle value="italic" icon="italic" label="Italic" :iconOnly="true" />
                    <x-ds::toggle value="underline" icon="underline" label="Underline" :iconOnly="true" />
                </x-ds::toggle-group>
                <x-ds::toolbar-separator />
                <x-ds::toolbar-button icon="link" label="Link" />
            </x-ds::toolbar>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Floating <span>&middot; <code>sm</code>, a pressed button, a link</span></div>
        <div class="px-4">
            <x-ds::toolbar label="Selection" variant="floating" size="sm">
                <x-ds::toolbar-button icon="archive-box" label="Archive" />
                <x-ds::toolbar-button icon="star" label="Star" :pressed="true" />
                <x-ds::toolbar-button icon="arrow-top-right-on-square" label="Open" href="#toolbar" />
            </x-ds::toolbar>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Vertical <span>&middot; <code>orientation="vertical"</code>, icon only</span></div>
        <div class="px-4">
            <x-ds::toolbar label="Tools" variant="floating" orientation="vertical">
                <x-ds::toolbar-button icon="cursor-arrow-rays" label="Select" :iconOnly="true" :pressed="true" />
                <x-ds::toolbar-button icon="pencil" label="Draw" :iconOnly="true" :pressed="false" />
                <x-ds::toolbar-separator />
                <x-ds::toolbar-button icon="trash" label="Delete" :iconOnly="true" />
            </x-ds::toolbar>
        </div>
    </div>
</section>

<section class="ds-spec" id="toggle-group">
    <header class="ds-spec-head">
        <h2>Toggle group</h2>
        <p>Buttons that stay pressed. <code>single</code> is a radio group: one is on, and pressing another moves it. <code>multiple</code> is a set of on/off buttons. With a <code>name</code> it posts like the radios or checkboxes it stands in for.</p>
    </header>

    <div class="ds-note">Needs Alpine and the package's script to press. The state is rendered from PHP, so this page, which loads no Alpine, shows it exactly.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Single <span>&middot; icon only, <code>sm</code> and <code>md</code></span></div>
        <div class="flex flex-col items-start gap-4 px-4">
            @foreach (['sm', 'md'] as $s)
                <x-ds::toggle-group label="Alignment, {{ $s }}" name="g-tg{{ $s }}" :size="$s">
                    <x-ds::toggle value="left" icon="bars-3-bottom-left" label="Left" :iconOnly="true" :pressed="true" />
                    <x-ds::toggle value="center" icon="bars-3" label="Center" :iconOnly="true" />
                    <x-ds::toggle value="right" icon="bars-3-bottom-right" label="Right" :iconOnly="true" />
                </x-ds::toggle-group>
            @endforeach
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Multiple <span>&middot; labelled, one disabled</span></div>
        <div class="px-4">
            <x-ds::toggle-group label="Filters" type="multiple" name="g-tg-filters">
                <x-ds::toggle value="open" label="Open" :pressed="true" />
                <x-ds::toggle value="mine" label="Assigned to me" :pressed="true" />
                <x-ds::toggle value="flagged" label="Flagged" />
                <x-ds::toggle value="spam" label="Spam" :disabled="true" />
            </x-ds::toggle-group>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Radio cards --}}
<section class="ds-spec" id="radio-cards">
    <header class="ds-spec-head">
        <h2>Radio cards</h2>
        <p>Options big enough to compare: plans, shipping methods. The whole card is the label of a real, visible radio, so the arrow keys, <code>required</code> and posting are the browser&rsquo;s. The chosen card is ringed by <code>:has(:checked)</code>; no ring slides between them.</p>
    </header>

    <div class="ds-note">No script. The name a screen reader hears is the title and the meta (&ldquo;Pro, $12 a month&rdquo;); the description and body are read after it. Every column count collapses to one on a phone.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Three columns <span>&middot; title, description, meta, body; one disabled</span></div>
        <div class="px-4">
            <x-ds::radio-cards name="g-plan" label="Plan" help="Change or cancel at any time." :columns="3">
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
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">One column, with icons <span>&middot; and an error</span></div>
        <div class="px-4">
            <x-ds::radio-cards name="g-ship" label="Shipping" error="Choose how it should be sent.">
                <x-ds::radio-card value="standard" title="Standard" description="3 to 5 working days.">
                    <x-slot:icon><x-ds::icon name="truck" size="5" /></x-slot:icon>
                    <x-slot:meta>Free</x-slot:meta>
                </x-ds::radio-card>
                <x-ds::radio-card value="express" title="Express" description="Next working day, ordered by 2pm.">
                    <x-slot:icon><x-ds::icon name="bolt" size="5" /></x-slot:icon>
                    <x-slot:meta>$9</x-slot:meta>
                </x-ds::radio-card>
            </x-ds::radio-cards>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Four columns <span>&middot; title alone</span></div>
        <div class="px-4">
            <x-ds::radio-cards name="g-size" label="Size" :columns="4">
                @foreach (['S', 'M', 'L', 'XL'] as $s)
                    <x-ds::radio-card :value="$s" :title="$s" :checked="$s === 'M'" />
                @endforeach
            </x-ds::radio-cards>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Tag input --}}
<section class="ds-spec" id="tag-input">
    <header class="ds-spec-head">
        <h2>Tag input</h2>
        <p>Short values typed into a field and kept as removable chips. Each chip carries the hidden input it posts, so what is on screen and what is submitted cannot disagree.</p>
    </header>

    <div class="ds-note">An entry that cannot be added stays in the box with a message under it, in the help line&rsquo;s place. Nothing typed is ever dropped. The gallery loads no script, so the chips here are the server&rsquo;s.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">States</div>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-ds::tag-input name="g-tag-a" label="Tags" placeholder="Add a tag" help="Press Enter or comma after each tag." />
            <x-ds::tag-input name="g-tag-b" label="Invite by email" :value="['maya@example.com', 'ada@example.com', 'grace@example.com']" validate="email" placeholder="Add an address" />
            <x-ds::tag-input name="g-tag-c" label="Tags" :value="['billing', 'urgent', 'security']" error="Choose at most two tags." />
            <x-ds::tag-input name="g-tag-d" label="Locked" :value="['billing', 'security']" :disabled="true" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Number field --}}
<section class="ds-spec" id="number-field">
    <header class="ds-spec-head">
        <h2>Number field</h2>
        <p>A native <code>type=number</code> with decrease and increase buttons. The arrow keys and typing are the browser&rsquo;s; the buttons add a press, a hold that repeats, and a stop at each bound.</p>
    </header>

    <div class="ds-note">The buttons are out of the tab order on purpose: a keyboard already has the arrow keys. A button at its bound is disabled by the server&rsquo;s render, before any script runs.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">States <span>&middot; the bound buttons are disabled from PHP</span></div>
        <div class="grid gap-6 sm:grid-cols-3">
            <x-ds::number-field name="g-num-a" label="Seats" :value="3" :min="1" :max="10" help="Up to ten on this plan." />
            <x-ds::number-field name="g-num-b" label="Seats" :value="1" :min="1" :max="10" />
            <x-ds::number-field name="g-num-c" label="Seats" :value="10" :min="1" :max="10" error="This plan has ten seats." />
            <x-ds::number-field name="g-num-d" label="Weight (kg)" :value="1.5" :min="0" :step="0.5" />
            <x-ds::number-field name="g-num-e" label="Locked" :value="3" :disabled="true" />
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Sizes <span>&middot; md is the input&rsquo;s own, sm is 32px for a table row</span></div>
        <div class="{{ $row }}">
            <x-ds::number-field name="g-num-f" aria-label="Quantity" :value="2" :min="0" class="w-40" />
            <x-ds::number-field name="g-num-g" aria-label="Quantity" :value="2" :min="0" size="sm" class="w-28" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Password --}}
<section class="ds-spec" id="password-strength">
    <header class="ds-spec-head">
        <h2>Password: reveal and strength</h2>
        <p><code>reveal</code> on <code>ds::input</code> adds a show/hide toggle; <code>ds::password-strength</code> is a meter bound to the field by its id. The score is a hint for the person typing, not a security control.</p>
    </header>

    <div class="ds-note">The level is spoken in words through a polite live region and shown by how many bars are lit, never by colour alone. The gallery loads no script, so the meter here is empty; the documentation image shows it scored.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Reveal and meter</div>
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <x-ds::input type="password" id="g-pw" name="g-pw" label="New password" :reveal="true" value="correct horse" />
                <x-ds::password-strength for="g-pw" />
            </div>
            <x-ds::input type="password" name="g-pw-b" label="Password" :reveal="true" error="That password is not right." />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Alert --}}
<section class="ds-spec" id="alert">
    <header class="ds-spec-head">
        <h2>Alert</h2>
        <p>Fifteen hand-written flash blocks across twelve files, all trying to be these four.</p>
    </header>

    <div class="ds-note">A <em>wash</em>, not a tint. The same hue needs more saturation to register in a badge than across a whole alert &mdash; badge-strength fill at panel size reads as shouting.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Tones <span>&middot; danger announces assertively; the rest do not interrupt</span></div>
        <div class="flex flex-col gap-3">
            <x-ds::alert tone="success" title="Export ready" :dismissible="true">Your file is ready and stays available for 24 hours.</x-ds::alert>
            <x-ds::alert tone="warning" title="Your plan expires in 3 days">Renew to keep unlimited exports. Existing projects stay editable either way.</x-ds::alert>
            <x-ds::alert tone="danger" title="Import failed">We couldn't read that file. Try a text-based export rather than a scan.</x-ds::alert>
            <x-ds::alert tone="accent">Template switching is always free &mdash; it never costs credits.</x-ds::alert>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Wash against tint <span>&middot; the same tone at two surface sizes</span></div>
        <div class="flex flex-wrap items-center gap-4">
            <x-ds::badge tone="success">A tint, at badge size</x-ds::badge>
            <x-ds::alert tone="success" class="flex-1">A wash, at alert size</x-ds::alert>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Stat tile --}}
<section class="ds-spec" id="stat-tile">
    <header class="ds-spec-head">
        <h2>Stat tile</h2>
        <p>Fifteen instances across the dashboard and admin, with the count-up duplicated verbatim five times in one file.</p>
    </header>

    <div class="ds-note">The count-up is skipped when you have asked for reduced motion &mdash; the hand-written version animated regardless.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">The row <span>&middot; interactive tiles carry a hover state; the third does not, because it goes nowhere</span></div>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-ds::stat-tile href="#stat-tile" label="Projects" value="12" :delta="18" />
            <x-ds::stat-tile href="#stat-tile" label="Collaborators" value="4" :delta="-9" />
            <x-ds::stat-tile label="Page views" value="2847" caption="Total public views" />
            <x-ds::stat-tile href="#stat-tile" label="Open tasks" value="31" :delta="0" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Panel --}}
<section class="ds-spec" id="panel">
    <header class="ds-spec-head">
        <h2>Panel</h2>
        <p>A bordered container with an optional header and a divided body. Four copies on the dashboard alone.</p>
    </header>

    <div class="ds-note">The title wraps and the action does not &mdash; truncating a title hides what the reader came for, and a wrapped &ldquo;View all&rdquo; costs a line <em>and</em> looks broken.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">List <span>&middot; one of several; stays quiet</span></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ds::panel title="Recent activity" action="View all" action-href="#panel">
                @foreach ([['Stripe — Product Designer', '2 hours ago', 'success', 'Complete'], ['Linear — Design Engineer', 'yesterday', 'warning', 'Draft']] as [$t, $when, $tone, $state])
                    <x-ds::panel-row href="#panel">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-body font-medium text-fg">{{ $t }}</span>
                            <span class="block text-meta text-fg-muted">{{ $when }}</span>
                        </span>
                        <x-ds::badge :tone="$tone">{{ $state }}</x-ds::badge>
                    </x-ds::panel-row>
                @endforeach
            </x-ds::panel>

            <x-ds::panel title="Open tickets" action="View all" action-href="#panel">
                <x-ds::panel-row href="#panel">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-body font-medium text-fg">Export is missing a page</span>
                        <span class="block text-meta text-fg-muted">Medium priority</span>
                    </span>
                    <x-ds::badge tone="accent">Open</x-ds::badge>
                </x-ds::panel-row>
                <x-ds::panel-row>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-body text-fg-muted">Not clickable &mdash; no hover state</span>
                    </span>
                </x-ds::panel-row>
            </x-ds::panel>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Feature <span>&middot; a destination, not a sibling &mdash; larger radius, solid edge, icon tile</span></div>
        <div>
            <x-ds::panel
                icon="briefcase"
                icon-tone="accent"
                title="Needs attention"
                subtitle="Based on your preferences"
                action="View all"
                action-href="#panel"
                body="plain"
            >
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ([['V', 'Staff Designer', 'Vercel · Remote'], ['N', 'Design Lead', 'Notion · London']] as [$i, $role, $where])
                        <span class="flex items-start gap-3 rounded-panel border border-divider bg-surface-subtle p-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-control bg-accent-tint text-meta font-semibold text-on-accent-tint">{{ $i }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-body font-medium text-fg">{{ $role }}</span>
                                <span class="block truncate text-meta text-fg-muted">{{ $where }}</span>
                            </span>
                        </span>
                    @endforeach
                </div>
            </x-ds::panel>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Table --}}
<section class="ds-spec" id="table">
    <header class="ds-spec-head">
        <h2>Table</h2>
        <p>Twelve admin screens share one recipe. Columns are passed as data, not written as markup.</p>
    </header>

    <div class="ds-note">The scroll container is not optional &mdash; without it one long cell makes the whole page scroll sideways, which on a phone looks like a broken layout.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Rows, alignment and an actions column</div>
        <div>
            <x-ds::table :columns="[
                'User',
                ['label' => 'Role', 'width' => 'w-24'],
                ['label' => 'Status', 'width' => 'w-28'],
                ['label' => 'Projects', 'align' => 'center', 'width' => 'w-24'],
                ['label' => 'Joined', 'width' => 'w-36'],
                ['label' => '', 'width' => 'w-14'],
            ]">
                @foreach ([
                    ['AR', 'Amara Reyes', 'amara@example.com', 'accent', 'Admin', 'success', 'Verified', '14', 'Mar 04, 2026'],
                    ['TO', 'Tobias Okonkwo', 'tobias@example.com', 'neutral', 'User', 'warning', 'Unverified', '2', 'Aug 19, 2026'],
                    ['SK', 'Sunniva Kall', 'sunniva@example.com', 'neutral', 'User', 'danger', 'Blocked', '7', 'Jan 22, 2026'],
                ] as [$in, $name, $mail, $rt, $role, $st, $status, $count, $joined])
                    <x-ds::table-row>
                        <x-ds::table-cell :nowrap="true">
                            <span class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-neutral-tint text-meta font-medium text-on-neutral-tint">{{ $in }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-body font-medium text-fg">{{ $name }}</span>
                                    <span class="block truncate text-meta text-fg-muted">{{ $mail }}</span>
                                </span>
                            </span>
                        </x-ds::table-cell>
                        <x-ds::table-cell :nowrap="true"><x-ds::badge :tone="$rt">{{ $role }}</x-ds::badge></x-ds::table-cell>
                        <x-ds::table-cell :nowrap="true"><x-ds::badge :tone="$st">{{ $status }}</x-ds::badge></x-ds::table-cell>
                        <x-ds::table-cell align="center" class="tabular-nums">{{ $count }}</x-ds::table-cell>
                        <x-ds::table-cell :nowrap="true">{{ $joined }}</x-ds::table-cell>
                        <x-ds::table-cell align="right" :nowrap="true">
                            <x-ds::button icon-only size="sm" variant="ghost" aria-label="More actions">
                                <x-ds::icon name="ellipsis-vertical" />
                            </x-ds::button>
                        </x-ds::table-cell>
                    </x-ds::table-row>
                @endforeach
            </x-ds::table>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Sortable <span>&middot; sorted by Joined, newest first &middot; hover or tab to User or Projects for their faint arrow</span></div>
        <div>
            <x-ds::table sort="joined" direction="desc" sortUrl="#table" :columns="[
                ['label' => 'User', 'key' => 'name', 'sortable' => true],
                ['label' => 'Role', 'width' => 'w-24'],
                ['label' => 'Projects', 'key' => 'projects', 'align' => 'right', 'width' => 'w-28', 'sortable' => true],
                ['label' => 'Joined', 'key' => 'joined', 'width' => 'w-36', 'sortable' => true],
            ]">
                @foreach ([
                    ['Tobias Okonkwo', 'User', '2', 'Aug 19, 2026'],
                    ['Amara Reyes', 'Admin', '14', 'Mar 04, 2026'],
                    ['Sunniva Kall', 'User', '7', 'Jan 22, 2026'],
                ] as [$name, $role, $count, $joined])
                    <x-ds::table-row>
                        <x-ds::table-cell class="font-medium text-fg">{{ $name }}</x-ds::table-cell>
                        <x-ds::table-cell>{{ $role }}</x-ds::table-cell>
                        <x-ds::table-cell align="right" class="tabular-nums">{{ $count }}</x-ds::table-cell>
                        <x-ds::table-cell :nowrap="true">{{ $joined }}</x-ds::table-cell>
                    </x-ds::table-row>
                @endforeach
            </x-ds::table>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Empty <span>&middot; the header stays &mdash; the reader needs to know what is empty</span></div>
        <div>
            <x-ds::table :columns="['User', ['label' => 'Role', 'width' => 'w-24'], ['label' => 'Joined', 'width' => 'w-36']]">
                <x-ds::table-row :hover="false">
                    <x-ds::table-cell colspan="3" align="center" class="py-12">
                        <x-ds::icon name="users" size="10" class="mx-auto text-fg-subtle" />
                        <p class="mt-2 text-body text-fg-muted">No users found.</p>
                    </x-ds::table-cell>
                </x-ds::table-row>
            </x-ds::table>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Pagination --}}
<section class="ds-spec" id="pagination">
    <header class="ds-spec-head">
        <h2>Pagination</h2>
        <p>Two paginator views, rendered here through links() on real paginators. The Livewire variants render the same row with buttons that call the component.</p>
    </header>

    @php
        $pages = (new \Illuminate\Pagination\LengthAwarePaginator(range(1, 15), 237, 15, 8, ['path' => '#pagination']))->onEachSide(1);
        $simple = new \Illuminate\Pagination\Paginator(range(1, 16), 15, 2, ['path' => '#pagination']);
    @endphp

    <div class="{{ $plate }}">
        <div class="ds-plate-label">ds::pagination <span>&middot; page 8 of 16 &middot; the current page is elevation, not colour</span></div>
        {{ $pages->links('ds::pagination') }}
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">ds::pagination-livewire <span>&middot; identical, with buttons calling gotoPage / previousPage / nextPage</span></div>
        {{ $pages->links('ds::pagination-livewire', ['scrollTo' => false]) }}
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">ds::simple-pagination <span>&middot; no position, because a simple paginator never counts</span></div>
        {{ $simple->links('ds::simple-pagination') }}
    </div>
</section>

{{-- ─────────────────────────────────────────── Nav item --}}
<section class="ds-spec" id="nav-item">
    <header class="ds-spec-head">
        <h2>Nav item</h2>
        <p>Twenty-nine of these across two sidebar files that are otherwise a copy-paste fork of each other.</p>
    </header>

    <div class="ds-note">The active item is a raised card, never a colour &mdash; colour would compete with the icon and the label for the same signal, and a change of <em>elevation</em> means the same thing in dark.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Expanded <span>&middot; on the rail's own white card, as it sits in the app</span></div>
        <div class="max-w-64 rounded-panel bg-surface p-2">
            <div class="flex flex-col gap-1">
                <x-ds::nav-item href="#nav-item" label="Home" :active="true">
                    <x-slot:icon><x-ds::icon name="home" /></x-slot:icon>
                </x-ds::nav-item>
                <x-ds::nav-item href="#nav-item" label="Projects">
                    <x-slot:icon><x-ds::icon name="document-text" /></x-slot:icon>
                </x-ds::nav-item>
                <x-ds::nav-item href="#nav-item" label="Reports">
                    <x-slot:icon><x-ds::icon name="envelope" /></x-slot:icon>
                </x-ds::nav-item>
                <x-ds::nav-item href="#nav-item" label="Support tickets" :badge="'3'">
                    <x-slot:icon><x-ds::icon name="lifebuoy" /></x-slot:icon>
                </x-ds::nav-item>
            </div>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Chipped <span>&middot; the admin rail &mdash; the chipped icon keeps one colour whatever the state</span></div>
        <div class="max-w-64 rounded-panel bg-surface p-2">
            <div class="flex flex-col gap-1">
                <x-ds::nav-item href="#nav-item" label="Dashboard" :active="true" :chip="true">
                    <x-slot:icon><x-ds::icon name="squares-2x2" size="3" /></x-slot:icon>
                </x-ds::nav-item>
                <x-ds::nav-item href="#nav-item" label="Users" :chip="true">
                    <x-slot:icon><x-ds::icon name="users" size="3" /></x-slot:icon>
                </x-ds::nav-item>
                <x-ds::nav-item href="#nav-item" label="Analytics" :chip="true">
                    <x-slot:icon><x-ds::icon name="chart-bar" size="3" /></x-slot:icon>
                </x-ds::nav-item>
            </div>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Sub-nav --}}
<section class="ds-spec" id="sub-nav">
    <header class="ds-spec-head">
        <h2>Sub-nav</h2>
        <p>A rail item that holds the pages belonging to it. The children are ordinary nav items, indented under a guide line.</p>
    </header>

    <div class="ds-note">Two different facts, two different marks: the page you are on is the raised card; the section it sits in is drawn at full strength, no card. The open and closed states are rendered by the server, which is why this page shows them without Alpine.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Expanded <span>&middot; the current page&rsquo;s section open, another closed</span></div>
        <div class="max-w-64 rounded-panel bg-surface p-2">
            <x-ds::nav-item href="#sub-nav" label="Home">
                <x-slot:icon><x-ds::icon name="home" /></x-slot:icon>
            </x-ds::nav-item>
            <x-ds::sub-nav label="Reports">
                <x-slot:icon><x-ds::icon name="chart-bar" /></x-slot:icon>
                <x-ds::nav-item href="#sub-nav" label="Revenue" />
                <x-ds::nav-item href="#sub-nav" label="Churn" :active="true" />
                <x-ds::nav-item href="#sub-nav" label="Usage" />
            </x-ds::sub-nav>
            <x-ds::sub-nav label="Settings">
                <x-slot:icon><x-ds::icon name="cog" /></x-slot:icon>
                <x-ds::nav-item href="#sub-nav" label="Profile" />
                <x-ds::nav-item href="#sub-nav" label="Billing" />
            </x-ds::sub-nav>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Chipped <span>&middot; the admin rail, opened with <code>:open="true"</code> though nothing in it is current</span></div>
        <div class="max-w-64 rounded-panel bg-surface p-2">
            <x-ds::sub-nav label="Users" :chip="true" :open="true">
                <x-slot:icon><x-ds::icon name="users" size="3" /></x-slot:icon>
                <x-ds::nav-item href="#sub-nav" label="Members" />
                <x-ds::nav-item href="#sub-nav" label="Invitations" />
            </x-ds::sub-nav>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Bottom nav item --}}
<section class="ds-spec" id="bottom-nav-item">
    <header class="ds-spec-head">
        <h2>Bottom nav item</h2>
        <p>The phone's tabs. The bar around them is <code>lg:hidden</code>, so on this page it would render nothing; the items are the real component, in a bar drawn to match.</p>
    </header>

    <div class="ds-note">The count is the component's to cap: pass the raw number and anything over 9 is drawn as &ldquo;9+&rdquo;, while a screen reader hears the real figure as part of the label.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">With counts <span>&middot; 3, then 12 drawn as 9+; 0 and null draw nothing</span></div>
        {{-- Wrapped: the plate pads its direct child, which would make the bar
             twice its real height. --}}
        <div>
        <div class="mx-auto max-w-sm overflow-hidden rounded-panel border border-border-strong bg-surface">
            <div class="flex h-16 items-center justify-around px-2">
                <x-ds::bottom-nav-item href="#bottom-nav-item" label="Home" :active="true">
                    <x-slot:icon><x-ds::icon name="home" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
                <x-ds::bottom-nav-item href="#bottom-nav-item" label="Inbox" :badge="3">
                    <x-slot:icon><x-ds::icon name="inbox" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
                <x-ds::bottom-nav-item href="#bottom-nav-item" label="Support" :badge="12">
                    <x-slot:icon><x-ds::icon name="lifebuoy" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
                <x-ds::bottom-nav-item href="#bottom-nav-item" label="Reports" :badge="0">
                    <x-slot:icon><x-ds::icon name="chart-bar" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
                <x-ds::bottom-nav-item label="More">
                    <x-slot:icon><x-ds::icon name="menu" size="6" /></x-slot:icon>
                </x-ds::bottom-nav-item>
            </div>
        </div>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Sheet --}}
<section class="ds-spec" id="sheet">
    <header class="ds-spec-head">
        <h2>Sheet</h2>
        <p>The mobile form of a modal or drawer. In the app it is the &ldquo;More&rdquo; menu &mdash; the largest single block in the shell.</p>
    </header>

    <div class="ds-note">Tile colours are a <em>pass-through, not a token</em>: these are decorative wayfinding, and five semantic tones do not stretch to ten features without lying about meaning.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Rows <span>&middot; shown flat; the real sheet slides up over a scrim</span></div>
        <div class="mx-auto max-w-sm overflow-hidden rounded-sheet border border-border bg-surface">
            <div class="flex justify-center pt-3 pb-2"><div class="h-1 w-10 rounded-full bg-fg/20"></div></div>
            <div class="flex items-center justify-between border-b border-divider px-5 pb-4">
                <h2 class="text-title text-fg">More Services</h2>
                <x-ds::icon name="x-mark" size="5" class="text-fg-muted" />
            </div>
            <div class="px-3 py-2">
                <x-ds::sheet-item href="#sheet" label="Projects" description="View and manage every project">
                    <x-slot:icon><x-ds::icon name="document-text" size="5" /></x-slot:icon>
                </x-ds::sheet-item>
                <x-ds::sheet-item href="#sheet" label="Reports" description="Usage and activity over time">
                    <x-slot:icon><x-ds::icon name="briefcase" size="5" /></x-slot:icon>
                </x-ds::sheet-item>
                <x-ds::sheet-item label="Forecasting" description="Not available on this plan" :disabled="true">
                    <x-slot:icon><x-ds::icon name="banknotes" size="5" /></x-slot:icon>
                </x-ds::sheet-item>
            </div>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Modal --}}
<section class="ds-spec" id="modal">
    <header class="ds-spec-head">
        <h2>Modal</h2>
        <p>The surface that blocks the page until the reader deals with it. The desktop form of what the sheet does on a phone.</p>
    </header>

    <div class="ds-note">Shown <em>as it really renders</em> &mdash; the specimen plate carries a <code>transform</code>, which makes it the containing block for a <code>fixed</code> child. So this is the actual component with its actual scrim, boxed rather than redrawn.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Confirmation <span>&middot; size sm &middot; the way out on the left, the answer on the right</span></div>
        <div class="relative h-80 [transform:translateZ(0)]">
            <x-ds::modal open="true" size="sm" title="Delete report?" description="This cannot be undone.">
                The report and every export made from it are removed immediately.
                <x-slot:footer>
                    <x-ds::button variant="ghost" size="sm">Cancel</x-ds::button>
                    <x-ds::button variant="danger" size="sm">Delete report</x-ds::button>
                </x-slot:footer>
            </x-ds::modal>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Scrolling body <span>&middot; the BODY scrolls, not the panel &mdash; the title keeps naming it and the footer keeps its actions</span></div>
        <div class="relative h-96 [transform:translateZ(0)]">
            <x-ds::modal open="true" size="lg" title="Release notes" description="v0.3.0 · every change since v0.2.1">
                <div class="space-y-3 pb-2">
                    @foreach (['Modal and dropdown', 'Four missing specs written', 'Render tests that instantiate Blade', 'Focus trap without a plugin', 'Arrow-key menu navigation', 'Scanner-safe class maps'] as $line)
                        <p><span class="font-semibold text-fg">{{ $line }}</span> &mdash; every class a component emits is now asserted to exist in the compiled CSS, which is the check the icon-size bug walked straight past.</p>
                    @endforeach
                </div>
                <x-slot:footer>
                    <x-ds::button variant="secondary" size="sm">Close</x-ds::button>
                </x-slot:footer>
            </x-ds::modal>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Wide <span>&middot; size 3xl &middot; for content whose natural measure is HORIZONTAL &mdash; a table at 28rem is a column of wrapped cells, not a preview</span></div>
        <div class="relative h-96 [transform:translateZ(0)]">
            <x-ds::modal open="true" size="3xl" title="invoice-2026-08.csv" description="First 4 of 1,204 rows">
                <x-ds::table :columns="[
                    ['label' => 'Reference', 'width' => 'w-32'],
                    'Account',
                    ['label' => 'Period', 'width' => 'w-28'],
                    ['label' => 'Amount', 'align' => 'right', 'width' => 'w-32'],
                ]">
                    @foreach ([
                        ['INV-4821', 'Northwind Trading', 'Aug 2026', '1,240.00'],
                        ['INV-4822', 'Contoso Logistics', 'Aug 2026', '318.50'],
                        ['INV-4823', 'Fabrikam Industrial', 'Aug 2026', '9,700.25'],
                        ['INV-4824', 'Tailspin Freight', 'Aug 2026', '76.00'],
                    ] as [$ref, $account, $period, $amount])
                        <x-ds::table-row>
                            <x-ds::table-cell :nowrap="true" class="font-medium text-fg">{{ $ref }}</x-ds::table-cell>
                            <x-ds::table-cell>{{ $account }}</x-ds::table-cell>
                            <x-ds::table-cell :nowrap="true">{{ $period }}</x-ds::table-cell>
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
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Every width <span>&middot; sm 24rem &middot; md 28 &middot; lg 32 &middot; xl 36 &middot; 2xl 42 &middot; 3xl 48 &middot; 4xl 56 &middot; full = the container less its p-4 gutter, capped at 96rem</span></div>
        <div class="flex flex-col gap-1.5 p-4">
            @foreach (['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl', '3xl' => 'max-w-3xl', '4xl' => 'max-w-4xl', 'full' => 'max-w-[96rem]'] as $name => $class)
                <div class="flex items-center gap-3">
                    <code class="w-12 shrink-0 text-meta text-fg-muted">{{ $name }}</code>
                    <div class="{{ $class }} h-6 w-full rounded-chip bg-neutral-tint"></div>
                </div>
            @endforeach
        </div>
        <div class="ds-note"><code>full</code> is capped at 96rem: uncapped it measures 2528px on a 2560 monitor, which is a line length nobody reads. Below a ~1568px viewport the cap does nothing at all. Past roughly <code>3xl</code>, ask whether it wants to be a page. Something needing 900px of horizontal room usually also wants a URL and a back button.</div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Not dismissible <span>&middot; no close button, no backdrop dismiss &mdash; so the footer MUST offer a way out</span></div>
        <div class="relative h-72 [transform:translateZ(0)]">
            <x-ds::modal open="true" size="sm" title="Session expired" :dismissible="false">
                You were signed out because the session ended. Anything unsaved is still here.
                <x-slot:footer>
                    <x-ds::button variant="ghost" size="sm">Discard</x-ds::button>
                    <x-ds::button size="sm">Sign back in</x-ds::button>
                </x-slot:footer>
            </x-ds::modal>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Dropdown --}}
<section class="ds-spec" id="dropdown">
    <header class="ds-spec-head">
        <h2>Dropdown</h2>
        <p>A menu of verbs anchored to the control that opened it. Not a select &mdash; a select holds values and posts them; this holds actions and performs them.</p>
    </header>

    <div class="ds-note">Elevation is <em>float, not overlay</em>. The shadow scale is where the system says whether a surface hovers over the page or blocks it, and this one hovers.</div>

    <div class="ds-note">The gallery loads <em>no Alpine</em>, so every menu here renders open. That is the state worth reviewing anyway &mdash; the closed state is just the trigger.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">bottom-end <span>&middot; the default, because a trigger on the right runs off the page any other way</span></div>
        <div class="flex h-80 items-start justify-center pt-2">
            <x-ds::dropdown open="true">
                <x-slot:trigger>
                    <x-ds::button variant="secondary" size="sm">
                        Actions
                        <x-ds::icon name="chevron-down" variant="mini" size="4" />
                    </x-ds::button>
                </x-slot:trigger>

                <x-ds::dropdown-item icon="pencil-square" href="#dropdown">Edit report</x-ds::dropdown-item>
                <x-ds::dropdown-item icon="document-duplicate" href="#dropdown">Duplicate</x-ds::dropdown-item>
                <x-ds::dropdown-item icon="arrow-down-tray" href="#dropdown">Export as CSV</x-ds::dropdown-item>
                <x-ds::dropdown-item icon="archive-box" :disabled="true">Archive</x-ds::dropdown-item>
                <x-ds::dropdown-item icon="trash" tone="danger" as="button">Delete</x-ds::dropdown-item>
            </x-ds::dropdown>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">top-start <span>&middot; position, margin AND transform origin together, so it grows from the corner it is pinned by</span></div>
        <div class="flex h-64 items-end justify-center pb-2">
            <x-ds::dropdown open="true" placement="top-start">
                <x-slot:trigger>
                    <x-ds::button variant="secondary" size="sm" iconOnly aria-label="Row actions">
                        <x-ds::icon name="ellipsis-vertical" size="4" />
                    </x-ds::button>
                </x-slot:trigger>

                <x-ds::dropdown-item icon="eye" href="#dropdown">View</x-ds::dropdown-item>
                <x-ds::dropdown-item icon="pencil-square" href="#dropdown">Edit</x-ds::dropdown-item>
                <x-ds::dropdown-item icon="trash" tone="danger" as="button">Delete</x-ds::dropdown-item>
            </x-ds::dropdown>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">In a toolbar <span>&middot; a flex row &mdash; where a stretched root would silently detach the menu from its trigger</span></div>
        <div class="flex h-72 items-center gap-2 px-4">
            <x-ds::button variant="secondary" size="sm">Filter</x-ds::button>
            <x-ds::button variant="secondary" size="sm">Sort</x-ds::button>
            <x-ds::dropdown open="true" placement="bottom-start">
                <x-slot:trigger>
                    <x-ds::button variant="secondary" size="sm">
                        Export
                        <x-ds::icon name="chevron-down" variant="mini" size="4" />
                    </x-ds::button>
                </x-slot:trigger>

                <x-ds::dropdown-item icon="table-cells" href="#dropdown">CSV</x-ds::dropdown-item>
                <x-ds::dropdown-item icon="document-text" href="#dropdown">PDF</x-ds::dropdown-item>
            </x-ds::dropdown>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Popover --}}
<section class="ds-spec" id="popover">
    <header class="ds-spec-head">
        <h2>Popover</h2>
        <p>A small panel of anything, anchored to the button that opened it: filters, a share form, a few settings. A non-modal dialog &mdash; the dropdown stays a menu.</p>
    </header>

    <div class="ds-note">The gallery loads <em>no Alpine</em>, so these panels stay closed: they are <em>x-cloak</em>ed until Alpine shows them, and placed by the package's script. The open panel, flipped and on screen, is in the documentation screenshot and driven by the behaviour suite.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Triggers <span>&middot; your own button; the popover gives it aria-expanded and aria-controls</span></div>
        <div class="{{ $row }}">
            <x-ds::popover label="Filters">
                <x-slot:trigger>
                    <x-ds::button variant="secondary" size="sm">
                        Filters
                        <x-ds::icon name="chevron-down" variant="mini" size="4" />
                    </x-ds::button>
                </x-slot:trigger>
                <x-ds::checkbox name="g-pop-failed" label="Only failed runs" />
            </x-ds::popover>
            <x-ds::popover labelledby="g-pop-share" placement="bottom-end" width="lg">
                <x-slot:trigger>
                    <x-ds::button size="sm">Share</x-ds::button>
                </x-slot:trigger>
                <h3 id="g-pop-share" class="text-section font-semibold text-fg">Share this report</h3>
            </x-ds::popover>
            <x-ds::popover label="Column settings" placement="right-start" width="sm">
                <x-slot:trigger>
                    <x-ds::button variant="ghost" size="sm" iconOnly aria-label="Column settings">
                        <x-ds::icon name="cog" size="4" />
                    </x-ds::button>
                </x-slot:trigger>
                <x-ds::checkbox name="g-pop-col" label="Show totals" />
            </x-ds::popover>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Hover card --}}
<section class="ds-spec" id="hover-card">
    <header class="ds-spec-head">
        <h2>Hover card</h2>
        <p>A look at where a link goes, on hover or keyboard focus, after half a second. Supplementary by contract: the trigger is untouched, a tap still follows it, and nothing in the card is only in the card.</p>
    </header>

    <div class="ds-note">With <em>no Alpine</em> here the cards stay closed; the triggers are your own links, unchanged. The open card is in the documentation screenshot.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">In running text <span>&middot; spans throughout, so it can sit inside a paragraph</span></div>
        <p class="text-body text-fg-body">
            Assigned to
            <x-ds::hover-card>
                <x-slot:trigger><a href="#hover-card" class="font-medium text-fg underline underline-offset-4">Ada Lovelace</a></x-slot:trigger>
                <span class="block font-medium text-fg">Ada Lovelace</span>
                <span class="block text-meta text-fg-muted">Analyst, Engines team</span>
            </x-ds::hover-card>
            and reviewed by
            <x-ds::hover-card placement="top" width="sm">
                <x-slot:trigger><a href="#hover-card" class="font-medium text-fg underline underline-offset-4">Grace Hopper</a></x-slot:trigger>
                <span class="block font-medium text-fg">Grace Hopper</span>
            </x-ds::hover-card>.
        </p>
    </div>
</section>

{{-- ─────────────────────────────────────────── Context menu --}}
<section class="ds-spec" id="context-menu">
    <header class="ds-spec-head">
        <h2>Context menu</h2>
        <p>The dropdown&rsquo;s menu, opened where the reader points: a right-click, a long press, or Shift+F10 on the focused row. The browser&rsquo;s own menu stays on fields and links inside.</p>
    </header>

    <div class="ds-note">With <em>no Alpine</em> here the menu stays closed, and a right-click shows the browser&rsquo;s menu: that is the component without its script. The open menu is in the documentation screenshot and driven by the behaviour suite.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">A region <span>&middot; the default slot; the items go in <code>menu</code>, and every one of them must be reachable some other way too</span></div>
        <x-ds::context-menu label="Report actions" class="rounded-panel border border-border bg-surface p-4">
            <p class="text-section font-semibold text-fg">Q3 revenue</p>
            <p class="text-meta text-fg-muted">Ready, &pound;12,400</p>
            <x-slot:menu>
                <x-ds::dropdown-item icon="pencil-square" href="#context-menu">Edit</x-ds::dropdown-item>
                <x-ds::dropdown-item icon="document-duplicate" href="#context-menu">Duplicate</x-ds::dropdown-item>
                <x-ds::dropdown-item icon="trash" tone="danger" as="button">Delete</x-ds::dropdown-item>
            </x-slot:menu>
        </x-ds::context-menu>
    </div>
</section>

{{-- ─────────────────────────────────────────── Avatar --}}
<section class="ds-spec" id="avatar">
    <header class="ds-spec-head">
        <h2>Avatar</h2>
        <p>An image when there is one, initials when there is not. Round is a person; square is a company, a project or a file.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Sizes <span>&middot; sm is 32px, level with a small button in a table row</span></div>
        <div class="{{ $row }} justify-center">
            <x-ds::avatar name="Ada Lovelace" size="xs" />
            <x-ds::avatar name="Ada Lovelace" size="sm" />
            <x-ds::avatar name="Ada Lovelace" size="md" />
            <x-ds::avatar name="Ada Lovelace" size="lg" />
            <x-ds::avatar name="Acme Holdings" :square="true" size="lg" />
            <x-ds::avatar size="lg" />
        </div>
        <div class="px-4 pb-4 ds-tick">Initials come from the FIRST and LAST word &mdash; <code>Ada King Lovelace</code> is AL, because the middle name is the part nobody uses.</div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Avatar group --}}
@php
    $galleryTeam = [
        ['name' => 'Ada Lovelace'],
        ['name' => 'Grace Hopper'],
        ['name' => 'Alan Turing'],
        ['name' => 'Katherine Johnson'],
        ['name' => 'Edsger Dijkstra'],
        ['name' => 'Acme Holdings', 'square' => true],
    ];
@endphp
<section class="ds-spec" id="avatar-group">
    <header class="ds-spec-head">
        <h2>Avatar group</h2>
        <p>The existing avatar, stacked. Each one sits on an opaque surface backing ringed in the ground colour, so the overlap reads as a cut-out on the panel and on the sunken ground, in both themes.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Sizes <span>&middot; max, then +K; the size passes down to every avatar and the chip</span></div>
        <div class="{{ $row }} gap-6 px-4">
            <x-ds::avatar-group :avatars="$galleryTeam" :max="3" size="xs" />
            <x-ds::avatar-group :avatars="$galleryTeam" :max="4" label="Project members" />
            <x-ds::avatar-group :avatars="$galleryTeam" :max="3" size="md" :total="24" href="#avatar-group" />
            <x-ds::avatar-group :avatars="$galleryTeam" size="lg" />
        </div>
        <div class="px-4 pb-4 ds-tick">A screen reader hears one sentence &mdash; &ldquo;Ada Lovelace, Grace Hopper, Alan Turing, and 3 more&rdquo; &mdash; not six images and a number.</div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">On the sunken ground</div>
        <div class="rounded-control bg-surface-sunken p-4">
            <x-ds::avatar-group :avatars="$galleryTeam" :max="4" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Progress --}}
<section class="ds-spec" id="progress">
    <header class="ds-spec-head">
        <h2>Progress</h2>
        <p>A task with a known length, or one without. The fill reads one number, <code>--ds-progress</code>, so a host moves it with a single binding and no class is ever built at runtime.</p>
    </header>

    <div class="ds-note">The indeterminate sweep is <em>motion-safe</em>. Ask for reduced motion and it becomes a static striped bar &mdash; a still partial bar would read as a real value.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Bar <span>&middot; md and sm, five tones, indeterminate</span></div>
        <div class="flex flex-col gap-5 px-4 pb-4">
            <x-ds::progress label="Uploading report.pdf" :value="64" :showValue="true" />
            <x-ds::progress label="Importing contacts" :value="3" :max="12" tone="accent">3 of 12 files</x-ds::progress>
            <x-ds::progress label="Checks passing" :value="100" tone="success" :showValue="true" size="sm" />
            <x-ds::progress label="Quota" :value="88" tone="warning" :showValue="true" size="sm" />
            <x-ds::progress label="Failed rows" :value="12" tone="danger" :showValue="true" size="sm" />
            <x-ds::progress label="Preparing the export" />
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Circle <span>&middot; md carries its value inside, sm beside</span></div>
        <div class="{{ $row }} gap-6 px-4">
            <x-ds::progress label="Synced" :value="100" variant="circle" tone="success" :showValue="true" />
            <x-ds::progress label="Disk" :value="91" variant="circle" tone="warning" :showValue="true" :hideLabel="true" />
            <x-ds::progress label="Building" :value="40" variant="circle" size="sm" :showValue="true" />
            <x-ds::progress label="Waiting" variant="circle" size="sm" />
            <x-ds::progress label="Waiting" variant="circle" tone="accent" :hideLabel="true" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Usage meter --}}
<section class="ds-spec" id="usage-meter">
    <header class="ds-spec-head">
        <h2>Usage meter</h2>
        <p>An allowance and what fills it, drawn on the progress bar's track. A <code>meter</code>, not a progressbar: nothing is moving towards done. The tone comes from the thresholds, and over the limit is said in words.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Thresholds <span>&middot; neutral, then warning at 80%, danger at 100%</span></div>
        <div class="flex flex-col gap-5 px-4 pb-4">
            <x-ds::usage-meter label="AI credits" :used="18" :limit="50" unit="credits" />
            <x-ds::usage-meter label="Team seats" :used="9" :limit="10" unit="seats" />
            <x-ds::usage-meter label="Emails this month" :used="5240" :limit="5000" />
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Segments <span>&middot; a stacked bar and its legend; over the limit they shrink together</span></div>
        <div class="flex flex-col gap-5 px-4 pb-4">
            <x-ds::usage-meter label="Storage" :limit="10" unit="GB" :segments="[
                ['label' => 'Files', 'value' => 4.5, 'tone' => 'accent'],
                ['label' => 'Backups', 'value' => 2, 'tone' => 'success'],
                ['label' => 'Logs', 'value' => 1.25, 'tone' => 'warning'],
            ]" />
            <x-ds::usage-meter label="Storage" :limit="10" unit="GB" :segments="[
                ['label' => 'Files', 'value' => 9, 'tone' => 'accent'],
                ['label' => 'Backups', 'value' => 3],
            ]" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Stepper --}}
<section class="ds-spec" id="stepper">
    <header class="ds-spec-head">
        <h2>Stepper</h2>
        <p>Where someone is in a flow. An ordered list; the current step is <code>aria-current="step"</code> and every status is spoken, not only coloured. The host says which step is current.</p>
    </header>

    <div class="ds-note">Below 40rem of <em>its own</em> width a horizontal stepper keeps the markers and prints &ldquo;Step 2 of 4&rdquo; and the current title underneath. Resize the window to watch the top one go.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Horizontal <span>&middot; a completed step links back</span></div>
        <div class="px-4 pb-4">
            <x-ds::stepper label="Checkout progress">
                <x-ds::step status="complete" title="Account" description="ada@example.com" href="#stepper" />
                <x-ds::step status="current" title="Plan" description="Pick what you need" />
                <x-ds::step status="upcoming" title="Billing" />
                <x-ds::step status="upcoming" title="Review" />
            </x-ds::stepper>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Vertical, and collapsed <span>&middot; the right one is horizontal in 20rem</span></div>
        <div class="grid gap-6 px-4 pb-4 sm:grid-cols-2">
            <x-ds::stepper orientation="vertical" label="Setup">
                <x-ds::step status="complete" title="Connect the repository" description="github.com/acme/api" />
                <x-ds::step status="error" title="Install the app" description="Permission was refused" />
                <x-ds::step status="current" title="Configure" />
                <x-ds::step status="upcoming" title="Deploy" />
            </x-ds::stepper>
            <div class="max-w-80">
                <x-ds::stepper label="Checkout progress">
                    <x-ds::step status="complete" title="Account" />
                    <x-ds::step status="current" title="Plan" />
                    <x-ds::step status="upcoming" title="Billing" />
                    <x-ds::step status="upcoming" title="Review" />
                </x-ds::stepper>
            </div>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Breadcrumb --}}
<section class="ds-spec" id="breadcrumb">
    <header class="ds-spec-head">
        <h2>Breadcrumb</h2>
        <p>An ordered list, because the order is the meaning. The current page is text, not a link &mdash; a link to where you already are goes nowhere and costs a tab stop.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">A path <span>&middot; every item draws a leading chevron; the parent hides the first</span></div>
        <div class="px-4">
            <x-ds::breadcrumb>
                <x-ds::breadcrumb-item href="#breadcrumb">Home</x-ds::breadcrumb-item>
                <x-ds::breadcrumb-item href="#breadcrumb">Reports</x-ds::breadcrumb-item>
                <x-ds::breadcrumb-item href="#breadcrumb">2026</x-ds::breadcrumb-item>
                <x-ds::breadcrumb-item :current="true">Q3 revenue</x-ds::breadcrumb-item>
            </x-ds::breadcrumb>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Empty state --}}
<section class="ds-spec" id="empty-state">
    <header class="ds-spec-head">
        <h2>Empty state</h2>
        <p>Most emptiness is the beginning, not a failure. The dashed edge says something belongs here and does not exist yet.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Nothing yet <span>&middot; neutral &mdash; nothing has gone wrong</span></div>
        <div class="px-4">
            <x-ds::empty-state title="No reports yet" description="Reports appear here once a project has run for a full day." icon="document-text">
                <x-slot:action><x-ds::button size="sm">New report</x-ds::button></x-slot:action>
            </x-ds::empty-state>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">With a cause <span>&middot; spend a tone only when the emptiness HAS one</span></div>
        <div class="grid gap-4 px-4 sm:grid-cols-2">
            <x-ds::empty-state title="Nothing matched" description="Try a broader date range." icon="magnifying-glass" tone="accent" />
            <x-ds::empty-state title="Import failed" description="The file could not be read." icon="exclamation-circle" tone="danger" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Skeleton --}}
<section class="ds-spec" id="skeleton">
    <header class="ds-spec-head">
        <h2>Skeleton</h2>
        <p>A picture of content that does not exist yet. The fill is <code>fg/10</code> so one value works on the card, on the ground behind it, and in dark.</p>
    </header>

    <div class="ds-note">The last bar is <em>short</em>. A stack of equal bars reads as a table; a paragraph ends mid-line, and that detail is most of what makes this look like text.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Text, block and circle</div>
        <div class="flex flex-col gap-6 px-4">
            <x-ds::skeleton :lines="3" />
            <div class="flex items-center gap-3">
                <x-ds::skeleton variant="circle" size="md" class="w-auto" />
                <x-ds::skeleton :lines="2" />
            </div>
            <x-ds::skeleton variant="block" size="sm" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Checkbox and radio --}}
<section class="ds-spec" id="choice">
    <header class="ds-spec-head">
        <h2>Checkbox &amp; radio</h2>
        <p>The native input, styled with <code>appearance-none</code> &mdash; not a hidden input beside a decorated span. The trick loses high-contrast rendering and forced-colors support, and every one of those failures is invisible in a normal browser.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Checkbox <span>&middot; the label is a real &lt;label for&gt;, so its whole width is the touch target</span></div>
        <div class="flex flex-col gap-3 px-4">
            <x-ds::checkbox name="g1" label="I agree to the terms" />
            <x-ds::checkbox name="g2" label="Email me updates" help="At most one a month." :checked="true" />
            <x-ds::checkbox name="g3" label="Required" error="You must agree to continue." />
            <x-ds::checkbox name="g4" label="Not available on this plan" :disabled="true" />
            <x-ds::checkbox name="g5" label="Locked on" :disabled="true" :checked="true" />
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Checkbox, <code>hideLabel</code> <span>&middot; a select column: the row names the thing, the label stays the accessible name, and a 44px square around the box takes the press</span></div>
        <div class="px-4">
            <x-ds::table>
                <thead class="bg-surface-subtle">
                    <tr>
                        <th scope="col" class="w-12 px-4 py-3"><x-ds::checkbox name="gs" label="Select all" :hideLabel="true" :indeterminate="true" /></th>
                        <th scope="col" class="px-4 py-3 text-left text-overline uppercase text-fg-muted">Report</th>
                    </tr>
                </thead>
                <x-ds::table-row>
                    <x-ds::table-cell><x-ds::checkbox name="gs1" label="Select Q3 revenue" :hideLabel="true" :checked="true" /></x-ds::table-cell>
                    <x-ds::table-cell>Q3 revenue</x-ds::table-cell>
                </x-ds::table-row>
                <x-ds::table-row>
                    <x-ds::table-cell><x-ds::checkbox name="gs2" label="Select Churn by cohort" :hideLabel="true" /></x-ds::table-cell>
                    <x-ds::table-cell>Churn by cohort</x-ds::table-cell>
                </x-ds::table-row>
            </x-ds::table>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Radio <span>&middot; a dot, not a tick &mdash; the shape is the difference between &ldquo;one of these&rdquo; and &ldquo;any of these&rdquo;</span></div>
        <div class="px-4">
            <x-ds::radio-group label="Billing period" help="You can change this later.">
                <x-ds::radio name="period" value="m" label="Monthly" :checked="true" />
                <x-ds::radio name="period" value="y" label="Yearly" help="Two months free." />
                <x-ds::radio name="period" value="n" label="Not now" :disabled="true" />
            </x-ds::radio-group>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Tabs --}}
<section class="ds-spec" id="tabs">
    <header class="ds-spec-head">
        <h2>Tabs</h2>
        <p>The active underline is <code>fg</code>, not <code>accent</code>. A tab row is structure, not a link &mdash; colouring the active one accent makes the inactive tabs look like the links.</p>
    </header>

    <div class="ds-note"><em>navigation</em> is not a styling flag, it is the accessibility contract. <code>role=&quot;tablist&quot;</code> promises arrow keys and in-place content; put it on page links and both halves are false.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Panels <span>&middot; role=tablist &mdash; buttons that swap content on this page</span></div>
        <div class="px-4">
            <x-ds::tabs label="Report sections">
                <x-ds::tab controls="g-p1" :active="true">Overview</x-ds::tab>
                <x-ds::tab controls="g-p2" count="12">Open</x-ds::tab>
                <x-ds::tab controls="g-p3" count="4">Flagged</x-ds::tab>
                <x-ds::tab controls="g-p4" :disabled="true">Archived</x-ds::tab>
            </x-ds::tabs>
            <x-ds::tab-panel id="g-p1" :active="true" class="text-body text-fg-body">
                Panels are hidden with the <code>hidden</code> ATTRIBUTE, so an inactive one leaves the tab order for free.
            </x-ds::tab-panel>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Navigation <span>&middot; &lt;nav&gt; and aria-current &mdash; these are links to other pages</span></div>
        <div class="px-4">
            <x-ds::tabs label="Account" :navigation="true">
                <x-ds::tab href="#tabs" :active="true">Profile</x-ds::tab>
                <x-ds::tab href="#tabs">Billing</x-ds::tab>
                <x-ds::tab href="#tabs" count="3">Team</x-ds::tab>
            </x-ds::tabs>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Select --}}
<section class="ds-spec" id="select">
    <header class="ds-spec-head">
        <h2>Select</h2>
        <p>A listbox, for where the native popup&rsquo;s unstyleable chrome is the problem. The native one is still right on anything phone-first &mdash; it gets typeahead and the platform&rsquo;s own scrolling for free.</p>
    </header>

    <div class="ds-note">The gallery loads <em>no Alpine</em>, so every list renders open &mdash; and the ticks and the trigger label are still right, because the component renders its selected state in PHP rather than waiting for Alpine.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Open <span>&middot; the selected option carries a tick AND the weight change &mdash; weight alone is not reliable when comparing two rows</span></div>
        {{-- The room for the open list comes from a spacer with a HEIGHT, not
             from padding: the gallery chrome sets `padding` on a plate's child
             with a more specific selector than any `pb-*` utility, so the
             utility is silently overridden. --}}
        <div class="mx-auto max-w-xs">
            <x-ds::select
                name="g-plan"
                label="Plan"
                :options="['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team', 'enterprise' => 'Enterprise']"
                value="pro"
                help="You can change this later."
            />
            <div class="h-56" aria-hidden="true"></div>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">States <span>&middot; the trigger shares the input&rsquo;s geometry exactly, so the two line up on one row</span></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ds::select name="g-r" label="Region" :options="['eu' => 'Europe']" placeholder="Choose a region…" />
            <x-ds::select name="g-c" label="Currency" :options="['gbp' => 'GBP']" error="Pick a currency." />
            <div class="h-24 sm:col-span-2" aria-hidden="true"></div>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Tooltip --}}
<section class="ds-spec" id="tooltip">
    <header class="ds-spec-head">
        <h2>Tooltip</h2>
        <p>Supplementary, never the only copy. It does not exist on a touch screen, in a printout, or once the pointer moves &mdash; so the button keeps its own accessible name and the tip only describes it.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Placements <span>&middot; shown open; in the app they appear on hover AND on focus</span></div>
        <div class="{{ $row }} justify-center gap-10 py-10">
            <x-ds::tooltip text="Above" placement="top"><x-ds::button variant="secondary" size="sm">Top</x-ds::button></x-ds::tooltip>
            <x-ds::tooltip text="Below" placement="bottom"><x-ds::button variant="secondary" size="sm">Bottom</x-ds::button></x-ds::tooltip>
            <x-ds::tooltip text="Delete this report permanently" placement="right">
                <x-ds::button variant="secondary" size="sm" iconOnly aria-label="Delete">
                    <x-ds::icon name="trash" size="4" />
                </x-ds::button>
            </x-ds::tooltip>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Toast --}}
<section class="ds-spec" id="toast">
    <header class="ds-spec-head">
        <h2>Toast</h2>
        <p>Surface with a border, not the alert&rsquo;s tinted wash: it floats over unknown content and has to paint an opaque ground. The tone lives in the icon alone, so four stacked toasts do not become a colour chart.</p>
    </header>

    <div class="ds-note">The region is rendered <em>always</em>, and empty most of the time. A live region only announces content that arrives after it is in the document &mdash; render it with the first toast and the first toast is silent.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Stacked <span>&middot; bottom-right</span></div>
        <div class="relative h-96 [transform:translateZ(0)]">
            <x-ds::toast-region position="bottom-right">
                <x-ds::toast tone="success" title="Report exported" dismiss="0">The CSV is in your downloads.</x-ds::toast>
                <x-ds::toast tone="danger" title="Export failed">Try a smaller date range.</x-ds::toast>
                <x-ds::toast tone="success" title="Report archived">
                    <x-slot:action><x-ds::button variant="ghost" size="sm">Undo</x-ds::button></x-slot:action>
                </x-ds::toast>
                <x-ds::toast>Draft saved.</x-ds::toast>
            </x-ds::toast-region>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Drawer --}}
<section class="ds-spec" id="drawer">
    <header class="ds-spec-head">
        <h2>Drawer</h2>
        <p>The third blocking overlay. A modal is <em>answered</em>; a drawer is <em>worked in</em>. The tell is how long the reader stays.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">From the right <span>&middot; filters &mdash; the body scrolls, the footer keeps Apply reachable</span></div>
        <div class="relative h-96 [transform:translateZ(0)]">
            <x-ds::drawer open="true" title="Filters" side="right" size="sm">
                <div class="flex flex-col gap-3">
                    <x-ds::checkbox name="d1" label="Only failed runs" />
                    <x-ds::checkbox name="d2" label="Include archived" :checked="true" />
                    <x-ds::checkbox name="d3" label="Mine only" />
                </div>
                <x-slot:footer>
                    <x-ds::button variant="ghost" size="sm">Reset</x-ds::button>
                    <x-ds::button size="sm">Apply</x-ds::button>
                </x-slot:footer>
            </x-ds::drawer>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Every width <span>&middot; sm 24rem &middot; md 28 &middot; lg 32 &middot; xl 36 &middot; 2xl 42 &middot; full = calc(100vw &minus; 3rem)</span></div>
        <div class="flex flex-col items-end gap-1.5 p-4">
            @foreach (['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl', 'full' => 'max-w-[calc(100vw-3rem)]'] as $name => $class)
                <div class="flex w-full items-center gap-3">
                    <code class="w-12 shrink-0 text-meta text-fg-muted">{{ $name }}</code>
                    <div class="{{ $class }} ml-auto h-6 w-full rounded-l-chip border-l border-border bg-neutral-tint"></div>
                </div>
            @endforeach
        </div>
        <div class="ds-note"><code>full</code> is <code>calc(100vw &minus; 3rem)</code>, not <code>max-w-none</code>. The container is <code>fixed inset-0</code> with no padding, so edge-to-edge would leave nothing of the page visible &mdash; and a panel covering everything is a screen, not a drawer. The sliver is what says the thing you came from is one click away.</div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Wide <span>&middot; size 2xl &middot; a drawer used to READ a record rather than filter one</span></div>
        <div class="relative h-96 [transform:translateZ(0)]">
            <x-ds::drawer open="true" title="Run #4821" side="right" size="2xl">
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
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">From the left <span>&middot; navigation lives on the left, so its drawer does too</span></div>
        <div class="relative h-80 [transform:translateZ(0)]">
            <x-ds::drawer open="true" title="Menu" side="left" size="sm">
                Navigation content.
            </x-ds::drawer>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Switch --}}
<section class="ds-spec" id="switch">
    <header class="ds-spec-head">
        <h2>Switch</h2>
        <p>A setting that takes effect. <code>role=&quot;switch&quot;</code> makes a screen reader say &ldquo;on&rdquo; and &ldquo;off&rdquo; &mdash; &ldquo;unchecked&rdquo;, for a setting that is simply off, reads as a form the reader failed to fill in.</p>
    </header>

    <div class="ds-note">The label sits <em>left</em> and the control right, the opposite of the checkbox: a switch is one of a list of settings the reader scans down the left edge.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">States <span>&middot; an unchecked checkbox posts NOTHING, so a hidden 0 rides alongside</span></div>
        <div class="mx-auto max-w-md">
            <div class="flex flex-col gap-4 rounded-control border border-border bg-surface p-4">
                <x-ds::switch name="g-sw1" label="Email notifications" help="At most one a month." :checked="true" />
                <x-ds::switch name="g-sw2" label="Beta features" help="Things that may change without warning." />
                <x-ds::switch name="g-sw3" label="Not available on this plan" :disabled="true" />
            </div>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Accordion --}}
<section class="ds-spec" id="accordion">
    <header class="ds-spec-head">
        <h2>Accordion</h2>
        <p>Sections that expand one at a time. The one component in the system that holds state &mdash; exclusivity is a relationship <em>between</em> items, so no single item can enforce it.</p>
    </header>

    <div class="ds-note">A collapsed panel is <em>visibility: hidden</em>, not <code>overflow: hidden</code>. That takes its content out of the tab order AND the accessibility tree; a collapsed section whose links are still tabbable is the classic broken accordion, and it looks perfect.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Exclusive <span>&middot; the open section renders open, before Alpine boots</span></div>
        <div class="mx-auto max-w-lg">
            <x-ds::accordion open="billing">
                <x-ds::accordion-item title="Billing" name="billing">
                    Your card ending 4242 is charged on the first of each month.
                </x-ds::accordion-item>
                <x-ds::accordion-item title="Security" name="security">Two-factor authentication is on.</x-ds::accordion-item>
                <x-ds::accordion-item title="Notifications" name="notifications">A weekly digest, every Monday.</x-ds::accordion-item>
            </x-ds::accordion>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Combobox --}}
<section class="ds-spec" id="combobox">
    <header class="ds-spec-head">
        <h2>Combobox</h2>
        <p>A select with a text filter. <code>multiple</code> is a <em>mode</em> of it, not a second component &mdash; chips and <code>aria-multiselectable</code> are the whole difference.</p>
    </header>

    <div class="ds-note">Chips sit <em>inside</em> the field. A list of choices below the control reads as results, and people click them expecting to select rather than to remove.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Multiple <span>&middot; shown open; the gallery loads no Alpine</span></div>
        <div class="mx-auto max-w-sm">
            <x-ds::combobox
                name="g-tags"
                label="Tags"
                :options="['a11y' => 'Accessibility', 'billing' => 'Billing', 'compliance' => 'Compliance', 'design' => 'Design']"
                :value="['a11y', 'billing']"
                :multiple="true"
                help="Start typing to filter."
            />
            <div class="h-64" aria-hidden="true"></div>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── Date picker --}}
<section class="ds-spec" id="date-picker">
    <header class="ds-spec-head">
        <h2>Date picker</h2>
        <p>A calendar for one date or a range. Every value is a <code>Y-m-d</code> string, never a <code>Date</code> &mdash; <code>new Date('2026-03-29')</code> is UTC midnight, which west of Greenwich is the 28th.</p>
    </header>

    <div class="ds-note">A range posts <em>two fields</em>. Two dates are two values, and squeezing them into one string means every consumer writing the same parser.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Range <span>&middot; endpoints inverse, the span washed &mdash; shown open</span></div>
        <div class="mx-auto max-w-sm">
            <x-ds::date-picker name="g-period" label="Reporting period" :range="true" :value="['2026-09-07', '2026-09-19']" />
            <div class="h-96" aria-hidden="true"></div>
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── File upload --}}
<section class="ds-spec" id="file-upload">
    <header class="ds-spec-head">
        <h2>File upload</h2>
        <p>A styled file field with drag-and-drop and local previews. It does <em>not</em> upload anything &mdash; progress needs somewhere to upload to, and that is a backend contract nothing else here has.</p>
    </header>

    <div class="ds-note">The real <code>&lt;input type=&quot;file&quot;&gt;</code> is stretched over the whole zone at zero opacity, so the entire area is the control and drag-and-drop lands on the element that owns the files.</div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">Drop zone <span>&middot; the accept string is rendered for a person: image/*,.pdf becomes &ldquo;images, PDF&rdquo;</span></div>
        <div class="mx-auto max-w-md">
            <x-ds::file-upload name="g-files[]" label="Attachments" :multiple="true" accept="image/*,.pdf" :maxSize="5242880" />
        </div>
    </div>
</section>

{{-- ─────────────────────────────────────────── In situ --}}
<section class="ds-spec" id="in-situ">
    <header class="ds-spec-head">
        <h2>Together</h2>
        <p>The components on a real surface, so spacing and weight can be judged in context rather than in isolation.</p>
    </header>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">A resource-list header and a row</div>
        <div class="rounded-panel border border-border bg-surface p-6 shadow-raised">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h3 class="text-heading text-fg">Projects</h3>
                    <p class="mt-1 text-body text-fg-muted">19 documents</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-ds::button variant="secondary" size="md">
                        <x-ds::icon name="funnel" />
                        Filter
                    </x-ds::button>
                    <x-ds::button size="md">
                        <x-ds::icon name="plus" />
                        Create project
                    </x-ds::button>
                </div>
            </div>

            <div class="mt-6 divide-y divide-border border-t border-border">
                @foreach ([
                    ['Senior Product Designer', 'Modern · 2 hours ago', 'success', '92'],
                    ['Design Engineer 2026', 'Classic · yesterday', 'warning', '74'],
                    ['Untitled project', 'Not started · 3 days ago', 'neutral', 'Draft'],
                ] as [$title, $meta, $tone, $score])
                    <div class="flex items-center gap-3 py-3">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-control bg-surface-subtle text-fg-subtle">
                            <x-ds::icon name="document-text" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-body font-medium text-fg">{{ $title }}</span>
                            <span class="block text-meta text-fg-muted">{{ $meta }}</span>
                        </span>
                        <x-ds::badge :tone="$tone">{{ $score }}</x-ds::badge>
                        <x-ds::button icon-only size="sm" variant="ghost" aria-label="More actions">
                            <x-ds::icon name="ellipsis-vertical" />
                        </x-ds::button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="{{ $plate }}">
        <div class="ds-plate-label">A destructive confirm</div>
        <div class="mx-auto max-w-md overflow-hidden rounded-panel bg-surface shadow-overlay ring-1 ring-border">
            <div class="flex items-start gap-3.5 p-6 pb-0">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-danger-tint text-on-danger-tint">
                    <x-ds::icon name="exclamation-triangle" size="5" />
                </span>
                <div>
                    <h3 class="text-section text-fg">Delete this project?</h3>
                    <p class="mt-1 text-body text-fg-muted">&ldquo;Senior Product Designer&rdquo; moves to trash. You can restore it for 30 days.</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2.5 border-t border-border bg-surface-subtle px-6 py-3.5">
                <x-ds::button variant="secondary" size="md">Cancel</x-ds::button>
                <x-ds::button variant="danger" size="md">Delete</x-ds::button>
            </div>
        </div>
    </div>
</section>
