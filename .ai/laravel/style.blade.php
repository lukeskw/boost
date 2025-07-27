## Laravel Style Guidelines
- Every PHP file must start with `declare(strict_types=1);`
- Enforce strict typing: scalar types, return types, property types — everywhere.
- Strict array shapes only - no loose or untyped arrays.
- Use enums for fixed values.
- Never use mixed types - including in array shapes.
- Prefer basic DTOs over raw complex arrays when appropriate.
