{{-- 
    Blade Component: x-form.number

    Purpose:
    Renders a numeric input field with optional masking for formatting purposes (e.g., currency, separators).

    Mask Usage Guide:
    - Use :mask="'###-###'"  
        For a simple fixed pattern mask (e.g., phone numbers, codes).
    - Use :mask="['mask' => '#.###', 'options' => ['reverse' => true]]"  
        For advanced masking, such as currency formatting where numbers are entered right-to-left.
    - Use :mask="['options' => ['reverse' => true]]"  
        To only apply options (default mask pattern will be used internally).

    Usage Example:

    <x-form.number 
        id="your_id"
        label="Your Label"
        :value="$your_value"
        :mask="your_mask_options"
        :disabled="true"               // (Optional) Disable the select input
        :required="true"               // (Optional) Make the field required
    />

    Notes:
    - Supports standard Laravel validation rules (e.g., numeric, required).
    - Automatically preserves input with `old()` to keep the value after validation errors.
    - Useful for formatted numbers like prices, phone numbers, or codes with specific formats.
--}}

@props([
    'id',
    'label' => '',
    'value' => '',
    'disabled' => false,
    'required' => false,
    'type' => 'number',
    'mask' => ''
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
        inputmode="numeric"
        pattern="[0-9]*"
        step="0.01"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'w-full px-3 py-2 border rounded bg-white text-gray-800 border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:bg-gray-100 disabled:text-gray-400']) }}
    />

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">
            {{ $message }}
        </span>
    @enderror
</div>


@push('scripts')
    <script>
        @if($mask)
            $('#{{ $id }}').attr('type', 'text')
            @if(is_array($mask) && isset($mask['mask']))
                $('#{{ $id }}').mask('{{ $mask['mask'] }}', {!! json_encode($mask['options'] ?? []) !!});
            @else
                $('#{{ $id }}').mask('#');
            @endif            
        @endif
    </script>
@endpush