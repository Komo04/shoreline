@extends('layouts.Admin.mainlayout')

@section('title', 'Laporan Pendapatan')

@push('styles')
<style>
    /* ===== Pagination Styling ===== */
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
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Laporan Pendapatan</h4>

        <a class="btn btn-primary"
           href="{{ route('admin.laporan.pendapatan.cetak', ['start' => $start->toDateString(), 'end' => $end->toDateString()]) }}"
           target="_blank">
            <i class="bi bi-printer-fill me-1"></i> Cetak
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form class="row g-3" method="GET" action="{{ route('admin.laporan.pendapatan') }}">
                <div class="col-md-4">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="start" class="form-control" value="{{ $start->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end" class="form-control" value="{{ $end->toDateString() }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button class="btn btn-success">Tampilkan</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.laporan.pendapatan') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                <div class="text-muted">
                    Periode:
                    <strong>{{ $start->format('d M Y') }}</strong> s/d
                    <strong>{{ $end->format('d M Y') }}</strong>
                </div>

                <div class="fs-5">
                    Total Pendapatan:
                    <strong>Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">No</th>
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
                                <td>{{ $rows->firstItem() + $i }}</td>
                                <td>{{ optional($r->paid_at)->format('d M Y H:i') }}</td>
                                <td>{{ $r->kode_transaksi }}</td>
                                <td>{{ $r->user?->name ?? '-' }}</td>
                                <td>{{ $r->metode_pembayaran }}</td>
                                <td class="text-end">Rp {{ number_format($r->total_pembayaran, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success">
                                        {{ $r->status_transaksi }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Tidak ada data pendapatan pada periode ini.
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

        {{-- PAGINATION --}}
        <div class="p-3 border-top">
            {{ $rows->links('pagination::bootstrap-5') }}
        </div>

    </div>

</div>
@endsection
