<!DOCTYPE html>
<html>
<head>
    <style>
        .panel { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 10px; margin: 20px 0; }
        .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; font-size: 12px; color: #777; }
        .status-badge { color: #d35400; font-weight: bold; }
    </style>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <p>Halo <strong>{{ $roleName }}</strong>,</p>
    
    <p>Terdapat pembatalan pesanan dengan detail berikut:</p>

    <div class="panel">
        <p style="margin: 5px 0;"><strong>Order ID:</strong> #{{ $model->order_number }}</p>
        <p style="margin: 5px 0;"><strong>Nama Konsumen:</strong> {{ $model->user->name ?? $model->buyer_name }}</p>
        <p style="margin: 5px 0;"><strong>Tanggal Pembatalan:</strong> {{ $model->updated_at->format('d F Y') }}</p>
        <p style="margin: 5px 0;"><strong>Alasan Pembatalan:</strong> {{ $model->reason }}</p>
        <p style="margin: 5px 0;"><strong>Status Terbaru:</strong> <span class="status-badge">{{ $statusLabel }}</span></p>
    </div>

    <p>Mohon untuk segera melakukan pengecekan dan tindak lanjut sesuai dengan prosedur yang berlaku.</p>
    <p>Terima kasih atas perhatian dan kerja samanya</p>

    <div class="footer">
        <p>Terima kasih,<br>Sistem {{ config('app.name') }}</p>
        <img src="{{ $message->embed(public_path('assets/img/favicons/logo_sarinah_email.png')) }}" width="150" alt="Logo Sarinah">
    </div>
</body>
</html>