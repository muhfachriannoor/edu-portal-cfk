<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Invoice {{ $invoice['is_paid'] ? 'Paid' : 'Unpaid' }} {{ $invoice['order_number']}}</title>

    <style>
      @font-face {
        font-family: "Graphik";
        src: url('{{ asset("assets/invoice/fonts/Graphik-Regular.ttf") }}')
          format("truetype");
        font-weight: 400;
        font-style: normal;
      }

      @font-face {
        font-family: "Graphik";
        src: url('{{ asset("assets/invoice/fonts/Graphik-Medium.ttf") }}')
          format("truetype");
        font-weight: 500;
        font-style: normal;
      }

      body {
        font-family: "Graphik", DejaVu Sans, Arial, sans-serif;
        font-size: 0.7em;
        color: #000;
        position: relative;
      }

      .header {
        width: 100%;
        margin-bottom: 20px;
      }

      .header-left {
        float: left;
      }

      .header-right {
        float: right;
        text-align: right;
      }
      .logo {
        width: 120px;
        height: auto;
      }

      .invoice-title {
        font-weight: bold;
        color: #c40000;
        font-size: 14px;
      }

      .clear {
        clear: both;
      }

      hr {
        border: none;
        border-top: 1px solid #000;
        margin: 20px 0;
      }

      .info {
        width: 100%;
        margin-bottom: 40px;
      }

      .info-left,
      .info-right {
        width: 48%;
        float: left;
      }

      .info-right {
        float: right;
      }

      .info p {
        margin: 4px 0;
      }

      .info strong {
        font-weight: bold;
      }

      table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 40px;
      }

      table thead th {
        border: 1px solid #000;
        padding: 14px;
        font-weight: bold;
      }
      table thead th.border-right {
        border-right: none !important;
      }
      table thead th.border-left {
        border-left: none !important;
      }

      table tbody td {
        padding: 14px 30px;
      }
      table td.bordered {
        border: 1px solid #000;
      }
      table td.bordered.border-right {
        border-right: none !important;
      }
      table td.bordered.border-left {
        border-left: none !important;
      }

      .text-center {
        text-align: center;
      }

      .text-right {
        text-align: right;
      }

      .summary {
        width: 100%;
        margin-top: 20px;
      }

      .summary table {
        width: 100%;
        border: none;
      }

      .summary td {
        padding: 14px 30px;
      }

      .summary .value {
        text-align: right;
        width: 150px;
      }

      .total {
        background: #d71920;
        color: #fff;
        font-weight: bold;
      }

      .footer-note {
        margin-top: 30px;
        font-size: 14px;
        color: #333;
      }

      @if(isset($isPdf) && $isPdf)

      .watermark {
        position: fixed;
        top: 43%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
        opacity: 0.1;
        width: 65%;
      }
      .watermark img {
        width: 100%;
        height: 95%;
        display: block;
      }

      @else
      .watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
        opacity: 0.1;
        width: 65%;
      }
      .watermark img {
        width: 100%;
        height: 100%;
        display: block;
      }
      
      @endif
    </style>
  </head>
  <body>
    <!-- HEADER -->
    <div class="header">
      <div class="header-left">
        @php
          $logoPath = isset($isPdf) && $isPdf
            ? public_path('assets/invoice/img/logo.png')
            : asset('assets/invoice/img/logo.png');
        @endphp
        <img class="logo" src="{{ $logoPath }}" alt="Logo" />
      </div>

      <div class="header-right">
        <div class="invoice-title">ORIGINAL INVOICE</div>
        <div>{{ $invoice['order_number'] }}</div>
      </div>
    </div>

    <div class="clear"></div>
    <hr />

    <!-- INFO -->
    <div class="info">
      <div class="info-left">
        <p><strong>BILL TO:</strong></p>
        <p><strong>Customer:</strong> {{ $invoice['customer_name'] }}</p>
        <p><strong>Phone Number:</strong> {{ $invoice['customer_phone'] }}</p>
        <p><strong>Email:</strong> {{ $invoice['customer_email'] }}</p>
        <p><strong>Transaction Date:</strong> {{ $invoice['transaction_date'] }}</p>
        @if($invoice['delivery_address'])
        <p>
          <strong>Delivery Address:</strong> {{ $invoice['delivery_address'] }}
        </p>
        @endif
      </div>

      <div class="info-right">
        <p><strong>{{ $invoice['store_name'] }}</strong></p>
        <p>{{ $invoice['store_address'] }}</p>
        <br />
        <p><strong>Tel:</strong> {{ $invoice['store_phone'] }}</p>
        <p><strong>Email:</strong> {{ $invoice['store_email'] }}</p>
        <hr />
        <p><strong>Order Number:</strong> {{ $invoice['order_number'] }}</p>
        <p><strong>Invoice Date:</strong> {{ $invoice['invoice_date'] }}</p>
        <p><strong>Method of Payment:</strong> {{ $invoice['payment_method'] }}</p>
      </div>
    </div>

    <div class="clear"></div>

    <!-- TABLE -->
    <table>
      <thead>
        <tr>
          <th class="text-left border-right">DESCRIPTION OF GOODS</th>
          <th class="text-center border-right border-left">QUANTITY</th>
          <th class="border-right border-left">UNIT PRICE (IDR)</th>
          <th class="border-left">TOTAL PRICE (IDR)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($invoice['items'] as $item)
        <tr>
          <td>{{ $item['description'] }}</td>
          <td class="text-center">{{ $item['quantity'] }}</td>
          <td class="text-right">{{ number_format($item['unit_price'], 0, ',', '.') }}</td>
          <td class="text-right">{{ number_format($item['total_price'], 0, ',', '.') }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <!-- SUMMARY -->
    <div class="summary">
      <table>
        <tr>
          <td class="label bordered border-right" colspan="3">
            <strong>SUB-TOTAL PRICE</strong>
          </td>
          <td class="value bordered border-left">
            <strong>{{ number_format($invoice['summary']['subtotal'], 0, ',', '.') }}</strong>
          </td>
        </tr>
        <tr>
          <td class="label" colspan="3">Total Amount/value discount offered</td>
          <td class="value">
            @if ($invoice['summary']['discount'] === 0)
            {{ number_format($invoice['summary']['discount'], 0, ',', '.') }}
            @else
            -{{ number_format($invoice['summary']['discount'], 0, ',', '.') }}
            @endif
            </td>
        </tr>
        <tr>
          <td class="label" colspan="3">Total post discount (including VAT)</td>
          <td class="value">{{ number_format($invoice['summary']['post_discount'], 0, ',', '.') }}</td>
        </tr>
        <tr>
          @if($invoice['delivery_address'])
          <td class="label" colspan="3">Delivery Fee</td>
          <td class="value">{{ number_format($invoice['summary']['packaging_fee'], 0, ',', '.') }}</td>
          @else
            <td class="label" colspan="3">Packaging Fee</td>
            <td class="value">{{ number_format($invoice['summary']['packaging_fee'], 0, ',', '.') }}</td>
          @endif
        </tr>
        <tr class="total">
          <td class="label" colspan="3">
            <strong>TOTAL BILL</strong>
          </td>
          <td class="value">
            <strong>{{ number_format($invoice['summary']['total'], 0, ',', '.') }}</strong>
          </td>
        </tr>
      </table>
    </div>

    <!-- FOOTER -->
    <div class="footer-note">
      This is a computer-generated document. No signature or stamp required.
    </div>

    <!-- WATERMARK -->
    <div class="watermark">
      @php
        $watermarkPath = $invoice['is_paid']
          ? public_path('assets/invoice/img/w-paid.png')
          : public_path('assets/invoice/img/unpaid.png');
        $watermarkSrc = null;
        if (isset($isPdf) && $isPdf) {
          if (file_exists($watermarkPath)) {
            $type = pathinfo($watermarkPath, PATHINFO_EXTENSION);
            $data = file_get_contents($watermarkPath);
            $base64 = base64_encode($data);
            $watermarkSrc = 'data:image/' . $type . ';base64,' . $base64;
          } else {
            $watermarkSrc = '';
          }
        } else {
          $watermarkSrc = $invoice['is_paid']
            ? asset('assets/invoice/img/w-paid.png')
            : asset('assets/invoice/img/unpaid.png');
        }
      @endphp
      @if($watermarkSrc)
        <img src="{{ $watermarkSrc }}" alt="Watermark" />
      @endif
    </div>
</body>
@if(!isset($isPdf) || !$isPdf)
<script>
  window.onload = function() {
    window.print();
  };
</script>
@endif
</html>
