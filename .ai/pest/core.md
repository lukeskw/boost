- You must write Pest tests. You must NOT write PHPUnit tests.
- You can run the tests with `vendor/bin/pest` or `artisan test`
- Pest tests look and behave like this:
```php
<?php

declare(strict_types=1);

it('is true', function () {
    expect(true)->toBeTrue();
});
```
