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

        {{-- ── dropdown ── --}}
        <div class="mt-6">
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

        {{-- ── tooltip ── --}}
        <div class="mt-6">
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
    </form>
</div>
