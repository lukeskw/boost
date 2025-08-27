@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

## Forms in Inertia

@if($assist->inertia()->hasFormComponent())
- The recommended way to build forms when using Inertia is with the `<Form>` component. Use `search-docs` with `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with `useForm helper` for guidance.

@boostsnippet("Example form using the `<Form>` component", "react")
import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
        errors,
        hasErrors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        setError,
        clearErrors,
        resetAndClearErrors,
        defaults,
        isDirty,
        reset,
        submit,
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)
@endboostsnippet

    @if($assist->inertia()->hasFormComponentResets())
    - Added `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` to the `<Form>` component. Use `search-docs` with 'form component resetting' for explicit guidance.
    @else
    - This version of Inertia does NOT support `resetOnError`, `resetOnSuccess`, or `setDefaultsOnSuccess` on the `<Form>` component.
    @endif
@endif

@if($assist->inertia()->hasFormComponent() === false)
            - Forms can be built using the `useForm` helper. Use `search-docs` with `useForm helper` for guidance.
@endif
