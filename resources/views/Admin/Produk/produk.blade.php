@extends('layouts.Admin.mainlayout')
@section('title', 'Produk Management')

@push('styles')
<style>
    .product-img-wrapper {
        width: 80px;
        height: 80px;
        margin: auto;
        border-radius: 12px;
        overflow: hidden;
        background: #f8f9fa;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        transition: all .3s ease;
    }

    .product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .3s ease;
    }

    .product-img-wrapper:hover .product-img {
        transform: scale(1.08);
    }

    .custom-input {
        height: 45px;
        border-radius: 12px;
        border: 1px solid #e5e5e5;
        font-size: 14px;
        transition: 0.3s;
        min-width: 200px;
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

    .btn-add-product {
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        font-weight: 500;
        white-space: nowrap;
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

    .col-desc {
        max-width: 320px;
    }

    .col-ket {
        max-width: 360px;
    }

    .clamp-2,
    .clamp-3 {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .clamp-2 {
        -webkit-line-clamp: 2;
    }

    .clamp-3 {
        -webkit-line-clamp: 3;
    }

    .badge-wrap {
        white-space: normal;
        text-align: left;
        line-height: 1.4;
    }

    td {
        word-break: normal !important;
        overflow-wrap: normal !important;
    }

    .table td {
        vertical-align: middle;
    }

</style>
@endpush

@section('content')

<div class="container-fluid">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center gy-3">

                <div class="col-lg-4">
                    <h5 class="fw-semibold mb-1">
                        <i class="bi bi-bag-fill me-2"></i> Produk Management
                    </h5>
                    <small class="text-muted">Kelola produk fashion anda</small>
                </div>

                <div class="col-lg-8">
                    <div class="d-flex flex-column flex-lg-row gap-2 justify-content-lg-end">

                        <form method="GET" action="{{ route('admin.produk.index') }}" class="d-flex flex-wrap gap-2">
                            <div class="position-relative">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control custom-input" placeholder="Cari produk...">
                            </div>

                            <select name="kategori" class="form-select custom-input">
                                <option value="">Semua Kategori</option>
                                @foreach($kategoris as $kategori)
                                <option value="{{ $kategori->id }}" {{ request('kategori') == $kategori->id ? 'selected' : '' }}>
                                    {{ $kategori->nama_kategori }}
                                </option>
                                @endforeach
                            </select>

                            <button type="submit" class="btn btn-dark px-4">Filter</button>

                            @if(request('search') || request('kategori'))
                            <a href="{{ route('admin.produk.index') }}" class="btn btn-outline-secondary">Reset</a>
                            @endif
                        </form>

                        <a href="{{ route('admin.produk.create') }}" class="btn btn-dark btn-add-product">
                            <i class="bi bi-plus-circle me-1"></i>
                            Tambah Produk
                        </a>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Produk</th>
                            <th class="text-start">Deskripsi</th>
                            <th>Harga</th>
                            <th class="text-start">Keterangan</th>
                            <th>Kategori</th>
                            <th width="12%">Gambar</th>
                            <th width="18%">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">
                        @forelse ($produks as $produk)
                        <tr>
                            <td class="fw-semibold">
                                {{ ($produks->currentPage() - 1) * $produks->perPage() + $loop->iteration }}
                            </td>

                            <td class="fw-semibold">{{ $produk->nama_produk }}</td>

                            <td class="text-start text-muted col-desc">
                                <div class="clamp-3" title="{{ $produk->deskripsi_produk }}">
                                    {{ $produk->deskripsi_produk }}
                                </div>
                            </td>

                            <td>
                                <span class="badge bg-success-subtle text-success px-3 py-2">
                                    Rp {{ $produk->harga_format }}
                                </span>
                            </td>

                            <td class="text-start col-ket">
                                <span class="badge bg-secondary-subtle text-dark badge-wrap clamp-2" title="{{ $produk->keterangan }}">
                                    {{ $produk->keterangan }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-info-subtle text-dark">
                                    {{ optional($produk->kategori)->nama_kategori ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <div class="product-img-wrapper">
                                    <img src="{{ $produk->gambar_produk ? asset('storage/'.$produk->gambar_produk) : '' }}" alt="{{ $produk->nama_produk }}" class="product-img">
                                </div>
                            </td>

                            <td>
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <a href="{{ route('admin.produk.show', $produk->id) }}" class="btn btn-dark btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a href="{{ route('admin.produk.edit', $produk->id) }}" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <a href="{{ route('admin.varian.index', $produk->id) }}" class="btn btn-info btn-sm text-white">
                                        <i class="bi bi-layers"></i>
                                    </a>

                                    <form id="delProduk-{{ $produk->id }}" method="POST" action="{{ route('admin.produk.destroy', $produk->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirmDeleteForm('delProduk-{{ $produk->id }}', {
            title: 'Hapus produk?',
            text: 'Produk akan dihapus permanen.'
          })">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                Data produk belum tersedia
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="p-3">
                    {{ $produks->withQueryString()->links('pagination::bootstrap-5') }}
                </div>

            </div>

        </div>
    </div>

</div>

@endsection
