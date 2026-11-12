/*
 * dsClear: the ✕ inside ds::input's `clearable`.
 *
 *   x-data="dsClear"   on the button, which names its input with aria-controls
 *
 * Empties the field, puts focus back in it, and dispatches `input` and then
 * `change` from it, both bubbling, as a reader deleting the text would: so a
 * host's x-model or wire:model, a filter listening for `input` and a form
 * listening for `change` all hear it. Nothing happens when the field is
 * already empty.
 *
 * Whether the button shows is not this behaviour's business: the view hides
 * it with `:placeholder-shown`, which follows the value however it changed.
 * The scope exposes nothing but Alpine's own init and destroy.
 */

export function clear(Alpine) {
    Alpine.data('dsClear', () => {
        let teardown = null;

        return {
            init() {
                teardown = wire(this.$el);
            },

            destroy() {
                teardown?.();
            },
        };
    });
}

function wire(button) {
    const input = () => document.getElementById(button.getAttribute('aria-controls'));

    function onClick() {
        const field = input();
        if (!field || field.disabled || field.readOnly) return;

        field.focus();
        if (field.value === '') return;

        field.value = '';
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    button.addEventListener('click', onClick);

    return () => button.removeEventListener('click', onClick);
}
