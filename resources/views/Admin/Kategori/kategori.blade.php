@extends('layouts.Admin.mainlayout')
@section('title', 'Kategori Management')

@push('styles')
<style>
    .category-img-wrapper {
        width: 80px;
        height: 80px;
        margin: auto;
        border-radius: 12px;
        overflow: hidden;
        background: #f8f9fa;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        transition: all .3s ease;
    }

    .category-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .3s ease;
    }

    .category-img-wrapper:hover .category-img {
        transform: scale(1.08);
    }

    .input-group-text {
        border-radius: 8px 0 0 8px !important;
    }

    .form-control {
        border-radius: 0 8px 8px 0 !important;
    }

    .form-control:focus {
        box-shadow: none;
        border-color: #000;
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

<div class="container-fluid">

    <!-- ===== PAGE HEADER ===== -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">

        <div>
            <h4 class="fw-semibold mb-1">
                <i class="bi bi-tags-fill me-2"></i> Kategori Management
            </h4>
            <small class="text-muted">Kelola kategori produk fashion</small>
        </div>

        <div class="d-flex gap-2">

            {{-- Search --}}
            <form method="GET" action="{{ route('admin.kategori.index') }}" class="d-flex">
                <div class="input-group">
                    <button type="submit" class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </button>

                    <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Cari kategori...">
                </div>
            </form>


            {{-- Button Tambah --}}
            <a href="{{ route('admin.kategori.create') }}" class="btn btn-dark">
                <i class="bi bi-plus-circle me-1"></i> Tambah
            </a>

        </div>

    </div>


    <!-- ===== CARD TABLE ===== -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Kategori</th>
                            <th width="15%">Gambar</th>
                            <th width="18%">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">

                        @forelse ($kategoris as $kategori)
                        <tr>
                            <td class="fw-semibold">{{ $loop->iteration }}</td>

                            <td class="fw-semibold">
                                {{ $kategori->nama_kategori }}
                            </td>

                            <td>
                                <div class="category-img-wrapper">
                                    <img src="{{ asset('storage/'.$kategori->gambar) }}" alt="{{ $kategori->nama_kategori }}" class="category-img">
                                </div>
                            </td>

                            <td>
                                <div class="d-flex justify-content-center gap-1 flex-wrap">

                                    <a href="{{ route('admin.kategori.edit', $kategori->id) }}" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form id="delKategori-{{ $kategori->id }}" method="POST" action="{{ route('admin.kategori.destroy', $kategori->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirmDeleteForm('delKategori-{{ $kategori->id }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                Data kategori belum tersedia
                            </td>
                        </tr>
                        @endforelse

                    </tbody>
                </table>
                <div class="p-3">
                    {{ $kategoris->withQueryString()->links('pagination::bootstrap-5') }}
                </div>

            </div>

        </div>
    </div>

</div>

@endsection
