/*
 * dsSlider: one native range input, or two sharing one track.
 *
 *   x-data="dsSlider({ minGap: 0, format: { …Chart::formatOptions() } })"
 *
 * The markup is the view's, rendered complete by PHP: the inputs
 * (data-ds-slider-input="high", and "low" with two thumbs), the body that
 * carries --ds-slider-low and --ds-slider-high as ratios from 0 to 1 (the
 * fill and the tips are placed from them in CSS), and the optional readouts
 * data-ds-slider-output-low/-high and data-ds-slider-tip. The keyboard, the
 * pointer on a thumb, touch and what the form posts are the browser's own.
 * This keeps the rest in step:
 *
 *   - the two variables, so the fill follows the thumbs;
 *   - aria-valuetext and the readouts, formatted as Support\Chart::format()
 *     formats them on the server (shared/format.js);
 *   - with two thumbs, neither passes the other: each stops `minGap` short
 *     of it, and the low thumb comes to the top past halfway
 *     (data-ds-slider-top), so two thumbs parked at one end can be parted;
 *   - with two thumbs, a press on the track moves the nearer thumb there,
 *     which a native input does for itself when it is alone;
 *   - the tips, shown while their thumb is dragged or has keyboard focus;
 *   - with a histogram (data-ds-slider-bar, each with its count and its
 *     middle value), which bars are lit (data-ds-in, a bar whose middle is
 *     in the range) and the count of what they hold, written at once and
 *     said in the status region (data-ds-slider-said) half a second after
 *     the thumbs stop;
 *   - with `inputs`, the number fields (data-ds-slider-number), shown once
 *     this runs (dsReady): each follows its thumb, and moves it while typed
 *     in once the number is one the thumb can take (the other thumb is its
 *     bound); leaving one moves it anyway, held to the other thumb, and
 *     writes back what the thumb took.
 *
 * Two-way binding goes through `dsValue`, which with `dsReady` is all the
 * scope exposes beside init and destroy: a number, or with two thumbs [low, high] (or
 * { low, high }, when that is the shape the host gave). The view puts
 * x-modelable="dsValue" on the root with the host's x-model or wire:model.
 * A value the host sets is drawn, clamped and stepped as the inputs would
 * take it, and the host is handed back what they hold when it differs. It
 * dispatches nothing: the inputs' own `input` and `change` bubble from them
 * as they always do.
 */
import { formatter } from '../shared/format.js';

export function slider(Alpine) {
    Alpine.data('dsSlider', (options = {}) => {
        let control = null;

        return {
            dsValue: null,
            dsReady: false,

            init() {
                control = wire(this.$el, options, (value) => {
                    this.dsValue = value;
                });
                this.dsValue = control.value();
                this.dsReady = true;
                this.$watch('dsValue', (value) => control.set(value));
            },

            destroy() {
                control?.teardown();
            },
        };
    });
}

function wire(root, options, report) {
    const body = root.querySelector('[data-ds-slider-body]');
    const inputs = {
        low: root.querySelector('[data-ds-slider-input="low"]'),
        high: root.querySelector('[data-ds-slider-input="high"]'),
    };
    const pair = Boolean(inputs.low);
    const keys = pair ? ['low', 'high'] : ['high'];
    const format = formatter(options.format ?? {});
    const gap = Math.max(0, Number(options.minGap) || 0);
    const tips = Object.fromEntries(keys.map((key) => [key, root.querySelector(`[data-ds-slider-tip="${key}"]`)]));
    const outputs = Object.fromEntries(keys.map((key) => [key, root.querySelector(`[data-ds-slider-output-${key}]`)]));
    const numbers = Object.fromEntries(keys.map((key) => [key, root.querySelector(`[data-ds-slider-number="${key}"]`)]));
    const bars = [...root.querySelectorAll('[data-ds-slider-bar]')];
    const counted = root.querySelector('[data-ds-slider-count]');
    const said = root.querySelector('[data-ds-slider-said]');
    const forms = String(options.count?.label ?? '').split('|');
    const formatCount = formatter(options.count?.format ?? {});
    let saying = 0;

    // The shape the host's value came in, so it is handed back the same way.
    let keyed = false;
    let dragging = null;

    const number = (input) => Number(input.value);
    const lowest = () => Number(inputs.high.min);
    const highest = () => Number(inputs.high.max);
    const ratio = (n) => (highest() > lowest() ? (n - lowest()) / (highest() - lowest()) : 0);

    function draw() {
        for (const key of keys) {
            const n = number(inputs[key]);
            const text = format(n);
            body.style.setProperty(`--ds-slider-${key}`, String(ratio(n)));
            inputs[key].setAttribute('aria-valuetext', text);
            if (tips[key]) tips[key].textContent = text;
            if (outputs[key]) outputs[key].textContent = text;
        }
        if (pair) inputs.low.toggleAttribute('data-ds-slider-top', ratio(number(inputs.low)) > 0.5);

        const low = pair ? number(inputs.low) : lowest();
        const high = number(inputs.high);
        if (bars.length) {
            let total = 0;
            for (const bar of bars) {
                const inside = Number(bar.dataset.dsMid) >= low && Number(bar.dataset.dsMid) <= high;
                bar.toggleAttribute('data-ds-in', inside);
                if (inside) total += Number(bar.dataset.dsCount);
            }
            const text = forms[total === 1 ? 0 : forms.length - 1].replace(':count', formatCount(total));
            if (counted) counted.textContent = text;
            clearTimeout(saying);
            saying = setTimeout(() => {
                if (said) said.textContent = text;
            }, 500);
        }

        for (const key of keys) {
            const field = numbers[key];
            if (!field) continue;
            if (document.activeElement !== field) field.value = inputs[key].value;
            // Each bounded by the other thumb, so the browser's own check
            // agrees with what leaving the field will do.
            if (pair) field[key === 'low' ? 'max' : 'min'] = String(key === 'low' ? high - gap : low + gap);
            // dsNumberField redraws its buttons on a change at its root.
            field.closest('[x-data]')?.dispatchEvent(new Event('change'));
        }
    }

    /*
     * A number field moves its thumb: while typed in, once the number is
     * one the thumb can take (the "3" of a "300" being typed is not); on
     * leaving it, always, and it is written back as what the thumb took.
     */
    function onNumber(event) {
        const key = keys.find((k) => numbers[k] === event.target);
        if (!key) return;
        const n = event.target.value === '' ? NaN : Number(event.target.value);
        const leaving = event.type === 'change';
        const field = event.target;
        if (!Number.isFinite(n) || (!leaving && (n < Number(field.min) || n > Number(field.max)))) return;
        const input = inputs[key];
        input.value = String(n);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        if (!leaving) return;
        field.value = input.value;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /*
     * Each thumb stops `minGap` short of the other. Written back into the
     * input, which steps it as it steps the reader's own drag: so the gap is
     * at least minGap, rounded out to the next step.
     */
    function hold(moved) {
        if (!pair) return;
        const low = number(inputs.low);
        const high = number(inputs.high);
        if (high - low >= gap) return;

        if (moved === 'low') {
            inputs.low.value = String(high - gap);
            if (number(inputs.low) > high - gap) inputs.low.stepDown();
        } else {
            inputs.high.value = String(low + gap);
            if (number(inputs.high) < low + gap) inputs.high.stepUp();
        }
    }

    function value() {
        if (!pair) return number(inputs.high);
        const low = number(inputs.low);
        const high = number(inputs.high);
        return keyed ? { low, high } : [low, high];
    }

    function keyOf(target) {
        return keys.find((key) => inputs[key] === target) ?? null;
    }

    function onInput(event) {
        const key = keyOf(event.target);
        if (!key) return;
        hold(key);
        draw();
        report(value());
    }

    function showTip(key, shown) {
        if (tips[key]) tips[key].hidden = !shown;
    }

    function onPointerDown(event) {
        const key = keyOf(event.target);
        if (key) {
            dragging = key;
            showTip(key, true);
            return;
        }
        // The track, or the bars over it; not a label, a help line or a
        // number field, which are inside the root too.
        if (pair && event.button === 0 && event.target.closest('[data-ds-slider-body], [data-ds-slider-bars]')) jump(event);
    }

    function onPointerUp() {
        if (!dragging) return;
        const key = dragging;
        dragging = null;
        showTip(key, document.activeElement === inputs[key] && inputs[key].matches(':focus-visible'));
    }

    function onFocusIn(event) {
        const key = keyOf(event.target);
        if (key && event.target.matches(':focus-visible')) showTip(key, true);
    }

    function onFocusOut(event) {
        const key = keyOf(event.target);
        if (key && dragging !== key) showTip(key, false);
    }

    /*
     * A press on the track between or beyond two thumbs moves the nearer one
     * there and gives it focus, as a lone native input does. The inputs let
     * the pointer through to the track, so without this nothing would.
     */
    function jump(event) {
        const box = inputs.high.getBoundingClientRect();
        const half = box.height / 2;
        const usable = box.width - half * 2;
        if (usable <= 0) return;

        let t = (event.clientX - box.left - half) / usable;
        if (getComputedStyle(root).direction === 'rtl') t = 1 - t;
        const at = lowest() + Math.max(0, Math.min(1, t)) * (highest() - lowest());

        const low = number(inputs.low);
        const high = number(inputs.high);
        const key = Math.abs(at - low) < Math.abs(at - high) || (low === high && at < low) ? 'low' : 'high';
        const input = inputs[key];
        if (input.disabled) return;

        event.preventDefault();
        const before = input.value;
        input.value = String(at);
        hold(key);
        input.focus({ preventScroll: true });
        if (input.value === before) return;

        draw();
        report(value());
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function set(given) {
        let wanted;
        if (pair) {
            keyed = given !== null && typeof given === 'object' && !Array.isArray(given);
            const list = Array.isArray(given) ? given : keyed ? [given.low, given.high] : [];
            wanted = list.map((n) => (n === null || n === undefined || n === '' ? NaN : Number(n)));
            if (Number.isFinite(wanted[0]) && Number.isFinite(wanted[1])) {
                const [low, high] = wanted[0] <= wanted[1] ? wanted : [wanted[1], wanted[0]];
                // High first, so a low above the old high is not stopped by it.
                inputs.high.value = String(highest());
                inputs.low.value = String(low);
                inputs.high.value = String(high);
                hold('high');
            }
        } else if (given !== null && given !== undefined && given !== '' && Number.isFinite(Number(given))) {
            inputs.high.value = String(Number(given));
        }
        draw();

        const now = value();
        const same = pair
            ? Array.isArray(given) && given.length === 2 && Number(given[0]) === now[0] && Number(given[1]) === now[1]
                || keyed && Number(given.low) === now.low && Number(given.high) === now.high
            : Number(given) === now && given !== null && given !== '';
        if (!same) report(now);
    }

    const listeners = [
        [root, 'input', onInput],
        [root, 'input', onNumber],
        [root, 'change', onNumber],
        [root, 'pointerdown', onPointerDown],
        [root, 'focusin', onFocusIn],
        [root, 'focusout', onFocusOut],
        [document, 'pointerup', onPointerUp],
        [document, 'pointercancel', onPointerUp],
    ];
    for (const [target, type, fn] of listeners) target.addEventListener(type, fn);

    draw();

    return {
        value,
        set,
        teardown() {
            clearTimeout(saying);
            for (const [target, type, fn] of listeners) target.removeEventListener(type, fn);
        },
    };
}
