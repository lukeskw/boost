## Filament
- Filament is used for functionality within this project, check how and where to follow existing project conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official documentation when needed. This is very useful for artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.

## Artisan
- You must use the Filament specific artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and other options with valid arguments.

## Filament's Core Features
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only list of data.
- Actions: Handle doing something within the app, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Notifications: Flash notifications to users within the app.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

## Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the app within the test.
- Filament uses Livewire so start assertions with `livewire()` or `Livewire::test()`.

### Example tests
@verbatim
<code-snippet name="Filament table test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament create resource test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing multiple panels (setup())" lang="php">
    use Filament\Facades\Filament;
    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an action in a test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');
    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>
@endverbatim

## Relationships
- Check if you can use the `relationship()` method on form components when needing `options` for a select, checkbox, repeater, or when building a Fieldset:
@verbatim
<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>
@endverbatim
