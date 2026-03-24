{{-- 
    Blade Component: x-form.tinymce

    Purpose:
    Renders a TinyMCE rich text editor (WYSIWYG) that can also edit HTML via "code" plugin.

    Usage Example:
    <x-form.tinymce
        id="headline_en"
        label="Headline (English)"
        :value="$value"
        :required="true"
    />

    Notes:
    - Requires TinyMCE JS to be loaded in layout:
      <script src="{{ asset('vendors/tinymce/tinymce.min.js') }}"></script>
    - TinyMCE will keep textarea value in sync on submit.
--}}

@props([
    'id',
    'label' => '',
    'required' => false,
    'disabled' => false,
    'value' => '',
    'height' => 240,
])

<div class="mb-5">
    @if($label)
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="{{ $id }}">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $id }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
    >{{ old($id, $value) }}</textarea>

    @error($id)
        <span class="text-red-500 text-sm mt-1 block">
            {{ $message }}
        </span>
    @enderror
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        // Prevent double init when page has partial reloads / repeated stacks
        if (window.tinymce && tinymce.get('{{ $id }}')) {
            return;
        }

        tinymce.init({
            selector: '#{{ $id }}',
            license_key: 'gpl',
            height: {{ (int) $height }},
            menubar: false,

            plugins: 'code link lists',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | code | removeformat',

            // Keep HTML classes you need (Tailwind-like)
            // This helps TinyMCE not strip class attributes on these tags.
            extended_valid_elements: 'br[class],i[class],em[class],span[class],h1[class],h2[class],h3[class],p[class]',

            // disable wrapping with <p>
            forced_root_block: '',
            force_br_newlines: true,
            force_p_newlines: false,

            // Ensure the textarea is updated as user types (extra safe)
            setup: function (editor) {
                const unwrapSingleP = (html) => {
                    if (!html) return html;

                    const container = document.createElement('div');
                    container.innerHTML = html.trim();

                    // remove whitespace-only text nodes
                    const nodes = Array.from(container.childNodes).filter(n => {
                        return !(n.nodeType === Node.TEXT_NODE && !n.textContent.trim());
                    });

                    if (nodes.length === 1 && nodes[0].nodeType === Node.ELEMENT_NODE && nodes[0].tagName === 'P') {
                        return nodes[0].innerHTML; // return inside of <p>...</p>
                    }

                    return html;
                };

                // Ensure textarea always holds "cleaned" content
                const syncToTextarea = () => {
                    const raw = editor.getContent();            // e.g. <p>...</p>
                    const cleaned = unwrapSingleP(raw);         // e.g. Discover handmade <br...>
                    document.getElementById('{{ $id }}').value = cleaned;
                };

                editor.on('change keyup', syncToTextarea);

                // When TinyMCE saves back to textarea (submit/save), also strip wrapper
                editor.on('SaveContent', function (e) {
                    e.content = unwrapSingleP(e.content);
                });

                @if ($disabled)
                    editor.on('init', function () {
                        editor.setMode('readonly');
                    });
                @endif
            }
        });
    });
</script>
@endpush
