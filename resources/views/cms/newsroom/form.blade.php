<x-layout.app :header="$pageMeta['title']">

    <div class="mb-6 dark:bg-gray-900 dark:text-white">
        <x-form.breadcrumb 
            :title="$pageMeta['title']" 
            :resourceName="$resourceName" 
            :indexLink="route('secretgate19.' . $resourceName . '.index')" 
            :action="ucfirst($mode)" 
        />

        <div class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow mb-6">
            <form action="{{ $pageMeta['url'] }}" method="post" enctype="multipart/form-data" accept-charset="UTF-8" data-mode="{{ $mode }}" novalidate>
                @method($pageMeta['method'])
                @csrf
                <div class="px-6 py-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 space-y-6">

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 border-b pb-2 mb-4">Content & Translation</h3>

                    <div x-data="{ tab: 'en' }" class="space-y-4">
                        <div class="flex border-b border-gray-300 dark:border-gray-700">
                            <button type="button" @click="tab = 'en'" 
                                :class="tab === 'en' 
                                    ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' 
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                class="px-4 py-2 text-sm transition">
                                English
                            </button>
                            <button type="button" @click="tab = 'id'" 
                                :class="tab === 'id' 
                                    ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' 
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                class="px-4 py-2 text-sm transition">
                                Indonesian
                            </button>
                        </div>

                        <!-- English Fields -->
                        <div x-show="tab === 'en'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="title_en" 
                                label="Title (English)" 
                                :value="$newsroom->translation('en')->name ?? old('title_en', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                                placeholder="Title (English)"
                            />

                            <x-form.textarea
                                id="content_en" 
                                label="Content (English)" 
                                :value="$newsroom->translation('en')->description ?? old('content_en', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />
                        </div>

                        <!-- Indonesian Fields -->
                        <div x-show="tab === 'id'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="title_id" 
                                label="Title (Indonesia)"
                                :value="$newsroom->translation('id')->name ?? old('title_id', '')"
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'"
                                placeholder="Title (Indonesia)"
                            />

                            <x-form.textarea
                                id="content_id" 
                                label="Content (Indonesia)"
                                :value="$newsroom->translation('id')->description ?? old('content_id', '')"
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700 my-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 border-b pb-2 mb-4">General Information</h3>

                    <x-form.text 
                        id="slug" 
                        label="Slug" 
                        :value="$newsroom->slug ?? old('slug')" 
                        :disabled="$mode === 'show'" 
                        :readonly=true
                        placeholder="Slug URL"
                    />

                    <x-form.file-image
                        id="image"
                        label="Featured Image"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create'"
                        :default="$newsroom->image ?? null" 
                        ratio="3:2"
                        min-resolution="1080 x 720"
                    />

                    <x-form.date 
                        id="published_at" 
                        label="Published At (Date)" 
                        :value="$newsroom->published_at ?? old('published_at')"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                        helper="Format: YYYY-MM-DD. Required if 'Is Active' status is enabled." 
                    />

                    <x-form.checkbox
                        id="is_active"
                        label="Is Active (Set to Published)?"
                        :checked="$newsroom->is_active ?? old('is_active')"
                        :disabled="$mode === 'show'"
                    />
                    
                    <hr class="border-gray-200 dark:border-gray-700 my-6">

                    <div x-data="{ seoTab: 'en' }" class="space-y-4">
                        {{-- Judul SEO --}}
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">SEO Metadata</h3>

                        <div class="flex border-b border-gray-300 dark:border-gray-700">
                            <button type="button" @click="seoTab = 'en'" 
                                :class="seoTab === 'en' 
                                    ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' 
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                class="px-4 py-2 text-sm transition focus:outline-none">
                                English
                            </button>
                            <button type="button" @click="seoTab = 'id'" 
                                :class="seoTab === 'id' 
                                    ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' 
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                class="px-4 py-2 text-sm transition focus:outline-none">
                                Indonesian
                            </button>
                        </div>

                        {{-- Isi Konten SEO English --}}
                        <div x-show="seoTab === 'en'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="meta_title_en" 
                                name="meta_title_en"
                                label="Meta Title (English)" 
                                :value="$newsroom->seoByLocale('en')->first()?->meta_title ?? old('meta_title_en')" 
                                :disabled="$mode === 'show'" 
                                placeholder="Enter English Meta Title"
                                helper="SEO title for English version (Max 60 characters)."
                            />

                            <x-form.simple-textarea
                                id="meta_description_en"
                                name="meta_description_en"
                                label="Meta Description (English)"
                                :value="$newsroom->seoByLocale('en')->first()?->meta_description ?? old('meta_description_en')"
                                :disabled="$mode === 'show'"
                                placeholder="Enter English Meta Description"
                            />

                            <x-form.text 
                                id="meta_keywords_en" 
                                name="meta_keywords_en"
                                label="Meta Keywords (English)" 
                                :value="$newsroom->seoByLocale('en')->first()?->meta_keywords ?? old('meta_keywords_en')" 
                                :disabled="$mode === 'show'" 
                                placeholder="Enter English Meta Keywords"
                                notes="Separate tags with commas (e.g., store, online)."
                            />
                        </div>

                        {{-- Isi Konten SEO Indonesian --}}
                        <div x-show="seoTab === 'id'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="meta_title_id" 
                                name="meta_title_id"
                                label="Meta Title (Indonesia)" 
                                :value="$newsroom->seoByLocale('id')->first()?->meta_title ?? old('meta_title_id')" 
                                :disabled="$mode === 'show'" 
                                placeholder="Masukkan Judul Meta Indonesia"
                                helper="Judul SEO untuk versi Bahasa Indonesia (Maks 60 karakter)."
                            />

                            <x-form.simple-textarea
                                id="meta_description_id"
                                name="meta_description_id"
                                label="Meta Description (Indonesia)"
                                :value="$newsroom->seoByLocale('id')->first()?->meta_description ?? old('meta_description_id')"
                                :disabled="$mode === 'show'"
                                placeholder="Masukkan Deskripsi Meta Indonesia"
                            />

                            <x-form.text 
                                id="meta_keywords_id" 
                                name="meta_keywords_id"
                                label="Meta Keywords (Indonesia)" 
                                :value="$newsroom->seoByLocale('id')->first()?->meta_keywords ?? old('meta_keywords_id')" 
                                :disabled="$mode === 'show'" 
                                placeholder="Masukkan Kata Kunci Meta Indonesia"
                                notes="Pisahkan kata kunci dengan koma (e.g., store, online)."
                            />
                        </div>
                    </div>

                </div>

                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($newsroom)->id ? route('secretgate19.'.$resourceName.'.edit', $newsroom->id) : null"
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>

</x-layout.app>

<script>
    function initSlugListener() {
        const sourceInput = document.getElementById('title_id');
        const slugInput = document.getElementById('slug');

        console.log('Source:', sourceInput); 
        console.log('Slug:', slugInput);

        if (!sourceInput || !slugInput) return;

        const slugify = (s) =>
            s.toString()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-');

        let slugEdited = false;

        slugInput.addEventListener('input', () => {
            slugEdited = true;
        });

        sourceInput.addEventListener('input', function() {
            if (!slugEdited) {
                slugInput.value = slugify(this.value);
            }
        });
        
        if (!slugInput.value && sourceInput.value) {
            slugInput.value = slugify(sourceInput.value);
        }
    }
    
    document.addEventListener('DOMContentLoaded', initSlugListener);
</script>