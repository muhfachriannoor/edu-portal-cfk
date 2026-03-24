{{-- 
    Blade Component: x-form.select-group

    Purpose:
    Renders a select dropdown input with customizable options.

    Usage Example:

    <x-form.select 
        id="your_id"                   
        label="Your Label"                   
        :options="$your_option"        // Array of options: ['group' => ['key' => 'value']]
        :selected="$your_value"        // (Optional) The selected option's value
        :disabled="true"               // (Optional) Disable the select input
        :required="true"               // (Optional) Make the field required
    />

    Notes:
    - Supports standard Laravel validation via 'required' attribute.
    - Use `old('your_id', $your_value)` in the backend for pre-selecting values if needed.
--}}

@props([
    'id' => '',
    'label' => '',
    'options' => [],       // can be flat or grouped associative array
    'selected' => null,
    'disabled' => false,
    'required' => false,
    'model' => null,       // <-- new prop for x-model
])

<div class="mb-5">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $id }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $model ? "x-model=$model" : '' }}
        {{ $attributes->merge([
            'class' => 'w-full px-3 py-2 text-sm rounded border 
                        bg-white text-gray-700 border-gray-300 
                        focus:outline-none focus:ring-0 focus:border-primary-500
                        dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600 
                        disabled:bg-gray-100 disabled:text-gray-400 dark:disabled:bg-gray-700 dark:disabled:text-gray-500 
                        transition ease-in-out duration-150'
        ]) }}
    >
        <option value="" class="text-gray-400" {{ old($id, $selected) === null ? 'selected' : '' }}>
            Select {{ $label }}
        </option>

        @foreach($options as $key => $value)
            @if(is_array($value))
                {{-- Render group --}}
                <optgroup label="{{ $key }}">
                    @foreach($value as $subKey => $subValue)
                        <option value="{{ $subKey }}" {{ old($id, $selected) == $subKey ? 'selected' : '' }}>
                            {{ $subValue }}
                        </option>
                    @endforeach
                </optgroup>
            @else
                {{-- Render single option --}}
                <option value="{{ $key }}" {{ old($id, $selected) == $key ? 'selected' : '' }}>
                    {{ $value }}
                </option>
            @endif
        @endforeach
    </select>

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
    @enderror
</div>