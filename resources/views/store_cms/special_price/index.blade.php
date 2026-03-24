<x-layout.store.app :header="$pageMeta['title']">

    <div id="pageWrapper" data-page="{{ $resourceName }}">
        <!-- Breadcrumb -->
        <x-form.breadcrumb 
            :title="$pageMeta['title']"
            baseLink="store_cms"
            :routeParam="[$storeCms]"
        />

        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 rounded-b-lg shadow">
            <div class="p-6">
                <div class="flex justify-end mb-4 space-x-2">
                    @if(auth()->user()->can("{$resourceName}.create"))

                    <a href="{{ route("store_cms.{$resourceName}.import", [$storeCms , 'sampleFile' => 'sample/Format_Special_Price_Import.xlsx']) }}" onclick="importFile(event)"
                        class="inline-flex items-center px-4 py-2 bg-purple-500 text-white text-sm font-medium rounded hover:bg-purple-600">
                            <i class="fas fa-file-import mr-1"></i> Import
                    </a>

                    <a href="{{ route("store_cms.{$resourceName}.create", [$storeCms]) }}"
                       class="inline-flex items-center p-3 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700" title="New">
                        <i class="fas fa-plus mr-1"></i> New
                    </a>
                    @endif
                    <button onclick="refresh()"
                            class="inline-flex items-center p-3 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700" title="Refresh">
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
                                <th scope="col">Product</th>
                                <th scope="col">Type</th>
                                <th scope="col">Discount / Percentage</th>
                                <th scope="col">Price</th>
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

    @push('styles')
    
    @endpush

    @push('scripts')
    <script>
        var dataTable = $('#datatable').DataTable({
            ajax: {
                url: '{{ route("store_cms.{$resourceName}.datatables", [$storeCms]) }}',
                type: 'GET'
            },
            columns: [
                {data: 'DT_RowIndex', searchable: false},
                {data: 'product_variant'},
                {data: 'type', name: 'type'},
                {data: 'discount_percentage', searchable: false},
                {data: 'price', searchable: false},
                {data: 'is_active', name: 'is_active', searchable: false},
                {data: 'actions', searchable: false, orderable: false}
            ],
            autoWidth: false // Important to override automatic sizing
        });

        function refresh() {
            dataTable.ajax.reload();
        }

        function importFile(e){
        e.preventDefault();

        const $href = e.currentTarget.getAttribute('href');

        Swal.fire({
            title: 'Import File',
            html: `@include('components.modal.import', ['sampleFile' => 'sample/Format_Special_Price_Import.xlsx'])`,
            showCancelButton: true,
            confirmButtonText: 'Upload',
            cancelButtonText: 'Cancel',
            focusConfirm: false,
            showLoaderOnConfirm: true, // Prevent auto close
            preConfirm: () => {
                const fileInput = document.getElementById('importFileInput');
                const file = fileInput.files[0];

                if (!file) {
                    Swal.showValidationMessage('Please select a file');
                    return false;
                }

                const formData = new FormData();
                formData.append('file', file);

                return $.ajax({
                    url: $href,
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).then(response => {
                    Swal.fire({
                        title: 'Success',
                        text: response.message || 'File imported successfully!',
                        icon: 'success',
                        timer: 5000, // 5 seconds
                        timerProgressBar: true,
                        willClose: () => {
                            refresh(); // call your refresh function after close
                        }
                    });
                }).catch(xhr => {
                    let htmlMessage = 'Import failed. Please check your file and try again.';

                    const resp = xhr.responseJSON;

                    if (resp) {
                        if (Array.isArray(resp.errors) && resp.errors.length > 0) {
                            const lines = resp.errors.map((err, idx) => {
                                const row    = err.row ?? '?';
                                const field  = err.field ? `(${err.field})` : '';
                                const msg    = err.message || 'Invalid value';
                                return `Row ${row} ${field}: ${msg}`;
                            });

                            htmlMessage = (resp.message ? resp.message + '<br><br>' : '') + lines.join('<br>');
                        } else if (resp.message) {
                            htmlMessage = resp.message;
                        }
                    }

                    Swal.showValidationMessage(htmlMessage);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        });
    }

    function showDownloadLoading(el) {
        var $el = $(el);
        var $spinner = $el.find('.download-spinner');
        var $text = $el.find('.download-text');

        $spinner.removeClass('hidden');
        $text.text('Preparing...');

        // Disable click temporarily
        $el.addClass('pointer-events-none');

        setTimeout(function () {
            $el.removeClass('pointer-events-none');
            $spinner.addClass('hidden');
            $text.text('Download Sample Format');
        }, 4000);
    }
    </script>
    @endpush
</x-layout>
