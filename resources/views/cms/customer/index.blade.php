<x-layout.app :header="$pageMeta['title']">

    <div id="pageWrapper" data-page="{{ $resourceName }}">
        <x-form.breadcrumb 
            :title="$pageMeta['title']"
        />

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <div class="flex justify-end mb-4 space-x-2">
                    <a href="{{ route("secretgate19.{$resourceName}.export") }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
                            <i class="fas fa-file-excel mr-1"></i> Export Excel
                    </a>
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
                            <th scope="col">Email</th>
                            <th scope="col">Mobile Number</th>
                            <th scope="col">Date Registered</th>
                            <th scope="col">Onboarding Status</th>
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
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("secretgate19.{$resourceName}.datatables") }}',
            type: 'GET'
        },
        columnDefs: [
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 10001, targets: 1 },
        ],
        columns: [
            {data: 'DT_RowIndex', name: 'id'},
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'mobile_number', name: 'mobile_number'},
            {data: 'created_at', name: 'created_at'},
            {data: 'onboarding_completed', name: 'onboarding_completed'},
            {data: 'actions', searchable: false, orderable: false}
        ],
        order: [[0, 'desc']], 
        autoWidth: false
    });

    function refresh() {
        dataTable.ajax.reload();
    }
</script>