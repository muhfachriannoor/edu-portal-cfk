<x-layout.app :header="$pageMeta['title']">

    <div class="mb-6 dark:bg-gray-900 dark:text-white">
        <!-- Breadcrumb & Page Header -->
        <x-form.breadcrumb 
            :title="$pageMeta['title']" 
            :resourceName="$resourceName" 
            :indexLink="route('secretgate19.' . $resourceName . '.index')" 
            :action="ucfirst($mode)" 
        />

        <!-- Form Card -->
        <div class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow mb-6">
            <form action="{{ $pageMeta['url'] }}" method="post" enctype="multipart/form-data" accept-charset="UTF-8" data-mode="{{ $mode }}" novalidate>
                @method($pageMeta['method'])
                @csrf
                <!-- Card Body -->
                <div class="px-6 py-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">

                    <!-- Language Tabs -->
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
                                :value="$store->translation('en')->name ?? ''" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />

                            <x-form.textarea
                                id="description_en" 
                                label="Description (English)" 
                                :value="$store->translation('en')->description ?? ''" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />
                        </div>

                        <!-- Indonesian Fields -->
                        <div x-show="tab === 'id'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="name_id" 
                                label="Name (Indonesia)" 
                                :value="$store->translation('id')->name ?? ''" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />

                            <x-form.textarea
                                id="description_id" 
                                label="Description (Indonesia)" 
                                :value="$store->translation('id')->description ?? ''" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />
                        </div>
                    </div>

                    <!-- Divider -->
                    <hr class="border-gray-200 dark:border-gray-700 my-6">

                    <!-- General Info -->
                    <x-form.text 
                        id="slug" 
                        label="Slug" 
                        :value="$store->slug" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.text 
                        id="phone" 
                        label="Phone" 
                        :value="$store->phone" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.text 
                        id="email" 
                        label="Email" 
                        :value="$store->email" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.listbox 
                        id="location_id"                   
                        label="Location"                   
                        :options="$lists['locations']"
                        :selected="$store->location_id"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'"                
                    />

                    <x-form.file-image
                        id="logo"
                        label="Upload Logo"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        :default="$store->logo"
                    />
                    
                    <x-form.checkbox
                        id="is_verified"
                        label="Is Verified?"
                        :checked="$store->is_verified"
                        :disabled="$mode === 'show'"
                    />
                    
                    <x-form.checkbox
                        id="is_delivery"
                        label="Is Delivery?"
                        :checked="$store->is_delivery"
                        :disabled="$mode === 'show'"
                    />

                    <x-form.checkbox
                        id="is_pickup"
                        label="Is Pickup?"
                        :checked="$store->is_pickup"
                        :disabled="$mode === 'show'"
                    />

                    <x-form.checkbox
                        id="is_active"
                        label="Is Activated?"
                        :checked="$store->is_active"
                        :disabled="$mode === 'show'"
                    />

                </div>


                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($store)->id ? route('secretgate19.'.$resourceName.'.edit', $store->id) : null"  
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>

</x-layout.app>

<script>
    $(function () {
        let manualSlug = false;

        // Get initial values
        const nameInput = $('#name_en');
        const slugInput = $('#slug');

        // Helper function to generate a slug from a name
        function generateSlug(str) {
            return str
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        }

        // Compare current slug with auto-generated one
        const autoSlug = generateSlug(nameInput.val());
        if (slugInput.val() !== autoSlug) {
            manualSlug = true; // user has custom slug
        }

        // Detect manual edits
        slugInput.on('input', function () {
            manualSlug = true;
        });

        // Auto-update slug only if not manually edited
        nameInput.on('input', function () {
            if (!manualSlug) {
                slugInput.val(generateSlug($(this).val()));
            }
        });
    });
</script>