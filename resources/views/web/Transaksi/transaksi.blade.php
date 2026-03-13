@extends('layouts.mainlayout')
@section('title', 'Pesanan Saya')

@section('content')
<style>
  /* ===== Elegant Orders Card ===== */
  .page-title { letter-spacing: .2px; }

  .order-card{
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 18px;
    background: #fff;
    overflow: hidden;
    transition: .2s ease;
  }
  .order-card:hover{
    transform: translateY(-2px);
    box-shadow: 0 12px 26px rgba(0,0,0,.08);
  }

  .order-top{
    padding: 14px 16px;
    background: linear-gradient(180deg, rgba(248,249,250,.9), rgba(255,255,255,1));
    border-bottom: 1px solid rgba(0,0,0,.06);
  }

  .order-code{
    font-weight: 700;
    font-size: 14px;
    letter-spacing: .3px;
  }

  .muted{ color:#6c757d; font-size:13px; }

  .kv{
    display:grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 12px;
    padding: 14px 16px;
  }
  .kv-item{
    grid-column: span 3;
    padding: 10px 12px;
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 14px;
    background: #fff;
  }
  .kv-label{ font-size:12px; color:#6c757d; margin-bottom:2px; }
  .kv-value{ font-weight:650; font-size:14px; }

  .order-actions{
    padding: 14px 16px;
    display:flex;
    gap: 10px;
    flex-wrap: wrap;
    border-top: 1px solid rgba(0,0,0,.06);
    background:#fff;
  }

  .btn-soft{
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,.10);
    background: #fff;
  }

  .badge-pill{
    border-radius: 999px;
    padding: 8px 10px;
    font-weight: 650;
    letter-spacing: .2px;
  }

  .badge-outline{
    background: rgba(0,0,0,.03) !important;
    color: #212529 !important;
    border: 1px solid rgba(0,0,0,.12);
  }

  .amount{
    font-weight: 800;
    font-size: 16px;
    letter-spacing: .2px;
  }

  /* Responsive */
  @media (max-width: 992px){ .kv-item{ grid-column: span 4; } }
  @media (max-width: 768px){ .kv-item{ grid-column: span 6; } }
  @media (max-width: 576px){
    .kv{ gap:10px; }
    .kv-item{ grid-column: span 12; }
  }
</style>

<div class="container py-5">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <h4 class="fw-bold mb-1 page-title">Pesanan Saya</h4>
      <div class="muted">Kelola status transaksi, pembayaran, dan refund dalam satu tampilan.</div>
    </div>

    <form method="GET" class="d-flex gap-2 flex-wrap">
      <select name="status" class="form-select form-select-sm" style="max-width: 240px">
        <option value="">Semua Status</option>
        @foreach (['pending','paid','diproses','dikirim','selesai','expired','dibatalkan'] as $st)
          <option value="{{ $st }}" @selected(request('status') === $st)>{{ strtoupper($st) }}</option>
        @endforeach
      </select>
      <button class="btn btn-dark btn-sm rounded-3 px-3">Filter</button>
    </form>
  </div>

  @if (session('success'))
    <div class="alert alert-success rounded-4 border-0">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger rounded-4 border-0">{{ session('error') }}</div>
  @endif

  {{-- List --}}
  <div class="row g-4">
    @forelse ($transaksis as $trx)

     @php
  // ===== Badge transaksi =====
  $trxBadge = match ($trx->status_transaksi) {
    'paid' => 'success',
    'pending' => 'secondary',
    'diproses' => 'info',
    'dikirim' => 'primary',
    'selesai' => 'dark',
    'expired' => 'secondary',
    'dibatalkan' => 'danger',
    default => 'secondary',
  };

  // ===== Status pembayaran (semua metode, termasuk midtrans) =====
  $payStatus = optional($trx->pembayaran)->status_pembayaran; // paid|pending|expired|ditolak|refund|...

  $payBadge = match ($payStatus) {
    'paid' => 'success',
    'pending' => 'warning',
    'menunggu_verifikasi' => 'warning',
    'expired' => 'secondary',
    'dibatalkan' => 'danger',
    'ditolak' => 'danger',
    'refund' => 'danger',
    'refund_processing' => 'info',
    'partial_refund' => 'warning',
    default => 'light',
  };

  $isMidtrans = $trx->metode_pembayaran === 'midtrans';

  /**
   * ✅ RULE:
   * - Label status pembayaran Midtrans HANYA berdasarkan status_pembayaran (bukan status_transaksi)
   * - Jadi tidak akan pernah tampil "DIKIRIM" dll di kolom pembayaran.
   */
  $midtransPayLabel = match ($payStatus) {
    'paid' => 'PAID',
    'expired' => 'EXPIRED',
    'ditolak', 'dibatalkan', 'failed' => 'GAGAL',
    'refund' => 'REFUND',
    'refund_processing' => 'REFUND DIPROSES',
    'partial_refund' => 'PARTIAL REFUND',
    default => 'MENUNGGU', // termasuk null/pending
  };

  $midtransPayBadge = match ($payStatus) {
    'paid' => 'success',
    'expired' => 'secondary',
    'ditolak', 'dibatalkan', 'failed' => 'danger',
    'refund' => 'danger',
    'refund_processing' => 'info',
    'partial_refund' => 'warning',
    default => 'warning', // pending/null
  };

  // Tombol bayar midtrans tetap berdasarkan status transaksi pending
  $canPayMidtrans = $isMidtrans && $trx->status_transaksi === 'pending';

  // ===== Refund =====
  $refund = $trx->latestRefund ?? null;
  $refundBadge = $refund
    ? match ($refund->status) {
        'requested' => 'warning',
        'processing' => 'info',
        'refunded' => 'success',
        'failed' => 'danger',
        default => 'secondary',
      }
    : null;

  $canRefund = $trx->status_transaksi === 'selesai' && (!$refund || $refund->status === 'failed');
@endphp

      <div class="col-12">
        <div class="order-card">

          {{-- Top --}}
          <div class="order-top d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
              <div class="order-code">
                <span class="me-2">#{{ $trx->kode_transaksi }}</span>
                <span class="badge badge-pill bg-{{ $trxBadge }}">{{ strtoupper($trx->status_transaksi) }}</span>
              </div>
              <div class="muted mt-1">{{ $trx->created_at->format('d M Y H:i') }}</div>
            </div>

            <div class="text-end">
              <div class="muted">Total</div>
              <div class="amount text-success">Rp {{ number_format($trx->total_pembayaran, 0, ',', '.') }}</div>
            </div>
          </div>

          {{-- Key Values --}}
          <div class="kv">

            <div class="kv-item">
              <div class="kv-label">Metode Pembayaran</div>
              <div class="kv-value">
                <span class="badge badge-pill badge-outline">{{ strtoupper($trx->metode_pembayaran) }}</span>
              </div>
            </div>

            <div class="kv-item">
  <div class="kv-label">Status Pembayaran</div>
  <div class="kv-value">
    @if ($isMidtrans)
      <span class="badge badge-pill bg-{{ $midtransPayBadge }} {{ $midtransPayBadge === 'warning' ? 'text-dark' : '' }}">
        {{ $midtransPayLabel }}
      </span>
    @else
      <span class="badge badge-pill bg-{{ $payBadge }} {{ in_array($payBadge, ['warning','light'], true) ? 'text-dark' : '' }}">
        {{ $payStatus ? strtoupper($payStatus) : 'BELUM' }}
      </span>
    @endif
  </div>
</div>

            <div class="kv-item">
              <div class="kv-label">Refund</div>
              <div class="kv-value">
                @if ($refund)
                  <span class="badge badge-pill bg-{{ $refundBadge }} {{ in_array($refundBadge, ['warning','light'], true) ? 'text-dark' : '' }}">
                    {{ strtoupper($refund->status) }}
                  </span>
                @else
                  <span class="muted">-</span>
                @endif
              </div>
            </div>

            <div class="kv-item">
              <div class="kv-label">Ringkasan</div>
              <div class="kv-value">
                <span class="muted">
                  {{ $isMidtrans ? 'Pembayaran via Midtrans' : 'Pembayaran manual/transfer' }}
                </span>
              </div>
            </div>

          </div>

          {{-- Actions --}}
          <div class="order-actions">
            <a href="{{ route('transaksi.show', $trx->id) }}" class="btn btn-sm btn-primary rounded-3 px-3">
              Detail
            </a>

            {{-- ✅ RULE: tombol Bayar hanya muncul saat status_transaksi = pending --}}
            @if ($canPayMidtrans)
              <a href="{{ route('midtrans.pay', $trx->id) }}" class="btn btn-sm btn-soft px-3">
                Bayar
              </a>
            @endif

            @if ($canRefund)
              <a href="{{ route('transaksi.show', $trx->id) }}#refund" class="btn btn-sm btn-outline-danger rounded-3 px-3">
                Refund
              </a>
            @endif
          </div>

        </div>
      </div>

    @empty
      <div class="col-12">
        <div class="alert alert-secondary text-center rounded-4 border-0 py-4">
          Belum ada transaksi
        </div>
      </div>
    @endforelse
  </div>

  {{-- Pagination --}}
  <div class="mt-4">
    {{ $transaksis->withQueryString()->links('pagination::bootstrap-5') }}
  </div>

</div>
@endsection
