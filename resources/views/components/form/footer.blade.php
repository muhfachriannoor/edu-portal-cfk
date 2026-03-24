{{-- 
    Blade Component: x-form.footer

    Purpose:
    Renders form footer actions such as Back, Submit, or Edit buttons based on the current mode.

    Usage Example:

    <x-form.footer
        :mode="$mode"  // Accepts 'create', 'edit', or 'show'. Controls which buttons appear.
        :editUrl="optional($admin)->id ? route($resourceName.'.edit', $admin->id) : null"  // (Optional) Provide edit URL when in 'show' mode.
        :backUrl="route($resourceName.'.index')" // URL to return to the listing or previous page.
    />
--}}

@props([
    'mode', // expected: 'create', 'edit', or 'show'
    'editUrl' => null,
    'backUrl',
])

<div class="px-6 py-4 border-t text-end space-x-2">

    <a href="{{ $backUrl }}"
       class="inline-block bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded shadow"
       title="Back">Back</a>
       
    @if(in_array($mode, ['create', 'edit']))
        <button type="submit"
            class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded shadow">
            Submit
        </button>
    @endif

    @if($mode === 'show' && $editUrl)
        <a href="{{ $editUrl }}"
           class="inline-block bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded shadow"
           title="Edit">Edit</a>
    @endif
</div>
