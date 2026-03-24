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
                <!-- Card Body -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    
                    <x-form.text 
                        id="name" 
                        label="Name" 
                        :value="$paymentCategory->name ?? old('name', '')" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.textarea
                        id="description" 
                        label="Description" 
                        :value="$paymentCategory->description ?? old('description', '')" 
                        :disabled="$mode === 'show'" 
                    />

                    <x-form.file-image
                        id="icon_image"
                        label="Upload Icon"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create'"
                        :default="$paymentCategory->icon ?? null"
                        ratio="1:1"
                        min-resolution="100 x 100"
                    />
                    
                    <x-form.checkbox
                        id="is_enabled"
                        label="Is Enabled?"
                        :checked="$paymentCategory->is_enabled"
                        :disabled="$mode === 'show'"
                    />

                    <x-form.checkbox
                        id="is_published"
                        label="Is Published?"
                        :checked="$paymentCategory->is_published"
                        :disabled="$mode === 'show'"
                    />
                </div>

                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($paymentCategory)->id ? route('secretgate19.'.$resourceName.'.edit', $paymentCategory->id) : null"  
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>

</x-layout.app>

<script src="{{ asset('assets/scripts/hide-form.js') }}"></script>
<script src="{{ asset('assets/scripts/slugify.js') }}" defer></script>