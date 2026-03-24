{{-- 
    Blade Component: x-form.daterange

    Purpose:
    Renders a date input field (YYYY-MM-DD - YYYY-MM-DD format).

    Usage Example:

    <x-form.daterange 
        id="your_field_id"
        label="Your Label" 
        :value="$your_value"   // (Optional) Pre-select a date range in format: "YYYY-MM-DD - YYYY-MM-DD"
        :disabled="true"       // (Optional) Disable the date range input
        :required="true"       // (Optional) Make the date range input required
    />

    Notes:
    - Supports standard Laravel validation via the 'required' attribute.
    - Use `old('your_field_id', $your_value)` in the backend to preserve the previous input after form submission.
--}}

@props([
    'id',
    'label' => '',
    'value' => '',
    'disabled' => false,
    'required' => false,
])

@php
    // Default value: if not set, show empty input
    $displayValue = old($id, $value);
@endphp

<div class="mb-5">
    @if($label)
        <label class="block text-sm font-medium mb-1" for="{{ $id }}">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input 
        id="{{ $id }}" 
        name="{{ $id }}"
        type="text"
        value="{{ $displayValue }}"
        autocomplete="off"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'w-full px-3 py-2 border rounded bg-white text-gray-800 border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:bg-gray-100 daterange-picker']) }}
    />

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">
            {{ $message }}
        </span>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            $('.daterange-picker').mask('0000-00-00 - 0000-00-00');
            
            $('.daterange-picker').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                    cancelLabel: 'Clear'
                }
            });

            $('.daterange-picker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            });

            $('.daterange-picker').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        </script>
    @endpush
@endonce
