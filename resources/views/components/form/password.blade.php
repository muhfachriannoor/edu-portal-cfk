{{-- 
    Blade Component: x-form.password

    Purpose:
    Renders a password input field with show/hide toggle and optional confirmation input.

    Usage Example:

    <x-form.password 
        id="password"                    // (Required) The HTML id and name of the input
        label="New Password"             // (Optional) Label for the password field (default: 'Password')
        :value="$value"                  // (Optional) Pre-fill the input with a value
        :required="true"                 // (Optional) Set to true to make the field required
        :disabled="false"                // (Optional) Set to true to disable the field
        :confirmation="true"             // (Optional) Show a "Confirm Password" field
        :hidden="$mode === 'show'"       // (Optional) Hide the field when true (e.g., in show mode)
    />

    Notes:
    - The password field includes a toggle to show/hide the password text.
    - If `:confirmation="true"` is passed, it will render a second input for confirmation using the name: {id}_confirmation.
--}}

@props([
    'id',
    'label' => 'Password',
    'value' => '',
    'required' => false,
    'disabled' => false,
    'confirmation' => false,
    'hidden' => false,
])

<div class="mb-5" x-data="{ show: false }" {{ $hidden ? 'hidden' : '' }}>
    @if($label)
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="{{ $id }}">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input
            :type="show ? 'text' : 'password'"
            id="{{ $id }}"
            name="{{ $id }}"
            value="{{ old($id, $value) }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge([
                'class' =>
                    'w-full px-3 py-2 pr-10 border rounded bg-white text-gray-800 border-gray-300 
                    dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:bg-gray-100'
            ]) }}
        />

        <div class="absolute inset-y-0 right-0 flex items-center px-3 cursor-pointer text-gray-500"
            @click="show = !show">
            <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
        </div>
    </div>

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
    @enderror

    @if($confirmation)
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-4" for="{{ $id }}_confirmation">
            Confirm {{ $label }}
        </label>
        <div class="relative">
            <input
                :type="show ? 'text' : 'password'"
                id="{{ $id }}_confirmation"
                name="{{ $id }}_confirmation"
                required
                class="w-full px-3 py-2 border rounded bg-white text-gray-800 border-gray-300 mt-1
                    dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            />
            <div class="absolute inset-y-0 right-0 flex items-center px-3 cursor-pointer text-gray-500"
                @click="show = !show">
                <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </div>
        </div>
    @endif
</div>
