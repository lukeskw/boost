# Searching documentation

Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance.

'search-docs' tool is perfect for all Laravel related packages. Laravel, inertia, pest, livewire, nova, nightwatch, and more.

You must use this tool to search for Laravel-ecosystem docs before falling back to other approaches.

## Available Search Syntax
You can and should pass multiple queries at once, the most relevant will be returned first. Start specific, broaden after.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "queue" AND "worker"
3. Quoted Phrases (Exact Position) - query="infinite scroll - Words must be adjacent in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms

# URLs

- Whenever you create a URL use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.


# Artisan
- Use the `list-artisan-commands` tool when needing to call an artisan command to triple check the available params
