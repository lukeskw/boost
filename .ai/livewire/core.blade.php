## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire.


## Livewire Best Practices
- **Single root element** in Blade components
- **Loading states**: Use `wire:loading` and `wire:dirty`.
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
