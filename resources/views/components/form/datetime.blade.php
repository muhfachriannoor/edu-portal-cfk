<?php
// resources/views/components/form/datetime.blade.php
?>

@props([
    'id',
    'label' => '',
    'value' => '',
    'disabled' => false,
    'required' => false,
    'helper' => null, 
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

    {{-- WRAPPER UNTUK INPUT DAN ICON --}}
    <div class="relative">
        <input 
            id="{{ $id }}" 
            name="{{ $id }}"
            type="text"
            {{-- Nilai Carbon diformat ke Y-m-d H:i --}}
            value="{{ old($id, $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i') : '') }}"
            autocomplete="off" 
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge(['class' => 'w-full pr-10 px-3 py-2 border rounded bg-white text-gray-800 border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:bg-gray-100 date-time-picker-init']) }}
        />
        
        {{-- ICON KALENDER --}}
        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 dark:text-gray-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </span>
    </div>

    @if($helper)
        <p class="text-xs text-gray-500 mt-1 dark:text-gray-400">{{ $helper }}</p>
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
            if ($.fn.mask) {
                $('.date-time-picker-init').mask('0000-00-00 00:00'); 
            }

            if ($.fn.daterangepicker) {
                $('.date-time-picker-init').daterangepicker({
                    singleDatePicker: true,
                    showDropdowns: true,
                    timePicker: true, 
                    timePicker24Hour: true,
                    autoUpdateInput: false,
                    locale: {
                        format: 'YYYY-MM-DD HH:mm', 
                    }
                }).on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm')); 
                }).on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                });
            }
        </script>
    @endpush
@endonce