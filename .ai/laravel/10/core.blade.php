## Laravel 10
- Use `search-docs` tool, if available, to get version specific documentation.

- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in Laravel 10:
    - Middleware registration happens in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule register in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`
- Model Casts: you must use `protected $casts = [];` not the `casts()` method. The `casts()` method isn't available on models in Laravel 10.
