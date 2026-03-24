<x-layout.store.app :header="$pageMeta['title']">

    <div class="mb-6 dark:bg-gray-900 dark:text-white">
        <!-- Breadcrumb & Page Header -->
        <x-form.breadcrumb 
            :title="$pageMeta['title']" 
            :resourceName="$resourceName" 
            :indexLink="route('store_cms.' . $resourceName . '.index', [$storeCms])" 
            :action="ucfirst($mode)" 
            baseLink="store_cms"
            :routeParam="[$storeCms]"
        />

        <!-- Form Card -->
        <div 
            class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow mb-6"
        >
            <form action="{{ $pageMeta['url'] }}" method="post" enctype="multipart/form-data" accept-charset="UTF-8" data-mode="{{ $mode }}" novalidate>
                @method($pageMeta['method'])
                @csrf
                <!-- Card Body -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                              
                    {{-- @dump($lists['variants']) --}}
                    <x-form.listbox-search 
                        id="product_variant_id" 
                        label="Product Variant"
                        :options="$lists['variants']"
                        :disabled="$mode === 'show'"
                        :selected="old('role', $special_price->product_variant_id ?? null)" 
                        :required="true"
                    />

                    {{-- Preview Product Variant --}}
                    <div id="variant-preview" class="my-4 hidden">
                        <div class="flex items-start gap-4 p-4 bg-white dark:bg-gray-900 border border-gray-700 rounded-lg">
                            <div class="w-24 h-24 rounded overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <img id="variant-image"
                                    src=""
                                    alt="Product image"
                                    class="w-full h-full object-cover"
                                >
                            </div>

                            <div class="flex-1 space-y-1 text-sm">
                                <div class="font-semibold text-gray-900 dark:text-gray-100" id="variant-name">
                                    {{-- INITIAL FROM AJAX JS --}}
                                </div>

                                <div class="text-gray-600 dark:text-gray-300">
                                    SKU:
                                    <span id="variant-sku" class="font-mono">-</span>
                                </div>

                                <div class="text-gray-600 dark:text-gray-300">
                                    Quantity:
                                    <span id="variant-quantity">-</span>
                                </div>

                                <div class="text-gray-800 dark:text-gray-100">
                                    Price:
                                    <span id="variant-price" class="font-semibold">-</span>
                                </div>

                                <div class="text-gray-800 dark:text-gray-100 hidden" id="special-price-row">
                                    Special Price:
                                    <span id="variant-special-price" class="font-semibold text-green-600 dark:text-green-400">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-form.listbox-search
                        id="type" 
                        label="Type"
                        :options="$lists['type']"
                        :disabled="$mode === 'show'"
                        :selected="old('role', $special_price->type ?? null)" 
                        :required="true"
                    />

                    <x-form.number 
                        id="discount" 
                        label="Discount" 
                        :value="$special_price->discount" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
                    />

                    <x-form.text 
                        id="percentage" 
                        label="Percentage" 
                        :value="$special_price->percentage" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.daterange 
                        id="period"
                        label="Period" 
                        :value="$special_price->period"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.checkbox
                        id="is_active"
                        label="Is Activated?"
                        :checked="$special_price->is_active"
                        :disabled="$mode === 'show'"
                    />
                </div>

                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl='optional($special_price)->id ? route("store_cms.{$resourceName}.edit", ["store" => $storeCms, "special_price" => $special_price->id]) : null'
                    :backUrl='route("store_cms.{$resourceName}.index", [$storeCms])' 
                />

            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // --- Utility & State for calculation special price ---

            let currentVariantPrice = null;

            function formatRupiah(value) {
                if (value === null || value === undefined || isNaN(value)) {
                    return '-';
                }

                return 'IDR. ' + Number(value).toLocaleString('id-ID');
            }

            function getDiscountValue() {
                const el = document.getElementById('discount');
                if (!el) return 0;

                // Mask input number "5.000" -> 5000
                const raw = (el.value || '').replace(/\./g, '').replace(/,/g, '');
                const num = parseInt(raw, 10);

                return isNaN(num) ? 0 : num;
            }

            function getPercentageValue() {
                const el = document.getElementById('percentage');
                if (!el) return 0;

                const raw = (el.value || '').replace(',', '.');
                const num = parseFloat(raw);
                return isNaN(num) ? 0 : num;
            }

            function updateSpecialPricePreview() {
                const row = document.getElementById('special-price-row');
                const valueEl = document.getElementById('variant-special-price');
                const typeEl = document.getElementById('type');

                if (!row || !valueEl || !typeEl || !currentVariantPrice) {
                    if (row) row.classList.add('hidden');
                    return;
                }

                const type = typeEl.value;
                let specialPrice = null;

                if (type === 'discount') {
                    const discount = getDiscountValue();
                    
                    if (discount > 0) {
                        specialPrice = Math.max(currentVariantPrice - discount, 0);
                    }
                } else if (type === 'percentage') {
                    const percentage = getPercentageValue();

                    if (percentage > 0) {
                        specialPrice = currentVariantPrice - (currentVariantPrice * (percentage / 100));
                    }
                }

                if (specialPrice !== null) {
                    row.classList.remove('hidden');
                    valueEl.textContent = formatRupiah(Math.round(specialPrice));
                } else {
                    row.classList.add('hidden');
                }
            }

            // --- VALIDASI PERCENTAGE ---

            const percentageInput = document.getElementById('percentage');

            if (percentageInput) {
                // Prevent typing invalid characters
                percentageInput.addEventListener('input', function () {
                    let value = this.value;

                    // Remove anything except digits and decimal point
                    value = value.replace(/[^0-9.]/g, '');

                    // Only allow one decimal point
                    const parts = value.split('.');
                    if (parts.length > 2) {
                        value = parts[0] + '.' + parts[1];
                    }

                    // Limit decimals to 2 digits
                    if (parts[1]) {
                        parts[1] = parts[1].slice(0, 2);
                        value = parts.join('.');
                    }

                    this.value = value;

                    updateSpecialPricePreview();
                });

                // Clamp value to 0–100 and force 2 decimals on blur
                percentageInput.addEventListener('blur', function () {
                    let value = parseFloat(this.value);

                    if (isNaN(value) || value < 0) value = 0;
                    if (value > 100) value = 100;

                    this.value = value.toFixed(2);

                    updateSpecialPricePreview();
                });
            }

            // Trigger calculation discount change
            const discountInput = document.getElementById('discount');

            if (discountInput) {
                discountInput.addEventListener('input', function () {
                    updateSpecialPricePreview();
                });
            }

            // --- SHOW/HIDE DISCOUNT & PERCENTAGE ---

            @if($special_price->type == 'discount')
                $('#percentage').closest('.mb-5').hide();
            @elseif($special_price->type == 'percentage')
                $('#discount').closest('.mb-5').hide();
            @else
                $('#percentage').closest('.mb-5').hide();
                $('#discount').closest('.mb-5').hide();
            @endif
                
            $(document).on('change', '#type', function() {
                let type = $(this).val();

                switch(type){
                    case 'discount':
                        $('#discount').closest('.mb-5').show(500);
                        $('#percentage').closest('.mb-5').hide(500);
                        break;
                        
                    case 'percentage':
                        $('#discount').closest('.mb-5').hide(500);
                        $('#percentage').closest('.mb-5').show(500);
                        break;
                }

                updateSpecialPricePreview();
            });

            // Script: preview product variant (price + image + sku + qty)
            (function () {
                const variantSelect = document.getElementById('product_variant_id');
                const previewWrapper = document.getElementById('variant-preview');
                const imageEl = document.getElementById('variant-image');
                const nameEl = document.getElementById('variant-name');
                const skuEl = document.getElementById('variant-sku');
                const qtyEl = document.getElementById('variant-quantity');
                const priceEl = document.getElementById('variant-price');

                if (!variantSelect || !previewWrapper) {
                    return;
                }

                const urlTemplate = "{{ route('store_cms.special_price.variant-detail', [
                    'store'   => $storeCms,
                    'variant' => '__VARIANT_ID__',
                ]) }}";

                function loadVariantDetail(variantId) {
                    if (!variantId) {
                        previewWrapper.classList.add('hidden');
                        currentVariantPrice = null;
                        updateSpecialPricePreview();
                        return;
                    }

                    const url = urlTemplate.replace('__VARIANT_ID__', variantId);

                    fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Failed to fetch variant detail');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (!data) {
                                previewWrapper.classList.add('hidden');
                                currentVariantPrice = null;
                                updateSpecialPricePreview();
                                return;
                            }

                            previewWrapper.classList.remove('hidden');

                            currentVariantPrice = data.price || null;

                            if (imageEl && data.image_url) {
                                imageEl.src = data.image_url;
                            }

                            if (nameEl) {
                                nameEl.textContent = data.product_name || '';
                            }

                            if (skuEl) {
                                skuEl.textContent = data.sku || '-';
                            }

                            if (qtyEl) {
                                qtyEl.textContent = data.quantity ?? '-';
                            }

                            if (priceEl) {
                                priceEl.textContent = data.formatted_price || formatRupiah(data.price);
                            }

                            updateSpecialPricePreview();
                        })
                        .catch(error => {
                            console.error(error);
                            previewWrapper.classList.add('hidden');
                            currentVariantPrice = null;
                            updateSpecialPricePreview();
                        });
                }

                // Change handler
                variantSelect.addEventListener('change', function (event) {
                    const variantId = event.target.value;
                    loadVariantDetail(variantId);
                });

                // Initial load (edit/show)
                const initialVariantId = '{{ $special_price->product_variant_id ?? '' }}';
                if (initialVariantId) {
                    loadVariantDetail(initialVariantId);
                }
            })();
        </script>
    @endpush
</x-layout>

