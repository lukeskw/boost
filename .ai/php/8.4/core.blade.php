## PHP 8.4 has new array functions that will make code simpler whenever we don't use Collections

- `array_find(array $array, callable $callback): mixed` - Find first matching element
- `array_find_key(array $array, callable $callback): int|string|null` - Find first matching key
- `array_any(array $array, callable $callback): bool` - Check if any element satisfies a callback function
- `array_all(array $array, callable $callback): bool` - Check if all elements satisfy a callback function

## Make use of cleaner chaining on new instances
<code-snippet name="No extra parentheses needed for chaining on new instances" lang="php">
// Before
$response = (new JsonResponse(['data' => $data]))->setStatusCode(201);

// After
$response = new JsonResponse(['data' => $data])->setStatusCode(201);
</code-snippet>
