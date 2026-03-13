@extends('layouts.mainlayout')
@section('title', 'Produk')

@push('styles')
<style>
    :root {
        --text: #111827;
        --muted: #6b7280;
        --radius: 18px;
    }

    .muted {
        color: var(--muted);
    }

    /* ================= FILTER ================= */
    .filter-card {
        border: 0;
        border-radius: var(--radius);
        box-shadow: 0 10px 28px rgba(0, 0, 0, .06);
        background: #fff;
        overflow: hidden;
    }

    .chip-check {
        position: relative;
        display: inline-flex;
    }

    .chip-check input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .8rem;
        border-radius: 999px;
        border: 1px solid rgba(0, 0, 0, .12);
        background: #fff;
        font-size: .85rem;
        cursor: pointer;
        user-select: none;
        transition: all .15s ease;
        color: var(--text);
    }

    .chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(0, 0, 0, .06);
    }

    .chip-check input:checked+.chip {
        border-color: rgba(13, 110, 253, .55);
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .15);
    }

    .dot {
        width: 14px;
        height: 14px;
        border-radius: 999px;
        border: 1px solid rgba(0, 0, 0, .15);
        background: #e5e7eb;
    }

    .section-title {
        font-weight: 900;
        letter-spacing: .2px;
        color: var(--text);
    }

    .accordion-button {
        font-weight: 800;
        padding-left: 0;
        padding-right: 0;
    }

    .accordion-button:not(.collapsed) {
        background: #f8fafc;
        box-shadow: none;
    }

    /* ================= PRODUCT ITEM ================= */

    .product-item {
        display: block;
        text-align: center;
        text-decoration: none;
        color: inherit;
        -webkit-tap-highlight-color: transparent;
    }

    .product-item,
    .product-item:hover,
    .product-item:focus,
    .product-item:active,
    .product-item:visited {
        text-decoration: none;
        color: inherit;
        outline: none;
    }

    .product-item:focus-visible {
        outline: none;
        box-shadow: none;
    }

    .product-item *::selection {
        background: transparent;
    }

    .product-thumb {
        width: 100%;
        aspect-ratio: 4 / 5;
        background: #f3f4f6;
        overflow: hidden;
        position: relative;
    }

    @supports not (aspect-ratio: 4 / 5) {
        .product-thumb {
            height: 0;
            padding-top: 125%;
        }
    }

    .product-thumb img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .25s ease;
    }

    .product-item:hover .product-thumb img {
        transform: scale(1.05);
    }

    .p-title {
        font-weight: 700;
        font-size: 15px;
        margin: 0;
        margin-top: 6px;
        line-height: 1.25;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .p-price {
        font-size: 14px;
        font-weight: 700;
        margin-top: 4px;
        color: #111827;
    }

    .rating-stars {
        color: #f59e0b;
        font-size: 13px;
        letter-spacing: 2px;
    }

    .rating-text {
        font-size: 12px;
        color: #6b7280;
        margin-left: 6px;
    }

    /* ===== Pagination Styling (DITAMBAHKAN) ===== */
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
<div class="container py-5">
    <div class="row g-4">

        {{-- ================= FILTER SIDEBAR ================= --}}
        <div class="col-lg-3">
            <form method="GET" action="{{ route('produk') }}" class="filter-card">
                <div class="p-4 pb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="fw-bold fs-5">Filters</div>
                        <a href="{{ route('produk') }}" class="small text-decoration-none">Reset</a>
                    </div>

                    {{-- Search --}}
                    <div class="mt-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white">🔎</span>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari produk...">
                        </div>
                    </div>
                </div>

                <div class="px-4 pb-4">
                    <div class="accordion" id="filterAcc">

                        {{-- Categories --}}
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="hCat">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cCat">
                                    Kategori
                                </button>
                            </h2>
                            <div id="cCat" class="accordion-collapse collapse" data-bs-parent="#filterAcc">
                                <div class="accordion-body px-0 pt-2">
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($kategoris as $kat)
                                        <label class="chip-check">
                                            <input type="checkbox" name="kategori[]" value="{{ $kat->id }}" @checked(in_array($kat->id, (array) request('kategori', [])))>
                                            <span class="chip">{{ $kat->nama_kategori }}</span>
                                        </label>
                                        @empty
                                        <div class="small muted">Belum ada kategori.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Price --}}
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="hPrice">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cPrice">
                                    Harga
                                </button>
                            </h2>
                            <div id="cPrice" class="accordion-collapse collapse" data-bs-parent="#filterAcc">
                                <div class="accordion-body px-0 pt-2">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="number" class="form-control" name="harga_min" value="{{ request('harga_min') }}" placeholder="Min" min="0">
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control" name="harga_max" value="{{ request('harga_max') }}" placeholder="Max" min="0">
                                        </div>
                                    </div>

                                    <div class="small muted mt-2">
                                        Range:
                                        Rp {{ number_format($minPriceAll ?? 0, 0, ',', '.') }}
                                        –
                                        Rp {{ number_format($maxPriceAll ?? 0, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Colors --}}
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="hColor">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cColor">
                                    Warna
                                </button>
                            </h2>
                            <div id="cColor" class="accordion-collapse collapse" data-bs-parent="#filterAcc">
                                <div class="accordion-body px-0 pt-2">
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($colors as $c)
                                        @php
                                            $map = [
                                                'Merah' => '#ef4444', 'Red' => '#ef4444',
                                                'Biru' => '#3b82f6', 'Blue' => '#3b82f6',
                                                'Biru Muda' => '#00ffff','Aqua' => '#00ffff',
                                                'Biru Tua' => '#000080','Navy' => '#000080',
                                                'Hitam' => '#111827', 'Black' => '#111827',
                                                'Putih' => '#ffffff', 'White' => '#ffffff',
                                                'Hijau' => '#22c55e', 'Green' => '#22c55e',
                                                'Kuning' => '#f59e0b','Yellow' => '#f59e0b',
                                                'Orange' => '#f97316','Oranye' => '#f97316',
                                                'Pink' => '#ec4899',
                                                'Ungu' => '#8b5cf6','Purple' => '#8b5cf6',
                                                'Coklat' => '#92400e','Brown' => '#92400e',
                                                'Abu' => '#9ca3af','Grey' => '#9ca3af','Gray' => '#9ca3af',
                                            ];
                                            $hex = $map[$c] ?? '#e5e7eb';
                                        @endphp
                                        <label class="chip-check">
                                            <input type="checkbox" name="warna[]" value="{{ $c }}" @checked(in_array($c, (array) request('warna', [])))>
                                            <span class="chip">
                                                <span class="dot" style="background:{{ $hex }};"></span>
                                                {{ $c }}
                                            </span>
                                        </label>
                                        @empty
                                        <div class="small muted">Belum ada warna.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <button class="btn btn-dark w-100 rounded-pill mt-4">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- ================= PRODUCT LIST ================= --}}
        <div class="col-lg-9">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="section-title mb-1">Semua Produk</h4>
                    <div class="small muted">Menampilkan {{ $produks->total() }} produk</div>
                </div>
                <div class="small muted">Halaman {{ $produks->currentPage() }} dari {{ $produks->lastPage() }}</div>
            </div>

            <div class="row g-4">
                @forelse ($produks as $produk)
                    @php
                        $path = (string) ($produk->gambar_produk ?? '');
                        $path = ltrim(str_replace(['public/', 'storage/'], '', $path), '/');

                        $imgUrl = $path !== ''
                            ? asset('storage/'.$path)
                            : 'https://via.placeholder.com/800x800?text=No+Image';

                        $avg = round($produk->ulasans_avg_rating ?? 0, 1);
                        $count = (int)($produk->ulasans_count ?? 0);
                        $full = floor($avg);
                        $half = ($avg - $full) >= 0.5 ? 1 : 0;
                        $empty = 5 - $full - $half;
                    @endphp

                    <div class="col-6 col-md-4 col-lg-4">
                        <a href="{{ route('produk.show', $produk->id) }}" class="product-item">

                            <div class="product-thumb">
                                <img src="{{ $imgUrl }}" alt="{{ $produk->nama_produk }}" loading="lazy">
                            </div>

                            <div class="p-title">{{ $produk->nama_produk }}</div>

                            <div class="p-price">
                                Rp {{ number_format($produk->harga, 0, ',', '.') }}
                            </div>

                            <div class="mt-1">
                                @if($count > 0)
                                    <span class="rating-stars">
                                        @for($i=0; $i<$full; $i++) ★ @endfor
                                        @if($half) ☆ @endif
                                        @for($i=0; $i<$empty; $i++) ☆ @endfor
                                    </span>
                                    <span class="rating-text">{{ $avg }}/5 ({{ $count }})</span>
                                @else
                                    <span class="rating-text">Belum ada rating</span>
                                @endif
                            </div>

                        </a>
                    </div>

                @empty
                    <div class="col-12">
                        <div class="alert alert-warning text-center mb-0">
                            Produk belum tersedia (coba reset filter)
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="d-flex justify-content-center mt-5">
                {{ $produks->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>

    </div>
</div>
@endsection
