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
                        <x-form.select 
                            id="category"
                            label="Category"
                            :options="['Content', 'List', 'Input']"
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
                            <th scope="col">Key</th>
                            <th scope="col">Category</th>
                            <th scope="col">Updated At</th>
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

    <div id="modalViewDetail" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <!-- Modal Box -->
        <div id="modalBox" class="bg-white rounded-lg w-full max-w-4xl shadow-lg relative transform transition-all duration-300 opacity-0 -translate-y-10">

            <!-- Header -->
            <div class="flex justify-between items-center px-5 py-3 border-b">
                <h2 class="text-lg font-semibold">JSON Viewer</h2>
                <button type="button" class="text-gray-500 hover:text-gray-700 text-2xl font-bold leading-none close-modal">&times;</button>
            </div>

            <!-- Body -->
            <div class="p-5 mx-5 max-h-[70vh] overflow-auto" id="json-viewer-container">
                <!-- JSON output goes here -->
            </div>
        </div>
    </div>

</x-layout.app>

<script>
    var dataTable = $('#datatable').DataTable({
        ajax: {
            url: '{{ route("secretgate19.{$resourceName}.datatables") }}',
            type: 'GET',
            data: function (d) {
                d.category = $('#category').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'id'},
            {data: 'name', name: 'name'},
            {data: 'key', name: 'key'},
            {data: 'category', name: 'category'},
            {data: 'updated_at', searchable: false, orderable: false},
            {data: 'actions', searchable: false, orderable: false}
        ],
        autoWidth: false // Important to override automatic sizing
    });

    function refresh() {
        dataTable.ajax.reload();
    }

    function filter() {
        dataTable.ajax.reload();
    }

    $(document).on('click', '[data-json]', function () {
        const rawJson = $(this).attr('data-json');
        const $modal = $('#modalViewDetail');
        const $viewer = $('#json-viewer-container');
        const $modalBox = $('#modalBox');

        try {
            const jsonData = JSON.parse(rawJson);
            $viewer.empty();

            if (typeof JSONFormatter !== 'undefined') {
                const formatter = new JSONFormatter(jsonData, 2);
                $viewer.append(formatter.render());
            } else {
                $viewer.jsonViewer(jsonData, { collapsed: false, withQuotes: true });
                // $viewer.html('<pre class="whitespace-pre-wrap text-sm">' + JSON.stringify(jsonData, null, 2) + '</pre>');
            }

            // Show modal and animate in
            $modal.removeClass('hidden');
            setTimeout(() => {
                $modalBox.removeClass('opacity-0 -translate-y-10').addClass('opacity-100 translate-y-0');
            }, 10);

        } catch (e) {
            $viewer.html('<p class="text-red-500">Invalid JSON</p>');
            $modal.removeClass('hidden');
        }
    });

    $(document).on('click', '.close-modal', function () {
        const $modal = $('#modalViewDetail');
        const $modalBox = $('#modalBox');

        $modalBox.removeClass('opacity-100 translate-y-0').addClass('opacity-0 -translate-y-10');

        setTimeout(() => {
            $modal.addClass('hidden');
        }, 300); // Wait for animation to finish
    });


</script>
