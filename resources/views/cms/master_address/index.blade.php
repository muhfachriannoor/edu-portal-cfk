<x-layout.app :header="$pageMeta['title']">

    <div id="pageWrapper" data-page="{{ $resourceName }}">
        <x-form.breadcrumb 
            :title="$pageMeta['title']"
        />

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <div class="flex justify-end mb-4 space-x-2">
                    @if(auth()->user()->can("{$resourceName}.create"))

                        <a href="{{ route("secretgate19.{$resourceName}.import", [$storeCms , 'sampleFile' => 'sample/Format_Lazada_Master_Address.csv']) }}" onclick="importFile(event)"
                            class="inline-flex items-center px-4 py-2 bg-purple-500 text-white text-sm font-medium rounded hover:bg-purple-600">
                                <i class="fas fa-file-import mr-1"></i> Import
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
                            <th scope="col">RID</th>
                            <th scope="col">Country Name</th>
                            <th scope="col">Province Name</th>
                            <th scope="col">City Name</th>
                            <th scope="col">District Name</th>
                            <th scope="col">Subdistrict Name</th>
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
            {data: 'subdistrict_id', name: 'subdistrict_id'},
            {data: 'country_name', name: 'country_name'},
            {data: 'province_name', name: 'province_name'},
            {data: 'city_name', name: 'city_name'},
            {data: 'district_name', name: 'district_name'},
            {data: 'subdistrict_name', name: 'subdistrict_name'},
        ],
        order: [[0, 'desc']], 
        autoWidth: false
    });

    function refresh() {
        dataTable.ajax.reload();
    }

    function importFile(e){
        e.preventDefault();

        const $href = e.currentTarget.getAttribute('href');

        Swal.fire({
            title: 'Import File',
            html: `@include('components.modal.import', ['sampleFile' => 'sample/Format_Lazada_Master_Address.csv'])`,
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
                }).done(response => {
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
                }).fail(xhr => {
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