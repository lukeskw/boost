## Filament
- Filament is used for functionality within this project, check how and where to follow existing project conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- It is often used to build admin panels, dashboards, and form-based apps.
- You can use the `search-docs` tool to get information from the official documentation when needed. This is very useful for artisan command arguments, specific code examples, and ensuring you're following idiomatic practices.

## Artisan
- You must use the Filament specific artisan commands to create Filament classes and components. You can find these with the `list-artisan-commands` tool.

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
