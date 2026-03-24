<x-layout.app :header="$pageMeta['title']">
    <div class="mb-6">
        <x-form.breadcrumb 
            :title="$pageMeta['title']" 
            :resourceName="$resourceName" 
            :indexLink="route('secretgate19.'. $resourceName . '.index')"
            :action="ucfirst($mode)" 
        />

        <div class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow mb-6">
            {{-- HEADER CARD: Customer Info --}}
            <div class="px-6 pt-6 pb-4">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Customer Info</h3>
            </div>

            <hr class="dark:border-gray-700">
            
            {{-- BODY CARD --}}
            <div class="px-6 pb-6 pt-6"> 
                <div class="space-y-4"> 
                    
                    {{-- Row: Customer ID --}}
                    <div class="grid grid-cols-2">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Customer ID</span>
                        <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $customer->id }}</span>
                    </div>

                    {{-- Row: Name --}}
                    <div class="grid grid-cols-2">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Name</span>
                        <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $customer->name ?? '-' }}</span>
                    </div>
                    
                    {{-- Row: Email --}}
                    <div class="grid grid-cols-2">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Email</span>
                        <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $customer->email ?? '-' }}</span>
                    </div>
                    
                    {{-- Row: Mobile Number --}}
                    <div class="grid grid-cols-2">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Mobile Number</span>
                        <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $customer->mobile_number ?? '-' }}</span>
                    </div>
                    
                    {{-- Row: Date Registered --}}
                    <div class="grid grid-cols-2">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Date Registered</span>
                        <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $customer->created_at->format('Y-m-d H:i') ?? '-' }}</span>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg shadow mb-6">
            {{-- HEADER CARD: Address --}}
            <div class="px-6 pt-6 pb-4">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Address Info</h3>
            </div>

            <hr class="dark:border-gray-700">

            {{-- BODY CARD --}}
            <div class="px-6 pb-6 pt-6 space-y-6">
                @foreach($customer->addresses as $address)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-gray-50 dark:bg-gray-700">
                    
                    {{-- HEADER ALAMAT: ID dan Status/Default --}}
                    <div class="flex justify-between items-start mb-4 pb-2 border-b border-gray-300 dark:border-gray-600">
                        <span class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ $address->label ?? '-' }}</span>
                        
                        
                    </div>
                    
                    {{-- DETAIL ALAMAT (Dibuat per baris 2 kolom untuk Label vs Value) --}}
                    <div class="space-y-3">
                        
                        {{-- Status --}}
                        <div class="grid grid-cols-2">
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Status</span>
                            @php
                                $statusClass = $address->is_default ? 'text-indigo-600' : 'text-gray-600';
                                $statusText = $address->is_default ? 'Default' : ($address->status ?? 'Inactive');
                            @endphp

                            <span class="text-base font-semibold text-right {{ $statusClass }} dark:bg-gray-600 dark:text-white">
                                {{ $statusText }}
                            </span>
                        </div>

                        {{-- Province --}}
                        <div class="grid grid-cols-2">
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Province</span>
                            <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $address->province_name ?? '-' }}</span>
                        </div>

                        {{-- City --}}
                        <div class="grid grid-cols-2">
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">City</span>
                            <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $address->city_name ?? '-' }}</span>
                        </div>

                        {{-- District --}}
                        <div class="grid grid-cols-2">
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">District</span>
                            <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $address->district_name ?? '-' }}</span>
                        </div>

                        {{-- Sub District --}}
                        <div class="grid grid-cols-2">
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Sub District</span>
                            <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $address->subdistrict_name ?? '-' }}</span>
                        </div>

                        {{-- Phone Number --}}
                        <div class="grid grid-cols-2">
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Phone Number</span>
                            <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $address->phone_number ?? '-' }}</span>
                        </div>

                        {{-- Address --}}
                        <div class="grid grid-cols-2">
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Address</span>
                            <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $address->address_line ?? '-' }}</span>
                        </div>
                        
                        {{-- Postal Code --}}
                        <div class="grid grid-cols-2">
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Postal Code</span>
                            <span class="text-base font-semibold text-gray-800 dark:text-gray-100 text-right">{{ $address->postal_code ?? '-' }}</span>
                        </div>
                        
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <x-form.footer
            :mode="$mode" 
            :backUrl="route('secretgate19.' . $resourceName . '.index')" 
        />
    </div>
</x-layout.app>