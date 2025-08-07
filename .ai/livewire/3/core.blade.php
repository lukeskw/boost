
#### Key Changes from Livewire 2

- **Namespace**: Components now use `App\Livewire` (not `App\Http\Livewire`)
- **Events**: Use `$this->dispatch()` (not `emit` or `dispatchBrowserEvent`)
- **Layout path**: `components.layouts.app` (not `layouts.app`)
- **Deferred by default**: Use `wire:model.live` for real-time updates
- **Alpine included**: Don't manually include Alpine.js

#### Livewire Best Practices

- **Single root element** in Blade components
- **Add wire:key** in loops:

@verbatim
```blade
@foreach ($items as $item)
    <div wire:key="item-{{ $item->id }}">
        {{ $item->name }}
    </div>
@endforeach
```
@endverbatim

- **Use attributes** for event listeners:

```php
#[On('todo-created')]
public function refreshList()
{
// ...
}
```

- **Loading states**: Use `wire:loading` and `wire:dirty`
- **Confirmations**: Use `wire:confirm="Are you sure?"`
