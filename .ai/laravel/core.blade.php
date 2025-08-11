## Do Things the Laravel Way
- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.).
- If you're creating a generic PHP class, use `artisan make:class`.

## Database
- **Model relationships**: Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- **Eloquent first approach**: Use Eloquent models and relationships before suggesting raw database queries - Avoid `DB::`; use `Model::query()` only. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- **Form request validation**: Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- **DB N+1**: Generate code that prevents N+1 query problems by using eager loading.
- For DB pivot tables, use correct alphabetical order, like "project_role" instead of "role_project"
- Use Laravel's query builder for very complex database operations.

## Model Creation
- When creating new models, create factories and seeders for them too. Ask the user if they need any other things, use `list-artisan-commands` to check the available options to `php artisan make:model`

## APIs and Eloquent Resources
- For APIs, use Eloquent API Resources and API versioning

## Queues
- **Job and queue patterns**: Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Authentication and Authorization
- Use Laravel built-in authentication and authorization features (Gates, Policies, Sanctum)

## Config
- **Use environment variables** via config files, never `env()` directly. Always use `config('app.name')` not `env('APP_NAME')`.

## Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.

## Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## URL Generation
- When generating links to other pages, always prefer named routes and the `route()` function.
