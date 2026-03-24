<div 
    class="tab-content px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
    x-show="activeTab === 'detail'"    
>
    <fieldset>
        <x-form.text 
            id="name" 
            label="Name" 
            :value="$product->name" 
            :disabled="$mode === 'show'" 
            :required="$mode === 'create' || $mode === 'edit'" 
        />
        
        @if ($mode === 'create')
            <x-form.multi-listbox
                id="store_id"
                label="Store"
                :options="$lists['stores']"
                :selected="old('store_id', $product->store_id ?? null)"
                :required="true"
            />
        @else
            {{-- show / edit --}}
            <x-form.listbox-search
                id="store_id"
                label="Store"
                :options="$lists['stores']"
                :disabled="in_array($mode, ['show', 'edit'])"
                :selected="old('store_id', $product->store_id ?? null)"
                :required="true"
            />
            @if($mode === 'edit')
                <input type="hidden" name="store_id" value="{{ $product->store_id }}">
            @endif
        @endif
        
        <x-form.listbox-search
            id="category_id" 
            label="Category"
            :options="$lists['categories']"
            :disabled="$mode === 'show'"
            :selected="old('category', $product->category_id ?? null)" 
            :required="true"
            x-ref="categoryBox"
        />
        
        <x-form.listbox-search
            id="sub_category_id" 
            label="Sub Category"
            :options="$lists['sub_categories']"
            :disabled="$mode === 'show'"
            :selected="old('sub_category', $product->sub_category_id ?? null)" 
            :required="true"
            x-ref="subCategoryBox"
        />
        
        <x-form.listbox-search
            id="brand_id" 
            label="Brands"
            :options="$lists['brands']"
            :disabled="$mode === 'show'"
            :required="true"
            :selected="old('sub_category', $product->brand_id ?? null)" 
        />

        <x-form.text 
            id="tags" 
            label="Tags" 
            :value="$product->tags" 
            :disabled="$mode === 'show'" 
            :required="$mode === 'create' || $mode === 'edit'"
            notes="Separate tags with commas (e.g., elegant, traditional, arts)."
        />

        <x-form.checkbox
            id="is_active"
            label="Is Activated?"
            :checked="$product->is_active"
            :disabled="$mode === 'show'"
        />

        <x-form.checkbox
            id="is_bestseller"
            label="Is Bestseller?"
            :checked="$product->is_bestseller"
            :disabled="$mode === 'show'"
        />
        
        <x-form.checkbox
            id="is_truly_indonesian"
            label="Is Truly Indonesian?"
            :checked="$product->is_truly_indonesian"
            :disabled="$mode === 'show'"
        />
        
        <x-form.checkbox
            id="is_limited_edition"
            label="Is Limited Edition?"
            :checked="$product->is_limited_edition"
            :disabled="$mode === 'show'"
        />

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
                <x-form.textarea
                    id="story_en" 
                    label="Description (English)" 
                    :value="$product->translation('en')->additional['story'] ?? ''" 
                    :disabled="$mode === 'show'" 
                />
                
                <x-form.textarea
                    id="material_en" 
                    label="Material (English)" 
                    :value="$product->translation('en')->additional['material'] ?? ''" 
                    :disabled="$mode === 'show'" 
                />
            </div>

            <!-- Indonesian Fields -->
            <div x-show="tab === 'id'" x-cloak class="space-y-5">
                <x-form.textarea
                    id="story_id" 
                    label="Description (Indonesia)" 
                    :value="$product->translation('id')->additional['story'] ?? ''" 
                    :disabled="$mode === 'show'" 
                />
                
                <x-form.textarea
                    id="material_id" 
                    label="Material (Indonesia)" 
                    :value="$product->translation('id')->additional['material'] ?? ''" 
                    :disabled="$mode === 'show'" 
                />
            </div>

            {{-- Feature Section --}}
            <div x-data="featureSection()" class="mb-5">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Feature
                </label>
                <!-- New Feature Button -->
                <button 
                    x-show="!isShowPage"
                    type="button" 
                    @click="openModal()"
                    class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 text-xs"
                >
                    New Feature
                </button>

                <!-- Features Table -->
                <div x-show="features.length > 0" class="overflow-x-auto mt-4">
                    <table class="min-w-full border border-gray-300 rounded-lg">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="px-3 py-2">Image</th>
                                <th class="px-3 py-2">Text (EN)</th>
                                <th class="px-3 py-2">Text (ID)</th>
                                <th x-show="!isShowPage" class="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(feature, index) in features" :key="feature.id || index">
                                <tr class="border-b">
                                    <td class="px-3 py-2">
                                        <img :src="feature.preview || feature.existing_image" alt="Feature Image" class="w-16 h-16 object-cover rounded">
                                    </td>
                                    <td class="px-3 py-2" x-text="feature.text_en"></td>
                                    <td class="px-3 py-2" x-text="feature.text_id"></td>
                                    <td x-show="!isShowPage" class="px-3 py-2">
                                        <div class="flex space-x-2">
                                            <button type="button" 
                                                    @click="editFeature(index)" 
                                                    class="bg-green-600 text-white px-2 py-1 text-sm rounded hover:bg-green-700">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>

                                            <button type="button" 
                                                    @click="removeFeature(index)" 
                                                    class="bg-red-600 text-white px-2 py-1 text-sm rounded hover:bg-red-700">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Modal for Add/Edit -->
                <div 
                    x-show="showModal" 
                    x-transition
                    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                >
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-96 relative">
                        <h3 class="text-lg font-semibold mb-4" x-text="editingIndex !== null ? 'Edit Feature' : 'Add Feature'"></h3>

                        <!-- Image Upload -->
                        <div class="mb-4">
                            <label class="block mb-1">Image</label>
                            <input type="file" accept="image/*" @change="previewImage($event)" x-ref="fileInput">
                            <template x-if="preview || (editingIndex !== null && features[editingIndex]?.existing_image)">
                                <img :src="preview || (editingIndex !== null && features[editingIndex]?.existing_image)" 
                                    alt="Preview" class="mt-2 w-32 h-32 object-cover rounded">
                            </template>
                            <template x-if="editingIndex !== null && features[editingIndex]?.existing_image && !preview">
                                <div class="mt-1 text-sm text-gray-600">
                                    Current image will be kept unless changed
                                </div>
                            </template>
                        </div>

                        <!-- Text EN -->
                        <div class="mb-4">
                            <label class="block mb-1">Text (EN)</label>
                            <textarea x-model="newFeature.text_en" class="w-full border rounded px-2 py-1"></textarea>
                        </div>

                        <!-- Text ID -->
                        <div class="mb-4">
                            <label class="block mb-1">Text (ID)</label>
                            <textarea x-model="newFeature.text_id" class="w-full border rounded px-2 py-1"></textarea>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="saveFeature()" 
                                class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700"
                                x-text="editingIndex !== null ? 'Update' : 'Add'">
                            </button>
                            <button type="button" @click="closeModal()" class="bg-gray-300 px-3 py-1 rounded hover:bg-gray-400">Cancel</button>
                        </div>
                    </div>
                </div>

                <!-- Hidden fields for form submission -->
                <input type="hidden" name="features_json" :value="JSON.stringify(features.map(f => ({ 
                    id: f.id, // For existing features
                    text_en: f.text_en, 
                    text_id: f.text_id,
                    _destroy: f._destroy || false, // For soft deletion
                    image_changed: f.image_changed || false // Track if image was changed
                })))">
                
                <!-- File inputs for new/changed images -->
                <input type="file" name="feature_images[]" multiple class="hidden" x-ref="filesInput">
                
                <!-- Hidden inputs for deleted images -->
                <template x-for="(feature, index) in features" :key="feature.id || index">
                    <template x-if="feature._destroy && feature.id">
                        <input type="hidden" :name="`deleted_features[]`" :value="feature.id">
                    </template>
                </template>
            </div>
        </div>
        
    </fieldset>
        
</div>

@push('scripts')
    <script>
        @if(!$product->sub_category_id)
            $('#sub_category_id').closest('.mb-5').hide();
        @endif

        $(document).on('change', '#category_id', function() {
            const categoryId = $(this).val();
            const url = "{{ route('ajax.category_list') }}";

            if (!categoryId) {
                $('#sub_category_id').closest('.mb-5').hide(500);
                return;
            }

            $('#sub_category_id').closest('.mb-5').show(500);

            $.ajax({
                url: `${url}`,
                data: {
                    category: categoryId
                },
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    const subBoxRoot = document.querySelector('#sub_category_id').closest('[x-data]');
                    const subBox = Alpine.$data(subBoxRoot);

                    subBox.options = [
                        { key: "", text: "Select Sub Category" },
                        ...response.data.map(item => ({ key: item.id, text: item.name }))
                    ];

                    subBox.selected = "";
                    subBox.selectedLabel = "Select Sub Category";
                },
                error: function(xhr) {
                    console.error('Failed to load subcategories:', xhr.responseText);
                }
            });
        });

        function featureSection() {
            return {
                features: @json($product->featuresJson ?? []), // Pass existing data from Laravel
                showModal: false,
                preview: null,
                editingIndex: null,
                newFeature: {
                    id: null,
                    file: null,
                    text_en: '',
                    text_id: '',
                    existing_image: null,
                    image_changed: false
                },

                init() {
                    // Initialize existing features with proper structure
                    this.features = this.features.map(feature => ({
                        ...feature,
                        existing_image: feature.image_path ?? null,
                        image_changed: false,
                        _destroy: false
                    }));
                },

                openModal() {
                    this.showModal = true;
                    this.editingIndex = null;
                    this.preview = null;
                    this.newFeature = { 
                        id: null, 
                        file: null, 
                        text_en: '', 
                        text_id: '',
                        existing_image: null,
                        image_changed: false 
                    };
                    this.$refs.fileInput.value = '';
                },

                editFeature(index) {
                    const feature = this.features[index];
                    this.editingIndex = index;
                    this.showModal = true;
                    this.preview = null;
                    this.newFeature = {
                        id: feature.id,
                        file: null,
                        text_en: feature.text_en,
                        text_id: feature.text_id,
                        existing_image: feature.existing_image,
                        image_changed: false
                    };
                    this.$refs.fileInput.value = '';
                },

                closeModal() {
                    this.showModal = false;
                    this.editingIndex = null;
                },

                previewImage(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    
                    this.newFeature.file = file;
                    this.newFeature.image_changed = true;
                    
                    // Create preview
                    const reader = new FileReader();
                    reader.onload = e => this.preview = e.target.result;
                    reader.readAsDataURL(file);
                },

                saveFeature() {
                    if (!this.newFeature.text_en || !this.newFeature.text_id) {
                        alert('Please fill all text fields.');
                        return;
                    }

                    const featureData = {
                        id: this.newFeature.id,
                        text_en: this.newFeature.text_en,
                        text_id: this.newFeature.text_id,
                        existing_image: this.newFeature.existing_image,
                        image_changed: this.newFeature.image_changed,
                        _destroy: false
                    };

                    if (this.newFeature.file) {
                        featureData.preview = this.preview;
                        featureData.file = this.newFeature.file;
                    }

                    if (this.editingIndex !== null) {
                        // Update existing feature
                        this.features[this.editingIndex] = {
                            ...this.features[this.editingIndex],
                            ...featureData
                        };
                    } else {
                        // Add new feature
                        this.features.push(featureData);
                    }

                    // Update files input
                    this.updateFilesInput();
                    this.closeModal();
                },

                removeFeature(index) {
                    const feature = this.features[index];
                    
                    if (feature.id) {
                        // Mark existing feature for deletion
                        this.features[index]._destroy = true;
                    } else {
                        // Remove new feature completely
                        this.features.splice(index, 1);
                    }
                    
                    this.updateFilesInput();
                },

                updateFilesInput() {
                    const dataTransfer = new DataTransfer();
                    
                    // Add only new/changed files
                    this.features.forEach(feature => {
                        if (feature.file && !feature._destroy) {
                            dataTransfer.items.add(feature.file);
                        }
                    });
                    
                    this.$refs.filesInput.files = dataTransfer.files;
                }
            }
        }
    </script>
@endpush