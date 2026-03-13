@extends('layouts.Admin.mainlayout')
@section('title', 'Stok Hampir Habis')

@push('styles')
<style>
    .low-stock-card {
        border-radius: 18px;
    }

    .table th {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: #6c757d;
    }

    .table td {
        vertical-align: middle;
        font-size: 14px;
    }

    .produk-title {
        font-weight: 600;
        font-size: 15px;
        margin-top: 25px;
    }

    .badge-stock {
        font-size: 13px;
        padding: 6px 14px;
        border-radius: 30px;
    }

    .empty-state {
        padding: 40px 0;
    }

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
<div class="container-fluid px-4">

    <!-- ===== HEADER ===== -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <h4 class="fw-semibold mb-0">
                Stok Hampir Habis
            </h4>
        </div>
        <small class="text-muted ms-4">
            Menampilkan produk dengan stok ≤ {{ $limit }}
        </small>
    </div>


    <!-- ===== CARD ===== -->
    <div class="card border-0 shadow-sm low-stock-card">
        <div class="card-body">

            @forelse ($produks as $produk)

            <div class="produk-title d-flex align-items-center gap-2">
                <i class="bi bi-box-seam text-secondary"></i>
                {{ $produk->nama_produk }}
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="30%">Warna</th>
                            <th width="30%">Ukuran</th>
                            <th width="20%">Sisa Stok</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">
                        @foreach ($produk->varians as $varian)
                        <tr>
                            <td>
                                <span class="badge bg-secondary-subtle text-dark px-3 py-2">
                                    {{ $varian->warna }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-info-subtle text-dark px-3 py-2">
                                    {{ $varian->ukuran }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-danger-subtle text-danger badge-stock">
                                    {{ $varian->stok }} pcs
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <hr>

            @empty

            <div class="text-center text-muted empty-state">
                <i class="bi bi-check-circle fs-1 text-success mb-3 d-block"></i>
                <h6 class="fw-semibold">Semua stok aman</h6>
                <p class="mb-0">Tidak ada produk dengan stok di bawah batas minimum</p>
            </div>

            @endforelse

        </div>
        <div class="p-3">
            {{ $produks->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

@endsection
