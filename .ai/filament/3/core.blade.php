## Filament 3

## Version 3 Changes To Focus On
- Resources are located in `app/Filament/Resources/` directory.
- Resource pages (List, Create, Edit) are auto-generated within the resource structure.
- Forms use the `Forms\Components` namespace for form fields.
- New RichEditor component available (`Filament\Forms\Components\RichEditor`) instead of Markdown Editor.
- Tables use the `Tables\Columns` namespace for table columns.
- Admin panel accessible at `/admin` by default.
- Form and table schemas now use fluent method chaining.
- Added `php artisan filament:optimize` command for production optimization.
- Requires implementing `FilamentUser` contract for production access control.
- New RichEditor component (Filament\Forms\Components\RichEditor) is available,
