# Laravel Boost Guidelines
The Laravel Boost Guidelines are specifically curated by Laravel maintainers for this project. These guidelines should be followed closely to help enhance the user's experience and satisfaction.

# Foundational Context
This project is a Laravel app and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure we abide by these specific packages & versions.

- php - {{ PHP_VERSION }}
@foreach (app(\Laravel\Roster\Roster::class)->packages()->unique(fn ($package) => $package->rawName()) as $package)
- {{ $package->rawName() }} ({{ $package->name() }}) - v{{ $package->majorVersion() }}
@endforeach

@if(!empty(config('boost.project_purpose')))
    Project purpose: {!! config('boost.project_purpose') !!}
@endif

## Conventions
- You must follow all existing code conventions used in this project. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names. e.g. `isRegisteredForDiscounts` not `discount()`
- Always use strict typing: declare(strict_types=1);

## Project Structure & Architecture
- Stick to existing directory structure - no new base folders without approval.
- No dependency changes without approval.

## Constructors
- Use PHP 8 constructor property promotion in `__construct()`
<code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` with zero parameters.

## Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

## Comments
- Prefer PHPDoc blocks, otherwise use minimal-to-zero comments, unless there is something very complex going on.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation files
- You must only create documentation files if explicitly requested by the user.

