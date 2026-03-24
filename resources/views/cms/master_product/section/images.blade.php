<div 
    class="tab-content px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
    x-show="activeTab === 'images'"
>
    <fieldset class="space-y-6">

        <!-- 🧾 Product Images Table -->
        <div
            x-data='{
                existingImages: @json($product->product_images ?? []),
                newImages: [], // Only the CURRENT selection
                deletedImageIds: [],
                mainImageIndex: @json($product->main_image_index ?? null),
                
                // Combined array for display
                get displayImages() {
                    return [...this.existingImages, ...this.newImages];
                },

                previewImages(event) {
                    const files = Array.from(event.target.files);
                    
                    // Clear previous new images and add the new selection
                    this.newImages = [];
                    
                    files.forEach(file => {
                        const reader = new FileReader();
                        reader.onload = e => {
                            const newImage = {
                                file: file,
                                url: e.target.result,
                                order: this.displayImages.length + 1,
                                is_new: true
                            };
                            this.newImages.push(newImage);

                            // If no cover selected, set first uploaded as cover
                            if (this.mainImageIndex === null) {
                                this.mainImageIndex = this.displayImages.length - 1;
                            }
                        };
                        reader.readAsDataURL(file);
                    });

                    event.target.value = "";
                    this.updateFileInput();
                },

                removeImage(index) {
                    const displayImages = this.displayImages;
                    const img = displayImages[index];
                    
                    if (img.id) {
                        // Existing image - mark for deletion
                        this.deletedImageIds.push(img.id);
                        // Remove from existingImages
                        const existingIndex = this.existingImages.findIndex(existing => existing.id === img.id);
                        if (existingIndex !== -1) {
                            this.existingImages.splice(existingIndex, 1);
                        }
                    } else if (img.is_new) {
                        // New image - remove from newImages
                        const newIndex = this.newImages.findIndex(newImg => newImg.url === img.url);
                        if (newIndex !== -1) {
                            this.newImages.splice(newIndex, 1);
                        }
                    }

                    // Reassign order numbers
                    this.displayImages.forEach((img, i) => img.order = i + 1);

                    // Handle cover image logic
                    if (this.mainImageIndex === index) {
                        this.mainImageIndex = this.displayImages[0] ? 0 : null;
                    } else if (this.mainImageIndex > index) {
                        this.mainImageIndex--;
                    }
                    
                    this.updateFileInput();
                },

                setCover(index) {
                    this.mainImageIndex = index;
                },

                updateFileInput() {
                    const dataTransfer = new DataTransfer();
                    
                    // Add CURRENT new images to file input
                    this.newImages.forEach(img => {
                        if (img.file) {
                            dataTransfer.items.add(img.file);
                        }
                    });
                    
                    this.$refs.fileInput.files = dataTransfer.files;
                }
            }'
            x-init="updateFileInput()"
            x-effect="updateFileInput()"
        >
            <h3 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Product Images</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                • Max 2MB<br>
                • Ratio 1:1<br>
                • Format: jpg, png, webp <br>
                • Max 10 Images
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 italic">
                <strong>Click on an image to set it as the cover image.</strong>
            </p>

            <div class="mb-3" x-show="!isShowPage">
                <button 
                    type="button"
                    @click="$refs.fileInput.click()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm"
                >
                    Add Images
                </button>
                <input 
                    type="file"
                    x-ref="fileInput"
                    name="product_images[]"
                    multiple
                    accept="image/*"
                    class="hidden"
                    @change="previewImages"
                />
            </div>

            <template x-if="displayImages.length > 0">
                <table class="w-full text-sm border border-gray-300 border-collapse
                            [&_th]:border [&_td]:border [&_th]:border-gray-300 [&_td]:border-gray-300 [&_th]:font-bold
                            [&_th]:px-3 [&_th]:py-2 [&_td]:px-3 [&_td]:py-2
                            [&_tbody_tr:hover_td]:bg-gray-50 dark:[&_tbody_tr:hover_td]:bg-gray-800 transition-colors duration-150">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="p-2 border">Image</th>
                            <th class="p-2 border">Sort Order</th>
                            <th class="p-2 border" x-show="!isShowPage">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(img, index) in displayImages" :key="img.id ?? img.url">
                            <tr class="border-t">
                                <td class="p-2 text-center relative">
                                    <div 
                                        @click="!isShowPage && setCover(index)"
                                        :class="[
                                            mainImageIndex === index ? 'border-4 border-blue-500' : 'border border-gray-300',
                                            isShowPage ? 'cursor-default' : 'cursor-pointer hover:border-blue-400'
                                        ]"
                                        class="w-32 h-32 mx-auto rounded overflow-hidden relative transition-all"
                                    >
                                        <img :src="img.url" class="w-full h-full object-cover">
                                        <span 
                                            x-show="mainImageIndex === index"
                                            class="absolute top-1 right-1 bg-blue-500 text-white px-1 text-xs rounded">Cover</span>
                                        <span 
                                            x-show="img.is_new"
                                            class="absolute top-1 left-1 bg-green-500 text-white px-1 text-xs rounded">New</span>
                                    </div>
                                </td>
                                <td class="p-2 text-center">
                                    <input type="number" x-model="img.order" name="image_order[]" class="w-full text-center" :disabled="isShowPage">
                                </td>
                                <td class="p-2 text-center" x-show="!isShowPage">
                                    <button type="button" @click="removeImage(index)" class="bg-red-600 hover:bg-red-700 text-white p-2 rounded text-xs">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>

            <template x-if="images.length === 0">
                <p class="text-gray-500 text-sm italic">No product images added.</p>
            </template>

            <!-- Hidden inputs -->
            <input type="hidden" name="deleted_image_ids" :value="deletedImageIds.join(',')">
            <input type="hidden" name="main_image_index" :value="mainImageIndex">
        </div>

    </fieldset>
</div>

@push('scripts')
<script>
    
</script>
@endpush