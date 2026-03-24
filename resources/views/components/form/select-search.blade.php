@props([
    'options' => [],     // array of ['value' => 'Label']
    'selected' => null,  // default selected value
    'placeholder' => 'Select an option',
    'name' => null,      // form name
])

<div x-data="selectSearch({{ json_encode($options) }}, '{{ $selected }}')" 
     x-init="init()" 
     class="relative w-64">

    <!-- Hidden input for form submission -->
    @if($name)
        <input type="hidden" name="{{ $name }}" :value="selectedValue">
    @endif

    <!-- Trigger Button -->
    <button type="button" 
            @click="open = !open" 
            class="w-full border px-3 py-2 rounded flex justify-between items-center bg-white dark:bg-gray-700 dark:border-gray-600">
        <span x-text="selectedLabel || '{{ $placeholder }}'" 
              class="text-sm text-gray-700 dark:text-gray-200"></span>
        <svg class="w-4 h-4 ml-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown -->
    <div x-show="open" @click.outside="close()" 
         class="absolute mt-1 w-full bg-white dark:bg-gray-800 border rounded shadow-lg z-10">
        
        <!-- Search Box -->
        <div class="p-2">
            <input type="text" 
                   x-model="search" 
                   placeholder="Search..." 
                   class="w-full border px-2 py-1 rounded text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
        </div>

        <!-- Options -->
        <ul class="max-h-60 overflow-y-auto">
            <template x-for="(label, value) in filteredOptions" :key="value">
                <li @click="select(value, label)"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                    :class="{ 'bg-gray-200 dark:bg-gray-700': value === selectedValue }"
                    x-text="label"></li>
            </template>
        </ul>
    </div>
</div>

@pushOnce('scripts')
<script>
    function selectSearch(options, selected) {
        return {
            open: false,
            options: options,
            search: '',
            selectedValue: selected,
            selectedLabel: options[selected] || '',
            init() {
                if (!this.selectedLabel) {
                    this.selectedLabel = '';
                }
                this.search = '';
            },
            get filteredOptions() {
                if (this.search === '') return this.options;
                return Object.fromEntries(
                    Object.entries(this.options).filter(([val, label]) =>
                        label.toLowerCase().includes(this.search.toLowerCase())
                    )
                );
            },
            select(value, label) {
                this.selectedValue = value;
                this.selectedLabel = label;
                this.open = false;
                this.close();

                this.$dispatch('select-changed', { value });
                this.$dispatch('select-changed-label', { label });
            },
            close() {
                this.open = false;
                this.search = ''; // 👈 clear search every time dropdown closes
            }
        }
    }
</script>
@endpushOnce