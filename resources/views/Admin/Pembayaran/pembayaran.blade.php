@extends('layouts.Admin.mainlayout')
@section('title','Verifikasi Pembayaran')

@push('styles')
<style>
    :root {
        --border: rgba(0, 0, 0, .08);
        --muted: #6c757d;
        --radius-card: 16px;
        --radius-btn: 12px;
        --radius-pill: 999px;
    }

    .wrap {
        max-width: 1250px;
        margin: 0 auto
    }

    .card {
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-card)
    }

    .btn {
        border-radius: var(--radius-btn)
    }

    .badge-pill {
        border-radius: var(--radius-pill);
        padding: .35rem .65rem
    }

    .page-title {
        font-weight: 900;
        margin: 0
    }

    .page-sub {
        color: var(--muted);
        font-size: .9rem;
        margin-top: 4px
    }

    .filter-bar {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
        align-items: center
    }

    .filter-input {
        min-width: 260px
    }

    .filter-select {
        min-width: 220px
    }

    @media (max-width: 576px) {

        .filter-input,
        .filter-select {
            min-width: 100%
        }

        .filter-bar {
            justify-content: stretch
        }

        .filter-bar>* {
            flex: 1 1 auto
        }
    }

    /* Desktop table look */
    .table th,
    .table td {
        vertical-align: middle
    }

    .table thead th {
        font-size: .8rem;
        font-weight: 800;
        color: #111;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
        border-bottom: 1px solid var(--border);
        padding: 12px 12px;
    }

    .table tbody td {
        padding: 12px 12px
    }

    .table tbody tr:hover {
        background: rgba(0, 0, 0, .015)
    }

    .muted {
        color: var(--muted)
    }

    .nowrap {
        white-space: nowrap
    }

    /* Mobile cards */
    .pay-card {
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 14px;
        background: #fff;
    }

    .kv {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid var(--border);
    }

    .kv:last-child {
        border-bottom: 0
    }

    .k {
        font-size: .82rem;
        color: var(--muted)
    }

    .v {
        font-weight: 700;
        text-align: right;
        word-break: break-word
    }

    /* Stats cards */
    .stat-card {
        border: 1px solid var(--border) !important;
        border-radius: 14px

    }
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
$statusList = ['menunggu_verifikasi','paid','ditolak','expired','dibatalkan','refund','refund_processing','partial_refund'];
@endphp

<div class="container-fluid py-4">
    <div class="wrap">


        {{-- HEADER + FILTER --}}
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h4 class="page-title">Verifikasi Pembayaran</h4>
                <div class="page-sub">Kelola pembayaran manual dan cek bukti transfer.</div>
            </div>

            <form class="filter-bar" method="GET" action="{{ route('admin.pembayaran') }}">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control filter-input" placeholder="Cari nama user...">

                <select name="status" class="form-select filter-select">
                    <option value="">Semua Status</option>
                    @foreach($statusList as $st)
                    <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>
                        {{ strtoupper(str_replace('_',' ', $st)) }}
                    </option>
                    @endforeach
                </select>

                <button class="btn btn-dark">Terapkan</button>

                @if(request('search') || request('status'))
                <a href="{{ route('admin.pembayaran') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>

        {{-- STATS --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Total</div>
                        <div class="fs-4 fw-bold">{{ $pembayarans->total() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Menunggu</div>
                        <div class="fs-4 fw-bold text-warning">{{ $menunggu }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Paid Hari Ini</div>
                        <div class="fs-4 fw-bold text-success">{{ $paidToday }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Dibatalkan</div>
                        <div class="fs-4 fw-bold text-danger">{{ $totalDibatalkan }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- LIST WRAPPER --}}
        <div class="card shadow-sm">
            <div class="card-body p-0">

                {{-- =======================
                     DESKTOP/TABLE (md+)
                ======================== --}}
                <div class="d-none d-md-block">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">User</th>
                                <th class="text-start">Kode</th>
                                <th class="text-center">Metode</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Bukti</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($pembayarans as $p)
                            @php
                            $status = $p->status_pembayaran ?? '-';
                            $badge = match($status){
                            'menunggu_verifikasi' => 'warning',
                            'paid' => 'success',
                            'ditolak' => 'danger',
                            'expired' => 'secondary',
                            'dibatalkan' => 'danger',
                            default => 'secondary'
                            };

                            $isMidtrans = ($p->metode_pembayaran === 'midtrans');
                            $canAction = ($status === 'menunggu_verifikasi') && !$isMidtrans;

                            $trx = $p->transaksi;
                            @endphp

                            <tr>
                                <td class="text-start">
                                    <div class="fw-semibold">{{ optional($trx->user)->name ?? '-' }}</div>
                                    <div class="small muted">{{ optional($trx->user)->email ?? '' }}</div>
                                </td>

                                <td class="text-start">
                                    <div class="fw-bold">{{ $trx->kode_transaksi ?? '-' }}</div>
                                    <div class="small muted">
                                        {{ optional($p->tanggal_pembayaran)->format('d M Y') ?? '-' }}
                                    </div>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        {{ strtoupper($p->metode_pembayaran ?? '-') }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-{{ $badge }} {{ $badge=='warning' ? 'text-dark' : '' }} badge-pill">
                                        {{ strtoupper(str_replace('_',' ', $status)) }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    @if(!$isMidtrans && $p->bukti_transfer)
                                    <span class="badge bg-success-subtle text-success border">ADA</span>
                                    @else
                                    <span class="badge bg-light text-dark border">-</span>
                                    @endif
                                </td>

                                <td class="text-end fw-bold nowrap">
                                    Rp {{ number_format($p->total_pembayaran ?? 0,0,',','.') }}
                                </td>

                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2 flex-wrap">

                                        @if($canAction)
                                        <form method="POST" action="{{ route('admin.pembayaran.konfirmasi', $p->id) }}">
                                            @csrf
                                            <button class="btn btn-success btn-sm" onclick="return confirm('Verifikasi pembayaran ini?')">
                                                Konfirmasi
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.pembayaran.tolak', $p->id) }}">
                                            @csrf
                                            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Tolak pembayaran ini?')">
                                                Tolak
                                            </button>
                                        </form>
                                        @endif

                                        @if(!$isMidtrans && $p->bukti_transfer)
                                        <a href="{{ asset('storage/'.$p->bukti_transfer) }}" target="_blank" class="btn btn-outline-dark btn-sm">
                                            Bukti
                                        </a>
                                        @endif

                                        <a href="{{ route('admin.pembayaran.show', $p->id) }}" class="btn btn-primary btn-sm">
                                            Detail
                                        </a>


                                    </div>
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-5 text-center text-muted">
                                    Belum ada pembayaran.
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
                    @forelse($pembayarans as $p)
                    @php
                    $status = $p->status_pembayaran ?? '-';
                    $badge = match($status){
                    'menunggu_verifikasi' => 'warning',
                    'paid' => 'success',
                    'ditolak' => 'danger',
                    'expired' => 'secondary',
                    'dibatalkan' => 'danger',
                    default => 'secondary'
                    };

                    $isMidtrans = ($p->metode_pembayaran === 'midtrans');
                    $canAction = ($status === 'menunggu_verifikasi') && !$isMidtrans;

                    $trx = $p->transaksi;
                    @endphp

                    <div class="pay-card mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-bold">{{ $trx->kode_transaksi ?? '-' }}</div>
                                <div class="small muted">
                                    {{ optional($trx->user)->name ?? '-' }} • {{ strtoupper($p->metode_pembayaran ?? '-') }}
                                </div>
                            </div>

                            <span class="badge bg-{{ $badge }} {{ $badge=='warning' ? 'text-dark' : '' }} badge-pill">
                                {{ strtoupper(str_replace('_',' ', $status)) }}
                            </span>
                        </div>

                        <div class="kv">
                            <div class="k">Tanggal</div>
                            <div class="v">{{ optional($p->tanggal_pembayaran)->format('d M Y') ?? '-' }}</div>
                        </div>

                        <div class="kv">
                            <div class="k">Total</div>
                            <div class="v">Rp {{ number_format($p->total_pembayaran ?? 0,0,',','.') }}</div>
                        </div>

                        <div class="kv">
                            <div class="k">Bukti</div>
                            <div class="v">
                                @if(!$isMidtrans && $p->bukti_transfer)
                                <span class="badge bg-success-subtle text-success border">ADA</span>
                                @else
                                <span class="badge bg-light text-dark border">-</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3 flex-wrap">

                            <a href="{{ route('admin.pembayaran.show', $p->id) }}" class="btn btn-primary btn-sm">
                                Detail
                            </a>

                            @if(!$isMidtrans && $p->bukti_transfer)
                            <a href="{{ asset('storage/'.$p->bukti_transfer) }}" target="_blank" class="btn btn-outline-dark btn-sm">
                                Bukti
                            </a>
                            @endif

                            @if($canAction)
                            <form method="POST" action="{{ route('admin.pembayaran.konfirmasi', $p->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Verifikasi pembayaran ini?')">
                                    Konfirmasi
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.pembayaran.tolak', $p->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tolak pembayaran ini?')">
                                    Tolak
                                </button>
                            </form>
                            @endif

                        </div>

                    </div>
                    @empty
                    <div class="py-4 text-center text-muted">
                        Belum ada pembayaran.
                    </div>
                    @endforelse
                </div>

                <div class="p-3 border-top">
                    {{ $pembayarans->withQueryString()->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
