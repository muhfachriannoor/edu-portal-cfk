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
                                id="voucher_name_en" 
                                label="Coupon Name (English)" 
                                :value="$coupon->translation('en')->name ?? old('voucher_name_en', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                                placeholder="Coupon Name (English)"
                            />
                        </div>

                        <!-- Indonesian Fields -->
                        <div x-show="tab === 'id'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="voucher_name_id" 
                                label="Coupon Name (Indonesia)"
                                :value="$coupon->translation('id')->name ?? old('voucher_name_id', '')"
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'"
                                placeholder="Coupon Name (Indonesia)"
                            />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700 my-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 border-b pb-2 mb-4">General Information</h3>
                
                    <div
                        x-data="{
                            mode: '{{ $mode }}',
                            type: '{{ old('type', $coupon->type ?? '') }}'
                        }"
                        class="space-y-5"
                    >

                        <x-form.text 
                            id="voucher_code" 
                            label="Coupon Code" 
                            :value="$coupon->voucher_code ?? old('voucher_code')" 
                            :disabled="$mode === 'show'" 
                            :required="$mode === 'create' || $mode === 'edit'"
                            :uppercase="true"
                            placeholder="Coupon Code"
                        />

                        <div class="mb-5">
                            <label for="type" class="block text-sm font-medium mb-1 text-gray-800 dark:text-gray-200">
                                Type
                                @if($mode === 'create' || $mode === 'edit')
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>

                            <select
                                id="type"
                                name="type"
                                x-model="type"
                                x-bind:disabled="mode === 'show'"
                                class="w-full px-3 py-2 text-sm rounded border bg-white text-gray-700 border-gray-300 
                                    focus:outline-none focus:ring-0 focus:border-blue-500
                                    dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600 
                                    disabled:bg-gray-100 disabled:text-gray-400 
                                    dark:disabled:bg-gray-700 dark:disabled:text-gray-500
                                    transition ease-in-out duration-150"
                                @if($mode === 'create' || $mode === 'edit') required @endif
                            >
                                <option value="">Select Type</option>
                                @foreach($lists['types'] as $key => $label)
                                    <option 
                                        value="{{ $key }}"
                                        @selected(old('type', $coupon->type ?? 'fixed_amount') === $key)
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            @error('type')
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <x-form.number 
                            id="amount" 
                            label="Amount" 
                            :value="$coupon->amount" 
                            :required="$mode === 'create' || $mode === 'edit'"
                            :disabled="$mode === 'show'" 
                            :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
                            placeholder="000"
                        />

                        <x-form.number 
                            id="usage_limit" 
                            label="Limit" 
                            :value="$coupon->usage_limit" 
                            :required="$mode === 'create' || $mode === 'edit'"
                            :disabled="$mode === 'show'"
                            placeholder="000"
                        />

                        <x-form.number 
                            id="min_transaction_amount" 
                            label="Min. Transaction Amount" 
                            :value="$coupon->min_transaction_amount" 
                            :required="$mode === 'create' || $mode === 'edit'"
                            :disabled="$mode === 'show'" 
                            :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
                            placeholder="000"
                        />

                        <x-form.number 
                            id="max_discount_amount" 
                            label="Max. Discount Amount" 
                            :value="$coupon->max_discount_amount" 
                            :required="false"
                            :disabled="false"
                            :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
                            placeholder="000"
                            x-bind:disabled="mode === 'show' || type !== 'percentage'"
                            x-bind:required="mode !== 'show' && type === 'percentage'"
                        />

                        <x-form.datetime
                            id="start_date"
                            label="Start Date"
                            :value="$coupon->start_date ?? old('start_date')"
                            :disabled="$mode === 'show'"
                            :required="$mode === 'create' || $mode === 'edit'"
                            helper="Format: YYYY-MM-DD HH:mm"
                        />

                        <x-form.datetime
                            id="end_date"
                            label="End Date"
                            :value="$coupon->end_date ?? old('end_date')"
                            :disabled="$mode === 'show'"
                            :required="$mode === 'create' || $mode === 'edit'"
                            helper="Format: YYYY-MM-DD HH:mm"
                        />

                        <x-form.file-image
                            id="image"
                            label="Featured Image"
                            :disabled="$mode === 'show'"
                            :default="$coupon->image ?? null"
                            :required="$mode === 'create'"
                            ratio="1:1"
                        />

                        <x-form.checkbox
                            id="is_active"
                            label="Is Active"
                            :checked="$coupon->is_active ?? old('is_active')"
                            :disabled="$mode === 'show'"
                        />
                    </div> 
                </div>

                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($coupon)->id ? route('secretgate19.'.$resourceName.'.edit', $coupon->id) : null"
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>

</x-layout.app>