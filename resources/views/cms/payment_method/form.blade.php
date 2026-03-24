<x-layout.app :header="$pageMeta['title']">

    <div class="mb-6 dark:bg-gray-900 dark:text-white">
        <!-- Breadcrumb & Page Header -->
        <x-form.breadcrumb 
            :title="$pageMeta['title']" 
            :resourceName="$resourceName" 
            :indexLink="route('secretgate19.'. $resourceName . '.index')"
            :action="ucfirst($mode)" 
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
                        :value="$paymentMethod->name ?? old('name', '')" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'" 
                        placeholder="Name"
                    />

                    <x-form.text 
                        id="code" 
                        label="Code" 
                        :value="$paymentMethod->code ?? old('code')" 
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        :uppercase="true"
                        placeholder="Code"
                    />

                    <x-form.listbox-search
                        id="channel_category_id" 
                        label="Payment Category" 
                        :options="$lists['payment_category']"
                        :selected="$paymentMethod->channel_category_id" 
                        :disabled="$mode === 'show'" 
                        
                        x-bind:required="('{{ $mode }}' === 'create' || '{{ $mode }}' === 'edit')"
                    />

                    <x-form.number 
                        id="expires_in_hours" 
                        label="Expires In Hours" 
                        :value="$paymentMethod->expires_in_hours" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        :disabled="$mode === 'show'" 
                    />

                    <x-form.number 
                        id="minimum_amount" 
                        label="Minimum Amount" 
                        :value="$paymentMethod->minimum_amount" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        :disabled="$mode === 'show'" 
                        :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
                    />

                    <x-form.number 
                        id="cost" 
                        label="Cost" 
                        :value="$paymentMethod->cost" 
                        :required="$mode === 'create' || $mode === 'edit'"
                        :disabled="$mode === 'show'" 
                        :mask="['mask' => '#.##0', 'options' => ['reverse' => true]]"
                    />

                    <x-form.text 
                        id="account_name" 
                        label="Account Name" 
                        :value="$paymentMethod->account_name ?? old('account_name', '')" 
                        :disabled="$mode === 'show'" 
                        {{-- :required="$mode === 'create' || $mode === 'edit'"  --}}
                        placeholder="Account Name"
                    />

                    <x-form.text 
                        id="account_number" 
                        label="Account Number" 
                        :value="$paymentMethod->account_number ?? old('account_number', '')" 
                        :disabled="$mode === 'show'" 
                        {{-- :required="$mode === 'create' || $mode === 'edit'"  --}}
                        placeholder="127xxxx"
                    />

                    <x-form.textarea
                        id="description" 
                        label="Description" 
                        :value="$paymentMethod->description ?? old('description', '')" 
                        :disabled="$mode === 'show'" 
                    />

                    <x-form.file-image
                        id="channel_image"
                        label="Upload Image"
                        :disabled="$mode === 'show'" 
                        :required="$mode === 'create'"
                        :default="$paymentMethod->image ?? null"
                        ratio="1:1"
                        min-resolution="100 x 100"
                    />
                    
                    <x-form.checkbox
                        id="is_enabled"
                        label="Is Enabled?"
                        :checked="$paymentMethod->is_enabled"
                        :disabled="$mode === 'show'"
                    />

                    <x-form.checkbox
                        id="is_published"
                        label="Is Published?"
                        :checked="$paymentMethod->is_published"
                        :disabled="$mode === 'show'"
                    />

                    <x-form.checkbox
                        id="is_manual"
                        label="Is Manual?"
                        :checked="$paymentMethod->is_manual"
                        :disabled="$mode === 'show'"
                    />
                </div>

                <!-- Card Footer -->
                <x-form.footer
                    :mode="$mode" 
                    :editUrl="optional($paymentMethod)->id ? route('secretgate19.'.$resourceName.'.edit', $paymentMethod->id) : null"  
                    :backUrl="route('secretgate19.'.$resourceName.'.index')" 
                />

            </form>
        </div>
    </div>

</x-layout.app>

<script src="{{ asset('assets/scripts/hide-form.js') }}"></script>
<script src="{{ asset('assets/scripts/slugify.js') }}" defer></script>