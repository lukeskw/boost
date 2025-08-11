@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

@if($assist->shouldEnforceStrictTypes())
- Always use strict typing at the head of a .php file: `declare(strict_types=1);`.
@endif
- Always use curly braces for control structures, even if it has one line.

## Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
<code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

## Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.
<code-snippet name="Explicit return types and method params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Use zero comments, unless there is something _very_ complex going on.

## PHPDoc blocks
- Add useful array shape definitions for arrays

## Enums
@if(empty($assist->enums()) || preg_match('/[A-Z]{3,8}/', $assist->enumContents()))
- Keys in an Enum should be UPPERCASE and words separated with an underscore. i.e. `FAVORITE_PERSON`, `BEST_LAKE`, `MONTHLY`
@else
- Keys in an Enum should follow existing Enum conventions.
@endif
