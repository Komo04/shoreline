<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cetak Laporan Pendapatan</title>

  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
    rel="stylesheet"
  />

  <style>
    /* Sembunyikan tombol saat print */
    @media print {
      .no-print { display: none !important; }
      .table { page-break-inside: auto; }
      tr, td, th { page-break-inside: avoid; page-break-after: auto; }
    }

    /* Rapihin tampilan */
    body { font-family: 'Poppins', sans-serif; }
    .report-header {
      border-bottom: 1px solid #e9ecef;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }
    .meta { font-size: .9rem; }
    .summary {
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: .5rem;
      padding: 12px 14px;
      margin-bottom: 16px;
    }
    .table thead th { white-space: nowrap; }
  </style>
</head>

<body class="bg-white">
  <div class="container py-4">
    <!-- Header -->
    <div class="report-header d-flex justify-content-between align-items-start gap-3">
      <div>
        <h4 class="fw-bold mb-1">Laporan Pendapatan</h4>
        <div class="meta text-muted">
          <div>Periode: <span class="fw-semibold">{{ $start->format('d M Y') }}</span> s/d <span class="fw-semibold">{{ $end->format('d M Y') }}</span></div>
          <div>Dicetak: <span class="fw-semibold">{{ now()->format('d M Y H:i') }}</span></div>
        </div>
      </div>

      <div class="no-print d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="window.print()">
          Print
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="window.close()">
          Tutup
        </button>
      </div>
    </div>

    <!-- Summary -->
    <div class="summary d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="text-muted">Total Pendapatan</div>
      <div class="fs-5 fw-bold">
        Rp {{ number_format($totalPendapatan, 0, ',', '.') }}
      </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 60px;">No</th>
            <th>Tgl Bayar</th>
            <th>Kode Transaksi</th>
            <th>Pelanggan</th>
            <th>Metode</th>
            <th class="text-end">Total</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          @forelse($rows as $i => $r)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>{{ optional($r->paid_at)->format('d M Y H:i') }}</td>
              <td>{{ $r->kode_transaksi }}</td>
              <td>{{ $r->user?->name ?? '-' }}</td>
              <td>{{ $r->metode_pembayaran }}</td>
              <td class="text-end">Rp {{ number_format($r->total_pembayaran, 0, ',', '.') }}</td>
              <td>
                <span class="badge bg-secondary">{{ $r->status_transaksi }}</span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                Tidak ada data.
              </td>
            </tr>
          @endforelse
        </tbody>

        @if($rows->count())
          <tfoot class="table-light">
            <tr>
              <th colspan="5" class="text-end">TOTAL</th>
              <th class="text-end">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</th>
              <th></th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>

  <script>
    // Auto print (kalau tidak mau auto print, hapus baris ini)
    window.addEventListener("load", () => window.print());
  </script>
</body>
</html>
