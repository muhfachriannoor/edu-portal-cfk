@props([
    'id' => '',
    'label' => '',
    'value' => '',
    'apiUrl' => '',
    'disabled' => false,
    'required' => false,
    'notes' => '',
    'notesType' => 'info', // info | warning | error | success
])

<div
    class="mb-5 relative"
    data-master-address
    data-id="{{ $id }}"
    data-api="{{ $apiUrl }}"
>
    {{-- Label --}}
    @if($label)
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="{{ $id }}_search">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- Search input --}}
    <input
        type="text"
        id="{{ $id }}_search"
        {{-- placeholder="Search country / province / city / district / subdistrict..." --}}
        autocomplete="off"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        class="w-full px-3 py-2 border rounded bg-white text-gray-800 border-gray-300
               dark:bg-gray-700 dark:border-gray-600 dark:text-white
               disabled:bg-gray-100 disabled:text-gray-400"
    />

    {{-- Hidden submitted value --}}
    <input
        type="hidden"
        name="{{ $id }}"
        id="{{ $id }}_value"
        value="{{ old($id, $value) }}"
    >

    {{-- Dropdown --}}
    <div
        id="{{ $id }}_dropdown"
        class="absolute z-50 w-full bg-white dark:bg-gray-800
               border border-gray-200 dark:border-gray-700
               rounded shadow mt-1 hidden max-h-64 overflow-y-auto"
    ></div>

    {{-- Notes --}}
    @if($notes)
        @php
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

    {{-- Validation error --}}
    @error($id)
        <span class="text-red-500 text-sm mt-1 block">
            {{ $message }}
        </span>
    @enderror
</div>

@push('scripts')
<script>
(function () {
    function bindMasterAddressSelect(wrapper) {
        const id = wrapper.dataset.id;
        const apiUrl = wrapper.dataset.api;
        let timer = null;

        const $search = $('#' + id + '_search');
        const $value = $('#' + id + '_value');
        const $dropdown = $('#' + id + '_dropdown');

        // -------------------------------
        // PRELOAD EXISTING VALUE (EDIT MODE)
        // -------------------------------
        const existingValue = $value.val();
        if (existingValue) {
            $.get(apiUrl, { id: existingValue }, function(res) {
                if (res.data && res.data.length) {
                    const item = res.data[0];
                    const label = `${item.subdistrict_name}, ${item.district_name}, ${item.city_name}, ${item.province_name}`;
                    $search.val(label);
                }
            });
        }

        // -------------------------------
        // SEARCH HANDLER
        // -------------------------------
        $search.on('input', function () {
            clearTimeout(timer);
            const keyword = this.value.trim();

            if (keyword.length < 3) {
                $dropdown.hide().empty();
                return;
            }

            timer = setTimeout(() => {
                $.get(apiUrl, { keyword, limit: 20 }, function (res) {
                    $dropdown.empty();

                    if (!res.data || !res.data.length) {
                        $dropdown
                            .append('<div class="px-3 py-2 text-sm text-gray-500">No results found</div>')
                            .show();
                        return;
                    }

                    res.data.forEach(item => {
                        $dropdown.append(`
                            <div class="px-3 py-2 cursor-pointer text-sm hover:bg-blue-50 dark:hover:bg-gray-700"
                                 data-id="${item.subdistrict_id}"
                                 data-label="${item.subdistrict_name}, ${item.district_name}, ${item.city_name}, ${item.province_name}">
                                <div class="font-medium">${item.subdistrict_name}</div>
                                <div class="text-xs text-gray-500">
                                    ${item.district_name}, ${item.city_name}, ${item.province_name}
                                </div>
                            </div>
                        `);
                    });

                    $dropdown.show();
                });
            }, 300);
        });

        // -------------------------------
        // SELECT ITEM
        // -------------------------------
        $dropdown.on('click', '[data-id]', function () {
            $search.val($(this).data('label'));
            $value.val($(this).data('id'));
            $dropdown.hide();
        });

        // -------------------------------
        // CLICK OUTSIDE
        // -------------------------------
        $(document).on('click', function (e) {
            if (!$(e.target).closest(wrapper).length) {
                $dropdown.hide();
            }
        });
    }

    $(document).ready(function () {
        document.querySelectorAll('[data-master-address]').forEach(bindMasterAddressSelect);
    });
})();
</script>
@endpush
