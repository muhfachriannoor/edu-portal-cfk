<x-layout.app :header="$pageMeta['title']">

    <div class="mb-6 dark:bg-gray-900 dark:text-white">
        <x-form.breadcrumb 
            :title="$pageMeta['title']" 
            :resourceName="$resourceName" 
            :indexLink="route('admin.'. $resourceName . '.index')"
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

                        <x-form.text 
                            id="name" 
                            label="Name"
                            :value="$course->name ?? old('name', '')"
                            :disabled="$mode === 'show'" 
                            :required="$mode === 'create' || $mode === 'edit'"
                            placeholder="Name"
                        />

                        <x-form.multi-listbox 
                            id="categories_id"                   
                            label="Category"                   
                            :options="$lists['category']"
                            :selected="$course->categories->pluck('id')->toArray()"
                            :disabled="$mode === 'show'"               
                        />

                        <x-form.simple-textarea
                            id="description"
                            name="description"
                            label="Description"
                            :value="$course->description ?? old('description', '')"
                            :required="$mode === 'create' || $mode === 'edit'" 
                            :disabled="$mode === 'show'"
                        />

                        <x-form.text 
                            id="slug" 
                            label="Slug" 
                            :value="$course->slug ?? old('slug', '')" 
                            :disabled="$mode === 'show'" 
                            :readonly=true
                        />
                        
                        <x-form.file-image
                            id="image"
                            label="Upload Image"
                            :required="$mode === 'create' || $mode === 'edit'"
                            :default="Storage::url($course->thumbnail) ?? null"
                            ratio="180:31"
                            min-resolution="1440 x 248"
                        />

                    </div>

                    <x-form.footer
                        :mode="$mode" 
                        :editUrl="optional($course)->id ? route('admin.'.$resourceName.'.edit', $course->id) : null"
                        :backUrl="route('admin.'.$resourceName.'.index')" 
                    />

                </form>
            </div>
        </div>
    </div>

</x-layout.app>

<script src="{{ asset('assets/scripts/hide-form.js') }}"></script>
<script src="{{ asset('assets/scripts/slugify.js') }}" defer></script>