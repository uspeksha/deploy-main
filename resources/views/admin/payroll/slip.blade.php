<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji {{ $item->slip_number }}</title>
    <style>
        @page { margin: 28px; }
        * { box-sizing: border-box; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { border-bottom: 3px solid #059669; margin-bottom: 18px; padding-bottom: 12px; }
        .logo { height: 42px; width: auto; }
        .brand { color: #047857; font-size: 21px; font-weight: bold; letter-spacing: 1px; margin-top: 8px; }
        .title { color: #111827; font-size: 15px; font-weight: bold; margin-top: 4px; }
        .meta, .identity, .earnings, .deductions, .summary { border-collapse: collapse; width: 100%; }
        .meta td { padding: 3px 0; }
        .identity, .earnings, .deductions { margin-top: 14px; }
        th { background: #ecfdf5; color: #065f46; text-align: left; }
        th, td { border: 1px solid #d1d5db; padding: 8px; }
        .section { background: #f3f4f6; font-weight: bold; margin-top: 14px; padding: 8px; }
        .amount { text-align: right; white-space: nowrap; }
        .summary { margin-top: 14px; }
        .summary td { border: 0; padding: 5px 8px; }
        .net { background: #ecfdf5; color: #065f46; font-size: 13px; font-weight: bold; }
        .status { border: 1px solid #d1d5db; display: inline-block; font-weight: bold; margin-top: 12px; padding: 6px 10px; }
        .draft { background: #fef3c7; color: #92400e; }
        .paid { background: #d1fae5; color: #065f46; }
        .footer { border-top: 1px solid #d1d5db; color: #6b7280; margin-top: 28px; padding-top: 10px; }
        .signature { margin-top: 34px; text-align: right; }
    </style>
</head>
<body>
    @php
        $money = fn ($amount) => 'Rp' . number_format((int) $amount, 0, ',', '.');
        $statusLabels = [
            'draft' => 'DRAFT - BELUM FINAL',
            'exported' => 'DITARIK - BELUM FINAL',
            'pending_approval' => 'MENUNGGU PERSETUJUAN',
            'approved' => 'DISETUJUI - BELUM DIBAYARKAN',
            'paid' => 'DIBAYARKAN (KONFIRMASI MANUAL)',
            'cancelled' => 'DIBATALKAN',
        ];
    @endphp
    <div class="header">
        <img class="logo" src="{{ public_path('images/logo-followme.png') }}" alt="Followme">
        <div class="brand">FOLLOWME</div>
        <div class="title">SLIP GAJI KARYAWAN FREELANCE</div>
    </div>

    <table class="meta">
        <tr><td><strong>Nomor Slip</strong></td><td>{{ $item->slip_number }}</td><td><strong>Periode</strong></td><td>{{ $period->label }}</td></tr>
        <tr><td><strong>Tanggal Terbit</strong></td><td>{{ now()->format('d/m/Y') }}</td><td><strong>Rentang</strong></td><td>{{ $period->start_date->format('d/m/Y') }} - {{ $period->end_date->format('d/m/Y') }}</td></tr>
    </table>

    <div class="section">IDENTITAS KARYAWAN</div>
    <table class="identity">
        <tr><th style="width: 25%">ID Karyawan</th><td>{{ $item->employee_id ?? '-' }}</td><th style="width: 25%">Nama</th><td>{{ $item->employee_name }}</td></tr>
        <tr><th>Jenis Karyawan</th><td>{{ ucfirst($item->employment_type) }}</td><th>Departemen</th><td>{{ $item->department ?? '-' }}</td></tr>
    </table>

    <div class="section">PENDAPATAN</div>
    <table class="earnings">
        <thead><tr><th>Rincian</th><th class="amount">Jumlah</th></tr></thead>
        <tbody>
            <tr><td>Hari kerja valid × {{ $money($item->daily_rate) }}</td><td class="amount">{{ $item->work_days }} hari</td></tr>
            <tr><td>Total gaji kotor</td><td class="amount">{{ $money($item->gross_pay) }}</td></tr>
        </tbody>
    </table>

    <div class="section">RINCIAN POTONGAN KETERLAMBATAN</div>
    <table class="deductions">
        <thead>
            <tr><th>Tanggal</th><th class="amount">Menit</th><th class="amount">Blok 30 menit</th><th class="amount">Potongan</th></tr>
        </thead>
        <tbody>
            @forelse ($item->latenessDetails as $detail)
                <tr>
                    <td>{{ $detail->work_date->format('d/m/Y') }}</td>
                    <td class="amount">{{ $detail->late_minutes }}</td>
                    <td class="amount">{{ $detail->penalty_blocks }}</td>
                    <td class="amount">{{ $money($detail->deduction_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tidak ada keterlambatan tercatat.</td></tr>
            @endforelse
            <tr><th colspan="3">Total potongan keterlambatan</th><th class="amount">{{ $money($item->late_deduction) }}</th></tr>
            <tr><td colspan="3">Potongan lain yang tercatat</td><td class="amount">{{ $money($item->other_deductions) }}</td></tr>
        </tbody>
    </table>

    <table class="summary">
        <tr><td>Total pendapatan</td><td class="amount">{{ $money($item->gross_pay) }}</td></tr>
        <tr><td>Total potongan</td><td class="amount">{{ $money($item->late_deduction + $item->other_deductions) }}</td></tr>
        <tr class="net"><td>GAJI BERSIH DITERIMA</td><td class="amount">{{ $money($item->net_pay) }}</td></tr>
    </table>

    <div class="status {{ in_array($period->status, ['draft', 'exported'], true) ? 'draft' : ($period->status === 'paid' ? 'paid' : '') }}">
        Status: {{ $statusLabels[$period->status] ?? $period->status }}
    </div>
    @if ($period->status === 'paid')
        <p>Dikonfirmasi dibayar {{ $period->paid_at?->format('d/m/Y H:i') }} oleh {{ $period->payer?->name ?? '-' }}. Referensi: {{ $period->payment_reference }}</p>
    @elseif ($period->status === 'approved')
        <p>Disetujui {{ $period->approved_at?->format('d/m/Y H:i') }} oleh {{ $period->approver?->name ?? '-' }}.</p>
    @endif

    <div class="signature">
        HRD FOLLOWME<br><br><br>
        {{ $period->approver?->name ?? 'Petugas Payroll' }}
    </div>
    <div class="footer">
        Potongan dihitung dari nilai keterlambatan tersimpan pada absensi; setiap 30 menit penuh per tanggal dikenakan Rp7.500.
        Slip ini mengikuti snapshot payroll dan tidak mengubah data absensi.
    </div>
</body>
</html>
