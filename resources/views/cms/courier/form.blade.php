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
                @if($pageMeta['method'])
                    @method($pageMeta['method'])
                @endif
                @csrf
                <div class="px-6 py-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
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
                                :value="$courier->translations->where('locale','en')->first()?->name ?? old('name_en', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                                placeholder="Name (English)"
                                name="name_en"
                            />
                            <x-form.textarea
                                id="description_en" 
                                label="Description (English)" 
                                :value="$courier->translations->where('locale','en')->first()?->description ?? old('description_en', '')" 
                                :disabled="$mode === 'show'"
                                name="description_en"
                            />
                        </div>
                        <!-- Indonesian Fields -->
                        <div x-show="tab === 'id'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="name_id" 
                                label="Name (Indonesia)"
                                :value="$courier->translations->where('locale','id')->first()?->name ?? old('name_id', '')"
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'"
                                placeholder="Name (Indonesia)"
                                name="name_id"
                            />
                            <x-form.textarea
                                id="description_id" 
                                label="Description (Indonesia)"
                                :value="$courier->translations->where('locale','id')->first()?->description ?? old('description_id', '')"
                                :disabled="$mode === 'show'"
                                name="description_id"
                            />
                        </div>
                    </div>

                    <x-form.text 
                        id="key" 
                        label="Key" 
                        :value="$courier->key" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.checkbox
                        id="is_pickup"
                        label="Is Pickup"
                        :checked="$courier->is_pickup ?? old('is_pickup')"
                        :disabled="$mode === 'show'"
                    />
                    <x-form.checkbox
                        id="is_active"
                        label="Is Active"
                        :checked="$courier->is_active ?? old('is_active')"
                        :disabled="$mode === 'show'"
                    />

                    <div 
                        x-data="{ hasFee: {{ old('has_fee', $courier->has_fee ?? false) ? 'true' : 'false' }} }"
                    >
                        <x-form.checkbox
                            id="has_fee"
                            label="Has Fee?"
                            x-model="hasFee"
                            :checked="$courier->has_fee ?? old('has_fee')"
                            :disabled="$mode === 'show'"
                        />

                        <div x-show="hasFee" x-transition>
                            <x-form.number 
                                id="fee" 
                                label="Fee" 
                                :value="$courier->fee"
                                :disabled="$mode === 'show'"
                                :required="$mode === 'create' || $mode === 'edit'"
                                :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
                            />
                        </div>
                    </div>
                </div>
                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($courier)->id ? route('secretgate19.'.$resourceName.'.edit', $courier->id) : null"
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />
            </form>
        </div>
    </div>
</x-layout.app>

<script src="{{ asset('assets/scripts/hide-form.js') }}"></script>
