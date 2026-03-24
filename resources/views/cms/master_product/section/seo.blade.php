<div 
    class="tab-content px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
    x-show="activeTab === 'seo'"    
>
    <fieldset>
        <x-form.text 
            id="seo[meta_title]" 
            label="Meta Title" 
            :value="$product->seo?->meta_title" 
            :disabled="$mode === 'show'" 
        />
        
        <x-form.text 
            id="seo[meta_description]" 
            label="Meta Description" 
            :value="$product->seo?->meta_description" 
            :disabled="$mode === 'show'" 
        />
        
        <x-form.text 
            id="seo[meta_keywords]" 
            label="Meta Keywords" 
            :value="$product->seo?->meta_keywords" 
            :disabled="$mode === 'show'" 
            notes="Separate tags with commas (e.g., store, online)."
        />

        <x-form.listbox-search
            id="seo[robot]" 
            label="Robot"
            :options="$lists['robot']"
            :disabled="$mode === 'show'"
            :selected="old('category', $product->seo?->robot ?? null)" 
            x-ref="categoryBox"
        />
    </fieldset>
        
</div>

@push('scripts')
    <script>
        
    </script>
@endpush