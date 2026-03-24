<x-layout.app :header="$pageMeta['title']">
@push('styles')
    <style>
        /* Container untuk merapikan pembungkus datatable */
        .dataTables_wrapper {
            padding: 0 !important;
        }

        /* Styling Tabel Utama */
        #datatable {
            border-collapse: collapse !important;
            width: 100% !important;
            border: 1px solid #e2e8f0;
            margin: 0 !important;
        }

        /* Memastikan header tetap solid dan sejajar */
        #datatable thead th {
            background-color: #f1f5f9 !important; /* Abu-abu header */
            color: #334155;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border: 1px solid #e2e8f0 !important;
            text-align: center;
            padding: 12px 15px !important;
            vertical-align: middle;
        }

        /* Padding Cell & Border Body */
        #datatable td {
            white-space: nowrap !important;
            padding: 12px 15px !important;
            vertical-align: middle;
            border: 1px solid #e2e8f0 !important;
        }

        /* Zebra Stripe Otomatis */
        #datatable tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        #datatable tbody tr:hover {
            background-color: #f1f5f9 !important;
        }

        /* Perbaikan garis untuk DataTables scrollX */
        .dataTables_scrollHead {
            border-top: 1px solid #e2e8f0 !important;
            border-left: 1px solid #e2e8f0 !important;
            border-right: 1px solid #e2e8f0 !important;
            background-color: #f1f5f9 !important;
        }
        
        .dataTables_scrollBody {
            border: 1px solid #e2e8f0 !important;
            border-top: none !important;
        }

        /* Kolom ID (Monospace) */
        .col-id { 
            font-family: 'Courier New', Courier, monospace; 
            font-weight: 600; 
            color: #1e293b;
        }

        /* Nominal Alignment */
        .text-right { text-align: right !important; }
        .col-money { min-width: 120px !important; }

        /* Badge Styling */
        .badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }
        .badge-success { background-color: #10b981; color: white; }
        .badge-warning { background-color: #f59e0b; color: white; }
        .badge-danger { background-color: #ef4444; color: white; }
        .badge-info { background-color: #3b82f6; color: white; }

        .expand-trigger {
            padding: 6px 12px;
            font-size: 11px;
            border-radius: 4px;
            background: #fff;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            transition: all 0.2s;
        }
        .expand-trigger:hover { 
            background: #f1f5f9; 
            border-color: #94a3b8; 
        }

        /* Detail Row Styling */
        .inner-detail-wrapper {
            margin: 10px;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #3b82f6;
            background: #fff;
        }
    </style>
@endpush

    <div id="pageWrapper" data-page="{{ $resourceName }}">
        <x-form.breadcrumb :title="$pageMeta['title']" />

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                {{-- FILTER SECTION --}}
                <div class="flex items-end gap-2 w-full">

                    <div class="flex-1">
                        <x-form.listbox-search 
                            id="payment_status"
                            label="Status Transaksi"
                            :options="$lists['payment_statuses']"
                            :use_key="true" 
                        />
                    </div>

                    <div class="flex-1">
                        <x-form.listbox-search 
                            id="order_status"
                            label="Status Order"
                            :options="$lists['order_statuses']"
                            :use_key="true" 
                        />
                    </div>

                    <div class="mb-5">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded flex items-center" onclick="filter()">
                            <i class="fa fa-filter mr-1"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="flex justify-end mb-4 space-x-2">
                    <a href="{{ route("secretgate19.{$resourceName}.export") }}"
                        id="btn-export"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
                            <i class="fas fa-file-excel mr-1"></i> Export Excel
                    </a>
                    <button onclick="refresh()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
                        <i class="fa fa-sync mr-1"></i> Refresh
                    </button>
                </div>

                <div class="w-full overflow-hidden">
                    <table id="datatable" class="display nowrap cell-border" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th class="col-id">Order ID</th>
                                <th>Tanggal</th>
                                <th>Store / Brand</th>
                                <th>Konsum ID</th>
                                <th>Nama Konsumen</th>
                                <th>Payment Method</th>
                                <th>Status Transaksi</th>
                                <th>Status Order</th>
                                <th class="text-right">Subtotal Barang</th>
                                <th class="text-right">Diskon Barang</th>
                                <th class="text-right">Diskon Voucher</th>
                                <th class="text-right">Biaya Lainnya</th>
                                <th class="text-right">Grand Total</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>

<script>
    const formatRupiah = (data) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(data);
    };

    var dataTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        autoWidth: false,
        ajax: {
            url: '{{ route("secretgate19.{$resourceName}.datatables") }}',
            type: 'GET',
            data: function (d) {
                d.filter = {
                    payment_status: $('#payment_status').val(),
                    order_status: $('#order_status').val()
                }
            },
            error: function (xhr, error, thrown) {
                console.error("DataTables Error: ", xhr.responseText);
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'id', className: 'text-center', orderable: false, searchable: false},
            {data: 'order_number', name: 'order_number', className: 'col-id font-bold', defaultContent: '-'},
            {data: 'ordered_date', name: 'created_at', defaultContent: '-'},
            {data: 'store_brand', name: 'store_brand', defaultContent: '-'},
            {data: 'customer_id', name: 'user.id', className: 'text-center', defaultContent: '-'},
            {data: 'customer_name', name: 'user.name', defaultContent: '-'},
            {data: 'payment_method', name: 'channel.name', defaultContent: '-'},
            {
                data: 'status_payment', 
                name: 'payment.status',
                defaultContent: 'Unpaid',
                render: function(data) {
                    let color = String(data).toLowerCase() === 'paid' ? 'badge-success' : 'badge-warning';
                    return `<span class="badge ${color}">${data}</span>`;
                }
            },
            {
                data: 'status_order', 
                name: 'status',
                defaultContent: 'Pending',
                render: function(data) {
                    let val = String(data).toLowerCase();
                    let color = 'badge-info';
                    if(val === 'canceled' || val === 'cancelled') color = 'badge-danger';
                    if(val === 'placed' || val === 'completed') color = 'badge-success';
                    return `<span class="badge ${color}">${data}</span>`;
                }
            },
            {
                data: 'subtotal_order', 
                name: 'subtotal', 
                className: 'text-right',
                render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
            },
            {
                data: 'discount', 
                name: 'discount', 
                className: 'text-right text-red-500',
                render: $.fn.dataTable.render.number('.', ',', 0, '-Rp ')
            },
            {
                data: 'discount_voucher', 
                name: 'discount_voucher', 
                className: 'text-right text-red-500',
                render: $.fn.dataTable.render.number('.', ',', 0, '-Rp ')
            },
            {
                data: 'other_fees', 
                name: 'other_fees', 
                className: 'text-right',
                render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
            },
            {
                data: 'grand_total', 
                name: 'grand_total', 
                className: 'text-right font-bold text-blue-700',
                render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
            },
            {
                data: 'details', 
                orderable: false, 
                searchable: false, 
                className: 'text-right',
                render: function() {
                    return `<button type="button" class="expand-trigger"><i class="fas fa-chevron-down mr-1"></i> Detail</button>`;
                }
            }
        ],
        order: [[2, 'desc']], // Urutkan berdasarkan created_at
        drawCallback: function() {
            // Menggunakan timeout sedikit agar browser sempat merender DOM sebelum kalkulasi lebar
            setTimeout(function() {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            }, 100);
        }
    });

    function formatDetail(data) {
        if (!data || !data.order_items || data.order_items.length === 0) {
            return '<div class="p-4 text-center text-gray-500">Tidak ada item dalam pesanan ini.</div>';
        }

        let rows = '';
        data.order_items.forEach((item, index) => {
            let category = item.product_variant?.product?.category?.name || '-';
            let productName = item.product_name || 'Produk Tidak Diketahui';
            
            rows += `
                <tr class="border-b">
                    <td class="py-2 px-4 text-center text-gray-400">${index + 1}</td>
                    <td class="py-2 px-4 font-bold text-gray-800">${productName}</td>
                    <td class="py-2 px-4 text-gray-600">${category}</td>
                    <td class="py-2 px-4 text-center">${item.quantity || 0}</td>
                    <td class="py-2 px-4 text-right">${formatRupiah(item.base_price || 0)}</td>
                    <td class="py-2 px-4 text-right text-red-500">${formatRupiah((item.base_price || 0) - (item.selling_price || 0))}</td>
                    <td class="py-2 px-4 text-right font-bold">${formatRupiah(item.subtotal || 0)}</td>
                </tr>`;
        });

        return `
            <div class="inner-detail-wrapper shadow-md rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border">NO</th>
                            <th class="py-2 px-4 border text-left">PRODUCT</th>
                            <th class="py-2 px-4 border text-left">CATEGORY</th>
                            <th class="py-2 px-4 border">QTY</th>
                            <th class="py-2 px-4 border text-right">PRICE</th>
                            <th class="py-2 px-4 border text-right">DISC</th>
                            <th class="py-2 px-4 border text-right">SUBTOTAL</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
    }

    $('#datatable tbody').on('click', '.expand-trigger', function () {
        var tr = $(this).closest('tr');
        var row = dataTable.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown bg-blue-50');
            $(this).html('<i class="fas fa-chevron-down mr-1"></i> Detail').removeClass('text-red-500');
        } else {
            row.child(formatDetail(row.data()), 'p-0').show();
            tr.addClass('shown bg-blue-50');
            $(this).html('<i class="fas fa-chevron-up mr-1"></i> Tutup').addClass('text-red-500');
        }
    });

    function updateExportUrl() {
        let baseUrl = '{{ route("secretgate19.{$resourceName}.export") }}';
        let payment_status = $('#payment_status').val();
        let order_status = $('#order_status').val();

        let params = new URLSearchParams();
        if(payment_status) params.append('payment_status', payment_status);
        if(order_status) params.append('order_status', order_status);

        let finalUrl = baseUrl + '?' + params.toString();
        $('#btn-export').attr('href', finalUrl);
    }

    function refresh() {
        dataTable.ajax.reload(null, false);
    }

    function filter() {
        dataTable.draw();
        updateExportUrl();
    }
</script>