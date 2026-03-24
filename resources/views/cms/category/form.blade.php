<x-layout.app :header="$pageMeta['title']">

    <div class="mb-6 dark:bg-gray-900 dark:text-white">
        <x-form.breadcrumb 
            :title="$pageMeta['title']" 
            :resourceName="$resourceName" 
            :indexLink="route('secretgate19.'. $resourceName . '.index')"
            :action="ucfirst($mode)" 
        />

        <div class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow">

            <div class="px-6 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <form 
                    action="{{ $pageMeta['url'] }}" 
                    method="post" 
                    enctype="multipart/form-data" 
                    accept-charset="UTF-8" 
                    data-mode="{{ $mode }}" 
                    novalidate 
                    x-cloak 
                    class="space-y-6"
                >
                    @method($pageMeta['method'])
                    @csrf

                    <div class="border-gray-200 dark:border-gray-700 space-y-6">
                        
                        {{-- Parent Category hanya tampil jika alreadyHaveCategory adalah 'yes' --}}
                        <x-form.listbox-search
                            id="parent_id" 
                            label="Parent Category" 
                            :options="$lists['categories']"
                            :selected="$category->parent_id" 
                            :disabled="$mode === 'show'" 
                            
                            x-bind:required="('{{ $mode }}' === 'create' || '{{ $mode }}' === 'edit')"
                        />

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

                            <div x-show="tab === 'en'" x-cloak class="space-y-5">
                                <x-form.text 
                                    id="name_en" 
                                    label="Name (English)" 
                                    :value="$category->translation('en')->name ?? old('name_en', '')" 
                                    :disabled="$mode === 'show'" 
                                    :required="$mode === 'create' || $mode === 'edit'" 
                                    placeholder="Name (English)"
                                />
                                <x-form.simple-textarea
                                    id="description_en"
                                    name="description_en"
                                    label="Description (English)"
                                    :value="$category->translation('en')->description ?? old('description_en', '')"
                                    :required="$mode === 'create' || $mode === 'edit'" 
                                    :disabled="$mode === 'show'"
                                />
                            </div>

                            <div x-show="tab === 'id'" x-cloak class="space-y-5">
                                <x-form.text 
                                    id="name_id" 
                                    label="Name (Indonesia)"
                                    :value="$category->translation('id')->name ?? old('name_id', '')"
                                    :disabled="$mode === 'show'" 
                                    :required="$mode === 'create' || $mode === 'edit'"
                                    placeholder="Name (Indonesia)"
                                />
                                <x-form.simple-textarea
                                    id="description_id"
                                    name="description_id"
                                    label="Description (Indonesia)"
                                    :value="$category->translation('id')->description ?? old('description_id', '')"
                                    :required="$mode === 'create' || $mode === 'edit'" 
                                    :disabled="$mode === 'show'"
                                />
                            </div>
                        </div>

                        <x-form.text 
                            id="slug" 
                            label="Slug" 
                            :value="$category->slug" 
                            :disabled="$mode === 'show'" 
                            :readonly=true
                        />

                        <x-form.number 
                            id="order" 
                            label="Order" 
                            :value="$category->order" 
                            :disabled="$mode === 'show'" 
                            :required="$mode === 'create' || $mode === 'edit'"
                        />
                        
                        <x-form.file-image
                            id="image"
                            label="Upload Image"
                            :required="$mode === 'create' || $mode === 'edit'"
                            :default="Storage::url($category->image) ?? null"
                            ratio="180:31"
                            min-resolution="1440 x 248"
                        />

                        <x-form.file-image
                            id="icon_image"
                            label="Upload Icon"
                            :required="$mode === 'create' || $mode === 'edit'"
                            :default="Storage::url($category->icon_image) ?? null"
                            ratio="1:1"
                            min-resolution="100 x 100"
                        />

                        <x-form.checkbox
                            id="is_active"
                            label="Is Active"
                            :checked="$category->is_active ?? old('is_active')"
                            :disabled="$mode === 'show'"
                        />

                        <x-form.checkbox
                            id="is_navbar"
                            label="Is Navbar"
                            :checked="$category->is_navbar ?? old('is_navbar')"
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
                                    :value="$category->seoByLocale('en')->first()?->meta_title ?? old('meta_title_en')" 
                                    :disabled="$mode === 'show'" 
                                    placeholder="Enter English Meta Title"
                                    helper="SEO title for English version (Max 60 characters)."
                                />

                                <x-form.simple-textarea
                                    id="meta_description_en"
                                    name="meta_description_en"
                                    label="Meta Description (English)"
                                    :value="$category->seoByLocale('en')->first()?->meta_description ?? old('meta_description_en')"
                                    :disabled="$mode === 'show'"
                                    placeholder="Enter English Meta Description"
                                />

                                <x-form.text 
                                    id="meta_keywords_en" 
                                    name="meta_keywords_en"
                                    label="Meta Keywords (English)" 
                                    :value="$category->seoByLocale('en')->first()?->meta_keywords ?? old('meta_keywords_en')" 
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
                                    :value="$category->seoByLocale('id')->first()?->meta_title ?? old('meta_title_id')" 
                                    :disabled="$mode === 'show'" 
                                    placeholder="Masukkan Judul Meta Indonesia"
                                    helper="Judul SEO untuk versi Bahasa Indonesia (Maks 60 karakter)."
                                />

                                <x-form.simple-textarea
                                    id="meta_description_id"
                                    name="meta_description_id"
                                    label="Meta Description (Indonesia)"
                                    :value="$category->seoByLocale('id')->first()?->meta_description ?? old('meta_description_id')"
                                    :disabled="$mode === 'show'"
                                    placeholder="Masukkan Deskripsi Meta Indonesia"
                                />

                                <x-form.text 
                                    id="meta_keywords_id" 
                                    name="meta_keywords_id"
                                    label="Meta Keywords (Indonesia)" 
                                    :value="$category->seoByLocale('id')->first()?->meta_keywords ?? old('meta_keywords_id')" 
                                    :disabled="$mode === 'show'" 
                                    placeholder="Masukkan Kata Kunci Meta Indonesia"
                                    notes="Pisahkan kata kunci dengan koma (e.g., store, online)."
                                />
                            </div>
                        </div>

                    </div>

                    <x-form.footer
                        :mode="$mode" 
                        :editUrl="optional($category)->id ? route('secretgate19.'.$resourceName.'.edit', $category->id) : null"
                        :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                    />

                </form>
            </div>
        </div>
    </div>

</x-layout.app>

<script src="{{ asset('assets/scripts/hide-form.js') }}"></script>
<script src="{{ asset('assets/scripts/slugify.js') }}" defer></script>