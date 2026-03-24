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
        <div 
            class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow mb-6"
            x-data='{ 
                activeTab: "detail",
                showDebug: new URLSearchParams(window.location.search).get("developer") === "1",
                isShowPage: @json($mode === "show"),

                masterOptions: @json(old("master_options", $product_option->master_options)),

                syncType(){
                    console.log(this.masterOptions.type)
                },

                addValue() {
                    const master = this.masterOptions;

                    // Base structure for all new values
                    const newValue = {
                        id: "",
                        name_en: "",
                        name_id: ""
                    };

                    // If master type is "color", add a "color" key
                    if (master.type === "color") {
                        newValue.color = "#ffffff"; // default value
                    }

                    // Push new value
                    master.values.push(newValue);

                    // Optional: ensure existing values also match current type
                    master.values.forEach(v => {
                        if (master.type === "color" && !("color" in v)) {
                            v.color = "#ffffff";
                        } else if (master.type !== "color" && "color" in v) {
                            delete v.color;
                        }
                    });
                }
            }'
        >
            
            <form action="{{ $pageMeta['url'] }}" method="post" enctype="multipart/form-data" accept-charset="UTF-8" data-mode="{{ $mode }}" novalidate>
                @method($pageMeta['method'])
                @csrf

                @error('options_json')
                    <div 
                        x-data="{ showAlert: true }" 
                        x-show="showAlert"
                        x-init="setTimeout(() => showAlert = false, 2000)"
                        x-transition:enter="transition ease-out duration-1000"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-500"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="mx-2 my-1 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                        role="alert"
                    >
                        {{-- <strong class="font-bold">Error! </strong> --}}
                        <span class="block sm:inline">Options are mandatory.</span>
                        <span 
                            class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" 
                            @click="showAlert = false"
                        >
                            <svg class="fill-current h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <title>Close</title>
                                <path d="M14.348 5.652a1 1 0 00-1.414 0L10 8.586 7.066 5.652a1 1 0 10-1.414 1.414L8.586 10l-2.934 2.934a1 1 0 101.414 1.414L10 11.414l2.934 2.934a1 1 0 001.414-1.414L11.414 10l2.934-2.934a1 1 0 000-1.414z"/>
                            </svg>
                        </span>
                    </div>
                @enderror

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-3 p-4">
                    <div class="flex flex-col md:flex-row gap-2 w-full">
                        <div class="flex-1">
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Name (EN)</label>
                            <input type="text"
                                x-model="masterOptions.name_en"
                                :disabled="isShowPage"
                                class="w-full border border-gray-300 rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600" />
                        </div>

                        <div class="flex-1">
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Name (ID)</label>
                            <input type="text" 
                                x-model="masterOptions.name_id"
                                :disabled="isShowPage"
                                class="w-full border border-gray-300 rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600" />
                        </div>

                        <!-- 🟢 New Type Select -->
                        <div class="flex-1">
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Type</label>
                            <select 
                                x-model="masterOptions.type"
                                :disabled="isShowPage"
                                class="w-full border border-gray-300 rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600"
                                @change="syncType();"
                            >
                                <option value="text">Text</option>
                                <option value="color">Color</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <div x-data="{ showImageSection: false }" class="px-4 mt-4">

                    <!-- Toggle Button -->
                    <button 
                        type="button"
                        @click="showImageSection = !showImageSection"
                        class="mb-4 px-4 py-2 bg-primary-600 text-black border rounded hover:bg-primary-700 transition"
                    >
                        <span x-show="!showImageSection">Show Image Settings</span>
                        <span x-show="showImageSection">Hide Image Settings</span>
                    </button>

                    <!-- Image Section -->
                    <div 
                        x-show="showImageSection"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="space-y-4"
                    >
                        <x-form.text 
                            id="image_text_en" 
                            label="Image Text (EN)" 
                            :value="$product_option->translation('en')->additional['image_text'] ?? null" 
                            :disabled="$mode === 'show'" 
                        />

                        <x-form.text 
                            id="image_text_id" 
                            label="Image Text (ID)" 
                            :value="$product_option->translation('id')->additional['image_text'] ?? null" 
                            :disabled="$mode === 'show'" 
                        />

                        <x-form.file-image
                            id="image"
                            label="Upload Image"
                            :disabled="$mode === 'show'" 
                            :default="$product_option->file_url"
                        />
                    </div>
                </div>

                <hr>
                <!-- Options Table -->
                <div class="flex flex-col gap-2 mb-3 p-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse border border-gray-300 border-collapse
                                    [&_th]:border [&_td]:border [&_th]:border-gray-300 [&_td]:border-gray-300 [&_th]:font-bold
                                    [&_th]:px-3 [&_th]:py-2 [&_td]:px-3 [&_td]:py-2
                                    [&_tbody_tr:hover_td]:bg-gray-50 dark:[&_tbody_tr:hover_td]:bg-gray-800 transition-colors duration-150">
                            <thead class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                <tr>
                                    <th class="px-2 py-1 text-left">Name (EN)</th>
                                    <th class="px-2 py-1 text-left">Name (ID)</th>

                                    <!-- 🟢 Show Color column if type = color -->
                                    <th class="px-2 py-1 text-left" x-show="masterOptions.type === 'color'">Color</th>

                                    <th class="px-2 py-1 text-left" x-show="!isShowPage">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(option, optionIndex) in masterOptions.values" :key="optionIndex">
                                    <tr class="border-b border-gray-200 dark:border-gray-600">
                                        <td>
                                            <input type="text" 
                                                x-model="option.name_en"
                                                :disabled="isShowPage"
                                                @input.debounce.300ms="generateVariants()"
                                                placeholder="e.g. Small"
                                                class="w-full border border-gray-300 rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600" />
                                        </td>
                                        <td>
                                            <input type="text" 
                                                x-model="option.name_id"
                                                :disabled="isShowPage"
                                                placeholder="e.g. Kecil"
                                                class="w-full border border-gray-300 rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600" />
                                        </td>

                                        <!-- 🟢 Color Picker -->
                                        <td class="text-center" x-show="masterOptions.type === 'color'">
                                            <input type="color"
                                                x-model="option.color"
                                                :disabled="isShowPage"
                                                class="w-10 h-10 border border-gray-300 rounded cursor-pointer" />
                                        </td>

                                        <td class="text-center" x-show="!isShowPage">
                                            <button
                                                @click="masterOptions.values.splice(optionIndex, 1)"
                                                type="button"
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
                            @click="addValue()"
                            type="button"
                            class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm"
                        >
                            + Add Option
                        </button>
                    </div>
                </div>

                <input type="hidden" name="options_json" :value="JSON.stringify(masterOptions)">

                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl='optional($product_option)->id ? route("secretgate19.{$resourceName}.edit", ["product_option" => $product_option->id]) : null'  
                    :backUrl='route("secretgate19.{$resourceName}.index")' 
                />
                
            </form>
        </div>
    </div>
</x-layout>