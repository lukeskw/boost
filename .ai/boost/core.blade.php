## Boost
- Boost MCP comes with powerful tools designed specifically for this application. Use them.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an artisan command to triple check the available parameters.

## Tinker / Debugging
- You should use the `tinker` tool from Boost MCP when you need to run PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

@if(config('boost.browser_logs', true) !== false || config('boost.browser_logs_watcher', true) !== false)
## Reading browser logs with the `browser-logs` tool
- You can read browser logs, errors, and exceptions with the `browser-logs` tool from Boost.
- Only recent browser logs will be useful, ignore old logs.
@endif

## Searching documentation (critically important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter docs on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages. Laravel, Inertia, Pest, Livewire, Nova, Nightwatch, etc..
- You must use this tool to search for Laravel-ecosystem docs before falling back to other approaches.
- Search the docs before making code changes to ensure we are approaching this in the correct way.
- Use multiple broad simple topic based queries to start, i.e. `rate limiting##routing rate limiting##routing`.

### Available search syntax
- You can and should pass multiple queries at once, the most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms

