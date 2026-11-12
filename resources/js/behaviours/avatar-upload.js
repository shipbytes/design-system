/*
 * dsAvatarUpload: choose a photo, crop it square under a circle or square
 * mask, and put the crop into the real file input.
 *
 *   x-data="dsAvatarUpload({ accept, maxSize, outputSize, outputType, quality, words })"
 *
 * The view renders the file input that posts (data-ds-avatar-input) as the
 * no-script control, a nameless picker (data-ds-avatar-picker), Change and
 * Remove buttons and the crop dialog, a ds::modal opened by `dsCropping`.
 * A photo comes from Change, a drop on the avatar or a paste in the
 * component; it is checked against `accept` and `maxSize` and, if it passes,
 * shown under the mask. Drag moves it (Pointer Events, captured), two
 * pointers pinch, the wheel and the zoom slider zoom, the arrow keys move and
 * + and − zoom, Rotate turns it a quarter. Nothing depends on dragging.
 *
 * Apply draws the crop to a canvas at `outputSize`, exports it with toBlob,
 * puts the file into the real input through DataTransfer and dispatches
 * `input` and `change` from it, so a plain form posts it and Livewire's
 * wire:model uploads it; then `crop` with { blob, width, height } from the
 * root, and the crop is shown at once. The picker never posts, so Livewire
 * never uploads an uncropped original. Where DataTransfer cannot be built
 * (old Safari) the original goes into the input instead, for the server to
 * crop. The photo is read only here, in the browser; nothing is sent.
 *
 * EXIF orientation: createImageBitmap with imageOrientation 'from-image',
 * falling back to an image element, which browsers draw upright themselves.
 *
 * Remove ticks the hidden {name}_remove checkbox, clears the input and shows
 * the initials. The scope exposes `dsReady`, `dsCropping` (the modal's open
 * state, so it is assignable) and `dsHasPhoto`, and nothing else.
 */

export function avatarUpload(Alpine) {
    Alpine.data('dsAvatarUpload', (options = {}) => {
        let control = null;

        return {
            dsReady: false,
            dsCropping: false,
            dsHasPhoto: false,

            init() {
                control = wire(this.$el, options, {
                    cropping: (on) => {
                        this.dsCropping = on;
                    },
                    photo: (on) => {
                        this.dsHasPhoto = on;
                    },
                });
                this.$watch('dsCropping', (on) => {
                    if (!on) control.closed();
                });
                this.dsReady = true;
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

const EXTENSIONS = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp' };
const clamp = (n, low, high) => Math.min(high, Math.max(low, n));

function wire(root, options, hooks) {
    const find = (selector) => root.querySelector(selector);
    const input = find('[data-ds-avatar-input]');
    const picker = find('[data-ds-avatar-picker]');
    const drop = find('[data-ds-avatar-drop]');
    const preview = find('[data-ds-avatar-preview]');
    const current = find('[data-ds-avatar-current]');
    const initials = find('[data-ds-avatar-initials]');
    const removed = find('[data-ds-avatar-removed]');
    const error = find('[data-ds-avatar-error]');
    const stage = find('[data-ds-avatar-stage]');
    const image = find('[data-ds-avatar-image]');
    const crop = find('[data-ds-avatar-crop]');
    const dialog = find('[data-ds-modal-panel]');
    const words = options.words ?? {};
    const pointers = new Map();

    let source = null;
    let view = { x: 0, y: 0, zoom: 1, turn: 0 };
    let pinch = null;
    let shown = null;

    const disabled = () => input.disabled;
    const zoomInput = () => crop.querySelector('[data-ds-avatar-zoom] input[type="range"]');

    function say(message) {
        error.querySelector('[data-ds-avatar-error-text]').textContent = message;
        error.hidden = !message;
    }

    function acceptable(file) {
        const name = file.name.toLowerCase();
        const type = file.type.toLowerCase();
        const list = String(options.accept ?? '').toLowerCase().split(',').map((a) => a.trim()).filter(Boolean);
        if (!list.length) return type.startsWith('image/');
        return list.some((a) => (a.startsWith('.') ? name.endsWith(a) : a.endsWith('/*') ? type.startsWith(a.slice(0, -1)) : type === a));
    }

    async function load(file) {
        const url = URL.createObjectURL(file);
        let bitmap = null;
        if (typeof createImageBitmap === 'function') {
            bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' }).catch(() => null);
        }
        bitmap ??= await new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = url;
        }).catch(() => null);
        const width = bitmap?.naturalWidth || bitmap?.width;
        const height = bitmap?.naturalHeight || bitmap?.height;
        if (!width || !height) {
            URL.revokeObjectURL(url);
            return null;
        }
        return { url, bitmap, width, height, file };
    }

    async function choose(file) {
        if (!file || disabled()) return;
        if (!acceptable(file)) return say(words.type);
        if (options.maxSize && file.size > options.maxSize) return say(words.size);
        const loaded = await load(file);
        if (!loaded) return say(words.unreadable);
        say('');
        forget();
        source = loaded;
        view = { x: 0, y: 0, zoom: 1, turn: 0 };
        image.src = source.url;
        zoomTo(1);
        hooks.cropping(true);
    }

    function forget() {
        if (source) URL.revokeObjectURL(source.url);
        source?.bitmap.close?.();
        source = null;
    }

    // The crop is the middle 80% of the stage; the photo always covers it.
    function geometry() {
        const box = stage.clientWidth || 1;
        const size = box * 0.8;
        const turned = view.turn % 180 !== 0;
        const w = turned ? source.height : source.width;
        const h = turned ? source.width : source.height;
        const scale = (size / Math.min(w, h)) * view.zoom;
        return { size, scale, maxX: Math.max(0, (w * scale - size) / 2), maxY: Math.max(0, (h * scale - size) / 2) };
    }

    function draw() {
        if (!source) return;
        const g = geometry();
        view.x = clamp(view.x, -g.maxX, g.maxX);
        view.y = clamp(view.y, -g.maxY, g.maxY);
        image.style.width = `${source.width}px`;
        image.style.height = `${source.height}px`;
        image.style.transform = `translate(-50%, -50%) translate(${view.x}px, ${view.y}px) rotate(${view.turn}deg) scale(${g.scale})`;
    }

    // Zoomed about the middle of the crop, so what is there stays there.
    function zoomTo(zoom, fromSlider = false) {
        zoom = clamp(zoom, 1, 4);
        view.x *= zoom / view.zoom;
        view.y *= zoom / view.zoom;
        view.zoom = zoom;
        const range = zoomInput();
        if (range && !fromSlider) {
            range.value = String(Math.round(zoom * 100));
            range.dispatchEvent(new Event('input', { bubbles: true }));
        }
        draw();
    }

    function show(url) {
        if (shown) URL.revokeObjectURL(shown);
        shown = url;
        if (url) preview.src = url;
        preview.hidden = !url;
        const saved = Boolean(!removed?.checked && current?.querySelector('img'));
        if (current) current.hidden = Boolean(url) || !saved;
        // Under a preview the initials stay, as the box the preview covers.
        if (initials) initials.hidden = !url && saved;
        hooks.photo(Boolean(url) || saved);
    }

    function put(file) {
        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
        } catch {
            if (picker.files?.length) input.files = picker.files;
        }
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    async function apply() {
        if (!source) return;
        const g = geometry();
        const size = options.outputSize ?? 512;
        const k = size / g.size;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const context = canvas.getContext('2d');
        // A transparent PNG exported as JPEG would turn black, not white.
        if (options.outputType !== 'image/png') {
            context.fillStyle = '#fff';
            context.fillRect(0, 0, size, size);
        }
        context.imageSmoothingQuality = 'high';
        context.translate(size / 2 + view.x * k, size / 2 + view.y * k);
        context.rotate((view.turn * Math.PI) / 180);
        context.scale(g.scale * k, g.scale * k);
        context.drawImage(source.bitmap, -source.width / 2, -source.height / 2);
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, options.outputType, options.quality));
        if (!blob) return say(words.unreadable);
        const base = source.file.name.replace(/\.[^.]*$/, '') || 'photo';
        // toBlob falls back to PNG for a type the browser cannot write.
        put(new File([blob], `${base}.${EXTENSIONS[blob.type] ?? 'png'}`, { type: blob.type }));
        if (removed) removed.checked = false;
        show(URL.createObjectURL(blob));
        forget();
        hooks.cropping(false);
        root.dispatchEvent(new CustomEvent('crop', { bubbles: true, detail: { blob, width: size, height: size } }));
    }

    function remove() {
        if (removed) {
            removed.checked = true;
            removed.dispatchEvent(new Event('change', { bubbles: true }));
        }
        input.value = '';
        show(null);
        find('[data-ds-avatar-change]')?.focus();
    }

    function onClick(event) {
        const target = event.target.closest('button');
        if (!target) return;
        if (target.matches('[data-ds-avatar-change]')) {
            picker.value = '';
            picker.click();
        } else if (target.matches('[data-ds-avatar-remove]')) remove();
        else if (target.matches('[data-ds-avatar-rotate]')) {
            view = { ...view, x: 0, y: 0, turn: (view.turn + 90) % 360 };
            draw();
        } else if (target.matches('[data-ds-avatar-apply]')) apply();
        else if (target.matches('[data-ds-avatar-cancel]')) hooks.cropping(false);
    }

    const onPick = () => choose(picker.files?.[0]);

    function onPaste(event) {
        const file = [...(event.clipboardData?.files ?? [])].find((f) => f.type.startsWith('image/'));
        if (!file || source) return;
        event.preventDefault();
        choose(file);
    }

    function onDrag(event) {
        if (!event.dataTransfer?.types.includes('Files') || disabled()) return;
        event.preventDefault();
        drop.toggleAttribute('data-ds-dragging', event.type === 'dragover');
        if (event.type === 'drop') choose(event.dataTransfer.files[0]);
    }

    function onSlider(event) {
        if (event.target === zoomInput()) zoomTo(Number(event.target.value) / 100, true);
    }

    function onDown(event) {
        stage.setPointerCapture(event.pointerId);
        pointers.set(event.pointerId, [event.clientX, event.clientY]);
        if (pointers.size === 2) pinch = { distance: spread(), zoom: view.zoom };
    }

    function spread() {
        const [[ax, ay], [bx, by]] = [...pointers.values()];
        return Math.hypot(ax - bx, ay - by) || 1;
    }

    function onMove(event) {
        const last = pointers.get(event.pointerId);
        if (!last) return;
        pointers.set(event.pointerId, [event.clientX, event.clientY]);
        if (pinch && pointers.size > 1) return zoomTo((pinch.zoom * spread()) / pinch.distance);
        view.x += event.clientX - last[0];
        view.y += event.clientY - last[1];
        draw();
    }

    function onUp(event) {
        pointers.delete(event.pointerId);
        if (pointers.size < 2) pinch = null;
    }

    function onWheel(event) {
        event.preventDefault();
        zoomTo(view.zoom * Math.exp(-event.deltaY / 500));
    }

    function onKey(event) {
        const step = event.shiftKey ? 40 : 8;
        const move = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] }[event.key];
        if (move) {
            view.x += move[0];
            view.y += move[1];
            draw();
        } else if (event.key === '+' || event.key === '=') zoomTo(view.zoom * 1.1);
        else if (event.key === '-' || event.key === '_') zoomTo(view.zoom / 1.1);
        else return;
        event.preventDefault();
    }

    const resized = new ResizeObserver(draw);
    const listeners = [
        [root, 'click', onClick], [root, 'paste', onPaste], [picker, 'change', onPick],
        [drop, 'dragover', onDrag], [drop, 'dragleave', onDrag], [drop, 'drop', onDrag],
        [crop, 'input', onSlider], [stage, 'pointerdown', onDown], [stage, 'pointermove', onMove],
        [stage, 'pointerup', onUp], [stage, 'pointercancel', onUp], [stage, 'wheel', onWheel, { passive: false }],
        [stage, 'keydown', onKey],
    ];
    for (const [target, type, fn, opts] of listeners) target.addEventListener(type, fn, opts);
    resized.observe(stage);
    hooks.photo(Boolean(current && !current.hidden));

    return {
        closed() {
            // Focus goes back to Change, whatever opened the dialog: a drop
            // or a paste leaves nothing for the modal to return to.
            requestAnimationFrame(() => {
                if (!root.contains(document.activeElement) || dialog?.contains(document.activeElement)) {
                    find('[data-ds-avatar-change]')?.focus();
                }
            });
            forget();
            pointers.clear();
            pinch = null;
        },

        teardown() {
            for (const [target, type, fn, opts] of listeners) target.removeEventListener(type, fn, opts);
            resized.disconnect();
            forget();
            if (shown) URL.revokeObjectURL(shown);
        },
    };
}
