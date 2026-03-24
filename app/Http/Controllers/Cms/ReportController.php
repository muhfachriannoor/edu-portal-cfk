<?php

namespace App\Http\Controllers\Cms;

use App\Models\Order;
use App\Http\Controllers\Cms\CmsController;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'report';

    /**
     * Constructor: Authorize resource wildcard.
     */
    public function __construct()
    {
        $this->authorizeResourceWildcard($this->resourceName);
    }

    /**
     * Display a listing of the resource for Datatables.
     */
    public function datatables()
    {
        return (new Order)->getDatatables(); 
    }

    /**
     * Get lists.
     */
    public function getLists(): array
    {
        return [
            'payment_statuses' => Payment::getStatusLists(),
            'order_statuses' => Order::getStatusMapping(),
        ];
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Report'
            ]
        ]);
    }

    public function export(Request $request)
    {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', -1);

        $attributes = [
            'Order ID', 'Tanggal', 'Store / Brand', 'Consumer ID', 'Nama Konsumen', 
            'Payment Method', 'Status Transaksi', 'Status Order', 'Detail Barang', 
            'Subtotal Barang', 'Diskon Barang', 'Diskon Voucher', 'Biaya Lainnya', 'Grand Total'
        ];

        $paymentStatus = $request->get('payment_status');
        $orderStatus = $request->get('order_status');

        (new Order)->exportToExcelCustom($attributes, function($query) use ($orderStatus, $paymentStatus) {
            return $query->with([
                'store', 'user', 'payment', 'channel', 
                'orderItems.productVariant.product.brand',
                'orderItems.productVariant.product.category' 
            ])
            ->applyReportFilter($paymentStatus, $orderStatus)
            ->orderBy('created_at', 'DESC');
        }, function ($sheet, $attributes, $collections) {
            // 1. Set Header
            $col = 'A';
            foreach ($attributes as $header) {
                $sheet->setCellValue($col . '1', $header);
                $sheet->getStyle($col . '1')->getFont()->setBold(true);
                $col++;
            }

            $row = 2;
            foreach ($collections as $order) {
                $startRow = $row;
                $items = $order->orderItems;
                $itemCount = $items->count();
                $endRow = ($itemCount > 1) ? ($startRow + $itemCount - 1) : $startRow;
                
                $paymentStatus = $order->payment ? $order->payment->status_label : 'No Payment';

                // 2. Isi Data Utama (A - H)
                $sheet->setCellValue('A' . $row, $order->order_number);
                $sheet->setCellValue('B' . $row, $order->created_at->format('d-M-y'));
                $sheet->setCellValue('C' . $row, ($order->store->name ?? '-') . ' / ' . ($order->getBrandNames() ?? '-'));
                $sheet->setCellValue('D' . $row, $order->user->id ?? '-');
                $sheet->setCellValue('E' . $row, $order->buyer_name);
                $sheet->setCellValue('F' . $row, $order->channel->name ?? '-');
                $sheet->setCellValue('G' . $row, $paymentStatus);
                $sheet->setCellValue('H' . $row, $order->status_name);

                // Kolom J - N (Totalan)
                $sheet->setCellValue('J' . $row, $order->subtotal);
                $sheet->setCellValue('K' . $row, $order->discount);
                $sheet->setCellValue('L' . $row, $order->discount_voucher);

                $totalOtherFees = ($order->other_fees ?? 0) + ($order->delivery_cost ?? 0);

                $sheet->setCellValue('M' . $row, $totalOtherFees);
                $sheet->setCellValue('N' . $row, $order->grand_total);

                $sheet->getStyle("J{$row}:N{$row}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // 3. Isi Detail Barang (Kolom I) - Per Baris
                foreach ($items as $index => $item) {
                    $currentRow = $startRow + $index;
                    
                    // Logika Nama Produk + Varian
                    $productFullName = $item->product_name . ($item->product_variant_name ? " - {$item->product_variant_name}" : "");
                    
                    // Ambil Nama Kategori
                    $categoryName = $item->productVariant?->product?->category?->name ?? '-';

                    $detailText = $productFullName . "\n" . 
                                "Category : " . $categoryName . "\n" .
                                "Qty : " . number_format($item->quantity, 0, ',', '.') . "\n" . 
                                "Harga : Rp " . number_format($item->base_price, 0, ',', '.') . "\n" .
                                "Promo : Rp " . number_format($item->selling_price, 0, ',', '.');
                    
                    $sheet->setCellValue('I' . $currentRow, $detailText);
                    $sheet->getStyle('I' . $currentRow)->getAlignment()->setWrapText(true);
                }

                // 4. Merge Cells jika item > 1
                if ($itemCount > 1) {
                    foreach (array_merge(range('A', 'H'), range('J', 'N')) as $column) {
                        $sheet->mergeCells("{$column}{$startRow}:{$column}{$endRow}");
                        $sheet->getStyle("{$column}{$startRow}")->getAlignment()->setVertical('top');
                    }
                }

                $row = $endRow + 1;
            }

            // Auto size columns
            foreach (range('A', 'N') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }
        }, "Reporting_Transaksi_" . now()->format('Ymd'));
    }
}