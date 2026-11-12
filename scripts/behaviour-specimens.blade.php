{{--
    Specimen source for the BEHAVIOUR tests.

    Every interactive component, wired the way a host would wire it, with an id
    on anything a test needs to press. Rendered by scripts/render.php and driven
    by scripts/test-behaviour.mjs.

    Separate from the gallery and the documentation specimens on purpose: those
    two exist to be LOOKED at and show every variant at once. This one exists to
    be PRESSED, so it carries one of each thing and a stable handle on all of it.
--}}

<div x-data="{ modal: false, drawer: false, sheet: false, accordionReady: true }" class="p-8">
    <form id="form">
        {{-- ── modal ── --}}
        <x-ds::button id="open-modal" @click="modal = true">Delete report</x-ds::button>
        <x-ds::modal open="modal" title="Delete report?" description="This cannot be undone." size="sm">
            Body text.
            <x-slot:footer>
                <x-ds::button id="modal-cancel" variant="ghost" size="sm" @click="modal = false">Cancel</x-ds::button>
                <x-ds::button id="modal-confirm" variant="danger" size="sm">Delete</x-ds::button>
            </x-slot:footer>
        </x-ds::modal>

        {{-- ── drawer ── --}}
        <x-ds::button id="open-drawer" @click="drawer = true">Filters</x-ds::button>
        <x-ds::drawer open="drawer" title="Filters" side="right" size="sm">
            <x-ds::checkbox name="failed" id="drawer-check" label="Only failed runs" />
            <x-slot:footer>
                <x-ds::button id="drawer-apply" size="sm">Apply</x-ds::button>
            </x-slot:footer>
        </x-ds::drawer>

        {{-- ── dropdown ──
             Centred, so the menu has room on every side and the edge check at
             the end of the run can prove it opens where its classes put it. --}}
        <div class="mt-6 flex justify-center">
            <x-ds::dropdown>
                <x-slot:trigger>
                    <x-ds::button id="menu-trigger" variant="secondary" size="sm">Actions</x-ds::button>
                </x-slot:trigger>
                <x-ds::dropdown-item id="item-edit" icon="pencil-square" href="#edit">Edit</x-ds::dropdown-item>
                <x-ds::dropdown-item id="item-dup" icon="document-duplicate" href="#dup">Duplicate</x-ds::dropdown-item>
                <x-ds::dropdown-item id="item-archive" icon="archive-box" :disabled="true">Archive</x-ds::dropdown-item>
                <x-ds::dropdown-item id="item-delete" icon="trash" tone="danger" as="button">Delete</x-ds::dropdown-item>
            </x-ds::dropdown>
        </div>

        {{-- ── tooltip ── centred for the same reason --}}
        <div class="mt-6 flex justify-center">
            <x-ds::tooltip text="Delete this report permanently">
                <x-ds::button id="tip-trigger" variant="secondary" size="sm" iconOnly aria-label="Delete">
                    <x-ds::icon name="trash" size="4" />
                </x-ds::button>
            </x-ds::tooltip>
        </div>

        {{-- ── select ── --}}
        <div class="mt-6 max-w-xs">
            <x-ds::select name="plan" id="plan" label="Plan"
                :options="['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team']" value="pro" />
        </div>

        {{-- ── combobox ── --}}
        <div class="mt-6 max-w-xs">
            <x-ds::combobox name="tags" id="tags" label="Tags"
                :options="['a11y' => 'Accessibility', 'billing' => 'Billing', 'compliance' => 'Compliance']"
                :value="['a11y']" :multiple="true" />
        </div>

        {{-- ── switch ── --}}
        <div class="mt-6 max-w-xs">
            <x-ds::switch name="notify" id="notify" label="Email notifications" />
        </div>

        {{-- ── checkbox, hidden label ── --}}
        <div class="mt-6 max-w-xs">
            <x-ds::table>
                <x-ds::table-row>
                    <x-ds::table-cell><x-ds::checkbox name="pick[]" value="42" id="pick" label="Select Invoice 42" :hideLabel="true" /></x-ds::table-cell>
                    <x-ds::table-cell>Invoice 42</x-ds::table-cell>
                </x-ds::table-row>
            </x-ds::table>
        </div>

        {{-- ── accordion ── --}}
        <div class="mt-6 max-w-md">
            <x-ds::accordion open="billing">
                <x-ds::accordion-item title="Billing" name="billing">
                    <a href="#b" id="in-billing">Billing link</a>
                </x-ds::accordion-item>
                <x-ds::accordion-item title="Security" name="security">
                    <a href="#s" id="in-security">Security link</a>
                </x-ds::accordion-item>
            </x-ds::accordion>
        </div>

        {{-- ── date picker ── --}}
        <div class="mt-6 max-w-xs">
            <x-ds::date-picker name="period" id="period" label="Period" :range="true" value="2026-09-10" />
        </div>

        {{-- ── tabs, wired by the host ──

             This is the example in specs/tabs.md, verbatim. It is here so that
             example is DRIVEN rather than asserted: the spec used to document
             `::active`, which binds an `active` ATTRIBUTE and does nothing at
             all, because the component computed its classes from the PHP prop at
             render time. Nothing errored. The tab simply never changed.

             Two things are being proved at once. That the classes actually flip
             — which needs Alpine's OBJECT syntax, since the string form only
             ADDS classes and the server-rendered `border-transparent` would
             survive and win. And that the host's arrow keys move both the
             selection and the focus. --}}
        <div
            class="mt-6"
            x-data="{
                tab: 'overview',
                tabs: ['overview', 'activity'],
                go(name) {
                    this.tab = name;
                    // Focus has to follow the selection, or a keyboard reader is
                    // left sitting on a tab that is no longer the selected one.
                    this.$nextTick(() => this.$refs[name].focus());
                },
                move(step) {
                    const at = this.tabs.indexOf(this.tab);
                    this.go(this.tabs[(at + step + this.tabs.length) % this.tabs.length]);
                },
            }"
        >
            <x-ds::tabs
                label="Report sections"
                @keydown.right.prevent="move(1)"
                @keydown.left.prevent="move(-1)"
            >
                <x-ds::tab
                    id="tab-overview"
                    controls="p-overview"
                    x-ref="overview"
                    :active="true"
                    ::class="{
                        'border-fg text-fg': tab === 'overview',
                        'border-transparent text-fg-muted hover:border-border-strong hover:text-fg': tab !== 'overview',
                    }"
                    ::aria-selected="tab === 'overview'"
                    ::tabindex="tab === 'overview' ? 0 : -1"
                    @click="tab = 'overview'"
                >Overview</x-ds::tab>

                <x-ds::tab
                    id="tab-activity"
                    controls="p-activity"
                    x-ref="activity"
                    ::class="{
                        'border-fg text-fg': tab === 'activity',
                        'border-transparent text-fg-muted hover:border-border-strong hover:text-fg': tab !== 'activity',
                    }"
                    ::aria-selected="tab === 'activity'"
                    ::tabindex="tab === 'activity' ? 0 : -1"
                    @click="tab = 'activity'"
                >Activity</x-ds::tab>
            </x-ds::tabs>

            {{-- `:active` is the PHP prop and `::hidden` the Alpine binding, on
                 purpose: the first paint is right before Alpine boots, and moves
                 after it. --}}
            <x-ds::tab-panel id="p-overview" labelledby="tab-overview" :active="true"
                ::hidden="tab !== 'overview'">Overview panel</x-ds::tab-panel>

            <x-ds::tab-panel id="p-activity" labelledby="tab-activity"
                ::hidden="tab !== 'activity'">Activity panel</x-ds::tab-panel>
        </div>

        {{-- ── file upload ── --}}
        <div class="mt-6 max-w-sm">
            <x-ds::file-upload name="files[]" id="files" label="Files" :multiple="true" />
        </div>

        {{-- ── otp input, from the shared script ──
             The first is wired the two ways docs/components/otp-input.md shows:
             x-model on the tag, bound both ways through x-modelable, and
             @complete on the tag hearing the event dispatched from the field
             that posts. The host's state starts at the server's value, as
             wire:model's would. --}}
        <div class="mt-6" x-data="{ code: '12' }">
            <x-ds::otp-input name="otp" id="otp" label="Verification code" value="12"
                x-model="code" @complete="window.otpHeard = [...(window.otpHeard ?? []), $event.detail.value]" />
            <span id="otp-mirror" x-text="code">12</span>
            <button type="button" id="otp-host-set" @click="code = '246810'">Set code</button>
            <button type="button" id="otp-host-bad" @click="code = '12ab'">Set letters</button>
        </div>
        <div class="mt-6 flex gap-8">
            <x-ds::otp-input name="pin" id="pin" label="PIN" :length="4" :masked="true" />
            <x-ds::otp-input name="ref" id="ref" label="Reference" :length="5" :numeric="false" />
        </div>

        {{-- ── tag input, from the shared script, x-model on the tag ── --}}
        <div class="mt-6 max-w-md" x-data="{ emails: ['maya@example.com'] }">
            <x-ds::tag-input name="emails" id="emails" label="Recipients" :value="['maya@example.com']"
                x-model="emails" validate="email" :max="5" help="Press Enter or comma after each address." />
            <span id="emails-mirror" x-text="emails.join(' ')">maya@example.com</span>
            <button type="button" id="emails-host-set" @click="emails = ['zoe@example.com', ' ', 'yan@example.com']">Set two</button>
            <button type="button" id="emails-host-clear" @click="emails = []">Clear</button>
        </div>

        {{-- ── rating: radios and CSS, no script ── --}}
        <div class="mt-6" id="rating">
            <x-ds::rating name="score" label="How was your visit?" :value="2" />
        </div>
        <div class="mt-6">
            <x-ds::rating id="avg" :readonly="true" :value="3.5" label="Average" />
        </div>

        {{-- ── chip groups: inputs and CSS, no script ── --}}
        <div class="mt-6" id="chips-single">
            <x-ds::chip-group name="status" label="Status">
                <x-ds::chip value="open" :checked="true" :count="12">Open</x-ds::chip>
                <x-ds::chip value="closed" :count="4">Closed</x-ds::chip>
                <x-ds::chip value="spam" :disabled="true">Spam</x-ds::chip>
            </x-ds::chip-group>
        </div>
        <div class="mt-6" id="chips-multi">
            <x-ds::chip-group name="labels" label="Labels" :multiple="true" size="sm">
                <x-ds::chip value="billing" :checked="true">Billing</x-ds::chip>
                <x-ds::chip value="bug" icon="bug-ant">Bug</x-ds::chip>
                <x-ds::chip value="sales">Sales</x-ds::chip>
            </x-ds::chip-group>
        </div>
        <div class="mt-6" id="chips-model" x-data="{ picked: ['bug'] }">
            <x-ds::chip-group name="picked" label="Picked" :multiple="true" x-model="picked">
                <x-ds::chip value="billing">Billing</x-ds::chip>
                <x-ds::chip value="bug">Bug</x-ds::chip>
                <x-ds::chip value="sales">Sales</x-ds::chip>
            </x-ds::chip-group>
            <output id="picked-mirror" x-text="JSON.stringify(picked)"></output>
            <button type="button" id="picked-host-set" @click="picked = ['sales']">host sets sales</button>
        </div>

        {{-- ── radio cards: radios and CSS, no script ── --}}
        <div class="mt-6" id="cards">
            <x-ds::radio-cards name="tier" label="Plan" :columns="3">
                <x-ds::radio-card value="free" title="Free" description="For trying it out.">
                    <x-slot:meta>$0</x-slot:meta>
                </x-ds::radio-card>
                <x-ds::radio-card value="pro" title="Pro" description="For a small team." :checked="true">
                    <x-slot:meta>$12 a month</x-slot:meta>
                </x-ds::radio-card>
                <x-ds::radio-card value="scale" title="Scale" :disabled="true" />
            </x-ds::radio-cards>
        </div>
        <div class="mt-6" id="cards-required">
            <x-ds::radio-cards name="ship" label="Shipping" required>
                <x-ds::radio-card value="standard" title="Standard" />
                <x-ds::radio-card value="express" title="Express" />
            </x-ds::radio-cards>
        </div>

        {{-- ── segmented control: radios and CSS, the glide an enhancement ── --}}
        <div class="mt-6" id="seg" x-data="{ view: 'list' }">
            <x-ds::segmented-control name="view" label="View" x-model="view">
                <x-ds::segment value="list" icon="list-bullet" :checked="true">List</x-ds::segment>
                <x-ds::segment value="board" icon="view-columns">Board</x-ds::segment>
                <x-ds::segment value="calendar">Calendar view</x-ds::segment>
                <x-ds::segment value="archive" :disabled="true">Archive</x-ds::segment>
            </x-ds::segmented-control>
            <output id="seg-mirror" x-text="view"></output>
            <button type="button" id="seg-host-set" @click="view = 'calendar'">host sets calendar</button>
        </div>
        <div class="mt-6 w-96" id="seg-rtl" dir="rtl">
            <x-ds::segmented-control name="period" label="Period" :fullWidth="true" size="sm">
                <x-ds::segment value="day">Day</x-ds::segment>
                <x-ds::segment value="week" :checked="true">Week</x-ds::segment>
                <x-ds::segment value="month">Month</x-ds::segment>
            </x-ds::segmented-control>
        </div>
        <div class="mt-6" id="seg-links">
            <x-ds::segmented-control label="Show">
                <x-ds::segment href="#all">All</x-ds::segment>
                <x-ds::segment href="#mine" :active="true">Mine</x-ds::segment>
            </x-ds::segmented-control>
        </div>

        {{-- ── button group and split button: plain Blade ── --}}
        <div class="mt-6" id="bgroup">
            <x-ds::button-group label="Save options">
                <x-ds::button id="bgroup-save">Save</x-ds::button>
                <x-ds::dropdown>
                    <x-slot:trigger>
                        <x-ds::button id="bgroup-more" iconOnly aria-label="More save options">
                            <x-ds::icon name="chevron-down" />
                        </x-ds::button>
                    </x-slot:trigger>
                    <x-ds::dropdown-item id="bgroup-draft" href="#draft">Save as draft</x-ds::dropdown-item>
                </x-ds::dropdown>
            </x-ds::button-group>
        </div>

        {{-- ── toolbar (dsToolbar, x-ds-glide.hover) with a toggle group inside ── --}}
        <div class="mt-6" id="tb-wrap">
            <x-ds::toolbar label="Formatting" id="tb">
                <x-ds::toolbar-button id="tb-undo" icon="arrow-uturn-left" label="Undo" :iconOnly="true" :tooltip="true" />
                <x-ds::toolbar-button id="tb-redo" icon="arrow-uturn-right" label="Redo" :iconOnly="true" :disabled="true" />
                <x-ds::toolbar-separator />
                <x-ds::toggle-group label="Text style" type="multiple" name="marks" id="tb-marks">
                    <x-ds::toggle id="tb-bold" value="bold" label="Bold" icon="bold" :iconOnly="true" :pressed="true" />
                    <x-ds::toggle id="tb-italic" value="italic" label="Italic" icon="italic" :iconOnly="true" />
                </x-ds::toggle-group>
                <x-ds::toolbar-separator />
                <x-ds::toolbar-button id="tb-star" icon="star" label="Star" :pressed="false" />
                <x-ds::toolbar-button id="tb-share" label="Share" href="#share" />
            </x-ds::toolbar>
        </div>
        <div class="mt-6" id="tb-v-wrap">
            <x-ds::toolbar label="Canvas tools" orientation="vertical" variant="floating" size="sm" id="tb-v">
                <x-ds::toolbar-button id="tbv-a" icon="cursor-arrow-rays" label="Select" :iconOnly="true" />
                <x-ds::toolbar-button id="tbv-b" icon="hand-raised" label="Pan" :iconOnly="true" />
                <x-ds::toolbar-button id="tbv-c" icon="pencil" label="Draw" :iconOnly="true" />
            </x-ds::toolbar>
        </div>

        {{-- ── toggle groups on their own (dsToggleGroup) ── --}}
        <div class="mt-6" id="tg-single-wrap">
            <x-ds::toggle-group label="Alignment" name="align" id="tg-single">
                <x-ds::toggle id="tg-left" value="left" label="Left" icon="bars-3-bottom-left" :pressed="true" />
                <x-ds::toggle id="tg-center" value="center" label="Center" icon="bars-3" />
                <x-ds::toggle id="tg-justify" value="justify" label="Justify" :disabled="true" />
                <x-ds::toggle id="tg-right" value="right" label="Right" icon="bars-3-bottom-right" />
            </x-ds::toggle-group>
        </div>
        <div class="mt-6" id="tg-model-wrap" x-data="{ marks: ['italic'] }">
            <x-ds::toggle-group label="Marks" type="multiple" id="tg-model" x-model="marks">
                <x-ds::toggle id="tgm-bold" value="bold" label="Bold" />
                <x-ds::toggle id="tgm-italic" value="italic" label="Italic" :pressed="true" />
                <x-ds::toggle id="tgm-under" value="underline" label="Underline" />
            </x-ds::toggle-group>
            <output id="tg-mirror" x-text="JSON.stringify(marks)"></output>
            <button type="button" id="tg-host-set" @click="marks = ['underline']">host sets underline</button>
        </div>

        {{-- ── number field, from the shared script ── --}}
        <div class="mt-6 max-w-xs">
            <x-ds::number-field name="seats" id="seats" label="Seats" :value="2" :min="1" :max="5" />
        </div>

        {{-- ── password: reveal and strength, from the shared script ── --}}
        <div class="mt-6 max-w-xs">
            <x-ds::input type="password" name="password" id="password" label="Password" :reveal="true" />
            <x-ds::password-strength for="password" id="strength" />
        </div>
    </form>

    {{-- ── x-ds-glide on its own, in markup no component wrote ──
         The host's `at` is what the glide watches, and the attribute it
         reads; the grow button changes an item's width and nothing else. --}}
    <div class="mt-6" x-data="{ at: 'b' }">
        <div id="glide-raw" x-ds-glide="at" data-ds-glide class="relative inline-flex gap-6 p-2">
            <span data-ds-glide-highlight hidden class="pointer-events-none absolute top-0 left-0 h-(--ds-glide-h) w-(--ds-glide-w) translate-x-(--ds-glide-x) translate-y-(--ds-glide-y) bg-fg/10 motion-safe:transition-[translate,width,height] motion-safe:duration-popover-enter motion-safe:ease-enter motion-safe:data-[instant]:transition-none"></span>
            <span id="glide-a" data-ds-glide-item class="relative" :aria-current="String(at === 'a')">Alpha</span>
            <span id="glide-b" data-ds-glide-item class="relative" :aria-current="String(at === 'b')">Bravo</span>
            <span id="glide-c" data-ds-glide-item class="relative" :aria-current="String(at === 'c')">Charlie</span>
        </div>
        <button type="button" id="glide-to-c" @click="at = 'c'">to c</button>
        <button type="button" id="glide-grow" @click="document.getElementById('glide-c').textContent = 'Charlie, now much longer'">grow</button>
    </div>

    {{-- ── x-ds-anchor on its own, in markup no component wrote ──
         Each panel sits inside a box that would defeat it: one clips with
         overflow hidden, one is transformed and so becomes the containing
         block of anything fixed inside it. The panels carry absolute
         fallback classes, as the overlays that will adopt this do. The wide
         panel is wider than the room to the right of its reference, and the
         tall one is taller than the window. --}}
    <div class="mt-6" x-data="{ anchorOpen: false, anchorWide: false, anchorTall: false, anchorBoxed: false }">
        <div id="anchor-clip" class="relative h-10 w-64 overflow-hidden border border-border">
            <button type="button" id="anchor-ref" @click="anchorOpen = ! anchorOpen">Anchor</button>
            <div id="anchor-panel" x-show="anchorOpen" x-ds-anchor="{ open: anchorOpen, reference: $el.previousElementSibling, placement: 'bottom-start' }"
                class="absolute top-full left-0 z-50 mt-2 h-40 w-48 border border-border bg-surface">Panel</div>
        </div>
        <div class="flex justify-end">
            <button type="button" id="anchor-wide-ref" @click="anchorWide = ! anchorWide">Wide</button>
            <div id="anchor-wide" x-show="anchorWide" x-ds-anchor="{ open: anchorWide, reference: $el.previousElementSibling, placement: 'bottom-start' }"
                class="absolute z-50 h-20 w-[30rem] border border-border bg-surface">Wide panel</div>
        </div>
        <div>
            <button type="button" id="anchor-tall-ref" @click="anchorTall = ! anchorTall">Tall</button>
            <div id="anchor-tall" x-show="anchorTall" x-ds-anchor="{ open: anchorTall, reference: $el.previousElementSibling, placement: 'bottom-start' }"
                class="absolute z-50 h-[1400px] w-48 border border-border bg-surface">Tall panel</div>
        </div>
        <div id="anchor-transformed" class="ml-24 mt-4 h-10 w-64 [transform:translateZ(0)]">
            <button type="button" id="anchor-boxed-ref" @click="anchorBoxed = ! anchorBoxed">Boxed</button>
            <div id="anchor-boxed" x-show="anchorBoxed" x-ds-anchor="{ open: anchorBoxed, reference: $el.previousElementSibling, placement: 'right-start', offset: 4 }"
                class="absolute z-50 h-24 w-40 border border-border bg-surface">Boxed panel</div>
        </div>
    </div>

    {{-- ── popover (dsPopover, x-ds-anchor) ──
         One that scopes its own state, one the host owns and closes from a
         button inside it, a nested pair with a combobox in the outer one, one
         that is not dismissible and holds nothing focusable, and one with no
         name of its own at the right edge of a clipping box, for the edge
         check at the end of the run. --}}
    <div class="mt-6 flex items-center gap-4" x-data="{ shareOpen: false }">
        <x-ds::popover label="Filters" id="pop-filters">
            <x-slot:trigger>
                <x-ds::button id="pop-trigger" variant="secondary" size="sm">Filters</x-ds::button>
            </x-slot:trigger>
            <x-ds::checkbox name="pop-failed" id="pop-first" label="Only failed runs" />
            <x-ds::button id="pop-last" size="sm" class="mt-3">Apply</x-ds::button>
        </x-ds::popover>
        <button type="button" id="pop-after">After</button>

        <x-ds::popover labelledby="pop-share-title" open="shareOpen" id="pop-share">
            <x-slot:trigger>
                <x-ds::button id="pop-share-trigger" size="sm">Share</x-ds::button>
            </x-slot:trigger>
            <h3 id="pop-share-title">Share this report</h3>
            <x-ds::button id="pop-share-done" size="sm" @click="shareOpen = false">Done</x-ds::button>
        </x-ds::popover>
        <output id="pop-share-mirror" x-text="String(shareOpen)">false</output>
        <button type="button" id="pop-share-host" @click="shareOpen = true">host opens</button>
    </div>

    <div class="mt-6">
        <x-ds::popover label="Outer" id="pop-outer" width="lg">
            <x-slot:trigger>
                <x-ds::button id="pop-outer-trigger" size="sm">Outer</x-ds::button>
            </x-slot:trigger>
            <x-ds::popover label="Inner" id="pop-inner" placement="right-start">
                <x-slot:trigger>
                    <x-ds::button id="pop-inner-trigger" variant="secondary" size="sm">Inner</x-ds::button>
                </x-slot:trigger>
                <button type="button" id="pop-inner-button">Inside inner</button>
            </x-ds::popover>
            <x-ds::combobox name="pop-tags" id="pop-tags" label="Tags" class="mt-3"
                :options="['a11y' => 'Accessibility', 'billing' => 'Billing']" :multiple="true" />
        </x-ds::popover>
    </div>

    <div class="mt-6">
        <x-ds::popover label="Pinned" id="pop-pinned" :dismissible="false" width="sm">
            <x-slot:trigger>
                <button type="button" id="pop-pinned-trigger">Pinned</button>
            </x-slot:trigger>
            Nothing focusable in here.
        </x-ds::popover>
    </div>

    <div class="mt-6 flex h-10 justify-end overflow-hidden border border-border" id="pop-edge-box">
        <x-ds::popover id="pop-edge" width="lg">
            <x-slot:trigger>
                <x-ds::button id="pop-edge-trigger" size="sm">Edge</x-ds::button>
            </x-slot:trigger>
            <p class="h-40">Named by its trigger, since it has no name of its own.</p>
        </x-ds::popover>
    </div>

    {{-- ── sortable table: plain links, no script ──
         The hrefs are fragments, so a press stays on the page and the test
         reads where it went. --}}
    <div class="mt-6 max-w-lg">
        <x-ds::table id="sort-table" sort="total" direction="desc" sortUrl="#sort-{key}-{direction}" :columns="[
            ['label' => 'Report', 'key' => 'name', 'sortable' => true],
            ['label' => 'Total', 'key' => 'total', 'align' => 'right', 'sortable' => true],
        ]">
            <x-ds::table-row>
                <x-ds::table-cell>Q3 revenue</x-ds::table-cell>
                <x-ds::table-cell align="right">£1,200</x-ds::table-cell>
            </x-ds::table-row>
        </x-ds::table>
    </div>

    {{-- ── sub-nav (inline Alpine) ──
         A rail the test can collapse: one section holding the current page,
         one that starts closed. --}}
    <nav id="sub-rail" aria-label="Sub-nav specimen" class="mt-6 w-56" x-data="{ subRailCollapsed: false }">
        <x-ds::sub-nav id="sub-reports" label="Reports" collapsedWhen="subRailCollapsed">
            <x-slot:icon><x-ds::icon name="chart-bar" /></x-slot:icon>
            <x-ds::nav-item href="#sub-revenue" label="Revenue" collapsedWhen="subRailCollapsed" />
            <x-ds::nav-item href="#sub-churn" label="Churn" :active="true" collapsedWhen="subRailCollapsed" />
        </x-ds::sub-nav>
        <x-ds::sub-nav id="sub-settings" label="Settings" collapsedWhen="subRailCollapsed">
            <x-slot:icon><x-ds::icon name="cog" /></x-slot:icon>
            <x-ds::nav-item href="#sub-profile" label="Profile" collapsedWhen="subRailCollapsed" />
            <x-ds::nav-item href="#sub-billing" label="Billing" collapsedWhen="subRailCollapsed" />
        </x-ds::sub-nav>
        <button type="button" id="sub-rail-toggle" @click="subRailCollapsed = ! subRailCollapsed">Collapse the rail</button>
    </nav>

    {{-- ── hover card (dsHoverCard, x-ds-anchor) ──
         Inside running text, as a name in a sentence, with a link in the
         card for Tab to reach; and one at the right edge of a clipping box,
         for the edge check at the end of the run. --}}
    <div class="mt-6">
        <button type="button" id="hc-before">before</button>
        <p id="hc-prose" class="mt-2">
            Assigned to
            <x-ds::hover-card id="hc-card">
                <x-slot:trigger><a href="#ada" id="hc-trigger">Ada Lovelace</a></x-slot:trigger>
                <span class="block font-medium text-fg">Ada Lovelace</span>
                <a href="#ada-profile" id="hc-link" class="text-meta">View profile</a>
            </x-ds::hover-card>
            since Monday. <a href="#after" id="hc-after">After</a>
        </p>
        <button type="button" id="hc-away" class="mt-16">elsewhere</button>
    </div>

    <div class="mt-6 flex h-10 justify-end overflow-hidden border border-border">
        <x-ds::hover-card id="hc-edge" width="lg">
            <x-slot:trigger><a href="#edge" id="hc-edge-trigger">Edge person</a></x-slot:trigger>
            <span class="block h-40">A tall card.</span>
        </x-ds::hover-card>
    </div>

    {{-- ── the overlays that predate x-ds-anchor, now on it ──
         Each at the right edge of a box that clips with overflow hidden, for
         the edge check at the end of the run, where each is opened at the
         bottom right of the window. Their placements prefer the side that
         has no room there, so each has to flip to stay on screen. --}}
    <div class="mt-6 flex h-10 justify-end overflow-hidden border border-border">
        <x-ds::dropdown id="dd-edge" placement="bottom-start">
            <x-slot:trigger>
                <x-ds::button id="dd-edge-trigger" size="sm">Edge menu</x-ds::button>
            </x-slot:trigger>
            <x-ds::dropdown-item href="#edge-edit">Edit</x-ds::dropdown-item>
            <x-ds::dropdown-item href="#edge-dup">Duplicate with a longer name</x-ds::dropdown-item>
            <x-ds::dropdown-item href="#edge-move">Move</x-ds::dropdown-item>
        </x-ds::dropdown>
    </div>
    <div class="mt-6 flex h-10 justify-end overflow-hidden border border-border">
        <div class="w-64">
            <x-ds::select name="sel-edge" id="sel-edge" aria-label="Edge plan"
                :options="['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team', 'scale' => 'Scale', 'custom' => 'Custom']" />
        </div>
    </div>
    <div class="mt-6 flex h-10 justify-end overflow-hidden border border-border">
        <div class="w-64">
            <x-ds::combobox name="cb-edge" id="cb-edge" aria-label="Edge tags"
                :options="['a11y' => 'Accessibility', 'billing' => 'Billing', 'compliance' => 'Compliance', 'design' => 'Design', 'infra' => 'Infrastructure']" />
        </div>
    </div>

    <div class="mt-6 flex h-10 justify-end overflow-hidden border border-border">
        <div class="w-64">
            {{-- A month no other picker on the page shows, so the date picker group's
                 day selectors stay unique. --}}
            <x-ds::date-picker name="dp-edge" id="dp-edge" aria-label="Edge date" value="2027-03-10" />
        </div>
    </div>

    <div class="mt-6 flex h-10 items-center justify-end overflow-hidden border border-border">
        <x-ds::tooltip text="A tip that prefers to sit below its trigger" placement="bottom">
            <x-ds::button id="tt-edge-trigger" variant="secondary" size="sm">Edge tip</x-ds::button>
        </x-ds::tooltip>
    </div>

    {{-- A combobox in a modal whose body scrolls: the body is overflow-y
         auto, which clipped the list before the anchor. The spacer makes the
         body taller than the window, so it really scrolls. --}}
    <div class="mt-6" x-data="{ cbModal: false }">
        <button type="button" id="cb-modal-open" @click="cbModal = true">Open the scrolling modal</button>
        <x-ds::modal open="cbModal" title="Tag the report" size="sm">
            <div id="cb-modal-spacer" class="h-[900px]">Long content above the field.</div>
            <x-ds::combobox name="cb-modal" id="cb-modal" label="Tags"
                :options="['a11y' => 'Accessibility', 'billing' => 'Billing', 'compliance' => 'Compliance', 'design' => 'Design']" />
            <div class="h-[400px]">Long content below the field.</div>
            <x-slot:footer>
                <x-ds::button id="cb-modal-close" size="sm" @click="cbModal = false">Close</x-ds::button>
            </x-slot:footer>
        </x-ds::modal>
    </div>

    {{-- ── context menu (dsContextMenu, x-ds-anchor at a point) ──
         A region with a link, a field and a focusable row inside it, and a
         menu whose second item is disabled. `cmChosen` records what was
         chosen and on which row, through dsTarget. The second region is full
         width, for the edges, and has `native` off. --}}
    <div class="mt-6" x-data="{ cmChosen: '' }">
        <x-ds::context-menu id="cm" label="Report actions" class="rounded-control border border-border p-4">
            <p id="cm-text">Right-click anywhere in here.</p>
            <a href="#cm-link" id="cm-link">A link inside</a>
            <input id="cm-input" aria-label="A field inside" />
            <div id="cm-row" data-row="q3" tabindex="0">Q3 revenue</div>
            <x-slot:menu>
                <x-ds::dropdown-item id="cm-edit" as="button" @click="cmChosen = 'edit ' + (dsTarget?.closest('[data-row]')?.dataset.row ?? '')">Edit</x-ds::dropdown-item>
                <x-ds::dropdown-item id="cm-archive" :disabled="true">Archive</x-ds::dropdown-item>
                <x-ds::dropdown-item id="cm-delete" tone="danger" as="button" @click="cmChosen = 'delete'">Delete</x-ds::dropdown-item>
            </x-slot:menu>
        </x-ds::context-menu>
        <span id="cm-chosen" x-text="cmChosen"></span>
        <button type="button" id="cm-before">Before the region</button>
    </div>

    <div class="mt-6">
        <x-ds::context-menu id="cm-wide" :native="false" class="h-40 border border-border">
            <a href="#cm-wide-link" id="cm-wide-link">A link, native off</a>
            <x-slot:menu>
                <x-ds::dropdown-item as="button">One</x-ds::dropdown-item>
                <x-ds::dropdown-item as="button">Two</x-ds::dropdown-item>
                <x-ds::dropdown-item as="button">Three</x-ds::dropdown-item>
            </x-slot:menu>
        </x-ds::context-menu>
    </div>

    {{-- ── dsClipboard, from the shared script ──
         No component uses it yet, so this is the markup a component would
         emit, through the same helper. --}}
    @php
        $clipboard = \Shipbytes\BladeUi\Support\Behaviour::data('dsClipboard', ['text' => 'sk_live_42']);
    @endphp
    <div class="mt-6" id="clip" x-data="{{ $clipboard }}">
        <button type="button" id="clip-copy" @click="copy()">Copy key</button>
        <button type="button" id="clip-other" @click="copy('other value')">Copy other</button>
        <span id="clip-state" x-text="copied ? 'copied' : 'idle'">idle</span>
    </div>

    {{-- ── copy button, on dsClipboard ──
         A value, an element's text, a field's current value, and a target that
         is not on the page, which must fail rather than copy nothing. The
         window listener is the toast a host would hang off copy-failed. --}}
    <div class="mt-6 flex flex-wrap items-center gap-3" x-on:copy-failed.window="window.copyFailedWindow = (window.copyFailedWindow ?? 0) + 1">
        <code id="key-text">
            sk_live_77
        </code>
        <x-ds::copy-button id="copy-value" value="sk_live_42" />
        <x-ds::copy-button id="copy-text" target="key-text" label="Copy key" />
        <input id="key-field" value="as rendered" />
        <x-ds::copy-button id="copy-field" target="#key-field" :iconOnly="true" label="Copy field" variant="ghost" />
        <x-ds::copy-button id="copy-missing" target="#not-on-the-page"
            @copy-failed="window.copyFailedHere = (window.copyFailedHere ?? 0) + 1" />
    </div>

    {{-- ── progress, driven by the host ──
         The example in docs/components/progress.md, verbatim. The component
         holds no state: the host binds --ds-progress and aria-valuenow on the
         tag and words the value through the slot. Server-rendered at 10 so the
         first paint is right before Alpine boots. --}}
    <div class="mt-6 max-w-sm" x-data="{ done: 10 }">
        <x-ds::progress
            id="live-progress"
            label="Uploading"
            :value="10"
            ::style="{ '--ds-progress': done }"
            ::aria-valuenow="done"
        ><span x-text="done + '%'">10%</span></x-ds::progress>
        <button type="button" id="progress-advance" @click="done = 75">Advance</button>
    </div>

    <div class="mt-6 max-w-sm">
        <x-ds::progress id="busy-progress" label="Indexing" />
    </div>
    {{-- ── announcement bar (dsDismiss) ──
         One remembered under a key, one dismissed from its action slot, one
         that cannot be dismissed, and the urgent outage. The remembered one's
         pre-paint check has host pages of its own, which reload. --}}
    <div class="mt-6" id="ab-box">
        <x-ds::announcement-bar id="ab" tone="warning" dismissKey="behaviour-ab" href="#status"
            @dismiss="window.abDismissed = (window.abDismissed ?? 0) + 1">Maintenance on Saturday.</x-ds::announcement-bar>
        <x-ds::announcement-bar id="ab-action" dismissible>New: exports.
            <x-slot:action><button type="button" id="ab-got" data-ds-dismiss>Got it</button></x-slot:action>
        </x-ds::announcement-bar>
        <x-ds::announcement-bar id="ab-plain" tone="accent">Not dismissible.</x-ds::announcement-bar>
        <x-ds::announcement-bar id="ab-urgent" tone="danger" :urgent="true">Payments are failing.</x-ds::announcement-bar>
        <button type="button" id="ab-after">After the bars</button>
    </div>

    {{-- ── confirm button (dsConfirm) ──
         In a form of its own, so what it submits, and who submitted it, can
         be read. The first sits end-aligned in a row, for the width check;
         the second is driven by events and a host @click, with a short
         timeout and the busy state; the third never times out. --}}
    <form id="cb-form" class="mt-6" action="#cb-sent"
        onsubmit="event.preventDefault(); window.cbSent = (window.cbSent ?? []); window.cbSent.push(event.submitter ? event.submitter.name + '=' + event.submitter.value : '?')">
        <div class="flex w-[40rem] items-center justify-end gap-2 border border-border p-2" id="cb-row">
            <button type="button" id="cb-before">Before</button>
            <x-ds::confirm-button id="cb" name="intent" value="delete" question="Delete this report?"
                @confirm="window.cbConfirmed = (window.cbConfirmed ?? 0) + 1"
                @cancel="window.cbCancelled = (window.cbCancelled ?? 0) + 1" />
        </div>
        <div class="mt-3 flex items-center gap-2" x-data="{ outerEscapes: 0 }" @keydown.escape="outerEscapes++">
            <x-ds::confirm-button id="cb-busy" type="button" :loading="true" :timeout="1" variant="ghost" size="sm" label="Remove"
                @click="window.cbClicks = (window.cbClicks ?? 0) + 1"
                @cancel="window.cbBusyCancelled = (window.cbBusyCancelled ?? 0) + 1" />
            <output id="cb-outer-escapes" x-text="outerEscapes">0</output>
            <x-ds::confirm-button id="cb-still" type="button" :timeout="0" variant="secondary" size="sm" label="Archive" />
        </div>
    </form>
    <button type="button" id="cb-away" class="mt-3">Away from the confirm buttons</button>

    {{-- ── hold button (dsHold) ──
         One that submits its form with its own name and value, a short one
         driven by events and a host @click, and a field that must be filled
         before the third's form will go. --}}
    <form id="hb-form" class="mt-6 flex items-center gap-3" action="#hb-sent"
        onsubmit="event.preventDefault(); window.hbSent = (window.hbSent ?? []); window.hbSent.push(event.submitter ? event.submitter.name + '=' + event.submitter.value : '?')">
        <x-ds::hold-button id="hb" label="Hold to delete" type="submit" name="intent" value="delete"
            @confirm="window.hbConfirmed = (window.hbConfirmed ?? 0) + 1" />
        <x-ds::hold-button id="hb-quick" label="Hold to archive" tone="neutral" :duration="600" size="sm"
            @click="window.hbClicks = (window.hbClicks ?? 0) + 1"
            @confirm="window.hbQuickConfirmed = (window.hbQuickConfirmed ?? 0) + 1" />
        <button type="button" id="hb-away">Away</button>
    </form>
    <form id="hb-required-form" class="mt-3 flex items-center gap-3" onsubmit="event.preventDefault(); window.hbRequiredSent = true">
        <input id="hb-required" name="reason" required>
        <x-ds::hold-button id="hb-invalid" label="Hold to send" type="submit" :duration="400"
            @confirm="window.hbInvalidConfirmed = true" />
    </form>

    {{-- ── confirm popover (dsConfirmDialog over dsPopover) ──
         Plain, type-to-confirm in a form, relaxed type-to-confirm, busy, and
         one at the right edge of a clipping box for the edge check. --}}
    <form id="cp-form" class="mt-6 flex items-center gap-3" action="#cp-sent"
        onsubmit="event.preventDefault(); window.cpSent = (window.cpSent ?? []); window.cpSent.push(event.submitter ? event.submitter.name + '=' + event.submitter.value : '?')">
        <button type="submit" id="cp-form-save" name="save" value="1" class="sr-only">Save</button>
        <x-ds::confirm-popover id="cp" title="Delete this report?" description="Its 12 runs go with it." name="intent" value="delete"
            @confirm="window.cpConfirmed = (window.cpConfirmed ?? 0) + 1"
            @cancel="window.cpCancelled = (window.cpCancelled ?? 0) + 1">
            <x-slot:trigger><x-ds::button id="cp-trigger" variant="danger" size="sm">Delete</x-ds::button></x-slot:trigger>
        </x-ds::confirm-popover>
        <x-ds::confirm-popover id="cp-typed" title="Delete project?" description="This cannot be undone." confirmText="acme-prod"
            confirmLabel="Delete project" name="intent" value="delete-project">
            <x-slot:trigger><x-ds::button id="cp-typed-trigger" variant="secondary" tone="danger" size="sm">Delete project</x-ds::button></x-slot:trigger>
        </x-ds::confirm-popover>
        <x-ds::confirm-popover id="cp-loose" title="Leave the team?" confirmText="Leave" :strict="false" type="button" tone="neutral" confirmLabel="Leave"
            @confirm="window.cpLooseConfirmed = (window.cpLooseConfirmed ?? 0) + 1">
            <x-slot:trigger><x-ds::button id="cp-loose-trigger" size="sm">Leave</x-ds::button></x-slot:trigger>
        </x-ds::confirm-popover>
        <x-ds::confirm-popover id="cp-busy" title="Rotate the key?" type="button" :loading="true" confirmLabel="Rotate"
            @click="window.cpBusyClicks = (window.cpBusyClicks ?? 0) + 1">
            <x-slot:trigger><x-ds::button id="cp-busy-trigger" size="sm">Rotate</x-ds::button></x-slot:trigger>
        </x-ds::confirm-popover>
        <button type="button" id="cp-away">Away</button>
    </form>
    <div class="mt-6 flex h-10 justify-end overflow-hidden border border-border" id="cp-edge-box">
        <x-ds::confirm-popover title="Delete?" description="At the edge of a clipping box." type="button">
            <x-slot:trigger><x-ds::button id="cp-edge-trigger" size="sm">Edge confirm</x-ds::button></x-slot:trigger>
        </x-ds::confirm-popover>
    </div>

    {{-- ── counter (x-ds-roll from the server, dsRoll from Alpine state) ──
         A server counter, and one bound to Alpine state with a format and
         buttons that change it. animateIn and the morphs have host pages
         of their own, in test-behaviour.mjs. --}}
    <div class="mt-6 flex items-center gap-4">
        <x-ds::counter id="ct-server" :value="998" />
        <div x-data="{ count: 40 }" class="flex items-center gap-3">
            <x-ds::counter id="ct-bound" ::value="count" :value="40" :format="['prefix' => '$', 'decimals' => 2]" size="lg" />
            <button type="button" id="ct-add" x-on:click="count += 1234.5">Add</button>
            <button type="button" id="ct-negative" x-on:click="count = -5">Negative</button>
        </div>
    </div>

    {{-- ── activity heatmap: a year in a box too narrow for it ── --}}
    <div class="mt-6 w-64" id="hm-narrow">
        <x-ds::activity-heatmap label="Commits" to="2026-03-08" :data="['2026-03-03' => 12, '2026-03-04' => 1, '2025-03-10' => 2]" unit="commit" href="/day/{date}" />
    </div>

    {{-- ── charts (x-ds-chart): grouped bars, a line, a donut, one bar
         chart with interactive off, batch 13's four and batch 14's three. The block is also cut out for the host
         pages without the script, by its id and the closing comment. ── --}}
    <div class="mt-6 w-[640px]" id="charts">
        <button type="button" id="ch-before">before the charts</button>
        <x-ds::bar-chart id="ch-bar" label="Revenue and costs" :format="['prefix' => '$', 'suffix' => 'K']"
            :data="['Jan' => ['Revenue' => 12, 'Costs' => 8], 'Feb' => ['Revenue' => 18, 'Costs' => 10], 'Mar' => ['Revenue' => 15, 'Costs' => 11]]" />
        <x-ds::bar-chart id="ch-stack" label="Tickets" :stacked="true" :data="['W1' => [5, 3], 'W2' => [7, 2]]" :series="['Open', 'Closed']" />
        <x-ds::line-chart id="ch-line" label="Visits" :data="['Mon' => [3, 1], 'Tue' => [4, 2], 'Wed' => [6, 3], 'Thu' => [5, 4]]" :series="['New', 'Returning']" />
        <x-ds::donut-chart id="ch-donut" label="Traffic" :data="['Direct' => 1200, 'Search' => 800, 'Social' => 400]" />
        <x-ds::bar-chart id="ch-static" label="Static" :interactive="false" :data="['a' => 1, 'b' => 2]" />
        {{-- batch 13: the four that joined x-ds-chart by its hooks alone. --}}
        <x-ds::split-bar id="ch-split" label="Storage" :total="500" :format="['suffix' => ' GB']" :data="['Photos' => 150, 'Video' => 96, 'Mail' => 0]" />
        <x-ds::waffle-chart id="ch-waffle" label="Seats" :data="['Free' => 62, 'Pro' => 30, 'Team' => 8]" />
        <x-ds::activity-rings id="ch-rings" label="Today" :rings="[['label' => 'Move', 'value' => 420, 'max' => 600], ['label' => 'Stand', 'value' => 14, 'max' => 12]]" />
        <x-ds::slope-chart id="ch-slope" label="Price" fromLabel="2024" toLabel="2025" :data="['Oslo' => [120, 180], 'Rome' => [150, 140], 'Lima' => [90, 90]]" />
        {{-- batch 14: a funnel, a two-item radar and a grouped treemap whose
             smallest tile is too small to label. --}}
        <x-ds::funnel-chart id="ch-funnel" label="Signups" :data="['Visits' => 1000, 'Trial' => 400, 'Paid' => 100]" />
        <x-ds::radar-chart id="ch-radar" label="Scores" :max="10" :axes="['Speed', 'Price', 'Support']" :data="['Acme' => [8, 6, 9], 'Globex' => [5, 9, 6]]" />
        <x-ds::treemap id="ch-treemap" label="Spend" :format="['prefix' => '$']" :data="[
            ['label' => 'Engineering', 'children' => ['Salaries' => 420, 'Cloud' => 120, 'Pens' => 2]],
            ['label' => 'Office', 'value' => 60],
        ]" />
    </div>
    <!-- /charts -->
    {{-- Outside #charts, which a no-script host Tabs straight past: tiles
         that are links stay links, each its own Tab stop. Also cut out for
         those hosts, by its id and the closing comment. --}}
    <div class="mt-6 w-[640px]" id="ch-links">
        <button type="button" id="ch-links-before">before the linked treemap</button>
        <x-ds::treemap id="ch-treemap-links" label="Pages" :data="[
            ['label' => 'Home', 'value' => 500, 'href' => '#home'],
            ['label' => 'Pricing', 'value' => 300, 'href' => '#pricing'],
            ['label' => 'Blog', 'value' => 200],
        ]" />
        <button type="button" id="ch-links-after">after the linked treemap</button>
    </div>
    <!-- /ch-links -->
    <div class="mt-6" dir="rtl" id="ch-waffle-rtl">
        <x-ds::waffle-chart label="Share" :data="['A' => 23, 'B' => 41]" :total="100" :interactive="false" legend="none" />
    </div>

    {{-- ── batch 10: tree (dsTree), filter bar, clearable input, code block,
         expandable text (dsClamp) and the collapsible panel. The block marked
         #lists is also cut out for a host page with no Alpine at all, by its
         id and the closing comment. ── --}}
    <div class="mt-6 w-[640px]" id="lists">
        <button type="button" id="lists-before">before the lists</button>

        <div class="mt-4 w-72">
            <x-ds::tree label="Files" id="tr-files"
                @expand="(window.treeEvents ??= []).push('expand:' + $event.detail.value)"
                @collapse="(window.treeEvents ??= []).push('collapse:' + $event.detail.value)">
                <x-ds::tree-item label="app" value="app" icon="folder">
                    <x-ds::tree-item label="Http" value="app/Http" icon="folder">
                        <x-ds::tree-item label="Kernel.php" value="app/Http/Kernel.php" icon="document" />
                    </x-ds::tree-item>
                    <x-ds::tree-item label="Models" value="app/Models" icon="folder" :expandable="true" />
                    <x-ds::tree-item label="helpers.php" value="app/helpers.php" icon="document" />
                </x-ds::tree-item>
                <x-ds::tree-item label="bootstrap" value="bootstrap" icon="folder">
                    <x-ds::tree-item label="app.php" value="bootstrap/app.php" icon="document" />
                </x-ds::tree-item>
                <x-ds::tree-item label="config" value="config" icon="folder">
                    <x-ds::tree-item label="cache.php" value="config/cache.php" icon="document" />
                </x-ds::tree-item>
                <x-ds::tree-item label="composer.json" value="composer.json" icon="document" :disabled="true" />
                <x-ds::tree-item label="routes" value="routes" icon="folder" :open="true">
                    <x-ds::tree-item label="web.php" value="routes/web.php" icon="document" />
                </x-ds::tree-item>
            </x-ds::tree>
        </div>

        <div class="mt-4 w-72">
            <x-ds::tree label="Documentation" id="tr-nav">
                <x-ds::tree-item label="Guides" href="#guides">
                    <x-ds::tree-item label="Install" href="#install" :selected="true" />
                    <x-ds::tree-item label="Upgrade" href="#upgrade" />
                </x-ds::tree-item>
                <x-ds::tree-item label="Reference" href="#reference">
                    <x-ds::tree-item label="Props" href="#props" />
                </x-ds::tree-item>
                <x-ds::tree-item label="API" href="#api" />
            </x-ds::tree>
        </div>
        <button type="button" id="lists-after-nav">after the nav tree</button>
    </div>
    <!-- /lists -->

    <form id="tree-form" class="mt-6 flex w-[640px] gap-6" x-data="{ bound: ['reports.export'] }" onsubmit="event.preventDefault()">
        <div class="w-60">
            <x-ds::tree label="Folder" selectable="single" name="folder" id="tr-single">
                <x-ds::tree-item label="Inbox" value="inbox">
                    <x-ds::tree-item label="Receipts" value="receipts" :selected="true" />
                    <x-ds::tree-item label="Travel" value="travel" />
                </x-ds::tree-item>
                <x-ds::tree-item label="Archive" value="archive" :disabled="true" />
                <x-ds::tree-item label="Sent" value="sent" />
            </x-ds::tree>
        </div>
        <div class="w-60">
            <x-ds::tree label="Permissions" selectable="multiple" name="perms" id="tr-multi">
                <x-ds::tree-item label="Billing" value="billing" :open="true">
                    <x-ds::tree-item label="View invoices" value="billing.view" :selected="true" />
                    <x-ds::tree-item label="Refund" value="billing.refund" />
                    <x-ds::tree-item label="Void" value="billing.void" :disabled="true" />
                </x-ds::tree-item>
                <x-ds::tree-item label="Reports" value="reports" :selected="true" :open="true">
                    <x-ds::tree-item label="Export" value="reports.export" />
                    <x-ds::tree-item label="Schedule" value="reports.schedule" />
                </x-ds::tree-item>
            </x-ds::tree>
        </div>
        <div class="w-60">
            <x-ds::tree label="Bound" selectable="multiple" name="bound" id="tr-bound" x-model="bound">
                <x-ds::tree-item label="Reports" value="reports" :open="true">
                    <x-ds::tree-item label="Export" value="reports.export" />
                    <x-ds::tree-item label="Schedule" value="reports.schedule" :selected="true" />
                </x-ds::tree-item>
            </x-ds::tree>
            <output id="tr-bound-out" x-text="JSON.stringify(bound)"></output>
            <button type="button" id="tr-bound-set" x-on:click="bound = ['reports.export', 'reports.schedule']">Set both</button>
        </div>
    </form>

    {{-- ── filter bar: collapsible, a clearable search, chips that dispatch
         and link, a reset that dispatches ── --}}
    <form id="fb-form" class="mt-6 w-[900px]" onsubmit="event.preventDefault()">
        <x-ds::filter-bar id="fbar" :active="2" :reset="true" :collapsible="true"
            @reset="window.fbReset = (window.fbReset ?? 0) + 1">
            <x-slot:search>
                <x-ds::input type="search" name="q" id="fb-q" label="Search" icon="magnifying-glass" :clearable="true" value="acme"
                    @input="window.fbInput = (window.fbInput ?? 0) + 1"
                    @change="window.fbChange = (window.fbChange ?? 0) + 1" />
            </x-slot:search>
            <x-ds::select name="fbstatus" id="fb-status" label="Status" :options="['open' => 'Open', 'paid' => 'Paid']" />
            <x-ds::chip-group name="fbsize" label="Size" :hideLabel="true" size="sm">
                <x-ds::chip value="s">Small</x-ds::chip>
                <x-ds::chip value="l">Large</x-ds::chip>
            </x-ds::chip-group>
            <x-slot:applied>
                <x-ds::filter-chip id="fb-chip" label="Status" value="Open" @remove="window.fbRemoved = $event.detail" />
                <x-ds::filter-chip label="Owner" value="Ada" removeHref="#owner-removed" />
            </x-slot:applied>
        </x-ds::filter-bar>
        <button type="button" id="fb-after">after the bar</button>
    </form>

    {{-- ── code block: copy, line numbers, a highlight, Show more; and one
         that fits under its limit ── --}}
    <div class="mt-6 w-[480px]" id="code">
        <x-ds::code-block id="cb" language="PHP" filename="Invoice.php" :lineNumbers="true" highlight="2" :maxHeight="3" :expandable="true"
            :code="implode(PHP_EOL, ['<?php', '', 'final class Invoice', '{', '    public function total(): int', '    {', '        return 42;', '    }', '}'])" />
        <x-ds::code-block id="cb-short" :maxHeight="5" :expandable="true" :copy="false" :code="'one' . PHP_EOL . 'two'" />
    </div>

    {{-- ── expandable text (dsClamp): long, short, and one whose box widens ── --}}
    <div class="mt-6 w-64" id="et-box">
        <x-ds::expandable-text id="et" :lines="2">
            Every invoice is checked against its order before it is sent, and anything that does not match is held back for a person to look at, with the reason written beside it so nobody has to guess.
        </x-ds::expandable-text>
        <x-ds::expandable-text id="et-short" :lines="3">Short enough.</x-ds::expandable-text>
    </div>

    {{-- ── collapsible panel ── --}}
    <div class="mt-6 w-96">
        <x-ds::panel id="pc" title="Billing" name="billing" :collapsible="true"
            @expand="(window.panelEvents ??= []).push('expand:' + $event.detail.name)"
            @collapse="(window.panelEvents ??= []).push('collapse:' + $event.detail.name)">
            <x-ds::panel-row href="#card">Card ending 4242</x-ds::panel-row>
        </x-ds::panel>
        <x-ds::panel id="pc-closed" title="Advanced" :collapsible="true" :open="false" body="plain">
            <a href="#advanced" id="pc-link">An advanced link</a>
        </x-ds::panel>
        <button type="button" id="pc-after">after the panels</button>
    </div>

    {{-- ── batch 11: slider, time picker, inline edit, scroll area. The block
         marked #inputs-c is also cut out for a host page with no Alpine at
         all, by its id and the closing comment. ── --}}
    <div class="mt-6 w-[640px]" id="inputs-c">
        <form id="c-form" onsubmit="event.preventDefault(); window.cSubmits = (window.cSubmits ?? 0) + 1">
            <button type="button" id="c-before">before the fields</button>
            <x-ds::slider id="sl" name="volume" label="Volume" :value="40" showValue="inline" :format="['suffix' => '%']" />
            <x-ds::slider id="slr" name="price" label="Price" :range="true" :value="[20, 80]" :minGap="10"
                lowLabel="Minimum price" highLabel="Maximum price" showValue="tooltip" :format="['prefix' => '$']" />
            <x-ds::time-picker id="tp" name="starts" label="Start" value="09:30" format="12" min="08:00" max="18:00" :step="30" :clearable="true" />
            <x-ds::time-picker id="tp24" name="ends" label="End" />
            <x-ds::inline-edit id="ie" name="title" label="Title" value="Q3 roadmap"
                @save="(window.ieEvents ??= []).push('save:' + $event.detail.value + '|' + $event.detail.previous)"
                @cancel="(window.ieEvents ??= []).push('cancel:' + $event.detail.value)" />
            <x-ds::inline-edit id="ie-blur" name="owner" label="Owner" value="Platform" submit="blur"
                @save="(window.ieEvents ??= []).push('blur-save:' + $event.detail.value)" />
            <x-ds::inline-edit id="ie-btn" name="note" label="Note" value="Draft" submit="buttons" :multiline="true"
                @save="(window.ieEvents ??= []).push('btn-save:' + $event.detail.value)" />
            <x-ds::inline-edit id="ie-err" name="code" label="Code" value="q3 roadmap" error="Use letters, numbers and dashes." />
            <button type="button" id="c-after">after the fields</button>
        </form>
        <x-ds::inline-edit id="ie-form" name="slug" label="Slug" value="q3" :action="'/projects/1'" method="PATCH"
            x-on:submit.prevent="window.ieSubmitted = new FormData($event.target).get('slug') + '|' + new FormData($event.target).get('_method')" />
        <x-ds::scroll-area id="sa" :maxHeight="80" label="Release notes" class="mt-4 w-64 border border-border">
            <div class="p-2">
                @foreach (range(1, 12) as $n)<p>Line {{ $n }} of the notes.</p>@endforeach
            </div>
        </x-ds::scroll-area>
        <x-ds::scroll-area id="sa-links" :maxHeight="48" class="mt-4 w-64">
            @foreach (range(1, 6) as $n)<a class="block" href="#sa-{{ $n }}">Link {{ $n }}</a>@endforeach
        </x-ds::scroll-area>
        <x-ds::scroll-area id="sa-short" :maxHeight="200" label="Short notes" class="mt-4 w-64"><p>Short.</p></x-ds::scroll-area>
    </div>
    <!-- /inputs-c -->

    {{-- ── batch 11, bound to Alpine state with x-model ── --}}
    <div class="mt-6 w-[640px]" id="c-model" x-data="{ vol: 30, range: [10, 90], at: '14:00', name: 'Ada' }">
        <x-ds::slider id="slm" name="m" label="Bound" x-model="vol" />
        <x-ds::slider id="slmr" name="mr" label="Bound range" :range="true" lowLabel="Low" highLabel="High" x-model="range" />
        <x-ds::time-picker id="tpm" name="tm" label="Bound time" x-model="at" />
        <x-ds::inline-edit id="iem" label="Bound name" x-model="name" />
        <output id="c-model-out" x-text="JSON.stringify({ vol, range, at, name })"></output>
    </div>

    {{-- ── batch 12: command palette, sortable list, split. After the modal
         in the page, so the modal's checks still find the modal first. The
         block marked #b12 is also cut out for a host page with no Alpine at
         all, by its id and the closing comment. ── --}}
    <div class="mt-6 w-[640px]" id="b12">
        <button type="button" id="cmd-open" onclick="window.dispatchEvent(new CustomEvent('open-command-palette', { detail: { name: 'main' } }))">Search</button>
        <input id="cmd-field" aria-label="Elsewhere">
        <x-ds::command-palette id="cmd" name="main"
            x-on:search="(window.cmdSearches ??= []).push($event.detail.query)"
            x-on:create-invoice="(window.cmdActions ??= []).push('create:' + $event.detail.value)"
            x-on:archive="(window.cmdActions ??= []).push('archive:' + $event.detail.value)">
            <x-ds::command-group label="Go to" id="cmd-go">
                <x-ds::command-item id="ci-invoices" label="Invoices" href="#invoices" keywords="bills receipts" />
                <x-ds::command-item id="ci-customers" label="Customers" href="#customers" shortcut="G+C" />
                <x-ds::command-item id="ci-settings" label="Settings" href="#settings" :disabled="true" />
            </x-ds::command-group>
            <x-ds::command-group label="Actions" id="cmd-actions">
                <x-ds::command-item id="ci-new" label="New invoice" action="create-invoice" value="draft" />
                <x-ds::command-item id="ci-archive" label="Archive" action="archive" value="42" keywords="hide" />
            </x-ds::command-group>
        </x-ds::command-palette>
        {{-- Searched on the server: no filter, still loading, a bare-key shortcut. --}}
        <x-ds::command-palette id="cmd-remote" name="remote" shortcut="/" :filter="false" :loading="true" emptyText="Nothing found" />

        <form id="so-form" class="mt-4" onsubmit="event.preventDefault()">
            <x-ds::sortable-list id="so" name="steps" label="Steps" x-on:reorder="(window.soEvents ??= []).push($event.detail)">
                <x-ds::sortable-item value="a" label="Alpha">Alpha</x-ds::sortable-item>
                <x-ds::sortable-item value="b" label="Bravo">Bravo</x-ds::sortable-item>
                <x-ds::sortable-item value="c">Charlie <span>third</span></x-ds::sortable-item>
                <x-ds::sortable-item value="d" label="Delta" :disabled="true">Delta</x-ds::sortable-item>
            </x-ds::sortable-list>
            <button type="button" id="so-after">after the list</button>
        </form>

        <div class="mt-4 h-64 w-[600px] border border-border" id="sp-box">
            <x-ds::split id="sp" name="outer" label="Resize the files"
                x-on:resize="(window.spEvents ??= []).push('resize:' + $event.detail.name)"
                x-on:resized="(window.spEvents ??= []).push('resized:' + $event.detail.name + ':' + $event.detail.sizes.join('/'))">
                <x-ds::split-pane id="sp-files" size="30%" min="100" max="60%" :collapsible="true">Files</x-ds::split-pane>
                <x-ds::split-pane id="sp-main">
                    <x-ds::split id="sp-inner" orientation="vertical" name="inner" label="Resize the console">
                        <x-ds::split-pane id="sp-editor">
                            <iframe id="sp-frame" title="Preview" class="block size-full" srcdoc="preview"></iframe>
                        </x-ds::split-pane>
                        <x-ds::split-pane id="sp-console" size="40%" min="40" :collapsible="true">Console</x-ds::split-pane>
                    </x-ds::split>
                </x-ds::split-pane>
            </x-ds::split>
        </div>
        {{-- Stacked below xl, and the page is 1200px wide. --}}
        <div class="mt-4 h-40 w-[600px]">
            <x-ds::split id="sp-stack" label="Resize" stackBelow="xl">
                <x-ds::split-pane id="sp-stack-a" size="50%">One</x-ds::split-pane>
                <x-ds::split-pane id="sp-stack-b">Two</x-ds::split-pane>
            </x-ds::split>
        </div>
    </div>
    <!-- /b12 -->

    {{-- ── batch 15: swap button, expanding group, icon swap, user menu,
         team switcher. The block marked #b15 is also cut out for a host page
         with no Alpine at all, by its id and the closing comment. Each form
         records its submit rather than leaving the page. ── --}}
    @php
        $b15Teams = [
            ['id' => 1, 'name' => 'Acme Inc', 'meta' => 'Pro plan'],
            ['id' => 2, 'name' => 'Globex', 'meta' => 'Team plan'],
            ['id' => 3, 'name' => 'Initech', 'meta' => 'Free'],
        ];
        $b15Many = array_merge($b15Teams, [
            ['id' => 4, 'name' => 'Umbrella Labs', 'meta' => 'Pro plan'],
            ['id' => 5, 'name' => 'Hooli', 'meta' => 'Enterprise'],
            ['id' => 6, 'name' => 'Stark Industries', 'meta' => 'Team plan'],
            ['id' => 7, 'name' => 'Wayne Enterprises', 'meta' => 'Free'],
            ['id' => 8, 'name' => 'Cyberdyne', 'meta' => 'Globally distributed'],
        ]);
        $b15Links = array_map(fn ($t) => $t + ['href' => '#team-'.$t['id']], $b15Teams);
    @endphp
    <div class="mt-6 w-[640px]" id="b15" onsubmit="event.preventDefault(); (window.b15Sent ??= []).push({ form: event.target.dataset.dsUserMenuSignOut !== undefined ? 'sign-out' : event.target.id || 'team', method: event.target.method, action: event.target.getAttribute('action'), data: Object.fromEntries(new FormData(event.target)) })">
        <div class="flex items-center gap-3" x-on:b15-saved.window="window.b15Heard = (window.b15Heard ?? 0) + 1">
            <x-ds::swap-button id="sw-click">Save<x-slot:swapped><x-ds::icon name="check" /> Saved to your library</x-slot:swapped></x-ds::swap-button>
            <x-ds::swap-button id="sw-toggle" :revert="0" variant="primary"><x-ds::icon name="plus" /> Follow<x-slot:swapped><x-ds::icon name="check" /> Following</x-slot:swapped></x-ds::swap-button>
            <x-ds::swap-button id="sw-event" event="b15-saved" :revert="600">Save changes<x-slot:swapped>Saved</x-slot:swapped></x-ds::swap-button>
            <x-ds::swap-button id="sw-server" :swapped="true" :revert="0"><x-ds::icon name="star" /> Star<x-slot:swapped><x-ds::icon name="star" variant="solid" /> Starred</x-slot:swapped></x-ds::swap-button>
        </div>

        <div class="mt-4 flex w-[480px] items-center gap-3">
            <div class="w-64">
                <x-ds::button-group id="exp" label="Text style" expanding>
                    <x-ds::button id="exp-bold" icon="bold" aria-pressed="true">Bold</x-ds::button>
                    <x-ds::button id="exp-italic" icon="italic" aria-pressed="false">Italic</x-ds::button>
                    <x-ds::button id="exp-under" icon="underline" aria-pressed="false">Underline</x-ds::button>
                </x-ds::button-group>
            </div>
            <span id="exp-after">after the group</span>
        </div>

        <div class="mt-4 flex items-center gap-3" x-data="{ menu: false }">
            <x-ds::button id="is-btn" iconOnly aria-label="Menu" x-bind:aria-expanded="menu ? 'true' : 'false'" aria-expanded="false" @click="menu = ! menu"><x-ds::icon-swap id="is-icon" from="bars-3" to="x-mark" preset="rotate" /></x-ds::button>
            <x-ds::button id="is-bars-btn" iconOnly aria-label="Menu" aria-expanded="true"><x-ds::icon-swap id="is-bars" preset="menu" /></x-ds::button>
            <span id="is-alone"><x-ds::icon-swap id="is-active" from="sun" to="moon" :active="true" /></span>
        </div>

        <div class="mt-4 flex justify-end">
            <x-ds::user-menu id="um" name="Ada Lovelace" email="ada@example.com" plan="Pro" signOutAction="/sign-out">
                <div role="group" aria-label="Account">
                    <x-ds::dropdown-item id="um-profile" href="#profile">Profile</x-ds::dropdown-item>
                    <x-ds::dropdown-item id="um-settings" href="#settings">Settings</x-ds::dropdown-item>
                </div>
                <div role="group" aria-label="Help">
                    <x-ds::dropdown-item id="um-docs" href="#docs">Documentation</x-ds::dropdown-item>
                </div>
                <x-slot:theme><x-ds::theme-switcher id="um-theme" variant="menu" storageKey="b15-theme" /></x-slot:theme>
            </x-ds::user-menu>
            <x-ds::user-menu id="um-compact" name="Grace Hopper" :compact="true" />
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4">
            <div x-data="{ team: '2' }">
                <x-ds::team-switcher id="tsw" :teams="$b15Teams" current="2" name="team" x-model="team"
                    x-on:change="(window.b15Changes ??= []).push($event.detail?.id ?? null)">
                    <x-slot:footer><x-ds::button id="tsw-create" variant="ghost" size="sm" icon="plus">Create team</x-ds::button></x-slot:footer>
                </x-ds::team-switcher>
                <output id="tsw-model" x-text="team">2</output>
            </div>
            <x-ds::team-switcher id="tsf" :teams="$b15Many" current="1" name="team" action="/teams/switch" method="PUT" label="Switch workspace" />
            <x-ds::team-switcher id="tsl" :teams="$b15Links" current="3" />
        </div>
    </div>
    <!-- /b15 -->

    {{-- ── batch 16: money input, phone input, expanding search, shortcut
         input. The block marked #b16 is also cut out for a host page with no
         Alpine at all, by its id and the closing comment. The form records
         what it would post rather than leaving the page. ── --}}
    <div class="mt-6 w-[640px]" id="b16">
        <form id="b16-form" onsubmit="event.preventDefault(); window.b16Sent = [...new FormData(event.target)]" onchange="event.target.closest('[data-ds-shortcut-input]') && (window.b16Changes ??= []).push(event.detail ?? null)">
            <div class="grid grid-cols-2 gap-4">
                <x-ds::money-input id="mi" name="price" label="Price" value="1234.5" min="1" max="100000" />
                <x-ds::money-input id="mi-in" name="budget" label="Budget" currency="INR" locale="en-IN" />
                <x-ds::money-input id="mi-de" name="cost" label="Cost" currency="EUR" locale="de" :allowNegative="true" />
                <x-ds::money-input id="mi-cur" name="pay" label="Pay" :currencies="['USD', 'JPY', 'BHD']" currency="USD" value="12.34" step="0.05" />
                <x-ds::money-input id="mi-minor" name="cents" label="Cents" :minor="true" :value="1999" />
                <div x-data="{ tip: '2.5' }">
                    <x-ds::money-input id="mi-model" name="tip" label="Tip" x-model="tip" />
                    <output id="mi-out" x-text="tip">2.5</output>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <x-ds::phone-input id="ph" name="phone" label="Phone" country="GB" countryName="phone_country" :preferred="['GB', 'US']" />
                <x-ds::phone-input id="ph-few" name="few" label="Few" :countries="['GB', 'IE', 'FR']" country="FR" value="+33612345678" />
                <div x-data="{ tel: '+919876543210' }">
                    <x-ds::phone-input id="ph-model" name="tel" label="Bound" x-model="tel" />
                    <output id="ph-out" x-text="tel"></output>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3" role="search">
                {{-- Not "/": the remote palette above takes that one. --}}
                <x-ds::input id="srch" name="q" label="Search" :expanding="true" :clearable="true" shortcut="f" />
                <span id="srch-after">after</span>
            </div>
            <div class="mt-4 flex w-[480px] items-center gap-3">
                <span id="ov-before">A title</span>
                <span class="ml-auto"></span>
                <x-ds::input id="srch-ov" name="q2" label="Search docs" :expanding="true" mode="overlay" />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <x-ds::shortcut-input id="sc" name="sc" label="Open search" value="mod+k" :conflicts="['mod+j' => 'Jump to']" />
                <x-ds::shortcut-input id="sc-bare" name="bare" label="Next" :allowBare="true" />
                <div x-data="{ keys: 'alt+n' }">
                    <x-ds::shortcut-input id="sc-model" name="keys" label="Bound" x-model="keys" />
                    <output id="sc-out" x-text="keys"></output>
                </div>
                <p id="kbd-sc-line">Press <x-ds::kbd id="kbd-sc" shortcut="mod+shift+k" />.</p>
            </div>
            <button id="b16-submit" type="submit">Send</button>
        </form>
    </div>
    <!-- /b16 -->

    {{-- ── batch 17: mention input, colour picker, slider histogram. The
         block marked #b17 is also cut out for a host page with no Alpine at
         all, by its id and the closing comment. The form records what it
         would post rather than leaving the page. ── --}}
    @php
        $b17People = [
            ['id' => 7, 'label' => 'Maya Patel', 'meta' => 'Design', 'initials' => 'MP'],
            ['id' => 8, 'label' => 'Omar Malik', 'meta' => 'Support'],
            ['id' => 9, 'label' => 'Ade Okafor', 'meta' => 'Engineering'],
            ['id' => 10, 'label' => 'Sam Rivera', 'meta' => 'Design'],
        ];
        $b17Issues = [['id' => 101, 'label' => 'Fix login redirect'], ['id' => 102, 'label' => 'Dark mode chart']];
        $b17Lines = "one\ntwo\nthree\nfour\nfive";
        $b17Prices = [12, 25, 33, 41, 48, 52, 55, 58, 61, 64, 66, 70, 72, 75, 80, 84, 90, 95, 120, 140, 160, 180, 220, 260, 310, 390, 450, 480];
    @endphp
    <div class="mt-6 w-[640px]" id="b17">
        <form id="b17-form" onsubmit="event.preventDefault(); window.b17Sent = [...new FormData(event.target)]">
            <div class="grid grid-cols-2 gap-4">
                <x-ds::mention-input id="mn" name="body" label="Comment" :triggers="['@' => $b17People, '#' => $b17Issues]" value="Thanks @Ade Okafor" />
                <x-ds::mention-input id="mn-token" name="post[update]" label="Update" :items="$b17People" insert="@[label](id)" :multiline="false" :atomic="true" />
                <div x-data="{ note: 'hi' }">
                    <x-ds::mention-input id="mn-model" name="note" label="Bound" :items="$b17People" x-model="note" :minChars="1" />
                    <output id="mn-out" x-text="note"></output>
                </div>
                <div x-on:search="(window.b17Search ??= []).push($event.detail)">
                    <x-ds::mention-input id="mn-remote" name="remote" label="Remote" :items="[]" :loading="true" />
                </div>
                {{-- Narrow, so the trigger wraps onto a second line; two rows
                     of a longer text, so the field scrolls. --}}
                <x-ds::mention-input id="mn-wrap" name="wrap" label="Wrapped" :items="$b17People" class="w-48" />
                {{-- In the right-hand column, so a list hung from its right edge has room. --}}
                <div dir="rtl">
                    <x-ds::mention-input id="mn-rtl" name="rtl" label="RTL" :items="[['id' => 1, 'label' => 'Maya']]" value="مرحبا يا صديقي العزيز " />
                </div>
                <x-ds::mention-input id="mn-scroll" name="scroll" label="Scrolled" :items="$b17People" :rows="2" :value="$b17Lines" />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <x-ds::color-picker id="col" name="brand" label="Brand" value="#336699" :alpha="true" contrastWith="#ffffff"
                    :swatches="['#ff0000' => 'Red', '#ffffff', 'teal']" />
                <x-ds::color-picker id="col-rgb" name="accent" label="Accent" value="nonsense" format="rgb" />
                <div x-data="{ tint: '#ff8800' }">
                    <x-ds::color-picker id="col-model" name="tint" label="Bound" x-model="tint" format="hsl" />
                    <output id="col-out" x-text="tint"></output>
                </div>
                <x-ds::color-picker id="col-presets" name="tag" label="Tag" value="teal" :presetsOnly="true" :swatches="['#ff0000' => 'Red', '#ffffff', 'teal']" />
            </div>

            <div class="mt-4 flex flex-col gap-6">
                <x-ds::slider id="hs" name="price" label="Price per night" :range="true" :value="[50, 200]" :max="500" step="10" lowLabel="Minimum price" highLabel="Maximum price"
                    :format="['prefix' => '$']" showValue="inline" :histogram="$b17Prices" :buckets="25" countLabel=":count stay|:count stays" :inputs="true" :minGap="20" />
                <x-ds::slider id="hs-one" name="volume" label="Volume" :value="40" :histogram="[3, 5, 2, 7]" :inputs="true" countLabel=":count track|:count tracks" />
            </div>
            <button id="b17-submit" type="submit">Send</button>
        </form>
    </div>
    <!-- /b17 -->

    {{-- ── batch 18: the calendar, and the date picker on the calendar's
         grid. Every date is in 2028, a year no other specimen shows, and
         every day selector is scoped to its component, because the grid
         carries hidden cells for the neighbouring months too. ── --}}
    <div class="mt-6 w-[960px]" id="b18">
        <form id="b18-form" onsubmit="event.preventDefault(); window.b18Sent = [...new FormData(event.target)]">
            <div class="flex flex-wrap items-start gap-6"
                x-on:month-change="(window.b18Months ??= []).push($event.detail)"
                x-on:change="(window.b18Changes ??= []).push($event.target.name + ':' + $event.constructor.name)">
                <x-ds::calendar id="cal" name="delivery" label="Delivery" value="2028-02-10" today="2028-02-14" min="2028-02-03"
                    :disabledDays="[0]" :disabledDates="['2028-02-22']" :events="['2028-02-03' => 'success', '2028-02-04' => 3]" :showWeekNumbers="true" />
                <x-ds::calendar id="cal-range" name="stay" aria-label="Stay" :range="true" :months="2" :value="['2028-03-06', '2028-03-10']" today="2028-02-14" :jump="false" />
                <x-ds::calendar id="cal-multi" name="off" aria-label="Days off" :multiple="true" :value="['2028-05-01', '2028-05-15']" today="2028-02-14" :jump="false" />
            </div>
            <div class="mt-4 flex gap-6">
                <div class="w-72">
                    <x-ds::date-picker id="dpp" name="period" label="Period" :range="true" :months="2" :presets="true" today="2028-02-29" :value="['2028-02-23', '2028-02-29']" />
                </div>
                <div class="w-72">
                    <x-ds::date-picker id="dpc" name="nights" label="Nights" :range="true" :minDays="3" :maxDays="7" :confirm="true" today="2028-06-01" />
                </div>
            </div>
            <button id="b18-submit" type="submit">Send</button>
        </form>
        <div class="mt-4 flex items-start gap-6" x-data="{ day: '2028-07-04', span: ['2028-08-01', '2028-08-03'] }">
            <x-ds::calendar id="cal-model" aria-label="Bound" x-model="day" today="2028-02-14" :jump="false" />
            <div class="w-72"><x-ds::date-picker id="dpm" name="span" label="Span" :range="true" :confirm="true" today="2028-02-14" x-model="span" /></div>
            <output id="cal-model-out" x-text="JSON.stringify({ day, span })"></output>
        </div>
    </div>
    <!-- /b18 -->

    {{-- ── batch 18: the avatar upload. The photos are drawn in the page by
         the test and set on the picker, as a reader's choice is. ── --}}
    <div class="mt-6 w-[640px]" id="b18a">
        <form id="b18a-form" class="flex flex-col gap-6" onsubmit="event.preventDefault(); window.b18aSent = [...new FormData(event.target)].map(([k, v]) => [k, typeof v === 'string' ? v : `${v.name}|${v.type}|${v.size}`])"
            x-on:crop="(window.b18aCrops ??= []).push({ id: $event.target.querySelector('[data-ds-avatar-input]').id, width: $event.detail.width, height: $event.detail.height, type: $event.detail.blob.type })">
            <x-ds::avatar-upload id="av" name="photo" alt="Ada Lovelace" :maxSize="200000" help="Up to 200 kB." />
            <x-ds::avatar-upload id="av-logo" name="logo" label="Logo" shape="square" alt="Acme Ltd" outputType="image/png" :outputSize="256" size="lg"
                src="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2710%27 height=%2710%27%3E%3Crect width=%2710%27 height=%2710%27 fill=%27%2300f%27/%3E%3C/svg%3E" />
            <button id="b18a-submit" type="submit">Send</button>
        </form>
    </div>

    <div class="mt-6 flex h-10 justify-end overflow-hidden border border-border">
        <div class="w-64">
            <x-ds::date-picker name="dpn-edge" id="dpn-edge" aria-label="Edge period" :range="true" :presets="true" today="2029-01-15" />
        </div>
    </div>

    {{-- ── batch 19: the overlay stack, on the desktop page: a drawer, a
         modal over it, a modal over that, and a modal written INSIDE
         another's body. ── --}}
    <div class="mt-6" id="stack" x-data="{ st: { drawer: false, one: false, two: false, outer: false, inner: false } }">
        <button type="button" id="st-open" @click="st.drawer = true">Open the stack</button>
        <x-ds::drawer open="st.drawer" title="Level one" id="st-drawer">
            <button type="button" id="st-open-one" @click="st.one = true">Open level two</button>
        </x-ds::drawer>
        <x-ds::modal open="st.one" title="Level two" id="st-one">
            <button type="button" id="st-open-two" @click="st.two = true">Open level three</button>
        </x-ds::modal>
        <x-ds::modal open="st.two" title="Level three" size="sm" id="st-two">
            <button type="button" id="st-two-a">A</button>
            <button type="button" id="st-two-b">B</button>
        </x-ds::modal>

        <button type="button" id="st-outer-open" @click="st.outer = true">Open nested</button>
        <x-ds::modal open="st.outer" title="Outer" id="st-outer">
            <button type="button" id="st-inner-open" @click="st.inner = true">Open inner</button>
            <x-ds::modal open="st.inner" title="Inner" size="sm" id="st-inner">
                <button type="button" id="st-inner-a">One</button>
                <button type="button" id="st-inner-b">Two</button>
                <button type="button" id="st-inner-c">Three</button>
            </x-ds::modal>
        </x-ds::modal>
    </div>

    {{-- ── batch 19: the share sheet, posting as a plain form. ── --}}
    <div class="mt-6 max-w-[480px]" id="b19s">
        <form id="b19s-form" onsubmit="event.preventDefault(); window.b19sSent = [...new FormData(event.target, event.submitter)]"
            x-data x-on:access-change="(window.b19sEvents ??= []).push(['access', $event.detail])"
            x-on:role-change="(window.b19sEvents ??= []).push(['role', $event.detail])"
            x-on:remove="(window.b19sEvents ??= []).push(['remove', $event.detail])">
            <x-ds::share-sheet id="ss" url="https://example.com/r/q3" label="Share Q3 report" title="Q3 report"
                access="restricted" :accessOptions="['restricted' => 'Restricted', 'link' => 'Anyone with the link']"
                :roles="['viewer' => 'Viewer', 'editor' => 'Editor']"
                :people="[['id' => 1, 'name' => 'Maya Patel', 'email' => 'maya@example.com', 'role' => 'owner', 'locked' => true], ['id' => 'u-2', 'name' => 'Omar Malik', 'email' => 'omar@example.com', 'role' => 'editor']]" />
        </form>
    </div>
    <!-- /b19s -->

    {{-- ── batch 19: the touch components, driven on a phone-sized page of
         their own (the sheet is lg:hidden). ── --}}
    <div id="b19" x-data="{ sh: { places: false, details: false, peek: true, plain: false }, b19log: [] }">
        <button type="button" id="sh-open" @click="sh.places = true">Places</button>
        <button type="button" id="sh-open-plain" @click="sh.plain = true">Plain</button>
        <x-ds::sheet open="sh.places" title="Places" :snap="['25%', '60%', 'full']" initialSnap="60%" id="sh"
            x-on:snap="b19log.push('snap:' + $event.detail.index + ':' + $event.detail.value)">
            <button type="button" id="sh-open-details" @click="sh.details = true" class="block w-full p-4 text-left">Details</button>
            @for ($i = 1; $i <= 40; $i++)
                <p class="px-5 py-2 text-fg">Place {{ $i }}</p>
            @endfor
            <button type="button" id="sh-last" class="block p-4">Last</button>
        </x-ds::sheet>
        <x-ds::sheet open="sh.details" title="Details" :snap="['50%']" id="sh-details">
            <p class="px-5 text-fg">Opening hours</p>
            <button type="button" id="sh-details-ok" @click="sh.details = false">Done</button>
        </x-ds::sheet>
        <x-ds::sheet open="sh.plain" :draggable="true" id="sh-plain">
            <p class="px-5 py-4 text-fg">A sheet at its natural height.</p>
        </x-ds::sheet>
        <x-ds::sheet open="sh.peek" title="Nearby" :snap="[96, '50%']" :modal="false" :dismissible="false" id="sh-peek">
            <p class="px-5 text-fg">Twelve places nearby</p>
        </x-ds::sheet>

        <ul role="list" id="sw-list" class="divide-y divide-divider" x-on:swipe="b19log.push('swipe:' + $event.detail.side)">
            @foreach (['one', 'two'] as $n)
                <li>
                    <x-ds::swipe-actions id="sw-{{ $n }}" :full="true">
                        <a href="#sw-{{ $n }}-open" id="sw-{{ $n }}-link" class="block px-4 py-3 text-fg">Message {{ $n }}</a>
                        <x-slot:start><x-ds::swipe-action id="sw-{{ $n }}-archive" label="Archive" icon="archive-box" tone="accent" x-on:click="b19log.push('archive:{{ $n }}')" /></x-slot:start>
                        <x-slot:end>
                            <x-ds::swipe-action id="sw-{{ $n }}-flag" label="Flag" icon="flag" x-on:click="b19log.push('flag:{{ $n }}')" />
                            <x-ds::swipe-action id="sw-{{ $n }}-delete" label="Delete" icon="trash" tone="danger" x-on:click="b19log.push('delete:{{ $n }}')" />
                        </x-slot:end>
                    </x-ds::swipe-actions>
                </li>
            @endforeach
        </ul>
        <div dir="rtl">
            <x-ds::swipe-actions id="sw-rtl">
                <span class="block px-4 py-3">رسالة</span>
                <x-slot:start><x-ds::swipe-action id="sw-rtl-archive" label="Archive" /></x-slot:start>
            </x-ds::swipe-actions>
        </div>

        <x-ds::pull-refresh id="pr" label="Inbox" class="h-64" x-on:refresh.prevent="b19log.push('refresh')">
            @for ($i = 1; $i <= 30; $i++)
                <p class="px-4 py-2 text-fg">Item {{ $i }}</p>
            @endfor
        </x-ds::pull-refresh>
        <x-ds::pull-refresh id="pr-link" href="#pr-followed" :timeout="400" label="Feed">
            <p class="px-4 py-2 text-fg">Feed</p>
        </x-ds::pull-refresh>
        <div class="h-[600px]"></div>
    </div>
    <!-- /b19 -->

    {{-- Outside #b17, which a page without Alpine reuses: a mention input in
         a modal, whose Escape must close the list and not the modal. --}}
    <div class="mt-6" x-data="{ mnModal: false }">
        <button type="button" id="mn-modal-open" @click="mnModal = true">Open the modal with a mention input</button>
        <x-ds::modal open="mnModal" title="Reply" size="sm">
            <x-ds::mention-input id="mn-modal" name="reply" label="Reply" :items="$b17People" />
        </x-ds::modal>
    </div>

</div>
