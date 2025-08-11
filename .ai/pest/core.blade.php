## Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

# Pest Tests
- All tests must be written using Pest.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.
- Tests should test all of the unhappy paths, happy paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic example Pest test" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

# Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing code edits.
- Run all tests: `php artisan test`.
- Run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- Filter on particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they'd like to run the entire test suite to ensure everything is still passing.

## Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden`, `assertNotFound` etc, instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest asserting postJson response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

## Mocking
- Mocking can be very helpful.
- When mocking, you can use the pest function `Pest\Laravel\mock`, and always import it before usage with `use function Pest\Laravel\mock;`. Alternatively you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

## Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This often the case when testing validation rules, so often go with the solution of using datasets when writing tests for validation rules.

<code-snippet name="Pest dataset example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>
