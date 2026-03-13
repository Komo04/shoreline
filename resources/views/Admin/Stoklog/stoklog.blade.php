@extends('layouts.Admin.mainlayout')
@section('title', 'Histori Stok')
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
@section('content')
<div class="container-fluid px-4">

    <!-- ===== HEADER ===== -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-clock-history fs-5 text-dark"></i>
            <h4 class="fw-semibold mb-0">Histori Perubahan Stok</h4>
        </div>
        <small class="text-muted ms-4">
            Riwayat stok masuk dan keluar produk
        </small>
    </div>

    <!-- ===== SUMMARY ===== -->
    @php
    $totalIn = $logs->where('tipe','IN')->sum('jumlah');
    $totalOut = $logs->where('tipe','OUT')->sum('jumlah');
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-success-subtle">
                <div class="card-body">
                    <small class="text-muted">Total Stok Masuk</small>
                    <h4 class="fw-bold text-success mb-0">+{{ $totalIn }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-danger-subtle">
                <div class="card-body">
                    <small class="text-muted">Total Stok Keluar</small>
                    <h4 class="fw-bold text-danger mb-0">-{{ $totalOut }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILTER & SEARCH ===== -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label class="form-label small text-muted">Search Produk / Varian</label>
                    <input type="text" name="search" class="form-control" placeholder="Cari produk atau warna..." value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Filter Tipe</label>
                    <select name="tipe" class="form-select">
                        <option value="">Semua</option>
                        <option value="IN" {{ request('tipe')=='IN'?'selected':'' }}>Stok Masuk</option>
                        <option value="OUT" {{ request('tipe')=='OUT'?'selected':'' }}>Stok Keluar</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button class="btn btn-dark w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- ===== TABLE ===== -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th>Tipe</th>
                            <th>Perubahan</th>
                            <th>Stok</th>
                            <th>Keterangan</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">

                        @forelse($logs as $log)
                        @php $tipe = strtoupper($log->tipe); @endphp

                        <tr>
                            <td class="fw-semibold">{{ $loop->iteration }}</td>

                            <td class="fw-semibold">
                                {{ optional(optional($log->varian)->produk)->nama_produk ?? '-' }}
                            </td>

                            <td>
                                <span class="badge bg-secondary-subtle text-dark">
                                    {{ $log->varian->warna ?? '-' }}
                                    /
                                    {{ $log->varian->ukuran ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-{{ $tipe=='IN'?'success':'danger' }}">
                                    {{ $tipe }}
                                </span>
                            </td>

                            <td class="fw-semibold text-{{ $tipe=='IN'?'success':'danger' }}">
                                {{ $tipe=='IN' ? '+' : '-' }}{{ $log->jumlah }}
                            </td>

                            <td>
                                <small class="text-muted">
                                    {{ $log->stok_sebelum }}
                                </small>
                                →
                                <span class="fw-semibold">
                                    {{ $log->stok_sesudah }}
                                </span>
                            </td>

                            <td class="text-muted">
                                {{ $log->keterangan ?? '-' }}
                            </td>

                            <td>
                                <small>
                                    {{ optional($log->created_at)->format('d M Y') }}
                                    <br>
                                    <span class="text-muted">
                                        {{ optional($log->created_at)->format('H:i') }}
                                    </span>
                                </small>
                            </td>
                        </tr>

                        @empty
                        <tr>
                            <td colspan="8" class="py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                Belum ada histori stok
                            </td>
                        </tr>
                        @endforelse

                    </tbody>

                </table>
                <div class="p-3">
                    {{ $logs->withQueryString()->links('pagination::bootstrap-5') }}
                </div>

            </div>

        </div>
    </div>

</div>
@endsection
