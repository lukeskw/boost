## InertiaJS Core

- Inertia.js components should be placed in the `resources/js/Pages` directory
- Use `Inertia::render()` for server-side routing instead of traditional Blade views
<code-snippet lang="php" name="Inertia::render example">
    // routes/web.php example
    Route::get('/users', function () {
    return Inertia::render('Users/Index', [
    'users' => User::all()
    ]);
    });
</code-snippet>
