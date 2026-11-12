{{--
    Specimen source for the FOCUS check in scripts/test-behaviour.mjs.

    One of each focusable component, in one column, each in a section named
    for the component. The check presses Tab from the top to the end button and
    needs every stop to draw a ring, so an element that relies on the browser's
    own ring, or hides it, fails here. A component with something focusable
    gets a section; the coverage check fails on a section no Tab reached.

    Overlays are opened by the check from their triggers, with the keyboard.
--}}
<div class="space-y-6 p-8" x-data="{ show: true, modalOpen: false, drawerOpen: false }">
<section data-focus="button"><x-ds::button>Save</x-ds::button> <x-ds::button variant="primary">Go</x-ds::button> <x-ds::button variant="ghost" iconOnly aria-label="Settings"><x-ds::icon name="cog" /></x-ds::button> <x-ds::button href="/x">Link</x-ds::button></section>
<section data-focus="pagination">
@php $paginator = new \Illuminate\Pagination\LengthAwarePaginator(range(1, 15), 237, 15, 4, ['path' => '/reports']); $simple = new \Illuminate\Pagination\Paginator(range(1, 16), 15, 2, ['path' => '/reports']); @endphp
{{ $paginator->links('ds::pagination') }}
{{ $simple->links('ds::simple-pagination') }}
</section>
<section data-focus="alert"><x-ds::alert tone="warning" :dismissible="true">Dismissible.</x-ds::alert></section>
<section data-focus="input" class="max-w-sm"><x-ds::input label="Name" name="name" /><x-ds::input type="password" name="password" label="Password" :reveal="true" /><x-ds::input as="textarea" label="Notes" name="notes" /><x-ds::input as="select" label="Plan" name="plan"><option>Free</option></x-ds::input></section>
<section data-focus="otp-input"><x-ds::otp-input name="code" label="Code" :length="4" /></section>
<section data-focus="tag-input" class="max-w-sm"><x-ds::tag-input name="tags" label="Tags" :value="['billing']" /></section>
<section data-focus="copy-button"><x-ds::copy-button value="x" /></section>
<section data-focus="rating"><x-ds::rating name="score" label="Score" :value="3" /></section>
<section data-focus="chip"><x-ds::chip-group name="status" label="Status"><x-ds::chip value="open" :checked="true">Open</x-ds::chip><x-ds::chip value="closed">Closed</x-ds::chip></x-ds::chip-group><x-ds::chip-group label="Show"><x-ds::chip href="/t" :active="true">All</x-ds::chip></x-ds::chip-group></section>
<section data-focus="radio-card"><x-ds::radio-cards name="plan2" label="Plan" :columns="2"><x-ds::radio-card value="free" title="Free" :checked="true" /><x-ds::radio-card value="pro" title="Pro" /></x-ds::radio-cards></section>
<section data-focus="segment"><x-ds::segmented-control name="view" label="View"><x-ds::segment value="list" :checked="true">List</x-ds::segment><x-ds::segment value="board">Board</x-ds::segment></x-ds::segmented-control><x-ds::segmented-control label="Show"><x-ds::segment href="/t" :active="true">All</x-ds::segment></x-ds::segmented-control></section>
<section data-focus="button-group"><x-ds::button-group label="Alignment"><x-ds::button>Left</x-ds::button><x-ds::button>Right</x-ds::button></x-ds::button-group></section>
<section data-focus="toolbar-button"><x-ds::toolbar label="Formatting"><x-ds::toolbar-button icon="star" label="Star" /><x-ds::toolbar-button label="Share" href="/share" /></x-ds::toolbar></section>
<section data-focus="toggle"><x-ds::toggle-group label="Alignment" name="align"><x-ds::toggle value="left" label="Left" :pressed="true" /><x-ds::toggle value="center" label="Center" /></x-ds::toggle-group></section>
<section data-focus="number-field" class="max-w-sm"><x-ds::number-field name="seats" label="Seats" :value="3" :min="1" :max="10" /></section>
<section data-focus="panel"><x-ds::panel title="Recent" action="View all" actionHref="/all"><x-ds::panel-row href="/r">A row</x-ds::panel-row></x-ds::panel></section>
<section data-focus="stat-tile"><x-ds::stat-tile href="/revenue" label="Revenue" value="12400" /></section>
<section data-focus="table"><x-ds::table sort="total" direction="desc" sortUrl="/r?sort={key}&direction={direction}" :columns="[['label' => 'Report', 'key' => 'name', 'sortable' => true]]"><x-ds::table-row><x-ds::table-cell>Q3</x-ds::table-cell></x-ds::table-row></x-ds::table></section>
<section data-focus="nav-item" class="w-64"><x-ds::nav-item href="/" label="Dashboard" :active="true"><x-slot:icon><x-ds::icon name="home" /></x-slot:icon></x-ds::nav-item><x-ds::nav-item href="/r" label="Reports" /></section>
<section data-focus="sub-nav" class="w-64"><x-ds::sub-nav label="Settings" :open="true"><x-ds::nav-item href="/s/p" label="Profile" /></x-ds::sub-nav></section>
<section data-focus="sheet-item" class="w-80"><x-ds::sheet-item href="/a" label="Projects" description="All of them" /><x-ds::sheet-item label="Sign out" tone="danger" as="button" /></section>
<section data-focus="bottom-nav-item" class="flex"><x-ds::bottom-nav-item href="/" label="Home" :active="true"><x-slot:icon><x-ds::icon name="home" size="6" /></x-slot:icon></x-ds::bottom-nav-item><x-ds::bottom-nav-item label="More"><x-slot:icon><x-ds::icon name="menu" size="6" /></x-slot:icon></x-ds::bottom-nav-item></section>
<section data-focus="avatar-group">@php $people = [['name' => 'Ada Lovelace'], ['name' => 'Grace Hopper'], ['name' => 'Alan Turing']]; @endphp<x-ds::avatar-group :avatars="$people" :max="2" :total="12" href="/team" /></section>
<section data-focus="step"><x-ds::stepper label="Checkout"><x-ds::step status="complete" title="Account" href="/a" /><x-ds::step status="current" title="Plan" /></x-ds::stepper></section>
<section data-focus="breadcrumb-item"><x-ds::breadcrumb><x-ds::breadcrumb-item href="/">Home</x-ds::breadcrumb-item><x-ds::breadcrumb-item :current="true">Q3</x-ds::breadcrumb-item></x-ds::breadcrumb></section>
<section data-focus="checkbox"><x-ds::checkbox name="terms" label="I agree" /></section>
<section data-focus="radio"><x-ds::radio-group label="Period"><x-ds::radio name="period" value="m" label="Monthly" :checked="true" /><x-ds::radio name="period" value="y" label="Yearly" /></x-ds::radio-group></section>
<section data-focus="tab"><x-ds::tabs label="Sections"><x-ds::tab controls="fp1" :active="true">Overview</x-ds::tab><x-ds::tab controls="fp2">Open</x-ds::tab></x-ds::tabs><x-ds::tab-panel id="fp1" :active="true">Overview content</x-ds::tab-panel><x-ds::tab-panel id="fp2">Open content</x-ds::tab-panel><x-ds::tabs label="Account" :navigation="true"><x-ds::tab href="/p" :active="true">Profile</x-ds::tab></x-ds::tabs></section>
<section data-focus="tooltip"><x-ds::tooltip text="Delete"><x-ds::button size="sm" iconOnly aria-label="Delete"><x-ds::icon name="trash" size="4" /></x-ds::button></x-ds::tooltip></section>
<section data-focus="toast"><x-ds::toast tone="success" title="Exported" dismiss="show = false">Done.</x-ds::toast></section>
<section data-focus="select" class="max-w-sm"><x-ds::select name="fplan" id="f-select" label="Plan" :options="['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team']" value="pro" /></section>
<section data-focus="switch"><x-ds::switch name="notify" label="Email" /></section>
<section data-focus="accordion-item"><x-ds::accordion open="billing"><x-ds::accordion-item title="Billing" name="billing">Card.</x-ds::accordion-item><x-ds::accordion-item title="Security" name="security">2FA.</x-ds::accordion-item></x-ds::accordion></section>
<section data-focus="combobox" class="max-w-sm"><x-ds::combobox name="ftags" id="f-combobox" label="Tags" :options="['a' => 'Accessibility', 'b' => 'Billing', 'c' => 'Compliance']" :value="['a']" :multiple="true" /></section>
<section data-focus="file-upload" class="max-w-sm"><x-ds::file-upload name="avatar" label="Photo" /></section>
<section data-focus="date-picker" class="max-w-sm"><x-ds::date-picker name="fdue" id="f-date" label="Due" value="2026-09-30" /></section>
<section data-focus="dropdown"><x-ds::dropdown placement="bottom-start"><x-slot:trigger><x-ds::button id="f-dd" size="sm">Actions</x-ds::button></x-slot:trigger><x-ds::dropdown-item href="/edit">Edit</x-ds::dropdown-item><x-ds::dropdown-item tone="danger" as="button">Delete</x-ds::dropdown-item></x-ds::dropdown></section>
<section data-focus="popover"><x-ds::popover label="Filters"><x-slot:trigger><x-ds::button id="f-pop" size="sm">Filters</x-ds::button></x-slot:trigger><x-ds::checkbox name="failed" label="Only failed" /></x-ds::popover></section>
<section data-focus="hover-card">
<x-ds::hover-card>
    <x-slot:trigger><x-ds::button id="f-hc" size="sm" variant="ghost">Ada</x-ds::button></x-slot:trigger>
    Analyst
</x-ds::hover-card>
</section>
<section data-focus="context-menu">
<x-ds::context-menu label="Row actions">
    {{-- The row is the consumer's, so it carries the system ring itself. --}}
    <div id="f-cm-row" tabindex="0" class="rounded-control p-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">Q3 revenue</div>
    <x-slot:menu>
        <x-ds::dropdown-item as="button">Edit</x-ds::dropdown-item>
        <x-ds::dropdown-item tone="danger" as="button">Delete</x-ds::dropdown-item>
    </x-slot:menu>
</x-ds::context-menu>
</section>
<section data-focus="theme-switcher"><x-ds::theme-switcher storageKey="focus-page-theme" /> <x-ds::theme-switcher variant="button" storageKey="focus-page-theme" /></section>
<section data-focus="announcement-bar"><x-ds::announcement-bar dismissible href="/status">Maintenance on Saturday.</x-ds::announcement-bar><x-ds::announcement-bar tone="warning" dismissible>Trial ends soon.</x-ds::announcement-bar></section>
{{-- A short timeout, so the question the walk leaves open is gone before the next walk. --}}
<section data-focus="confirm-button"><x-ds::confirm-button type="button" :timeout="1" /></section>
<section data-focus="hold-button"><x-ds::hold-button label="Hold to delete" /> <x-ds::hold-button tone="neutral" label="Hold to archive" /></section>
<section data-focus="confirm-popover"><x-ds::confirm-popover title="Delete?" type="button"><x-slot:trigger><x-ds::button id="f-cp" size="sm" variant="danger">Delete</x-ds::button></x-slot:trigger></x-ds::confirm-popover></section>
<section data-focus="modal"><x-ds::button id="f-modal-open" @click="modalOpen = true">Open modal</x-ds::button><x-ds::modal open="modalOpen" title="Confirm" size="sm">Body<x-slot:footer><x-ds::button size="sm" @click="modalOpen = false">OK</x-ds::button></x-slot:footer></x-ds::modal></section>
<section data-focus="drawer"><x-ds::button id="f-drawer-open" @click="drawerOpen = true">Open drawer</x-ds::button><x-ds::drawer open="drawerOpen" title="Filters">Body<x-slot:footer><x-ds::button size="sm" @click="drawerOpen = false">Apply</x-ds::button></x-slot:footer></x-ds::drawer></section>
{{-- Two weeks, two linked days: the scroll region and its links are stops. --}}
<section data-focus="activity-heatmap"><x-ds::activity-heatmap label="Commits" to="2026-03-08" :weeks="2" :data="['2026-03-03' => 4, '2026-03-05' => 1]" href="#day-{date}" /></section>
{{-- One stop per chart, then its legend keys; a donut's ring is on its wrapper. A timeline's link. --}}
<section data-focus="bar-chart"><x-ds::bar-chart label="Two" :data="['a' => [1, 2], 'b' => [3, 4]]" :series="['One', 'Two']" height="sm" /></section>
<section data-focus="line-chart"><x-ds::line-chart label="Line" :data="['a' => 1, 'b' => 3, 'c' => 2]" height="sm" /></section>
<section data-focus="donut-chart"><x-ds::donut-chart label="Donut" size="sm" legend="none" :data="['a' => 1, 'b' => 3]" /></section>
{{-- Batch 13: a segment's own ring; a waffle's on its first cell; rings and slope as the donut and line are. --}}
<section data-focus="split-bar"><x-ds::split-bar label="Split" legend="none" :data="['a' => 1, 'b' => 3]" /></section>
<section data-focus="waffle-chart"><x-ds::waffle-chart label="Waffle" legend="none" :data="['a' => 40, 'b' => 60]" size="sm" /></section>
<section data-focus="activity-rings"><x-ds::activity-rings label="Rings" size="sm" legend="none" :rings="['a' => 40, 'b' => 70]" /></section>
<section data-focus="slope-chart"><x-ds::slope-chart label="Slope" fromLabel="Then" toLabel="Now" height="sm" :data="['a' => [1, 2], 'b' => [3, 1]]" /></section>
{{-- Batch 14: a funnel's bar, a radar's point; a treemap's ring inside its tile, and a linked tile's too. --}}
<section data-focus="funnel-chart"><x-ds::funnel-chart label="Funnel" height="sm" :data="['a' => 4, 'b' => 2]" /></section>
<section data-focus="radar-chart"><x-ds::radar-chart label="Radar" size="sm" legend="none" :axes="['a', 'b', 'c']" :data="['x' => [3, 2, 1]]" /></section>
<section data-focus="treemap"><x-ds::treemap label="Treemap" height="sm" :legend="false" :data="['a' => 3, 'b' => 1]" /> <x-ds::treemap label="Linked" height="sm" :data="[['label' => 'a', 'value' => 3, 'href' => '#a'], ['label' => 'b', 'value' => 1, 'href' => '#b']]" /></section>
<section data-focus="timeline"><x-ds::timeline><x-ds::timeline-item title="Linked entry" href="#entry" time="2026-11-12" /></x-ds::timeline></section>
{{-- A tree is one stop; a navigation tree's links join it once the script runs. --}}
<section data-focus="tree"><x-ds::tree label="Files"><x-ds::tree-item label="app" :open="true"><x-ds::tree-item label="a.php" /></x-ds::tree-item></x-ds::tree> <x-ds::tree label="Perms" selectable="multiple" name="fperms"><x-ds::tree-item label="View" /></x-ds::tree></section>
{{-- The clear button shows while the field has a value; a chip's remove, link and button; Clear all. --}}
<section data-focus="input-clearable"><x-ds::input type="search" aria-label="Search" :clearable="true" value="acme" /></section>
<section data-focus="filter-chip"><x-ds::filter-chip label="Status" value="Open" /> <x-ds::filter-chip label="Owner" value="Ada" removeHref="#owner" /></section>
{{-- The scroll region, the copy button, Show more. --}}
<section data-focus="code-block"><x-ds::code-block filename="a.txt" :maxHeight="2" :expandable="true" :code="implode(PHP_EOL, ['one', 'two', 'three', 'four'])" /></section>
<section data-focus="expandable-text"><div class="w-48"><x-ds::expandable-text :lines="1">A sentence long enough to need more than one line in a box this narrow.</x-ds::expandable-text></div></section>
<section data-focus="panel-collapsible"><x-ds::panel title="Billing" :collapsible="true" action="Manage" actionHref="#manage"><x-ds::panel-row>Row</x-ds::panel-row></x-ds::panel></section>
{{-- A range input's ring is on its thumb, which getComputedStyle cannot read: the walk compares pictures for it. --}}
<section data-focus="slider" class="max-w-sm"><x-ds::slider name="fvol" label="Volume" :value="40" /><x-ds::slider name="fprice" label="Price" :range="true" :value="[20, 80]" lowLabel="Minimum price" highLabel="Maximum price" /></section>
<section data-focus="time-picker" class="max-w-sm"><x-ds::time-picker name="fat" id="f-time" label="Start" value="09:30" :clearable="true" /></section>
<section data-focus="inline-edit"><x-ds::inline-edit name="ftitle" label="Title" value="Q3 roadmap" /></section>
{{-- Plain text: the region is the stop. With a link inside, the link is. --}}
<section data-focus="scroll-area"><x-ds::scroll-area :maxHeight="40" label="Notes" class="w-48"><p>One</p><p>Two</p><p>Three</p><p>Four</p></x-ds::scroll-area></section>
{{-- Each handle, then its row's Move up and Move down, shown on focus. --}}
<section data-focus="sortable-list" class="max-w-sm"><x-ds::sortable-list label="Steps"><x-ds::sortable-item value="1">One</x-ds::sortable-item><x-ds::sortable-item value="2">Two</x-ds::sortable-item></x-ds::sortable-list></section>
<section data-focus="split"><div class="h-24 w-96 border border-border"><x-ds::split label="Resize"><x-ds::split-pane size="50%">A</x-ds::split-pane><x-ds::split-pane>B</x-ds::split-pane></x-ds::split></div></section>
{{-- The trigger is the host's; the field's ring is on its row, checked by the overlay walk. --}}
<section data-focus="command-palette"><x-ds::button id="f-cmd-open" x-on:click="$dispatch('open-command-palette', { name: 'focus' })">Search</x-ds::button><x-ds::command-palette name="focus" :shortcut="false"><x-ds::command-item label="Invoices" href="#invoices" /></x-ds::command-palette></section>
{{-- The walk stops here. A plain button, so it is not checked. --}}
<p><button id="f-end" type="button">end</button></p>
</div>
