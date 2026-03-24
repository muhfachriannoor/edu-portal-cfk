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
                        :value="$warehouse->name" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />
                    
                    <x-form.master-address-select
                        id="master_address_id"
                        label="Subdistrict"
                        api-url="{{ route('api.master.master_address') }}"
                        :value="$warehouse->master_address_id ?? ''"
                        :disabled="$mode === 'show'"
                        :required="true"
                    />

                    <x-form.text 
                        id="address" 
                        label="Address" 
                        :value="$warehouse->address" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.text 
                        id="postal_code" 
                        label="Postal Code" 
                        :value="$warehouse->postal_code" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />
                    
                    <x-form.checkbox
                        id="is_active"
                        label="Is Activated?"
                        :checked="$warehouse->is_active"
                        :disabled="$mode === 'show'"
                    />
                </div>

                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($warehouse)->id ? route('secretgate19.'.$resourceName.'.edit', $warehouse->id) : null"  
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>

</x-layout.app>

<script src="{{ asset('assets/scripts/hide-form.js') }}"></script>
<script src="{{ asset('assets/scripts/slugify.js') }}" defer></script>