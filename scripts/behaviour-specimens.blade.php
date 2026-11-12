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
</div>
