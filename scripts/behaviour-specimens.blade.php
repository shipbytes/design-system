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

        {{-- ── file upload ── --}}
        <div class="mt-6 max-w-sm">
            <x-ds::file-upload name="files[]" id="files" label="Files" :multiple="true" />
        </div>
    </form>
</div>
