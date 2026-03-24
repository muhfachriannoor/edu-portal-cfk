<x-layout.app :header="$pageMeta['title']">

    <div class="mb-6 dark:bg-gray-900 dark:text-white">
        <x-form.breadcrumb 
            :title="$pageMeta['title']"
            :resourceName="$resourceName"
            :indexLink="route('secretgate19.'. $resourceName . '.index')"
            :action="ucfirst($mode)"
        />

        <div class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow mb-6">
            <form action="{{ $pageMeta['url'] }}" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
                @method($pageMeta['method'])
                @csrf

                <div class="px-6 py-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    
                    @if($setting->category === 'CONTENT')

                        <div x-data="{ tab: 'en' }" class="space-y-4">
                            <div class="flex border-b border-gray-300 dark:border-gray-700">
                                <button type="button" @click="tab = 'en'" :class="tab === 'en' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500'" class="px-4 py-2 text-sm transition">
                                    English
                                </button>
                                <button type="button" @click="tab = 'id'" :class="tab === 'id' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500'" class="px-4 py-2 text-sm transition">
                                    Indonesian
                                </button>
                            </div>

                            <!-- Load different forms based on the setting key -->
                            @if ($setting->key === 'HOME')
                                @include('cms.setting.page.home_not_logged')

                            @elseif ($setting->key === 'COMPANY_PROFILE')
                                @include('cms.setting.page.company_profile')

                            @elseif ($setting->key === 'EMAIL_PREFERENCE')
                                @include('cms.setting.page.email_preference')

                            @elseif ($setting->key === 'TERM_AND_CONDITION')
                                @include('cms.setting.page.term_and_condition')

                            @elseif ($setting->key === 'PRIVACY_POLICY')
                                @include('cms.setting.page.privacy_policy')

                            @elseif ($setting->key === 'HOW_TO_ORDER')
                                @include('cms.setting.page.how_to_order')
                                
                            @elseif ($setting->key === 'ABOUT_SARINAH')
                                @include('cms.setting.page.about_sarinah')

                            @elseif ($setting->key === 'INTELLECTUAL_PROPERTY')
                                @include('cms.setting.page.intellectual_property')

                            @elseif ($setting->key === 'NEWSROOM')
                                @include('cms.setting.page.newsroom')

                            @elseif ($setting->key === 'CAREERS')
                                @include('cms.setting.page.careers')

                            @elseif ($setting->key === 'AFFILIATES')
                                @include('cms.setting.page.affiliates')

                            @elseif ($setting->key === 'SARINAH_CARE')
                                @include('cms.setting.page.sarinah_care')

                            @elseif ($setting->key === 'SELL_ON_SARINAH')
                                @include('cms.setting.page.sell_on_sarinah')

                            @elseif ($setting->key === 'SHIPPING')
                                @include('cms.setting.page.shipping')

                            @elseif ($setting->key === 'RETURN_POLICY')
                                @include('cms.setting.page.return_policy')
                                
                            @elseif ($setting->key === 'EVENTS')
                                @include('cms.setting.page.events')
                                
                            @elseif ($setting->key === 'SUSTAINABILITY')
                                @include('cms.setting.page.sustainability')

                            @else
                                <!-- Default form if no specific setting key matches -->
                                <x-form.text id="default_field" label="Default Field" :value="$setting->data['default_field'] ?? ''" />
                            @endif
                        </div>

                    @else
                        @if ($setting->key === 'SARINAH_API')
                            @include('cms.setting.page.sarinah_api')

                        @elseif ($setting->key === 'ORDER_STATUS_ROLES')
                            @include('cms.setting.page.order_status_roles')
                        @endif
                    @endif
                </div>

                <x-form.footer :mode="$mode" :editUrl="optional($setting)->id ? route('secretgate19.'.$resourceName.'.edit', $setting->id) : null" :backUrl="route('secretgate19.'.$resourceName.'.index')" />
            </form>
        </div>
    </div>

</x-layout.app>
