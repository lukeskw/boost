- Always use strict typing at the head of a .php file: `declare(strict_types=1);`.

## Constructors
- Use PHP 8 constructor property promotion in `__construct()`
<code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` with zero parameters.

## Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

## Comments
- Prefer PHPDoc blocks, otherwise use minimal-to-zero comments, unless there is something very complex going on.
