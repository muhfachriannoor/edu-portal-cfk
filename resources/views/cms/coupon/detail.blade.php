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
            x-data="{ 
                activeTab: 'detail' ,
                showDebug: new URLSearchParams(window.location.search).get('developer') === '1',
                isShowPage: @json($mode === 'show'),
            }"
            >

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <div class="flex space-x-6 text-gray-600 px-6 py-3">
                    <!-- Detail Tab -->
                    <button 
                        type="button" 
                        class="tab-link px-4 py-2"
                        :class="activeTab === 'detail' 
                            ? 'border-b-2 border-blue-500 text-blue-600' 
                            : 'text-gray-500'"
                        @click="activeTab = 'detail'"
                    >
                        Detail
                    </button>
                    
                    <!-- Usage Info Tab -->
                    <button 
                        type="button"
                        class="tab-link px-4 py-2"
                        :class="activeTab === 'usage_info' 
                            ? 'border-b-2 border-blue-500 text-blue-600' 
                            : 'text-gray-500 hover:text-blue-500'"
                        @click="activeTab = 'usage_info'"
                    >
                        Usage Info
                    </button>

                </div>
            </div>
            
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

                <div>
                    {{-- General Section --}}
                    @include('cms.coupon.section.view_form')

                    @include('cms.coupon.section.usage_info')
                    
                </div>

                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($coupon)->id ? route('secretgate19.'.$resourceName.'.edit', $coupon->id) : null"
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />
                
            </form>
        </div>
    </div>
</x-layout>