<div x-show="tab === 'en'" x-cloak class="space-y-5">
    <x-form.textarea
        id="content_en" 
        label="Affiliates (EN)" 
        :value="$setting->data['en']['content'] ?? old('content_en')"
        {{-- :required="true" --}}
    />

    <x-form.text 
        id="meta_title_en" 
        label="Meta Title (EN)"
        :value="$setting->data['en']['meta_title'] ?? old('meta_title_en')"
        :disabled="$mode === 'show'" 
        helper="Judul untuk keperluan SEO (Max 60 karakter)."
    />

    <x-form.simple-textarea
        id="meta_description_en"
        name="meta_description_en"
        label="Meta Description (EN)"
        :value="$setting->data['en']['meta_description'] ?? old('meta_description_en')"
    />

    <x-form.text 
        id="meta_keywords_en" 
        label="Meta Keywords (EN)"
        :value="$setting->data['en']['meta_keywords'] ?? old('meta_keywords_en')"
        :disabled="$mode === 'show'" 
        notes="Separate tags with commas (e.g., store, online)."
    />
</div>

<div x-show="tab === 'id'" x-cloak class="space-y-5">
    <x-form.textarea 
        id="content_id" 
        label="Affiliates (ID)"
        :value="$setting->data['id']['content'] ?? old('content_id')"
        {{-- :required="true"  --}}
    />

    <x-form.text 
        id="meta_title_id" 
        label="Meta Title (ID)" 
        :value="$setting->data['id']['meta_title'] ?? old('meta_title_id')"
        :disabled="$mode === 'show'" 
        helper="Judul untuk keperluan SEO (Max 60 karakter)."
    />

    <x-form.simple-textarea
        id="meta_description_id"
        name="meta_description_id"
        label="Meta Description (ID)"
        :value="$setting->data['id']['meta_description'] ?? old('meta_description_id')"
    />

    <x-form.text 
        id="meta_keywords_id" 
        label="Meta Keywords (ID)"
        :value="$setting->data['id']['meta_keywords'] ?? old('meta_keywords_id')"
        :disabled="$mode === 'show'" 
        notes="Separate tags with commas (e.g., store, online)."
    />
</div>