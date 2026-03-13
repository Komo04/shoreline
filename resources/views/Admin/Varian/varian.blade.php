@extends('layouts.Admin.mainlayout')
@section('title', 'Varian Produk')

@push('styles')
<style>
    .variant-img-wrapper {
        width: 70px;
        height: 70px;
        margin: auto;
        border-radius: 12px;
        overflow: hidden;
        background: #f8f9fa;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
    }

    .variant-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .3s ease;
    }

    .variant-img-wrapper:hover .variant-img {
        transform: scale(1.08);
    }

    .custom-input {
        height: 45px;
        border-radius: 12px;
        border: 1px solid #e5e5e5;
        font-size: 14px;
        min-width: 200px;
        transition: .3s;
    }

    .custom-input:focus {
        border-color: #000;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
    }

    .search-icon {
        position: absolute;
        top: 50%;
        left: 15px;
        transform: translateY(-50%);
        color: #aaa;
    }

    .custom-input[type="text"] {
        padding-left: 38px;
    }

    .btn-add-variant {
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        font-weight: 500;
        white-space: nowrap;

        flex-shrink: 0;
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
<div class="container-fluid px-4">

    <!-- ===== HEADER ===== -->
    <!-- ===== HEADER + FILTER ===== -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">

            <div class="row align-items-center gy-3">

                <!-- LEFT -->
                <div class="col-lg-4">
                    <h5 class="fw-semibold mb-1">
                        <i class="bi bi-layers-fill me-2"></i>
                        Varian Produk
                    </h5>
                    <small class="text-muted">
                        {{ $produk->nama_produk }}
                    </small>
                </div>

                <!-- RIGHT -->
                <div class="col-lg-8">

                    <div class="d-flex flex-column flex-lg-row gap-2 justify-content-lg-end">

                        <!-- SEARCH + FILTER -->
                        <form method="GET" action="{{ route('admin.varian.index', $produk->id) }}" class="d-flex flex-wrap gap-2">

                            <!-- SEARCH -->
                            <div class="position-relative">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control custom-input" placeholder="Cari warna / ukuran...">
                            </div>

                            <!-- FILTER STOK -->
                            <select name="stok" class="form-select custom-input">
                                <option value="">Semua Stok</option>
                                <option value="available" {{ request('stok') == 'available' ? 'selected' : '' }}>
                                    Tersedia
                                </option>
                                <option value="empty" {{ request('stok') == 'empty' ? 'selected' : '' }}>
                                    Habis
                                </option>
                            </select>

                            <button type="submit" class="btn btn-dark px-4">
                                Filter
                            </button>

                            @if(request('search') || request('stok'))
                            <a href="{{ route('admin.varian.index', $produk->id) }}" class="btn btn-outline-secondary">
                                Reset
                            </a>
                            @endif
                        </form>

                        <!-- TAMBAH VARIAN BUTTON -->
                        <a href="{{ route('admin.varian.create', $produk->id) }}" class="btn btn-dark btn-add-variant">
                            <i class="bi bi-plus-circle me-1"></i>
                            Tambah Varian
                        </a>

                    </div>

                </div>

            </div>

        </div>
    </div>


    <!-- ===== TABLE CARD ===== -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">

                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Warna</th>
                            <th>Ukuran</th>
                            <th>Stok</th>
                            <th width="12%">Gambar</th>
                            <th width="18%">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($varians as $varian)
                        <tr>
                            <td class="fw-semibold">{{ $loop->iteration }}</td>

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
                                @if($varian->stok > 0)
                                <span class="badge bg-success-subtle text-success px-3 py-2">
                                    {{ $varian->stok }} pcs
                                </span>
                                @else
                                <span class="badge bg-danger-subtle text-danger px-3 py-2">
                                    Habis
                                </span>
                                @endif
                            </td>

                            <td>
                                <div class="variant-img-wrapper">
                                    <img src="{{ asset('storage/'.$varian->gambar_varian) }}" class="variant-img" alt="{{ $varian->warna }}">
                                </div>
                            </td>

                            <td>
                                <div class="d-flex justify-content-center gap-1 flex-wrap">

                                    <a href="{{ route('admin.varian.edit',$varian->id) }}" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form id="delVarian-{{ $varian->id }}" method="POST" action="{{ route('admin.varian.destroy', $varian->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirmDeleteForm('delVarian-{{ $varian->id }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                        @empty
                        <tr>
                            <td colspan="6" class="py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                Belum ada varian untuk produk ini
                            </td>
                        </tr>
                        @endforelse

                    </tbody>

                </table>
                <div class="p-3">
                    {{ $varians->withQueryString()->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>

    </div>
    @endsection
