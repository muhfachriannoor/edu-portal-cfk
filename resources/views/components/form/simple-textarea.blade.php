{{-- 
    Blade Component: x-form.textarea

    Purpose:
    Renders a multi-line textarea input field.

    Usage Example:

    <x-form.textarea
        id="your_id"
        label="Your Label"
        :value="$your_value"           // (Optional) The textarea's content
        :disabled="true"               // (Optional) Disable the textarea
        :required="true"               // (Optional) Make the field required
    />

    Notes:
    - Supports standard Laravel validation via the 'required' attribute.
    - Use `old('your_id', $your_value)` in the backend to repopulate the field after validation failure.
--}}

@props([
    'id',
    'name',
    'label' => '',
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'value' => '',
])

@php
    $editorId = $id . '_editor'; // separate ID for the Quill container
@endphp

<div class="mb-5">
    @if($label)
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="{{ $id }}">
            {{ $label }} 
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <textarea id="{{ $id }}" name="{{ $name }}" {{ $required ? 'required' : '' }} {{ $readonly ? 'readonly' : '' }} {{ $disabled ? 'disabled' : '' }} class="w-full px-3 py-2 border rounded bg-white text-gray-800 border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:bg-gray-100 disabled:text-gray-400"  cols="30" rows="4">{{ old($id, $value) }}</textarea>

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">
            {{ $message }}
        </span>
    @enderror
</div>