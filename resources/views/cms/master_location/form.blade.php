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
                        id="city" 
                        label="City" 
                        :value="$masterLocation->city"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        placeholder="City..."
                    />
                    
                    <x-form.text 
                        id="location" 
                        label="Location" 
                        :value="$masterLocation->location"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                        placeholder="Location..."
                    />

                    <x-form.text 
                        id="type_label" 
                        label="Type Label" 
                        :value="$masterLocation->type_label"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        placeholder="Type Label..."
                    />

                    <x-form.text 
                        id="location_path_api" 
                        label="Location Path API" 
                        :value="$masterLocation->location_path_api"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        placeholder="Location Path..."
                    />
                    
                    <x-form.master-address-select
                        id="master_address_id"
                        label="Master Address"
                        api-url="{{ route('api.master.master_address') }}"
                        :value="$masterLocation->master_address_id ?? ''"
                        :disabled="$mode === 'show'"
                        :required="true"
                    />

                    <x-form.text 
                        id="postal_code" 
                        label="Postal Code" 
                        :value="$masterLocation->postal_code" 
                        :disabled="$mode === 'show'"
                        placeholder="Postal Code..."
                    />

                    <x-form.text 
                        id="slug" 
                        label="Slug" 
                        :value="$masterLocation->slug" 
                        :disabled="$mode === 'show'" 
                        :readonly=true
                        placeholder="slug-location"
                    />

                    <x-form.textarea
                        id="address" 
                        label="Address" 
                        :value="$masterLocation->address"
                        :disabled="$mode === 'show'"
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

                        <!-- English Fields -->
                        <div x-show="tab === 'en'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="point_operational_en" 
                                label="Point Operational (English)" 
                                :value="$masterLocation->translation('en')->name ?? old('point_operational_en', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                                placeholder="Point Operational (English)"
                            />
                        </div>

                        <!-- Indonesian Fields -->
                        <div x-show="tab === 'id'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="point_operational_id" 
                                label="Point Operational (Indonesia)"
                                :value="$masterLocation->translation('id')->name ?? old('point_operational_id', '')"
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'"
                                placeholder="Point Operational (Indonesia)"
                            />
                        </div>
                    </div>
                    
                    <x-form.checkbox
                        id="warning_pickup"
                        label="Is Warning Pickup?"
                        :checked="$masterLocation->warning_pickup"
                        :disabled="$mode === 'show'"
                    />

                    <x-form.checkbox
                        id="is_active"
                        label="Is Activated?"
                        :checked="$masterLocation->is_active"
                        :disabled="$mode === 'show'"
                    />
                </div>

                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($masterLocation)->id ? route('secretgate19.'.$resourceName.'.edit', $masterLocation->id) : null"  
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>
</x-layout.app>

<script src="{{ asset('assets/scripts/hide-form.js') }}"></script>
<script src="{{ asset('assets/scripts/slugify.js') }}" defer></script>