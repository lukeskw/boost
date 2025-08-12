#### Key Changes from Livewire 2
- These changed in Livewire 2, but may not have in this project. Verify this project's setup to ensure you conform with conventions.

- **Namespace**: Components now use `App\Livewire` (not `App\Http\Livewire`).
- **Events**: Use `$this->dispatch()` (not `emit` or `dispatchBrowserEvent`).
- **Layout path**: `components.layouts.app` (not `layouts.app`).
- **Deferred by default**: Use `wire:model.live` for real-time updates.
- **Alpine included**: Don't manually include Alpine.js.
