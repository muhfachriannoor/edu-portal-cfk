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
        <div class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow mb-6">
            <form action="{{ $pageMeta['url'] }}" method="post" enctype="multipart/form-data" accept-charset="UTF-8" data-mode="{{ $mode }}" novalidate>
                @method($pageMeta['method'])
                @csrf
                <!-- Card Body -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    
                    <x-form.text 
                        id="name" 
                        label="Name" 
                        :value="$store_owner->name" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />

                    <x-form.text 
                        id="email" 
                        label="Email" 
                        :value="$store_owner->email" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                    />
                    
                    <x-form.select 
                        id="role" 
                        label="Role"
                        :options="$lists['roles']"
                        :disabled="$mode === 'show'"
                        :selected="old('role', $store_owner->role ?? null)" 
                        :required="true"
                    />


                    <x-form.password 
                        id="password"
                        label="New Password"
                        :required="true"
                        :confirmation="true"
                        :hidden="$mode === 'show'"
                        :required="$mode === 'create'" 
                    />

                    <x-form.checkbox
                        id="is_active"
                        label="Is Activated?"
                        :checked="$store_owner->is_active"
                        :disabled="$mode === 'show'"
                    />
                </div>

                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl='optional($store_owner)->id ? route("store_cms.{$resourceName}.edit", ["store" => $storeCms, "store_owner" => $store_owner->id]) : null'
                    :backUrl='route("store_cms.{$resourceName}.index", [$storeCms])' 
                />

            </form>
        </div>
    </div>

</x-layout>

