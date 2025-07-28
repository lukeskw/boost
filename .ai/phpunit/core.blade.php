## PHPUnit Core

- We are using PHPUnit for testing - if you see an example using Pest as part of a prompt, convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, make sure to also run the entire test suite to ensure things are still ok.
- Tests should test all of the the unhappy paths, happy paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.


# Running tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- Run all tests: `php artisan test`
- Run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`
- Filter on particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file)
- When the tests relating to your feature are passing, make sure to also run the entire test suite to ensure things are still ok.
