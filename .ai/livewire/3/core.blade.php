## Livewire 3

### Key Changes From Livewire 2

- **Namespace**: Components now use `App\Livewire` (not `App\Http\Livewire`).
- **Events**: Use `$this->dispatch()` (not `emit` or `dispatchBrowserEvent`).
- **Layout Path**: `components.layouts.app` (not `layouts.app`).
- **Deferred by Default**: Use `wire:model.live` for real-time updates.
- **Alpine Included**: Don't manually include Alpine.js.

### Livewire Best Practices

- Always use a **single root element** in Blade components.
- Always add `wire:key` in loops to prevent DOM merging errors.

@verbatim
```blade
@foreach ($items as $item)
    <div wire:key="item-{{ $item->id }}">
        {{ $item->name }}
    </div>
@endforeach
```
@endverbatim

- Use attributes to configure Livewire event listeners:

```php
#[On('todo-created')]
public function refreshList()
{
// ...
}
```

- Use `wire:loading` and `wire:dirty` to configure loading states when applicable.
- Use something like `wire:confirm="Are you sure?"` to confirm destructive actions.
