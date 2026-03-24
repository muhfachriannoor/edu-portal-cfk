<x-layout.app :header="$pageMeta['title']">

    <div class="mb-6 dark:bg-gray-900 dark:text-white">
        <!-- Breadcrumb & Page Header -->
        <x-form.breadcrumb 
            :title="$pageMeta['title']" 
            :resourceName="$resourceName" 
            :indexLink="route('secretgate19.'. $resourceName . '.index')"
            :action="ucfirst($mode)" 
        />

        <!-- Form Card -->
        <div class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow mb-6">
            <form action="{{ $pageMeta['url'] }}" method="post" enctype="multipart/form-data" accept-charset="UTF-8" data-mode="{{ $mode }}" novalidate>
                @method($pageMeta['method'])
                @csrf
                <div class="px-6 py-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 space-y-6">

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
                                id="name_en" 
                                label="Name (English)" 
                                :value="$subCategory->translation('en')->name ?? old('name_en', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                                placeholder="Name (English)"
                            />

                            <x-form.textarea
                                id="description_en" 
                                label="Description (English)" 
                                :value="$subCategory->translation('en')->description ?? old('description_en', '')" 
                                :disabled="$mode === 'show'"
                            />
                        </div>

                        <!-- Indonesian Fields -->
                        <div x-show="tab === 'id'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="name_id" 
                                label="Name (Indonesia)"
                                :value="$subCategory->translation('id')->name ?? old('name_id', '')"
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'"
                                placeholder="Name (Indonesia)"
                            />

                            <x-form.textarea
                                id="description_id" 
                                label="Description (Indonesia)"
                                :value="$subCategory->translation('id')->description ?? old('description_id', '')"
                                :disabled="$mode === 'show'"
                            />
                        </div>
                    </div>

                    <x-form.text 
                        id="slug" 
                        label="Slug" 
                        :value="$subCategory->slug" 
                        :disabled="$mode === 'show'" 
                        :readonly=true
                    />

                    <x-form.listbox 
                        id="category_id"                   
                        label="Category"                   
                        :options="$lists['categories']"
                        :selected="$subCategory->category_id"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'"                
                    />

                    <x-form.number 
                        id="order" 
                        label="Order" 
                        :value="$subCategory->order" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'"
                    />

                    <x-form.file-image
                        id="image"
                        label="Upload Category Image"
                        :required="$mode === 'create' || $mode === 'edit'"
                        :default="Storage::url($subCategory->image) ?? null"
                    />

                    <x-form.checkbox
                        id="is_active"
                        label="Is Active"
                        :checked="$subCategory->is_active ?? old('is_active')"
                        :disabled="$mode === 'show'"
                    />

                </div>

                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($subCategory)->id ? route('secretgate19.'.$resourceName.'.edit', $subCategory->id) : null"
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>

</x-layout.app>

<script src="{{ asset('assets/scripts/hide-form.js') }}"></script>
<script src="{{ asset('assets/scripts/slugify.js') }}" defer></script>