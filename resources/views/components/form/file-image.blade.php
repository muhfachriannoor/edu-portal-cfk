{{-- 
    Blade Component: x-form.file-image

    Purpose:
    Renders an image upload input. Displays the uploaded image or a default image if provided.

    Usage Example:

    <x-form.file-image 
        id="your_field_id"
        label="Your Label"
        :default="$your_value"         // (Optional) The default image URL or path to display
        :disabled="true"               // (Optional) Disable the file input
        :required="true"               // (Optional) Make the field required
    />

    Notes:
    - Accepts only image files.
    - Shows a preview of the selected image.
    - Use this for forms where users need to upload or update images.
--}}

@props([
    'id' => 'image',
    'label' => 'File Image',
    'required' => false,
    'default' => null,
    'maxSize' => '2MB',
    'ratio' => '1:1',
    'minResolution' => null,
    'formats' => '.jpeg, .jpg, .png',
])

<div class="mb-5">
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">
        {{ $label }} @if($required) <span class="text-red-500">*</span> @endif
    </label>

    <input
        type="file"
        name="{{ $id }}"
        id="{{ $id }}"
        data-allowed-file-extensions="jpg png jpeg"
        accept="image/png, image/jpeg"
        @if($default) data-default-file="{{ $default }}" @endif
        data-show-remove="true"
        {{ $attributes->merge(['class' => 'dropify']) }}
    />

    <small class="text-gray-500 dark:text-gray-400 block mt-1">
        <span class="font-semibold">Format:</span> {{ $formats }} | 
        <span class="font-semibold">Max Size:</span> {{ $maxSize }} |
        @if ($minResolution != null)
            <span class="font-semibold">Min Resolution:</span> {{ $minResolution }} |
        @endif
        <span class="font-semibold">Ratio:</span> {{ $ratio }} (Suggested)
    </small>

    @error($id)
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>