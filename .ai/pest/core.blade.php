# Pest tests
- All tests must be written using Pest.
- You must not remove any tests without approval.
- Tests live in the `tests/Feature` and `tests/Unit` directories
- Pest tests look and behave like this:
<code-snippet lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

# Running tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- Run all tests: `php artisan test`
- Run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`
- Filter on particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file)
- When the tests relating to your feature are passing, make sure to also run the entire test suite to ensure things are still ok.

## Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden`, `assertNotFound` etc, instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet>
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

## Mocking
- Mocking is very helpful.
- When mocking, you can use the pest function `Pest\Laravel\mock`, and always import it before usage with `use function Pest\Laravel\mock;` or you can use `$this->mock()`.
- You can also create partial mocks using the same import or self method.

## Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This if for example often the case when testing validation rules, so often go with the solution of using datasets when writing tests for validation rules.

<code-snippet lang="php" package="pest">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>
