/*
 * One pointer, from press to release: what every drag in the script shares
 * (split's dividers, sortable's rows, the sheet, swipe actions, pull to
 * refresh). Hand-written on Pointer Events; no gesture library.
 *
 *   const handle = drag(root, { press, start, move, end, cancel, axis, slop, swallowClick });
 *
 * press(event) decides whether a primary press inside `root` is a drag: it
 * returns the element to capture the pointer on, or nothing. Capture waits
 * for the drag to start: a press captured at once on a box would make the
 * box the target of the click that follows, and a link or a button inside it
 * would never hear it. It is refused quietly for a pointer the browser no
 * longer counts as down; the move, up and cancel listeners are on the window
 * for the length of the press either way, so a drag reaches its end
 * whatever capture did.
 *
 * Nothing is a drag until the pointer has moved `slop` pixels (0: at once).
 * With `axis` ('x' or 'y'), a press that first travels further along the
 * other axis is let go without a word, so a vertical scroll that starts on a
 * horizontal swipe stays the page's (the element's touch-action lets the
 * browser have it, and the browser's pointercancel then ends it here too).
 *
 * start, move, end and cancel are each given the one state object:
 * { target, pressed, type, x0, y0, x, y, dx, dy, vx, vy, event }, pressed
 * being what was under the pointer at the press, the velocities in
 * pixels a millisecond over the last 100 ms, so a flick is told from a slow
 * drag. start returning false lets the press go. end is a release, cancel a
 * pointercancel or handle.cancel(). Callers throttle their own drawing.
 *
 * swallowClick: the click a mouse sends after a drag that ended on a link or
 * a button is not a click the reader meant, so the next one is dropped.
 */

const WINDOW = 100;

export function drag(root, options) {
    const slop = options.slop ?? 0;
    let d = null;
    let swallow = false;

    function track(event) {
        const t = event.timeStamp;
        d.trail.push([t, event.clientX, event.clientY]);
        while (d.trail.length > 2 && t - d.trail[0][0] > WINDOW) d.trail.shift();
        const [t0, x0, y0] = d.trail[0];
        const dt = t - t0;
        Object.assign(d, {
            event, x: event.clientX, y: event.clientY, dx: event.clientX - d.x0, dy: event.clientY - d.y0,
            vx: dt > 0 ? (event.clientX - x0) / dt : 0, vy: dt > 0 ? (event.clientY - y0) / dt : 0,
        });
    }

    function listen(on) {
        const method = on ? addEventListener : removeEventListener;
        method('pointermove', onMove);
        method('pointerup', onUp);
        method('pointercancel', onCancel);
    }

    function finish(how) {
        const state = d;
        d = null;
        listen(false);
        if (state.target.hasPointerCapture?.(state.id)) state.target.releasePointerCapture(state.id);
        if (!state.started) return;
        if (how === 'end' && options.swallowClick) {
            swallow = true;
            setTimeout(() => (swallow = false));
        }
        options[how]?.(state);
    }

    function begin() {
        if (options.start?.(d) === false) return finish('cancel');
        d.started = true;
        try {
            d.target.setPointerCapture(d.id);
        } catch {}
    }

    function onDown(event) {
        if (d || event.button !== 0 || !event.isPrimary) return;
        const target = options.press(event);
        if (!target) return;
        d = {
            target, pressed: event.target, id: event.pointerId, type: event.pointerType, started: false, event,
            x0: event.clientX, y0: event.clientY, x: event.clientX, y: event.clientY,
            dx: 0, dy: 0, vx: 0, vy: 0, trail: [[event.timeStamp, event.clientX, event.clientY]],
        };
        listen(true);
        if (!slop) begin();
    }

    function onMove(event) {
        if (!d || event.pointerId !== d.id) return;
        if (!d.started) {
            const dx = Math.abs(event.clientX - d.x0);
            const dy = Math.abs(event.clientY - d.y0);
            if (Math.hypot(dx, dy) < slop) return;
            if (options.axis === 'x' ? dy > dx : options.axis === 'y' && dx > dy) return finish('cancel');
            track(event);
            begin();
            if (!d) return;
        } else {
            track(event);
        }
        options.move?.(d);
    }

    function onUp(event) {
        if (!d || event.pointerId !== d.id) return;
        if (d.started) track(event);
        finish('end');
    }

    function onCancel(event) {
        if (d && event.pointerId === d.id) finish('cancel');
    }

    function onClick(event) {
        if (!swallow) return;
        swallow = false;
        event.preventDefault();
        event.stopPropagation();
    }

    root.addEventListener('pointerdown', onDown);
    root.addEventListener('click', onClick, true);

    return {
        cancel() {
            if (d) finish('cancel');
        },
        stop() {
            if (d) finish('cancel');
            root.removeEventListener('pointerdown', onDown);
            root.removeEventListener('click', onClick, true);
        },
    };
}
