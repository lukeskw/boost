## Laravel 12
- Use `search-docs` tool, if available, to get version specific documentation.

@if (file_exists(base_path('app/Http/Kernel.php')))
{{-- Migrated from L10 to L12, but did't migrate to the new L11 Structure --}}
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is **perfectly fine** and recommended by Laravel. Follow the existing structure from Laravel 10. We do not to need migrate to the new Laravel structure unless the user explicitly requests that.

### Laravel 10 Structure
- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
- Middleware registration happens in `app/Http/Kernel.php`
- Exception handling is in `app/Exceptions/Handler.php`
- Console commands and schedule register in `app/Console/Kernel.php`
- Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`
@else
{{-- Laravel 12 project anew, or upgraded & migrated structure --}}
- Laravel brought a new streamlined file structure which this project uses.

### Laravel file Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` for project specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configurations.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available.
@endif



