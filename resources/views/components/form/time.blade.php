{{-- 
    Blade Component: x-form.time

    Purpose:
    Renders a time input field (HH:MM format).

    Usage Example:

    <x-form.time 
        id="your_field_id"
        label="Your Label"
        :value="$your_value"           // (Optional) Pre-select a time value in "HH:MM" format
        :disabled="true"               // (Optional) Disable the time input field
        :required="true"               // (Optional) Make the field required
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
    'type' => 'text',
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
        type="{{ $type }}"
        value="{{ old($id, $value) }}"
        autocomplete="off" 
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'w-full px-3 py-2 border rounded bg-white text-gray-800 border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:bg-gray-100 time-picker']) }}
    />

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">
            {{ $message }}
        </span>
    @enderror

    @once
        @push('scripts')
            <script>
                $('.time-picker').mask('00:00');

                flatpickr(".time-picker", {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i", // 24-hour format with seconds (HH:mm:ss)
                    time_24hr: true,
                    allowInput: true,
                    onOpen: function(selectedDates, dateStr, instance) {
                        if (!instance.input.value) {
                        const now = new Date();
                        instance.setDate(now, true); // true = trigger change event
                        }
                    }
                });
            </script>
        @endpush
    @endonce
</div>