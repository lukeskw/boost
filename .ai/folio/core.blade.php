
- Laravel Folio is a file based router. With Laravel Folio, a new route is creatted for every Blade file within the correct directory. i.e. `Pages` are usually in in `resources/views/pages/` and the file structure determines routes:
- `pages/index.blade.php` → `/`
- `pages/profile/index.blade.php` → `/profile`
- `pages/auth/login.blade.php` → `/auth/login`
- List available Folio routes using `php artisan folio:list` or using Boost's `list-routes` tool.

### Folio: New pages & routes
- Always create new `folio` pages and routes using `artisan folio:page [name]` following existing naming conventions.

@verbatim
<code-snippet name="Example folio:page commands for automatic routing" lang="shell">
    // Creates: resources/views/pages/products.blade.php → /products
    php artisan folio:page 'products'


    // Creates: resources/views/pages/products/[id].blade.php → /products/{id}
    php artisan folio:page 'products/[id]'
</code-snippet>
@endverbatim

- Add a 'name' to each new Folio page at the very top of the file, so it has a named route available for other parts of the codebase to use.
@verbatim
<code-snippet name="Adding named route to Folio page" lang="php">
use function Laravel\Folio\name;

name('products.index');
</code-snippet>
@endverbatim


### Folio: Support & Docs
- Folio supports: middleware, serving pages from multiple paths, subdomain routing, named routes, nested routes, index routes, route parameters, and route model binding.
- If available, use Boost's `search-docs` tool to use Folio to its full potential and help the user effectively.

@verbatim
<code-snippet name="Folio middleware example" lang="php">
use function Laravel\Folio\{name, middleware};

name('admin.products');
middleware(['auth', 'verified', 'can:manage-products']);
?>
</code-snippet>
@endverbatim
