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
                {{-- <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">

                    <x-form.text 
                        id="title" 
                        label="Title" 
                        :value="$notification->title ?? old('title')" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.textarea
                        id="message" 
                        label="Message" 
                        :value="$notification->message ?? old('message')" 
                        :disabled="$mode === 'show'"
                    />

                </div> --}}

                <div class="px-6 py-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 space-y-6">

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
                                id="title_en" 
                                label="Title (English)" 
                                :value="$notification->translation('en')->name ?? old('title_en', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                                placeholder="Title (English)"
                            />

                            <x-form.textarea
                                id="message_en" 
                                label="Message (English)" 
                                :value="$notification->translation('en')->description ?? old('message_en', '')" 
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />
                        </div>

                        <!-- Indonesian Fields -->
                        <div x-show="tab === 'id'" x-cloak class="space-y-5">
                            <x-form.text 
                                id="title_id" 
                                label="Title (Indonesia)"
                                :value="$notification->translation('id')->name ?? old('title_id', '')"
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'"
                                placeholder="Title (Indonesia)"
                            />

                            <x-form.textarea
                                id="message_id" 
                                label="Message (Indonesia)"
                                :value="$notification->translation('id')->description ?? old('message_id', '')"
                                :disabled="$mode === 'show'" 
                                :required="$mode === 'create' || $mode === 'edit'" 
                            />
                        </div>
                    </div>
                </div>

                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($notification)->id ? route('secretgate19.'.$resourceName.'.edit', $notification->id) : null"
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>

</x-layout.app>