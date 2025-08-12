- Always use Tailwind CSS v4, do not use the deprecated utilities.
- In Tailwind v4 you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:
@verbatim
<code-snippet name="Tailwind v4 import tailwind diff" lang="diff"
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>
@endverbatim
- `corePlugins` is not supported in v4.

## Replaced utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option, use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |

