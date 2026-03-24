<x-layout.store.app :header="$pageMeta['title']">

    <div id="pageWrapper" data-page="{{ $resourceName }}">
        <!-- Breadcrumb -->
        <x-form.breadcrumb 
            :title="$pageMeta['title']"
        />

        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 rounded-b-lg shadow">
            <div class="p-6">
                <div class="flex justify-end mb-4 space-x-2">
                    @if(auth()->user()->can("{$resourceName}.create"))
                    <a href="{{ route("store_cms.{$resourceName}.create", [$storeCms]) }}"
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
                url: '{{ route("store_cms.{$resourceName}.datatables", [$storeCms]) }}',
                type: 'GET'
            },
            columns: [
                {data: 'DT_RowIndex'},
                {data: 'name', name: 'name'},
                {data: 'category_name', name: 'category.name'},
                {data: 'subcategory_name', name: 'subcategory.name'},
                {data: 'is_active', name: 'is_active', searchable: false},
                {data: 'actions', searchable: false, orderable: false}
            ],
            autoWidth: false // Important to override automatic sizing
        });

        function refresh() {
            dataTable.ajax.reload();
        }
    </script>
    @endpush
</x-layout>
