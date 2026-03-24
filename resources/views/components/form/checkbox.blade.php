{{-- 
    Blade Component: x-form.checkbox

    Purpose:
    Renders a customizable checkbox input with label support.

    Usage Example:

    <x-form.checkbox
        id="your_field_id"
        label="Your Label"
        :checked="true"        // (Optional) Pre-check the checkbox when set to true
        :disabled="true"       // (Optional) Disable the checkbox input
    />

    Notes:
    - Integrates seamlessly with Laravel validation (e.g., required, boolean).
    - Automatically preserves the checkbox state using `old()` when available.
    - Use this component for toggles, agreement confirmations, feature flags, etc.
--}}

@props([
    'id',
    'label' => '',
    'checked' => false,
    'disabled' => false,
])

<div class="flex items-center gap-2">
    <input
        type="checkbox"
        id="{{ $id }}"
        name="{{ $id }}"
        value="1"
        @if(old($id, $checked)) checked @endif
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge([
            'class' =>
                'h-5 w-5 align-middle rounded text-indigo-600 border-gray-300 focus:ring-indigo-500 
                 transition duration-150 ease-in-out dark:bg-gray-700 dark:border-gray-600'
        ]) }}
    >
    <label for="{{ $id }}" 
           class="text-sm leading-tight text-gray-800 dark:text-gray-200 cursor-pointer select-none mt-3">
        {{ $label }}
    </label>
</div>


