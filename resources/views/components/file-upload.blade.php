@props([
    /** Submitted field name. With `multiple`, pass it as "attachments[]". */
    'name',
    'label' => null,
    /** Accept more than one file. */
    'multiple' => false,
    /** Passed through to the input — "image/*", ".pdf,.csv". */
    'accept' => null,
    /**
     * Largest file the reader should choose, in bytes. Checked in the BROWSER,
     * for the reader's benefit only — it saves them a failed upload, and it is
     * not a security control. Validate on the server as well, always.
     */
    'maxSize' => null,
    /** Guidance under the drop zone. Replaced by `error`, never stacked. */
    'help' => null,
    'error' => null,
    'disabled' => false,
    /** Show a thumbnail for image files. Read locally; nothing is uploaded. */
    'preview' => true,
])

@php
    $id = $attributes->get('id') ?: 'ds-'.substr(md5($name.$label.uniqid()), 0, 8);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    // "image/*,.pdf" is a machine string. The reader gets "images, PDF" —
    // the hint exists to tell a person what to look for on their own disk.
    $types = $accept === null ? null : collect(explode(',', $accept))
        ->map(fn ($type) => trim($type))
        ->filter()
        ->map(fn ($type) => match (true) {
            $type === 'image/*' => 'images',
            $type === 'video/*' => 'video',
            $type === 'audio/*' => 'audio',
            str_starts_with($type, '.') => strtoupper(ltrim($type, '.')),
            str_contains($type, '/') => strtoupper(substr($type, strrpos($type, '/') + 1)),
            default => $type,
        })
        ->unique()
        ->implode(', ');

    $hint = collect([
        $types ?: null,
        $maxSize ? 'up to '.round($maxSize / 1024 / 1024, 1).' MB' : null,
    ])->filter()->implode(' · ');

    /*
     * This is a REAL <input type="file">, styled — not a button that opens a
     * hidden one. It stays in the layout, keeps its own focus behaviour, and
     * posts in a plain form; `wire:model` works on it unchanged.
     *
     * The component owns no transport. Progress bars need somewhere to upload
     * to, and the moment a component knows that, it has a backend contract —
     * which is the one thing nothing else in this system has.
     */
    $zone = implode(' ', [
        'relative flex flex-col items-center justify-center gap-1 rounded-control',
        'border-2 border-dashed px-6 py-8 text-center transition-colors',
        $error ? 'border-danger' : 'border-border-strong',
        $disabled
            ? 'cursor-not-allowed bg-surface-subtle'
            : 'cursor-pointer bg-surface hover:border-accent hover:bg-accent-wash',
    ]);

    /*
     * Drag state, the file list and the previews.
     *
     * `dragging` counts enter/leave rather than toggling a boolean: dragging
     * over a CHILD of the zone fires dragleave on the parent, so a plain toggle
     * flickers the highlight off and on as the pointer crosses the label.
     */
    $onChange = <<<'JS'
        rejected = [];
        files = [...$event.target.files].filter((file) => {
            if (maxSize && file.size > maxSize) { rejected.push(file.name); return false; }
            return true;
        }).map((file) => ({
            name: file.name,
            size: file.size,
            url: preview && file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
        }));
    JS;

    /*
     * Removing one file means rebuilding the input's FileList, which is
     * read-only — a DataTransfer is the only way to construct one. Without this
     * the chip disappears and the file is still submitted, which is worse than
     * having no remove control at all.
     */
    $onRemove = <<<'JS'
        const transfer = new DataTransfer();
        [...$refs.input.files]
            .filter((file) => file.name !== name)
            .forEach((file) => transfer.items.add(file));
        $refs.input.files = transfer.files;
        $refs.input.dispatchEvent(new Event('change', { bubbles: true }));
    JS;

    $state = json_encode([
        'files' => [],
        'rejected' => [],
        'dragging' => 0,
        'maxSize' => $maxSize ? (int) $maxSize : null,
        'preview' => (bool) $preview,
    ]);
@endphp

<div x-data="{{ $state }}" {{ $attributes->only('class')->merge(['class' => 'block w-full']) }}>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-body font-medium text-fg">{{ $label }}</label>
    @endif

    <div
        class="{{ $zone }}"
        :class="dragging > 0 && 'border-accent bg-accent-wash'"
        @dragenter.prevent="dragging++"
        @dragleave.prevent="dragging--"
        @dragover.prevent
        @drop.prevent="dragging = 0; $refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change', { bubbles: true }))"
    >
        {{-- The input covers the whole zone and is transparent, so the entire
             area is the real control: one focus ring, one click target, and drag
             and drop landing on the element that owns the files. --}}
        <input
            type="file"
            id="{{ $id }}"
            x-ref="input"
            @change="{{ $onChange }}"
            {{ $attributes->except(['class', 'id'])->merge(['name' => $name, 'accept' => $accept])->filter(fn ($v) => $v !== null) }}
            @if ($multiple) multiple @endif
            @disabled($disabled)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            class="absolute inset-0 size-full cursor-pointer opacity-0 disabled:cursor-not-allowed"
        />

        <x-ds::icon name="arrow-up-tray" size="6" class="text-fg-muted" />

        <p class="text-body text-fg-body">
            <span class="font-medium text-fg">Choose {{ $multiple ? 'files' : 'a file' }}</span>
            or drag {{ $multiple ? 'them' : 'it' }} here
        </p>

        @if ($hint)
            <p class="text-meta text-fg-muted">{{ $hint }}</p>
        @endif
    </div>

    {{-- Announced politely: the list changes in response to something the reader
         did, and they are not looking at it while the file dialog closes. --}}
    <ul x-show="files.length" x-cloak class="mt-2 flex flex-col gap-1.5" aria-live="polite">
        <template x-for="file in files" :key="file.name">
            <li class="flex items-center gap-3 rounded-control border border-border bg-surface px-3 py-2">
                <template x-if="file.url">
                    <img :src="file.url" alt="" class="size-9 shrink-0 rounded-chip object-cover" />
                </template>
                <template x-if="! file.url">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-chip bg-neutral-tint text-on-neutral-tint">
                        <x-ds::icon name="document-text" size="4" />
                    </span>
                </template>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-body text-fg" x-text="file.name"></span>
                    <span
                        class="block text-meta text-fg-muted tabular-nums"
                        x-text="file.size < 1024 * 1024
                            ? Math.max(1, Math.round(file.size / 1024)) + ' KB'
                            : (file.size / 1024 / 1024).toFixed(1) + ' MB'"
                    ></span>
                </span>

                <button
                    type="button"
                    class="shrink-0 rounded-control p-1.5 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                    :aria-label="'Remove ' + file.name"
                    @click="const name = file.name; {{ $onRemove }}"
                >
                    <x-ds::icon name="x-mark" size="4" />
                </button>
            </li>
        </template>
    </ul>

    {{-- A file silently dropped for being too large is the reader assuming it
         uploaded. `alert` rather than `status`: they need to know now. --}}
    <p x-show="rejected.length" x-cloak class="mt-1.5 text-meta text-danger" role="alert">
        <span x-text="rejected.join(', ')"></span>
        <span>{{ $maxSize ? 'is over '.round($maxSize / 1024 / 1024, 1).' MB and was not added.' : 'was not added.' }}</span>
    </p>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-start gap-1.5 text-meta text-danger">
            <x-ds::icon name="exclamation-circle" size="3.5" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="mt-1.5 text-meta text-fg-muted">{{ $help }}</p>
    @endif
</div>
