## Key Changes from Livewire 2
- These changed in Livewire 2, but may not have been updated in this project. Verify this project's setup to ensure you conform with project conventions.
- **Wire:model**: Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
- **Namespace**: Components now use `App\Livewire` (not `App\Http\Livewire`).
- **Events**: Use `$this->dispatch()` (not `emit` or `dispatchBrowserEvent`).
- **Layout path**: `components.layouts.app` (not `layouts.app`).

## New directives
- `wire:show`, `wire:transition`, `wire:cloak, `wire:offline`, `wire:target` are available for use. Use the docs to find usages.

## Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins built in to Alpine: persist, intersect, collapse, and focus.

## Lifecycle hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:
@verbatim
<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>
@endverbatim
