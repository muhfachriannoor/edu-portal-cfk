<div 
    class="tab-content px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
    x-show="activeTab === 'usage_info'"    
>
    <fieldset>
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
            <div class="flex justify-between mt-4">
                <div class="flex flex-col">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Usage</span>
                    <p class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $couponUsage }}</p>
                </div>
                <div class="flex flex-col">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Total Orders</span>
                    <p class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ $totalOrders }}</p>
                </div>
            </div>
        </div>

        <!-- Data Table Section -->
        <div class="w-full overflow-x-auto">
            <table id="datatable" class="min-w-full table-auto text-sm text-left text-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase text-xs">
                    <tr>
                        <th scope="col" class="px-4 py-2">#</th>
                        <th scope="col" class="px-4 py-2">Order Number</th>
                        <th scope="col" class="px-4 py-2">Customer ID</th>
                        <th scope="col" class="px-4 py-2">Customer Name</th>
                        <th scope="col" class="px-4 py-2">Ordered Date</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </fieldset>
</div>

@push('scripts')
<script>
    var dataTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
           
            url: '{{ route("secretgate19.{$resourceName}.datatables.orders", $coupon->id) }}',

            type: 'GET'
        },
        columnDefs: [
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 10001, targets: 1 },
        ],
        columns: [
            {data: 'DT_RowIndex', name: 'id'},
            {data: 'order_number', name: 'order_number'},
            {data: 'customer_id', name: 'customer_id'},
            {data: 'customer_name', name: 'customer_name'},
            {data: 'ordered_date', name: 'ordered_date'},
        ],
        order: [[0, 'desc']], 
        autoWidth: false
    });

    function refresh() {
        dataTable.ajax.reload();
    }
</script>
@endpush