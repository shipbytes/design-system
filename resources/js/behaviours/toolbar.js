/*
 * dsToolbar: the keyboard of ds::toolbar.
 *
 *   x-data="dsToolbar"   on the element with role="toolbar"
 *
 * role="toolbar" promises one tab stop and the arrow keys, and this keeps the
 * promise: the arrows along aria-orientation move between the items marked
 * data-ds-toolbar-item, wrapping and skipping disabled ones, Home and End go
 * to the ends, and Tab leaves. Toggles of a ds::toggle-group inside the
 * toolbar are items of the toolbar; the group leaves its own arrow keys to it.
 *
 * The highlight under the pointer is x-ds-glide.hover on the same element,
 * not this. The scope exposes nothing but Alpine's own init and destroy.
 */
import { roving } from '../shared/roving.js';

export function toolbar(Alpine) {
    Alpine.data('dsToolbar', () => {
        let teardown = null;

        return {
            init() {
                const root = this.$el;
                teardown = roving(root, {
                    // Not the items of a toolbar nested inside this one.
                    items: () => [...root.querySelectorAll('[data-ds-toolbar-item]')]
                        .filter((item) => item.closest('[data-ds-toolbar]') === root),
                    axis: () => (root.getAttribute('aria-orientation') === 'vertical' ? 'vertical' : 'horizontal'),
                });
            },

            destroy() {
                teardown?.();
            },
        };
    });
}
