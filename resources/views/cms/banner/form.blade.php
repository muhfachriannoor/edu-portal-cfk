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
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 border-b pb-2 mb-4">General Information</h3>

                    <x-form.text 
                        id="name" 
                        label="Name" 
                        :value="$banner->name ?? old('name')" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                        placeholder="Name"
                    />

                    <x-form.listbox-search
                        id="category" 
                        label="Category" 
                        :options="$lists['category']"
                        :selected="$banner->category" 
                        :disabled="$mode === 'show'" 
                        
                        x-bind:required="('{{ $mode }}' === 'create' || '{{ $mode }}' === 'edit')"
                    />

                    <x-form.number 
                        id="sequence" 
                        label="Sequence" 
                        :value="$banner->sequence" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        :disabled="$mode === 'show'"
                        placeholder="0"
                    />

                    <x-form.file-image
                        id="image"
                        label="Featured Image"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create'"
                        :default="$banner->image ?? null" 
                        ratio="3:2"
                        min-resolution="1080 x 720"
                    />

                    <x-form.checkbox
                        id="is_active"
                        label="Is Active?"
                        :checked="$banner->is_active ?? old('is_active')"
                        :disabled="$mode === 'show'"
                    />

                    {{-- <hr class="border-gray-200 dark:border-gray-700 my-6"> --}}
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 border-b pb-2 mb-4">Headline, Sub Headline & Translation</h3>

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
                            <x-form.tinymce
                                id="headline_en"
                                label="Headline (English)"
                                :value="$banner->translation('en')->description ?? old('headline_en', '')" 
                                :disabled="$mode === 'show'" 
                                {{-- :required="$mode === 'create' || $mode === 'edit'"  --}}
                            />

                            <x-form.tinymce
                                id="subheadline_en" 
                                label="Sub Headline (English)" 
                                :value="$banner->translation('en')->name ?? old('subheadline_en', '')" 
                                :disabled="$mode === 'show'" 
                                {{-- :required="$mode === 'create' || $mode === 'edit'"  --}}
                            />
                        </div>

                        <!-- Indonesian Fields -->
                        <div x-show="tab === 'id'" x-cloak class="space-y-5">
                            <x-form.tinymce
                                id="headline_id" 
                                label="Headline (Indonesia)" 
                                :value="$banner->translation('id')->description ?? old('headline_id', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />

                            <x-form.tinymce
                                id="subheadline_id" 
                                label="Sub Headline (Indonesia)" 
                                :value="$banner->translation('id')->name ?? old('subheadline_id', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />
                        </div>
                    </div>
                </div>

                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($banner)->id ? route('secretgate19.'.$resourceName.'.edit', $banner->id) : null"
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>

</x-layout.app>