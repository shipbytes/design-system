/*
 * A flag that goes up and, `ms` later, comes back down by itself: the
 * "Copied" of dsClipboard and the swapped face of dsSwap. Registers nothing.
 *
 *   const flag = timedFlag((on) => { scope.copied = on; }, 1500);
 *   flag.set(true);    up, and down again in 1500 ms
 *   flag.set(false);   down now
 *   flag.stop();       forget the timer, for destroy()
 *
 * Restarted rather than stacked: raised again while up, it stays up for the
 * whole time after the LATER raise, not for what was left of the first. An
 * `ms` of 0 or less never comes down by itself.
 */
export function timedFlag(write, ms) {
    let timer = null;

    return {
        set(on) {
            clearTimeout(timer);
            timer = null;
            write(on);
            if (on && ms > 0) timer = setTimeout(() => write(false), ms);
        },

        stop() {
            clearTimeout(timer);
        },
    };
}
