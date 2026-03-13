@extends('layouts.Admin.mainlayout')
@section('title', 'Detail Produk')

@push('styles')
<style>
    .product-detail-card {
        border: 0;
        border-radius: 24px;
        box-shadow: 0 18px 40px rgba(17, 24, 39, 0.08);
        overflow: hidden;
    }

    .product-cover {
        width: 100%;
        max-width: 360px;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border-radius: 22px;
        background: #f3f4f6;
        box-shadow: 0 18px 36px rgba(17, 24, 39, 0.08);
    }

    .metric-chip {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .7rem 1rem;
        border-radius: 999px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        color: #374151;
        font-weight: 600;
        font-size: 13px;
    }

    .section-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #6b7280;
        font-weight: 700;
        margin-bottom: .6rem;
    }

    .info-panel {
        border: 1px solid #eceff3;
        background: #fff;
        border-radius: 18px;
        padding: 1.1rem 1.15rem;
        height: 100%;
    }

    .variant-table th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #6b7280;
        white-space: nowrap;
    }

    .variant-table td,
    .variant-table th {
        vertical-align: middle;
        padding: .9rem 1rem;
    }

    .variant-thumb {
        width: 58px;
        height: 58px;
        object-fit: cover;
        border-radius: 14px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
    }

    .empty-thumb {
        width: 58px;
        height: 58px;
        border-radius: 14px;
        background: #f3f4f6;
        color: #9ca3af;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-semibold mb-1">Detail Produk</h4>
            <div class="text-muted">Lihat informasi lengkap produk dan semua variannya.</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.varian.index', $produk->id) }}" class="btn btn-outline-dark">
                <i class="bi bi-layers me-1"></i>Kelola Varian
            </a>
            <a href="{{ route('admin.produk.edit', $produk->id) }}" class="btn btn-dark">
                <i class="bi bi-pencil-square me-1"></i>Edit Produk
            </a>
            <a href="{{ route('admin.produk.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    <div class="card product-detail-card mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="row g-4 align-items-start">
                <div class="col-lg-4">
                    @if($produk->gambar_produk)
                        <img
                            src="{{ asset('storage/' . $produk->gambar_produk) }}"
                            alt="{{ $produk->nama_produk }}"
                            class="product-cover"
                        >
                    @else
                        <div class="product-cover d-flex align-items-center justify-content-center">
                            <i class="bi bi-image fs-1 text-muted"></i>
                        </div>
                    @endif
                </div>

                <div class="col-lg-8">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="metric-chip">
                            <i class="bi bi-grid-3x3-gap-fill"></i>{{ $totalVarian }} varian
                        </span>
                        <span class="metric-chip">
                            <i class="bi bi-box-seam"></i>{{ $totalStok }} stok total
                        </span>
                        <span class="metric-chip">
                            <i class="bi bi-tag-fill"></i>{{ optional($produk->kategori)->nama_kategori ?? '-' }}
                        </span>
                    </div>

                    <h2 class="fw-semibold mb-2">{{ $produk->nama_produk }}</h2>
                    <div class="fs-5 fw-semibold text-success mb-4">Rp {{ $produk->harga_format }}</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-panel">
                                <div class="section-label">Deskripsi</div>
                                <div class="text-muted lh-lg">{{ $produk->deskripsi_produk }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-panel">
                                <div class="section-label">Keterangan</div>
                                <div class="text-muted lh-lg">{{ $produk->keterangan }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card product-detail-card">
        <div class="card-body p-0">
            <div class="px-4 py-3 border-bottom">
                <h5 class="fw-semibold mb-1">Daftar Varian</h5>
                <div class="text-muted small">Warna, ukuran, stok, dan berat per varian.</div>
            </div>

            <div class="table-responsive">
                <table class="table variant-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Gambar</th>
                            <th>Warna</th>
                            <th>Ukuran</th>
                            <th>Stok</th>
                            <th>Berat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($produk->varians as $varian)
                            <tr>
                                <td>
                                    @if($varian->gambar_varian)
                                        <img
                                            src="{{ asset('storage/' . $varian->gambar_varian) }}"
                                            alt="{{ $produk->nama_produk }}"
                                            class="variant-thumb"
                                        >
                                    @else
                                        <span class="empty-thumb">
                                            <i class="bi bi-image"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="fw-medium">{{ $varian->warna ?: '-' }}</td>
                                <td>{{ $varian->ukuran ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $varian->stok > 5 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-dark' }}">
                                        {{ $varian->stok }}
                                    </span>
                                </td>
                                <td>{{ $varian->berat_gram ? number_format($varian->berat_gram, 0, ',', '.') . ' gr' : '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.varian.edit', $varian->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1"></i>Edit Varian
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Varian produk belum tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
