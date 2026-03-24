{{-- 
    Blade Component: x-form.text

    Purpose:
    Renders a single-line text input field.

    Usage Example:

    <x-form.text 
        id="your_id"
        label="Your Label" 
        :value="$your_value" 
        :disabled="true"               // (Optional) Disable the select input
        :required="true"               // (Optional) Make the field required
    />

    Notes:
    - Supports standard Laravel validation via the 'required' attribute.
    - Use `old('your_id', $your_value)` in the backend to preserve the previous input value after form submission.
--}}

@props([
    'id' => '',
    'label' => '',
    'value' => '',
    'disabled' => false,
    'required' => false,
    'type' => 'text',
    'readonly' => false,
    'notes' => '',
    'notesType' => 'info', // info | warning | error | success
    'uppercase' => false,
])

<div class="mb-5">
    @if($label)
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="{{ $id }}">
            {{ $label }} 
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input 
        id="{{ $id }}" 
        name="{{ $id }}"
        type="{{ $type }}"
        value="{{ old($id, $value) }}"
        {{ $required ? 'required' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge([
            'class' => 'w-full px-3 py-2 border rounded bg-white text-gray-800 border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:bg-gray-100 disabled:text-gray-400'
                . ($uppercase ? ' js-uppercase-input uppercase' : ''),
        ]) }}
    />
    
    @if($notes)

        @php
            // Define colors and icons based on note type
            $noteColor = match($notesType) {
                'warning' => 'text-amber-600 dark:text-amber-400',
                'error' => 'text-red-500',
                'success' => 'text-green-600 dark:text-green-400',
                default => 'text-gray-500 dark:text-gray-400',
            };

            $noteIcon = match($notesType) {
                'warning' => '<i class="fas fa-exclamation-triangle text-yellow-500"></i>',
                'error'   => '<i class="fas fa-times-circle text-red-500"></i>',
                'success' => '<i class="fas fa-check-circle text-green-500"></i>',
                default   => '<i class="fas fa-info-circle text-blue-500"></i>',
            };
        @endphp

        <div class="flex items-start mt-1 space-x-1 {{ $noteColor }}">
            <span>{!! $noteIcon !!}</span>
            <span class="text-sm">{{ $notes }}</span>
        </div>
    @endif

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">
            {{ $message }}
        </span>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('input', function (e) {
                if (e.target.classList && e.target.classList.contains('js-uppercase-input')) {
                    e.target.value = e.target.value.toUpperCase();
                }
            });
        </script>
    @endpush
@endonce