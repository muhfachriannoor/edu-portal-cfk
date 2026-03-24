{{--
    Blade Component: x-form.multi-listbox

    Purpose:
    Renders a **multi-select listbox with badges** for selected items. 
    Provides an interactive UI with optional "Reset" and "Select All" functionality.

    Usage Example:

    <x-form.multi-listbox
        id="your_id"
        label="Your Label"
        :options="$your_option"        // Array of options: ['value' => 'label']
        :selected="$your_value"        // (Optional) The selected option's value
        :disabled="true"               // (Optional) Disable the select input
        :required="true"               // (Optional) Make the field required
    />

    Features:
    - **Multi-selection** with badge display for each selected item.
    - **Remove badges** individually via close (×) button.
    - **Keeps the dropdown open** while selecting/unselecting options.
    - Closes automatically when clicking outside.
    - **Reset button** appears when at least one option is selected.
    - Dark mode support via Tailwind CSS.
    - Handles **Laravel validation errors** automatically.

    Notes:
    - Use `old('categories', $selected)` in the backend to retain selections after validation errors.
    - Outputs hidden `<input>` fields for each selected option to ensure proper form submission.
--}}

@props([
    'id',
    'label' => '',
    'options' => [],
    'selected' => [],
    'disabled' => false,
    'required' => false
])

@php
    $selectedValues = old($id, $selected);
    if (!is_array($selectedValues)) {
        $selectedValues = $selectedValues ? [$selectedValues] : [];
    }
@endphp

<div 
    x-data="multiListboxComponent()" 
    x-init='init(@json($options), @json($selectedValues), @json($label))'
    @click.away="close()"
    class="mb-5 relative"
>
    @if($label)
        <label class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <template x-for="val in selected">
        <input type="hidden" name="{{ $id }}[]" :value="val">
    </template>

    <button 
        type="button"
        @click="toggle()"
        :disabled="{{ $disabled ? 'true' : 'false' }}"
        class="w-full px-3 py-2 text-sm rounded border bg-white text-gray-700 border-gray-300 
               focus:outline-none focus:ring-0 focus:border-blue-500
               dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600 
               disabled:bg-gray-100 disabled:text-gray-400 dark:disabled:bg-gray-700 dark:disabled:text-gray-500 
               transition ease-in-out duration-150 relative flex items-center justify-between"
    >
        <div class="flex flex-wrap gap-1 items-center">
            <template x-if="!selectedLabels.length">
                <span class="text-gray-400">Select {{ $label }}</span>
            </template>

            <template x-for="(text, index) in selectedLabels" :key="index">
                <span class="bg-gray-200 text-gray-700 font-bold px-2 py-1 rounded-full text-xs flex items-center gap-1">
                    <span x-text="text"></span>
                    <button 
                        type="button" 
                        @click.stop="removeByIndex(index)"
                        class="hover:text-red-500 text-gray-700"
                    >
                        &times;
                    </button>
                </span>
            </template>
        </div>

        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <ul 
        x-show="open" 
        x-transition 
        class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded shadow-lg max-h-60 overflow-y-auto"
    >
        <template x-for="opt in options" :key="opt.key">
            <li 
                @click="toggleSelect(opt.key, opt.text)"
                :class="selected.includes(opt.key) ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600'"
                class="cursor-pointer px-4 py-2 text-sm flex items-center justify-between"
            >
                <span x-text="opt.text"></span>
                <svg x-show="selected.includes(opt.key)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </li>
        </template>
    </ul>

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            function multiListboxComponent() {
                return {
                    open: false,
                    options: [],
                    selected: [],
                    selectedLabels: [],

                    init(opts, selected, label) {
                        const optionsObject = opts || {};

                        this.options = [
                            ...Object.entries(optionsObject).map(([key, text]) => ({ key: Number(key), text }))
                        ];

                        this.selected = (selected || []).map(v => Number(v));

                        this.label = label;

                        this.updateLabels();
                    },

                    toggle() { this.open = !this.open },
                    close() { this.open = false },

                    toggleSelect(key, text) {
                        key = Number(key);
                        const idx = this.selected.indexOf(key);
                        if (idx === -1) {
                            this.selected.push(key);
                        } else {
                            this.selected.splice(idx, 1);
                        }
                        this.updateLabels();
                    },

                    updateLabels() {
                        this.selectedLabels = this.options
                            .filter(opt => this.selected.includes(opt.key))
                            .map(opt => opt.text);
                    },
                    
                    removeByIndex(index) {
                        this.selected.splice(index, 1);
                        this.updateLabels();
                    }
                }
            }
        </script>
    @endpush
@endonce
