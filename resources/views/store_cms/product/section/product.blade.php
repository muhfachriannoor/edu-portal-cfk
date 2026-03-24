<div 
    class="tab-content px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 w-full"
    x-data="{
        hasVariants: {{ old('has_variants', $product->has_variants) ? 'true' : 'false' }}
    }"
    x-show="activeTab === 'product'"
>
    <div
        x-show="!isShowPage"
    >
        <x-form.checkbox
            id="has_variants"
            label="Has Variants?"
            name="has_variants"
            x-model="hasVariants"
            :checked="old('has_variants', $product->has_variants)"
            :disabled="$mode === 'show'"
        />
    </div>

    <fieldset x-show="!hasVariants">
        <x-form.number 
            id="price" 
            label="Price" 
            :value="old('price', $product->price)" 
            :required="$mode === 'create' || $mode === 'edit'"
            :disabled="$mode === 'show'" 
            :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
        />

        <x-form.number 
            id="quantity" 
            label="Quantity" 
            :value="old('quantity', $product->quantity)" 
            :disabled="$mode === 'show'" 
            :required="$mode === 'create' || $mode === 'edit'"
            :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
        />

        <x-form.text 
            id="sku" 
            label="SKU" 
            :value="old('sku', $product->sku)" 
            :disabled="$mode === 'show'" 
            :required="$mode === 'create' || $mode === 'edit'"
        />

        <x-form.number 
            id="discount_price" 
            label="Discount Price" 
            :value="old('discount_price', $product->discount_price)" 
            :disabled="$mode === 'show'" 
            {{-- :required="$mode === 'create' || $mode === 'edit'" --}}
            :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
        />

        <x-form.daterange 
            id="discount_period" 
            label="Discount Period" 
            :value="old('discount_period', $product->discount_period)" 
            :disabled="$mode === 'show'" 
            {{-- :required="$mode === 'create' || $mode === 'edit'" --}}
        />
    </fieldset>

    <fieldset x-show="hasVariants">
        <div 
            x-data='{
                masterOptions: @json(old("options_json") ? json_decode(old("options_json")) : $product->master_options),
                variants: @json(old("variants_json") ? json_decode(old("variants_json")) : $product->product_variants),
                listOptionValues: @json($lists['option_values']),
                errorMessage: "",
                option: "",

                addMaster() {
                    this.showError = false;

                    if (this.option === "") {
                        this.errorMessage = "Please select an option first."; // 👈 custom
                        this.showError = true;
                        setTimeout(() => (this.showError = false), 3000);
                        return;
                    }

                    const exists = this.masterOptions.some(m => m.option == this.option);

                    if (exists) {
                        this.errorMessage = "This option is already added."; // 👈 custom
                        this.showError = true;
                        setTimeout(() => (this.showError = false), 3000);
                        return;
                    }

                    let optionText = this.$refs.optionSelect.options[this.$refs.optionSelect.selectedIndex].text;
                    this.masterOptions.push({
                        option: this.option,
                        option_text: optionText,
                        values: []
                    });

                    this.option = "";
                    this.generateVariants();
                },

                removeMaster(index) {
                    this.masterOptions.splice(index, 1);
                    this.generateVariants();
                },
                addValue(masterIndex) {
                    const master = this.masterOptions[masterIndex];

                    // Base structure for all new values
                    const newValue = {
                        id: "",
                        value_id: "",
                        order: master.values.length + 1
                    };

                    // Push new value
                    master.values.push(newValue);

                    this.generateVariants();
                },
                removeValue(masterIndex, valueIndex) {
                    this.masterOptions[masterIndex].values.splice(valueIndex, 1);
                    this.generateVariants();
                },
                generateVariants() {
                    // Collect all value_id arrays per master
                    const optionValues = this.masterOptions.map(master =>
                        master.values
                            .filter(v => v.value_id) // only include values with a selected value_id
                            .map(v => ({ id: v.value_id, name: v.name_en || "" }))
                    );

                    // If any master has no values, clear variants
                    if (optionValues.length === 0 || optionValues.some(v => v.length === 0)) {
                        this.variants = [];
                        return;
                    }

                    // Cartesian product helper (combine option objects)
                    const combine = arr => arr.reduce(
                        (a, b) => a.flatMap(d => b.map(e => [].concat(d, e)))
                    );

                    // Handle 1 or more master options
                    const combinations = optionValues.length > 1
                        ? combine(optionValues)
                        : optionValues[0].map(v => [v]); // normalize single master

                    // Remove duplicate combinations (based on value_id)
                    const uniqueCombinations = [];
                    const seen = new Set();

                    combinations.forEach(combo => {
                        const comboIds = combo.map(o => o.id).join(",");
                        if (!seen.has(comboIds)) {
                            uniqueCombinations.push(combo);
                            seen.add(comboIds);
                        } else {
                            // Optionally show alert for duplicates
                            Swal.fire({
                                icon: "warning",
                                title: "Duplicate Variant",
                                text: `Variant ` + combo.map(o => o.name).join(" / ") + ` already exists.`
                            });
                        }
                    });

                    // Map existing variants by combination string
                    const oldVariants = this.variants.reduce((map, v) => {
                        map[v.combination] = v;
                        return map;
                    }, {});

                    // Generate new variants
                    this.variants = uniqueCombinations.map((combo, index) => {
                        const combinationIds = combo.map(o => o.id).filter(Boolean).join(",");
                        const name = combo.map(o => o.name).filter(Boolean).join(" / ");
                        const old = oldVariants[combinationIds] || {};

                        return {
                            id: old.id || "",
                            combination: combinationIds,
                            name,
                            order: index + 1,
                            quantity: old.quantity || 0,
                            price: old.price || 0,
                            sku: old.sku || "",
                            discount_price: old.discount_price || "",
                        };
                    });
                },
                optionValuesMap(optionKey, valueId) {
                    return (this.$refs.optionValuesList[optionKey] || {})[valueId] || "";
                }
            }'
            class="space-y-6"
        >
            <!-- Add Master Option -->
            <div class="flex flex-col md:flex-row gap-2">
                
                <!-- Select Options -->
                <select 
                    x-model="option"
                    x-ref="optionSelect"
                    x-show="!isShowPage"
                    class="border rounded px-3 py-2 w-full md:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">-- Select Option --</option>

                    @foreach($lists['options'] as $id => $opt)
                        <option value="{{ $id }}">{{ $opt }}</option>
                    @endforeach
                </select>

                <button 
                    x-show="!isShowPage"
                    type="button" 
                    @click="addMaster"
                    class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700"
                >
                    Add
                </button>
            </div>

            <!-- Error message -->
            <span 
                x-show="typeof showError !== 'undefined' && showError"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-500"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="text-red-500 text-sm mt-1"
                x-text="errorMessage"
            >
            </span>

            <!-- Master Option List -->
            <template x-for="(master, masterIndex) in masterOptions" :key="masterIndex">
                <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800 mt-4">
                    
                    <!-- Editable Master Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-3">
                        <!-- Option Name -->
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white" x-text="master.option_text || 'Unnamed Option'"></h3>

                        <!-- Delete Master -->
                        <button 
                            x-show="!isShowPage"
                            type="button"
                            @click="removeMaster(masterIndex)"
                            class="self-start md:self-end bg-red-600 hover:bg-red-700 text-white p-2 rounded text-xs"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>

                    <!-- Options Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse border border-gray-300 border-collapse
                                    [&_th]:border [&_td]:border [&_th]:border-gray-300 [&_td]:border-gray-300 [&_th]:font-bold
                                    [&_th]:px-3 [&_th]:py-2 [&_td]:px-3 [&_td]:py-2
                                    [&_tbody_tr:hover_td]:bg-gray-50 dark:[&_tbody_tr:hover_td]:bg-gray-800 transition-colors duration-150">
                            <thead class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                <tr>
                                    <th class="px-2 py-1 text-left">Value</th>
                                    <th class="px-2 py-1 text-left" x-show="!isShowPage">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(option, optionIndex) in master.values" :key="optionIndex">
                                    <tr class="border-b border-gray-200 dark:border-gray-600">
                                        <td>
                                            <select
                                                x-model="option.value_id"
                                                @change="
                                                    option.name_en = (listOptionValues[String(master.option)] || {})[option.value_id] || '';
                                                    generateVariants()
                                                "
                                                :disabled="isShowPage"
                                                class="w-full border border-gray-300 rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600"
                                            >
                                                <option value="">-- Select Value --</option>
                                                <template x-for="(valName, valId) in (listOptionValues[String(master.option)] || {})" :key="valId">
                                                    <option 
                                                        :value="String(valId)" 
                                                        x-text="valName" 
                                                        :selected="option.value_id == String(valId)">
                                                    </option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="text-center" x-show="!isShowPage">
                                            <button 
                                                type="button"
                                                @click="removeValue(masterIndex, optionIndex)"
                                                class="bg-red-600 hover:bg-red-700 text-white p-2 rounded text-xs"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Add New Option -->
                    <div class="mt-3 text-right">
                        <button 
                            x-show="!isShowPage"
                            type="button"
                            @click="addValue(masterIndex)"
                            class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm"
                        >
                            + Add Option
                        </button>
                    </div>
                </div>
            </template>


            <!-- Variant Table -->
            @if ($errors->has('variants_json'))
                <div class="mb-4 p-3 rounded bg-red-100 text-red-700 border border-red-300 text-sm">
                    {{ $errors->first('variants_json') }}
                </div>
            @endif
            <div x-show="variants.length > 0" class="border border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-gray-100 dark:bg-gray-900 mt-6">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Generated Variants</h3>
                
                <!-- Scrollable container -->
                <div class="overflow-x-auto">
                    <table class="min-w-[800px] w-full table-auto text-sm border border-gray-300 border-collapse
                                [&_th]:border [&_td]:border [&_th]:border-gray-300 [&_td]:border-gray-300 [&_th]:font-bold
                                [&_th]:px-3 [&_th]:py-2 [&_td]:px-3 [&_td]:py-2
                                [&_tbody_tr:hover_td]:bg-gray-50 dark:[&_tbody_tr:hover_td]:bg-gray-800 transition-colors duration-150">
                        <thead class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                            <tr>
                                <th class="px-2 py-1 text-left">Variant Name</th>
                                <th class="px-2 py-1 text-left">Quantity</th>
                                <th class="px-2 py-1 text-left">Price</th>
                                <th class="px-2 py-1 text-left">SKU</th>
                                <th class="px-2 py-1 text-left">Discount Price</th>
                                <th class="px-2 py-1 text-left">Discount Period</th>
                                <th class="px-2 py-1 text-left">Order</th>
                                <th class="px-2 py-1 text-left" x-show="!isShowPage">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(variant, index) in variants" :key="index">
                                <tr class="border-b border-gray-200 dark:border-gray-600">
                                    <td class="px-2 py-1 text-left" x-text="variant.name"></td>
                                    <td class="px-2 py-1"><input type="text" x-model="variant.quantity" min="0"
                                        :disabled="isShowPage"
                                        x-init="() => $( $el ).mask('#.##0', { reverse: true })"
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-center dark:bg-gray-700 dark:border-gray-600" /></td>
                                    <td class="px-2 py-1"><input type="text" x-model="variant.price" step="1" min="0"
                                        :disabled="isShowPage"
                                        x-init="() => $( $el ).mask('#.##0', { reverse: true })"
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-center dark:bg-gray-700 dark:border-gray-600" /></td>
                                    <td class="px-2 py-1"><input type="text" x-model="variant.sku"
                                        :disabled="isShowPage"
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-center dark:bg-gray-700 dark:border-gray-600" /></td>
                                    <td class="px-2 py-1"><input type="text" x-model="variant.discount_price"
                                        x-init="() => $( $el ).mask('#.##0', { reverse: true })"
                                        :disabled="isShowPage"
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-center dark:bg-gray-700 dark:border-gray-600" /></td>
                                    <td class="px-2 py-1">
                                        <input 
                                            type="text" 
                                            x-model="variant.discount_period"
                                            x-init="() => {
                                                $( $el ).daterangepicker({
                                                    autoUpdateInput: false,
                                                    locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' }
                                                });
                                                
                                                $( $el ).on('apply.daterangepicker', (ev, picker) => {
                                                    variant.discount_period = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
                                                });
                                                
                                                $( $el ).on('cancel.daterangepicker', () => {
                                                    variant.discount_period = '';
                                                });
                                            }"
                                            :disabled="isShowPage"
                                            class="w-full border border-gray-300 rounded px-2 py-1 text-center dark:bg-gray-700 dark:border-gray-600"
                                        />
                                    </td>
                                    <td class="px-2 py-1"><input type="number" x-model="variant.order" min="1"
                                        :disabled="isShowPage"
                                        class="w-full border border-gray-300 rounded px-2 py-1 text-center dark:bg-gray-700 dark:border-gray-600" /></td>
                                    <td class="px-2 py-1 text-center" x-show="!isShowPage">
                                        <button 
                                            type="button"
                                            @click="variant.discount_price = ''; variant.discount_period = ''"
                                            class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs"
                                        >
                                            Remove Discount
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Debug -->
            <pre 
                x-show="showDebug" 
                class="bg-gray-900 text-green-400 text-xs p-3 rounded overflow-auto"
            ><code x-text="JSON.stringify({ masterOptions, variants }, null, 2)"></code></pre>

            <!-- Hidden inputs to send JSON to backend -->
            <input type="hidden" name="options_json" :value="JSON.stringify(masterOptions)">
            <input type="hidden" name="variants_json" :value="JSON.stringify(variants)">
        </div>
    </fieldset>
</div>