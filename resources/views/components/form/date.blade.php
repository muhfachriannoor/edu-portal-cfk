{{-- 
    Blade Component: x-form.date

    Purpose:
    Renders a date input field (YYYY-MM-DD format).

    Usage Example:

    <x-form.date 
        id="your_field_id"
        label="Your Label" 
        :value="$your_value"   // (Optional) Pre-select a date value in format: "YYYY-MM-DD"
        :disabled="true"       // (Optional) Disable the date input field
        :required="true"       // (Optional) Make the date input required
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
    'required' => false
])

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
        value="{{ old($id, $value) }}"
        autocomplete="off" 
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'w-full px-3 py-2 border rounded bg-white text-gray-800 border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:bg-gray-100 date-picker']) }}
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
            $('.date-picker').mask('0000-00-00');

            $('.date-picker').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                }
            }).on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD'));
            });
        </script>
    @endpush
@endonce