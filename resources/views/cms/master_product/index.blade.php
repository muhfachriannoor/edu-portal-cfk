<x-layout.app :header="$pageMeta['title']">

    <div id="pageWrapper" data-page="{{ $resourceName }}">
        <!-- Breadcrumb -->
        <x-form.breadcrumb 
            :title="$pageMeta['title']"
        />

        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 rounded-b-lg shadow">
            <div class="p-6">
                <!-- Filter Form -->
                <div class="flex items-end gap-2 w-full">

                    <div class="flex-1">
                        <x-form.listbox-search 
                            id="store"
                            label="Store"
                            :options="$lists['stores']"
                            :use_key="false"
                        />
                    </div>

                    <div class="flex-1">
                        <x-form.listbox-search 
                            id="category"
                            label="Category"
                            :options="$lists['categories']"
                            :use_key="false"
                        />
                    </div>

                    <div class="flex-1">
                        <x-form.listbox-search 
                            id="subcategory"
                            label="Subcategory"
                            :options="$lists['sub_categories']"
                            :use_key="false"
                        />
                    </div>

                    <div class="mb-5">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded flex items-center" onclick="filter()">
                            <i class="fa fa-filter mr-1"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="flex justify-end mb-4 space-x-2">
                    @if(auth()->user()->can("{$resourceName}.create"))

                        <a href="{{ route("secretgate19.{$resourceName}.sync") }}" onclick="syncData(event)"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded hover:bg-orange-700">
                            <i class="fas fa-sync mr-1"></i> Sync
                        </a>
                        
                        <a href="{{ route("secretgate19.{$resourceName}.create") }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
                            <i class="fas fa-plus mr-1"></i> New
                        </a>

                    @endif

                    <button onclick="refresh()"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
                        <i class="fa fa-sync mr-1"></i> Refresh
                    </button>
                </div>

                <div class="w-full overflow-x-auto">
                    <table id="datatable"
                        class="min-w-full table-auto text-sm text-left text-gray-700 border border-gray-300 border-collapse
                                [&_th]:border [&_td]:border [&_th]:border-gray-300 [&_td]:border-gray-300 [&_th]:font-bold
                                [&_th]:px-3 [&_th]:py-2 [&_td]:px-3 [&_td]:py-2
                                [&_tbody_tr:hover_td]:bg-gray-50 dark:[&_tbody_tr:hover_td]:bg-gray-800 transition-colors duration-150">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Store</th>
                            <th scope="col">Name</th>
                            <th scope="col">Category</th>
                            <th scope="col">Sub Category</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        var dataTable = $('#datatable').DataTable({
            ajax: {
                url: '{{ route("secretgate19.{$resourceName}.datatables") }}',
                type: 'GET',
                data: function (d) {
                    d.store = $('#store').val();
                    d.category = $('#category').val();
                    d.subcategory = $('#subcategory').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex'},
                {data: 'store_name', name: 'store.name'},
                {data: 'name', name: 'name'},
                {data: 'category_name', name: 'category.name'},
                {data: 'subcategory_name', name: 'subcategory.name'},
                {data: 'is_active', name: 'is_active', searchable: false},
                {data: 'actions', searchable: false, orderable: false}
            ],
            autoWidth: false // Important to override automatic sizing
        });

        function filter() {
            dataTable.ajax.reload();
        }

        function refresh() {
            dataTable.ajax.reload();
        }

        function syncData(e) {
            e.preventDefault();

            const href = e.currentTarget.getAttribute('href');

            Swal.fire({
                title: 'Sync Products',
                html: `
                    <div class="text-sm text-gray-600 text-center">
                        <p class="mb-2">
                            This will sync product data from
                            <strong class="text-gray-900">Sarinah</strong>.
                        </p>
                        <p class="text-red-600 font-medium">
                            Existing products may be updated or overwritten.
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sync Now',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#2563eb', // blue-600
                cancelButtonColor: '#9ca3af', // gray-400
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),

                preConfirm: () => {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: href,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: resolve,
                            error: reject
                        });
                    }).catch(xhr => {
                        Swal.showValidationMessage(
                            xhr.responseJSON?.message || 'Sync failed. Please try again.'
                        );
                    });
                }
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        title: 'Sync Completed',
                        text: result.value.message || 'Products synced successfully.',
                        icon: 'success',
                        timer: 4000,
                        timerProgressBar: true,
                        willClose: () => refresh()
                    });
                }
            });
        }

    </script>
    @endpush
</x-layout>
