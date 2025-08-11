# Laravel Boost Guidelines
The Laravel Boost Guidelines are specifically curated by Laravel maintainers for this project. These guidelines should be followed closely to help enhance the user's experience and satisfaction.

## Foundational Context
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
- Use descriptive names. For example, `isRegisteredForDiscounts` not `discount()`.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Project Structure and Architecture
- Stick to existing directory structure - no new base folders without approval.
- No dependency changes without approval.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

