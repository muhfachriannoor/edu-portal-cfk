<x-layout.app :header="$pageMeta['title']">

    <div id="pageWrapper" data-page="{{ $resourceName }}">
        <!-- Breadcrumb -->
        <x-form.breadcrumb 
            :title="$pageMeta['title']"
        />

        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <div class="flex justify-end mb-4 space-x-2">
                    @if(auth()->user()->can("{$resourceName}.create"))
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
                    <table id="datatable" class="min-w-full table-auto text-sm text-left text-gray-700">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Slug</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Email</th>
                            <th scope="col">Is Verified</th>
                            <th scope="col">Is Active</th>
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

</x-layout.app>

<script>
    var dataTable = $('#datatable').DataTable({
        ajax: {
            url: '{{ route("secretgate19.{$resourceName}.datatables") }}',
            type: 'GET'
        },
        columns: [
            {data: 'id', name: 'id'},
            {data: 'name', name: 'name'},
            {data: 'slug', name: 'slug'},
            {data: 'phone', name: 'phone'},
            {data: 'email', name: 'email'},
            {data: 'verified_at', name: 'verified_at'},
            {data: 'is_active', name: 'is_active', searchable: false},
            {data: 'actions', searchable: false, orderable: false}
        ],
        autoWidth: false // Important to override automatic sizing
    });

    function refresh() {
        dataTable.ajax.reload();
    }
</script>