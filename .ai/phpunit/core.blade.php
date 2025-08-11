## PHPUnit Core

- We are using PHPUnit for testing. All tests must be written as PHPUnit classes.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they'd like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the unhappy paths, happy paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.


# Running tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- Run all tests: `php artisan test`.
- Run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- Filter on particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
