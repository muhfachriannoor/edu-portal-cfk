{{-- 
    Blade Component: x-form.listbox

    Purpose:
    Renders a listbox (select dropdown) input with customizable options.

    Usage Example:

    <x-form.listbox 
        id="your_id"                   
        label="Your Label"                   
        :options="$your_option"        // Array of options: ['value' => 'label']
        :selected="$your_value"        // (Optional) The selected option's value
        :disabled="true"               // (Optional) Disable the select input
        :required="true"               // (Optional) Make the field required
    />
    
    Notes:
    - Supports standard Laravel validation via 'required' attribute.
    - Use `old('your_id', $your_value)` in the backend for pre-selecting values if needed.
--}}

@props([
    'id',
    'label' => '',
    'options' => [],
    'selected' => null,
    'disabled' => false,
    'required' => false,
    'use_key' => true
])

@php
    $selectedValue = old($id, $selected);
    $selectedLabel = $selectedValue && isset($options[$selectedValue]) 
        ? $options[$selectedValue] 
        : 'Select ' . $label;
@endphp

<div 
    x-data="listboxComponent()" 
    x-init='init(@json($options), @json($selectedValue), @json($label))'
    data-use-key="{{ $use_key ? 'true' : 'false' }}"
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

    <input 
        x-ref="input"
        type="hidden" 
        id="{{ $id }}" 
        name="{{ $id }}" 
        x-model="selected" 
        :value="selected"
        {{ $disabled ? 'disabled' : '' }} 
        {{ $required ? 'required' : '' }}
    >

    <button 
        type="button"
        @click="toggle()"
        @click.away="close()"
        :disabled="{{ $disabled ? 'true' : 'false' }}"
        class="w-full px-3 py-2 text-sm rounded border bg-white text-gray-700 border-gray-300 
               focus:outline-none focus:ring-0 focus:border-blue-500
               dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600 
               disabled:bg-gray-100 disabled:text-gray-400 dark:disabled:bg-gray-700 dark:disabled:text-gray-500 
               transition ease-in-out duration-150 relative flex items-center justify-between"
    >
        <span x-text="selectedLabel"></span>
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div @click.stop @keydown.window="handleKeydown($event)">
        <ul 
            x-show="open" 
            x-transition 
            class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded shadow-lg max-h-60 overflow-y-auto"
        >
            <!-- Search box -->
            <div class="p-2 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800">
                <input 
                    type="text" 
                    x-model="search"
                    placeholder="Search..."
                    class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:outline-none focus:ring-0"
                >
            </div>

            <!-- Options -->
            <template x-for="(opt, index) in filteredOptions" :key="opt.key">
                <li 
                    @click="choose(opt.key, opt.text)"
                    :class="{
                        'bg-blue-500 text-white': selected == opt.key,
                        'bg-blue-100 dark:bg-blue-700': highlightedIndex == index && selected != opt.key,
                        'hover:bg-blue-500 hover:text-white dark:hover:bg-blue-600': true
                    }"
                    class="cursor-pointer px-4 py-2 text-sm"
                    x-text="opt.text"
                ></li>
            </template>
        </ul>
    </div>

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            function listboxComponent() {
                return {
                    open: false,
                    options: [],
                    selected: '',
                    selectedLabel: '',
                    search: '',
                    labelText: '',
                    highlightedIndex: -1,

                    init(opts, selected, label) {
                        this.labelText = `Select ${label}`;

                        const optionsObject = opts || {};
                        const useKey = this.$el.getAttribute('data-use-key') === 'true';

                        // Build options
                        if (Array.isArray(optionsObject)) {
                            this.options = [
                                { key: "", text: `Select ${label}` },
                                ...optionsObject.map((val, index) => ({
                                    key: useKey ? index : val,
                                    text: val
                                }))
                            ];
                        } else {
                            this.options = [
                                { key: "", text: `Select ${label}` },
                                ...Object.entries(optionsObject).map(([key, text]) => ({ key, text }))
                            ];
                        }

                        // Initial selection
                        this.selected = selected ?? "";
                        const found = this.options.find(opt => opt.key == this.selected);
                        this.selectedLabel = found ? found.text : `Select ${label}`;
                    },

                    // Computed filtered options
                    get filteredOptions() {
                        const term = this.search.toLowerCase();
                        return this.options.filter(opt => opt.text.toLowerCase().includes(term));
                    },

                    setOptions(newOptions) {
                        // Rebuild the options with label
                        this.options = [
                            { key: "", text: this.labelText },
                            ...Object.entries(newOptions).map(([key, text]) => ({
                                key, text
                            }))
                        ];
                    },

                    toggle() {
                        this.open = !this.open;
                        if (this.open) {
                            this.$nextTick(() => {
                                const input = this.$el.querySelector('input[type=text]');
                                input?.focus();
                            });
                        }
                    },

                    close() {
                        this.open = false;
                        this.highlightedIndex = -1;
                    },

                    choose(key, text) {
                        this.selected = key;
                        this.selectedLabel = text;
                        this.close();

                        // In your Alpine code
                        const hiddenInput = this.$refs.input;
                        if (hiddenInput) {
                            hiddenInput.value = key;
                            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    },

                    // ⌨️ Keyboard navigation
                    handleKeydown(e) {
                        if (!this.open) return;

                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            this.highlightedIndex = (this.highlightedIndex + 1) % this.filteredOptions.length;
                        } 
                        else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            this.highlightedIndex = (this.highlightedIndex - 1 + this.filteredOptions.length) % this.filteredOptions.length;
                        } 
                        else if (e.key === 'Enter') {
                            e.preventDefault();
                            const item = this.filteredOptions[this.highlightedIndex];
                            if (item) this.choose(item.key, item.text);
                        } 
                        else if (e.key === 'Escape') {
                            e.preventDefault();
                            this.close();
                        }
                    }
                }
            }
        </script>
    @endpush
@endonce
