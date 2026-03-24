{{-- 
    Blade Component: x-form.textarea

    Purpose:
    Renders a multi-line textarea input field.

    Usage Example:

    <x-form.textarea
        id="your_id"
        label="Your Label"
        :value="$your_value"           // (Optional) The textarea's content
        :disabled="true"               // (Optional) Disable the textarea
        :required="true"               // (Optional) Make the field required
    />

    Notes:
    - Supports standard Laravel validation via the 'required' attribute.
    - Use `old('your_id', $your_value)` in the backend to repopulate the field after validation failure.
--}}

@props([
    'id',
    'label' => '',
    'required' => false,
    'disabled' => false,
    'value' => '',
])

@php
    $editorId = $id . '_editor'; // separate ID for the Quill container
@endphp

<div class="mb-5">
    @if($label)
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="{{ $id }}">
            {{ $label }} 
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- This is the Quill editor container --}}
    <div id="{{ $editorId }}" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded p-2"></div>

    {{-- This is the hidden textarea that will store the HTML for submission --}}
    <textarea
        id="{{ $id }}"
        name="{{ $id }}"
        class="hidden"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
    >{{ old($id, $value) }}</textarea>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        const quill = new Quill('#{{ $editorId }}', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    ['link', 'blockquote', 'code-block'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['clean']
                ],
                // handlers: {
                //     image: imageHandler
                // }
            }
        });

        // Set initial content from DB into Quill
        quill.root.innerHTML = @json(old($id, $value));

        // Update textarea on change
        quill.on('text-change', function () {
            $('#{{ $id }}').val(quill.root.innerHTML);
        });

        @if ($disabled)
            quill.disable();
        @endif

        function imageHandler() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = () => {
                const file = input.files[0];
                if (/^image\//.test(file.type)) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const range = quill.getSelection();
                        quill.insertEmbed(range.index, 'image', e.target.result);
                    };
                    reader.readAsDataURL(file);
                } else {
                    alert('Please select an image.');
                }
            };
        }
    });
</script>
@endpush