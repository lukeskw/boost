## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

## Inertia Forms Core
@if($assist->inertia()->hasFormComponent())
- The recommended way to build forms when using Inertia is with the `<Form>` component, a useful example is below. Use `search-docs` with the `form component` query for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with the `useForm helper` query for guidance.
@if($assist->inertia()->hasFormComponentResets())
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with 'form component resetting' for explicit guidance.
@else
- This version of Inertia does NOT support `resetOnError`, `resetOnSuccess`, or `setDefaultsOnSuccess` on the `<Form>` component. Using these will cause errors.
@endif
@else
- Build forms using the `useForm` helper. Use the code examples and `search-docs` tool with the `useForm helper` query for guidance.
@endif
