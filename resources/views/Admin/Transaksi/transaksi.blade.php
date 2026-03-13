@extends('layouts.Admin.mainlayout')
@section('title', 'Data Transaksi')

@push('styles')
<style>
    :root {
        --border: rgba(0, 0, 0, .08);
        --muted: #6c757d;
        --radius-card: 16px;
        --radius-btn: 12px;
        --radius-pill: 999px;
    }

    .wrap { max-width: 1250px; margin: 0 auto }
    .card { border: 1px solid var(--border) !important; border-radius: var(--radius-card) }
    .btn { border-radius: var(--radius-btn) }
    .badge-pill { border-radius: var(--radius-pill); padding: .35rem .65rem }

    .page-title { font-weight: 900; margin: 0 }
    .page-sub { color: var(--muted); font-size: .9rem; margin-top: 4px }

    .filter-bar{
        display:flex;gap:.5rem;flex-wrap:wrap;
        justify-content:flex-end;align-items:center
    }
    .filter-select{min-width:220px}

    .table th,.table td{vertical-align:middle}
    .table thead th{
        font-size:.8rem;font-weight:800;color:#111;
        text-transform:uppercase;letter-spacing:.04em;
        white-space:nowrap;border-bottom:1px solid var(--border);
        padding:12px 12px;
    }
    .table tbody td{padding:12px 12px}
    .table tbody tr:hover{background:rgba(0,0,0,.015)}
    .muted{color:var(--muted)}
    .nowrap{white-space:nowrap}

    .trx-card{
        border:1px solid var(--border);
        border-radius:14px;
        padding:14px;
        background:#fff;
    }
    .trx-row{
        display:flex;
        justify-content:space-between;
        gap:12px;
        padding:8px 0;
        border-bottom:1px solid var(--border);
    }
    .trx-row:last-child{border-bottom:0}
    .trx-k{font-size:.82rem;color:var(--muted)}
    .trx-v{font-weight:700;text-align:right;word-break:break-word}
      .pagination {
        margin: 0;
        padding-left: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
    }

    .pagination .page-item .page-link {
        border-radius: 10px;
        padding: 6px 14px;
        border: 1px solid #ddd;
        color: #333;
        font-weight: 500;
        transition: 0.2s;
    }

    .pagination .page-item .page-link:hover {
        background-color: #212529;
        color: #fff;
        border-color: #212529;
    }

    .pagination .page-item.active .page-link {
        background-color: #212529;
        border-color: #212529;
        color: #fff;
    }

    .pagination .page-item.disabled .page-link {
        background-color: #f1f1f1;
        color: #aaa;
        border-color: #ddd;
    }
</style>
@endpush

@section('content')

@php
    $statuses = ['pending','paid','diproses','dikirim','selesai','expired','dibatalkan'];
@endphp

<div class="container-fluid py-4">
    <div class="wrap">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h4 class="page-title">Data Transaksi</h4>
                <div class="page-sub">Daftar transaksi pembayaran dan pengiriman.</div>
            </div>

            <form method="GET" class="filter-bar">
                <select name="status" class="form-select filter-select">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ request('status')===$st ? 'selected' : '' }}>
                            {{ strtoupper($st) }}
                        </option>
                    @endforeach
                </select>

                <button class="btn btn-dark">Filter</button>
                <a href="{{ route('admin.transaksi') }}" class="btn btn-outline-secondary">Reset</a>
            </form>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">

                {{-- =======================
                     DESKTOP/TABLE (md+)
                ======================== --}}
                <div class="d-none d-md-block">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">Kode</th>
                                <th class="text-start">Customer</th>
                                <th class="text-center">Metode</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Status</th>
                                <th class="text-start">Pengiriman</th>
                                <th class="text-center">Refund</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($transaksis as $t)
                                @php
                                    $badge = match($t->status_transaksi){
                                        'pending' => 'warning',
                                        'paid' => 'success',
                                        'diproses' => 'info',
                                        'dikirim' => 'primary',
                                        'selesai' => 'dark',
                                        'expired' => 'secondary',
                                        'dibatalkan' => 'danger',
                                        default => 'secondary'
                                    };

                                    $statusLabel = match($t->status_transaksi){
                                        'pending' => 'PENDING',
                                        'paid' => 'PAID',
                                        'diproses' => 'DIPROSES',
                                        'dikirim' => 'DIKIRIM',
                                        'selesai' => 'SELESAI',
                                        'expired' => 'EXPIRED',
                                        'dibatalkan' => 'BATAL',
                                        default => strtoupper($t->status_transaksi ?? '-')
                                    };

                                    $refund = $t->latestRefund ?? null;
                                    $refundColor = $refund ? match($refund->status){
                                        'requested' => 'warning',
                                        'processing' => 'info',
                                        'refunded' => 'success',
                                        'failed' => 'danger',
                                        default => 'secondary'
                                    } : null;
                                @endphp

                                <tr>
                                    <td class="text-start">
                                        <div class="fw-bold">{{ $t->kode_transaksi ?? '-' }}</div>
                                        <div class="small muted">{{ $t->created_at?->format('d M Y H:i') ?? '-' }}</div>
                                    </td>

                                    <td class="text-start">
                                        <div class="fw-semibold">{{ optional($t->user)->name ?? '-' }}</div>
                                        <div class="small muted">{{ optional($t->user)->email ?? '' }}</div>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            {{ strtoupper($t->metode_pembayaran ?? '-') }}
                                        </span>
                                    </td>

                                    <td class="text-end fw-bold nowrap">
                                        Rp {{ number_format($t->total_pembayaran ?? 0,0,',','.') }}
                                    </td>

                                    <td class="text-center">
                                        <span class="badge bg-{{ $badge }} {{ $badge=='warning' ? 'text-dark' : '' }} badge-pill">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td class="text-start">
                                        @if($t->no_resi)
                                            <div class="fw-semibold">{{ $t->ekspedisi ?? '-' }}</div>
                                            <div class="small muted">{{ $t->no_resi }}</div>
                                            <div class="small muted">
                                                Dikirim: {{ $t->tanggal_dikirim ? $t->tanggal_dikirim->format('d M Y H:i') : '-' }}
                                            </div>
                                        @else
                                            <span class="muted">Belum dikirim</span>
                                        @endif
                                    </td>

                                    {{-- ✅ REFUND KOLOM (BENAR DI DALAM TR) --}}
                                    <td class="text-center">
                                        @if($refund)
                                            <span class="badge bg-{{ $refundColor }} {{ $refundColor==='warning' ? 'text-dark' : '' }} badge-pill">
                                                {{ strtoupper($refund->status) }}
                                            </span>
                                            <div class="small muted">{{ strtoupper($refund->method) }}</div>
                                        @else
                                            <span class="muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-end nowrap">
                                        @if(in_array($t->status_transaksi, ['paid','diproses'], true))
                                            <a href="{{ route('admin.transaksi.show', $t->id) }}#kirim"
                                               class="btn btn-outline-dark btn-sm ms-1">
                                                Kirim
                                            </a>
                                        @endif

                                        <a href="{{ route('admin.transaksi.show', $t->id) }}" class="btn btn-primary btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-5 text-center text-muted">
                                        Belum ada transaksi
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- =======================
                     MOBILE/CARDS (sm)
                ======================== --}}
                <div class="d-md-none p-3">
                    @forelse($transaksis as $t)
                        @php
                            $badge = match($t->status_transaksi){
                                'pending' => 'warning',
                                'paid' => 'success',
                                'diproses' => 'info',
                                'dikirim' => 'primary',
                                'selesai' => 'dark',
                                'expired' => 'secondary',
                                'dibatalkan' => 'danger',
                                default => 'secondary'
                            };

                            $statusLabel = match($t->status_transaksi){
                                'pending' => 'PENDING',
                                'paid' => 'PAID',
                                'diproses' => 'DIPROSES',
                                'dikirim' => 'DIKIRIM',
                                'selesai' => 'SELESAI',
                                'expired' => 'EXPIRED',
                                'dibatalkan' => 'BATAL',
                                default => strtoupper($t->status_transaksi ?? '-')
                            };

                            $refund = $t->latestRefund ?? null;
                            $refundColor = $refund ? match($refund->status){
                                'requested' => 'warning',
                                'processing' => 'info',
                                'refunded' => 'success',
                                'failed' => 'danger',
                                default => 'secondary'
                            } : null;
                        @endphp

                        <div class="trx-card mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-bold">{{ $t->kode_transaksi ?? '-' }}</div>
                                    <div class="small muted">{{ $t->created_at?->format('d M Y H:i') ?? '-' }}</div>
                                </div>
                                <span class="badge bg-{{ $badge }} {{ $badge=='warning' ? 'text-dark' : '' }} badge-pill">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="trx-row">
                                <div class="trx-k">Customer</div>
                                <div class="trx-v">
                                    {{ optional($t->user)->name ?? '-' }}
                                    <div class="small muted fw-normal">{{ optional($t->user)->email ?? '' }}</div>
                                </div>
                            </div>

                            <div class="trx-row">
                                <div class="trx-k">Metode</div>
                                <div class="trx-v">{{ strtoupper($t->metode_pembayaran ?? '-') }}</div>
                            </div>

                            <div class="trx-row">
                                <div class="trx-k">Total</div>
                                <div class="trx-v">Rp {{ number_format($t->total_pembayaran ?? 0,0,',','.') }}</div>
                            </div>

                            <div class="trx-row">
                                <div class="trx-k">Pengiriman</div>
                                <div class="trx-v">
                                    @if($t->no_resi)
                                        {{ $t->ekspedisi ?? '-' }} • {{ $t->no_resi }}
                                        @if($t->tanggal_dikirim)
                                            <div class="small muted fw-normal">
                                                {{ $t->tanggal_dikirim->format('d M Y H:i') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="muted fw-normal">Belum dikirim</span>
                                    @endif
                                </div>
                            </div>

                            {{-- ✅ REFUND ROW (DI DALAM CARD) --}}
                            <div class="trx-row">
                                <div class="trx-k">Refund</div>
                                <div class="trx-v">
                                    @if($refund)
                                        <span class="badge bg-{{ $refundColor }} {{ $refundColor==='warning' ? 'text-dark' : '' }} badge-pill">
                                            {{ strtoupper($refund->status) }}
                                        </span>
                                        <div class="small muted fw-normal">{{ strtoupper($refund->method) }}</div>
                                    @else
                                        <span class="muted fw-normal">-</span>
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <a class="btn btn-primary btn-sm" href="{{ route('admin.transaksi.show', $t->id) }}">
                                    Detail
                                </a>

                                @if(in_array($t->status_transaksi, ['paid','diproses'], true))
                                    <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.transaksi.show', $t->id) }}#kirim">
                                        Kirim
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-4 text-center text-muted">
                            Belum ada transaksi
                        </div>
                    @endforelse
                </div>

                <div class="p-3 border-top">
                    {{ $transaksis->withQueryString()->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
