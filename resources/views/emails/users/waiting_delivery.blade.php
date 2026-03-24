<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menunggu Pembayaran Delivery</title>

  <style>
    /* ======================== */
    /* 🧩 Styling Sarinah by Metha Willia */
    /* ======================== */
    p {
      margin: 0 !important;
    }
  </style>
</head>

<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;color:#1E1E1E;line-height: 1.5 !important;">

  <table width="100%" cellpadding="0" cellspacing="0" style="padding:1.5rem 0">
    <tr>
      <td align="center">

        <!-- Container -->
        <table cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width: 600px; width: 100%;">

          <!-- Header -->
          <tr>
            <td align="center" style="padding:1.5rem 1.5rem 0">
              <img
                src="{{ isset($message) ? $message->embed(public_path('assets/img/favicons/logo_sarinah_email.png')) : asset('assets/img/favicons/logo_sarinah_email.png') }}"
                width="150" alt="Logo Sarinah">
            </td>
          </tr>

          <!-- Title -->
          <tr>
            <td align="center" style="padding:1.5rem">
              <h1 style="margin-top:0 !important; margin-bottom: 0.5rem !important; line-height: 1 !important;">
                Menunggu Pembayaran
              </h1>
              <p style="color:rgba(0, 0, 0, 0.60);line-height:1.5; font-size: 0.9rem !important;">
                Halo {{ $model->user->name ?? $model->buyer_name }}, pesanan <strong>#{{
                  $model->order_number }}</strong> berhasil dibuat.
              </p>
              <p style="color:rgba(0, 0, 0, 0.60);line-height:1.5; font-size: 0.9rem !important;">
                Mohon selesaikan pembayaran dalam waktu 1×24 jam sejak pesanan dibuat agar pesanan dapat segera
                diproses.
              </p>
            </td>
          </tr>

          <!-- Order Code -->
          <tr>
            <td style="padding:1rem 1.5rem;background:#F3F3F3">
              <p>#{{ $model->order_number }}</p>
            </td>
          </tr>

          <tr>
            <td colspan="2" style="padding: 0.5rem 1.5rem;">
            </td>
          </tr>

          <!-- Store -->
          <tr>
            <td style="padding:0 1.5rem 0.5rem 1.5rem">
              <h3 style="color:#D71920; margin-top: 0 !important; margin-bottom: 0.6rem !important;">
                {{ $model->store->name ?? 'Sarinah' }}
              </h3>
              {{-- <p>
                {{ $model->getBrandNames() }}
              </p> --}}
            </td>
          </tr>

          <!-- Product -->
          @foreach($model->orderItems as $item)
          <tr>
            <td style="padding:0 1.5rem 0.5rem 1.5rem">
              <p>
                {{ $item->productVariant?->product?->brand?->name ?? '' }}
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:0 1.5rem 1rem">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="80" height="80" valign="top">
                    @php
                    $imagePath = public_path(str_replace(config('app.url'), '',
                    $item->productVariant?->product?->file_url));
                    @endphp
                    <img
                      src="{{ (isset($message) && file_exists($imagePath)) ? $message->embed($imagePath) : ($item->productVariant?->product?->file_url) }}"
                      alt="{{ $item->product_name }}"
                      style="width:100%;height:100%; aspect-ratio: auto; object-fit: cover; display:block">
                  </td>
                  <td valign="top" style="padding-left: 0.8rem !important; line-height: 1.3 !important;">
                    <p style="padding-bottom: 0.2rem !important;">
                      <strong>
                        {{ $item->product_name }}
                      </strong>
                    </p>
                    @if($item->product_variant_name != null)
                    <p style="color:rgba(0, 0, 0, 0.40)">
                      Varian: {{ $item->product_variant_name ?? '-' }}<br>
                    </p>
                    @endif
                    <p style="color:rgba(0, 0, 0, 0.40)">
                      Jumlah: {{ $item->quantity }}x<br>
                    </p>
                    {{-- <p style="color:rgba(0, 0, 0, 0.40)">
                      Ukuran: M
                    </p> --}}
                  </td>
                  <td align="right" valign="top">
                    @if ($item->base_price == $item->selling_price)
                    <p>
                      <strong>
                        Rp{{ number_format($item->selling_price, 0, ',', '.') }}
                      </strong>
                    </p>
                    @else
                    <p style="text-decoration: line-through; font-size:0.8rem !important">
                      Rp{{ number_format($item->base_price, 0, ',', '.') }}
                    </p>
                    <p style="color:#D71920">
                      <strong>
                        Rp{{ number_format($item->selling_price, 0, ',', '.') }}
                      </strong>
                    </p>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach

          <tr>
            <td colspan="2" style="padding: 0 1.5rem;">
            </td>
          </tr>
          <tr>
            <td colspan="2" style="padding: 0 1.5rem;">
              <div style="border: 1px dashed #F1F1F2; width:100%">
              </div>
            </td>
          </tr>


          <!-- Summary -->
          <tr>
            <td style="padding:1.5rem">
              <table width="100%" cellpadding="0" cellspacing="0" style="line-height: 1.8 !important;">
                <tr>
                  <td>
                    <p style="color:rgba(0, 0, 0, 0.70);">
                      Subtotal
                    </p>
                  </td>
                  <td align="right">
                    <p>
                      <strong>
                        Rp{{ number_format($model->subtotal, 0, ',', '.') }}
                      </strong>
                    </p>
                  </td>
                </tr>
                <tr>
                  <td>
                    <p style="color:rgba(0, 0, 0, 0.70);">
                      Biaya Pengiriman
                    </p>
                  </td>
                  <td align="right">
                    {{-- <p style="color:#218037;">
                      <strong>
                        Gratis
                      </strong>
                    </p> --}}
                    <p style="{{ $model->delivery_cost == 0 ? 'color:#218037;' : '' }}">
                      <strong>{{ $model->delivery_cost == 0 ? 'Gratis' : 'Rp' . number_format($model->delivery_cost, 0,
                        ',', '.') }}</strong>
                    </p>
                  </td>
                </tr>
                @if($model->discount > 0 || $model->discount_voucher > 0)
                <tr>
                  <td>
                    <p style="color:rgba(0, 0, 0, 0.70);">
                      Diskon
                    </p>
                  </td>
                  <td align="right">
                    <p style="color:#d71920;">
                      <strong>-Rp{{ number_format(($model->discount + $model->discount_voucher), 0, ',', '.')
                        }}</strong>
                    </p>
                  </td>
                </tr>
                @endif
                @if($model->other_fees > 0)
                <tr>
                  <td>
                    <p style="color:rgba(0, 0, 0, 0.70);">
                      Biaya lainnya
                    </p>
                  </td>
                  <td align="right">
                    <p><strong>Rp{{ number_format($model->other_fees, 0, ',', '.') }}</strong></p>
                  </td>
            </td>
          </tr>
          @endif
        </table>
      </td>
    </tr>

    <!-- Total -->
    <tr>
      <td colspan="2" style="padding: 0 1.5rem;">
        <div style="border: 1px dashed #F1F1F2; width:100%">
        </div>
      </td>
    </tr>
    <tr>
      <td style="padding:1.5rem;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td style="color:#d71920">
              <h3 style="margin:0 !important">
                Total Pembayaran

              </h3>
            </td>
            <td align="right">
              <h3 style="margin:0 !important">
                <strong>
                  Rp{{ number_format($model->grand_total, 0, ',', '.') }}
                </strong>
              </h3>
            </td>
          </tr>
          <tr>
            <td style="padding-top: 1rem !important;">
              <p style="color:rgba(0, 0, 0, 0.70);">
                Metode Pembayaran
              </p>
              <p>
                <strong>
                  {{ $model->channel->name ?? '-' }}
                </strong>
              </p>
            </td>
            <td align="left" style="padding-top: 1rem !important;">
              <p style="color:rgba(0, 0, 0, 0.70);">
                Tanggal Pesanan
              </p>
              <p>
                <strong>{{ $model->created_at->format('d F Y') }}</strong>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <div style="border: 1px solid #F1F1F2; width:100%">
        </div>
      </td>
    </tr>
    <!-- Address -->
    <tr>
      <td style="padding:1.5rem; line-height: 1.4 !important;">
        <h3 style="color:#d71920;margin:0 !important;margin-bottom:1rem !important">
          Alamat Pengiriman
        </h3>
        <p style="margin-bottom: 0.5rem !important;">
          <strong>
            {{ $model->recipient_name ?? $model->buyer_name }}
          </strong>
        </p>
        <p style="margin-bottom: 0.5rem !important;">
          {{ $model->phone_number }}
        </p>
        <p style="margin-bottom: 0.5rem !important;">
          @php
          $addressLine = $model->recipient_data['address_line'] ?? '';
          $province = $model->recipient_data['province'] ?? '';
          $city = $model->recipient_data['city'] ?? '';
          $district = $model->recipient_data['district'] ?? '';
          $subdistrict = $model->recipient_data['subdistrict'] ?? '';
          $postalCode = $model->recipient_data['postal_code'] ?? '';

          $deliveryAddress = "$addressLine, $province, $city, $district, $subdistrict $postalCode";
          @endphp
          {{ $model->recipient_data['address'] ?? ($deliveryAddress ?? 'Alamat tidak ditemukan') }}
        </p>
      </td>
    </tr>

    <!-- Button -->
    <tr>
      <td colspan="2" align="center" style="padding:1rem 1.5rem 0">
        <a href="{{ $urlPath }}" style="background:#d71920;color:#fff;text-decoration:none; width: 100%;
                      padding:1rem 0;
                      display:inline-block;">
          Bayar Sekarang
        </a>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td align="center" style="padding:1rem 1.5rem;">
        <p style="color:rgba(0, 0, 0, 0.70); font-size: 0.9rem !important
              ">
          Jika pembayaran tidak diselesaikan dalam 24 jam,
          pesanan akan dibatalkan secara otomatis.
        </p>
        <table cellpadding="0" cellspacing="0" style="padding:1.5rem 0">

          <tr>
            <td align="center" style="margin: 6px !important;">
              <a href="https://www.facebook.com/people/Sarinah-Indonesia/100054305149130/" target="_blank">
                <span
                  style="background:#F3F3F3; border-radius:50%; width:20px; height:20px; display:inline-block; text-align:center; padding:12px;">
                  <img src="{{ isset($message) ? $message->embed(public_path('assets/img/favicons/icon_facebook.png')) : asset('assets/img/favicons/icon_facebook.png') }}" alt="Facebook Sarinah"
                    style="vertical-align:middle; display:inline-block; width:100%;height: auto;">
                </span>

              </a>
            </td>
            <td align="center">
              <a href="https://www.instagram.com/sarinahindonesia/?hl=en" target="_blank" style="margin:0 0.8rem">
                <span
                  style="background:#F3F3F3; border-radius:50%; width:20px; height:20px; display:inline-block; text-align:center; padding:12px;">
                  <img src="{{ isset($message) ? $message->embed(public_path('assets/img/favicons/icon_instagram.png')) : asset('assets/img/favicons/icon_instagram.png') }}" alt="Instagram Sarinah"
                    style="vertical-align:middle; display:inline-block; width:100%;height: auto;">
                </span>
              </a>
            </td>
            <td align="center">
              <a href="https://www.tiktok.com/@sarinahindonesia?lang=en" target="_blank">
                <span
                  style="background:#F3F3F3; border-radius:50%; width:20px; height:20px; display:inline-block; text-align:center; padding:12px;">
                  <img src="{{ isset($message) ? $message->embed(public_path('assets/img/favicons/icon_tiktok.png')) : asset('assets/img/favicons/icon_tiktok.png') }}" alt="TikTok Sarinah"
                    style="vertical-align:middle; display:inline-block; width:100%;height: auto;">
                </span>
              </a>
            </td>
          </tr>
        </table>
        <p style="color:#000;margin-bottom: 0.5rem !important;">
          Terimakasih telah berbelanja di Sarinah
        </p>
        <p style="color:rgba(0, 0, 0, 0.40); font-size: 0.8rem !important;">
          © 2026 Sarinah eCommerce. Hak cipta dilindungi undang-undang. Anda menerima email ini
          karena Anda
          melakukan pembelian di toko kami
        </p>
        <br><br>
        <p style="font-size: 0.8rem !important; margin-bottom: 0.3em !important;">
          Butuh bantuan? Hubungi kami <a href="{{ $supportUrl }}" style="color:#D71920">di sini</a>
        </p>
        <p style="color:rgba(0, 0, 0, 0.40);font-size: 0.8rem !important;">
          Ini adalah email otomatis. Mohon tidak membalas email ini. PT Sarinah (Persero), Gedung
          Sarinah, Jl.
          M.H. Thamrin No. 11,
          Jakarta Pusat 10350
        </p>
        <br>
      </td>
    </tr>

  </table>

  </td>
  </tr>
  </table>

</body>

</html>