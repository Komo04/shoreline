@extends('layouts.mainlayout')
@section('title', 'Home Page')

@section('content')

<style>
    body {
        background: #fff;
    }

    /* ================= HERO FULL SCREEN (TIDAK DIUBAH) ================= */
    .hero-full {
        position: relative;
        width: 100%;
        height: 100vh;
        overflow: hidden;
    }

    .hero-full::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image:url("{{ asset('assets/images/corousel/hero.png') }}");
        background-size: cover;
        background-repeat: no-repeat;
        background-position: 97% 60%;
        z-index: 0;
    }

    .hero-full::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg,
                rgba(255, 255, 255, 1) 0%,
                rgba(255, 255, 255, 0.95) 30%,
                rgba(255, 255, 255, 0.75) 55%,
                rgba(255, 255, 255, 0.35) 65%,
                rgba(255, 255, 255, 0) 100%);
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        height: 100%;
        display: flex;
        align-items: center;
        padding-left: 8%;
        max-width: 720px;
    }

    .hero-inner {
        max-width: 620px;
    }

    .hero-title {
        font-size: clamp(2.6rem, 4vw, 4.5rem);
        font-weight: 900;
        line-height: 1.05;
        letter-spacing: -1px;
        margin-bottom: 20px;
        color: #111;
    }

    .hero-desc {
        color: #666;
        line-height: 1.7;
        margin-bottom: 30px;
    }

    .hero-buttons .btn {
        padding: 14px 32px;
        border-radius: 999px;
        font-weight: 700;
    }

    /* ================= FADE ANIMATION (HERO LOAD) ================= */
    @keyframes fadeInBg {
        from {
            opacity: 0;
            transform: scale(1.01);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-full::before,
    .hero-full::after {
        opacity: 0;
    }

    .fade-item {
        opacity: 0;
    }

    .loaded .hero-full::before,
    .loaded .hero-full::after {
        animation: fadeInBg .9s ease-out both;
    }

    .loaded .fade-item {
        animation: fadeUp .85s ease-out both;
    }

    .loaded .fade-item:nth-child(1) {
        animation-delay: .10s;
    }

    .loaded .fade-item:nth-child(2) {
        animation-delay: .22s;
    }

    .loaded .fade-item:nth-child(3) {
        animation-delay: .34s;
    }

    .loaded .fade-item:nth-child(4) {
        animation-delay: .46s;
    }

    :root {
        --text: #111827;
        --muted: #6b7280;
    }

    .content-wrap {
        padding-left: 8%;
        padding-right: 8%;
        padding-top: 72px;
        padding-bottom: 72px;
        background: #fff;
    }

    .section {
        padding: 72px 0;
    }

    .section:first-child {
        padding-top: 0;
    }

    .section-head {
        text-align: center;
        margin-bottom: 40px;
    }

    .section-title {
        font-weight: 900;
        letter-spacing: .08em;
        font-size: clamp(1.8rem, 2.5vw, 2.4rem);
        color: #111;
        margin: 0;
    }

    .btn-pill {
        border-radius: 999px;
        padding: 10px 18px;
        font-weight: 800;
    }

    .section-actions {
        margin-top: 26px;
        display: flex;
        justify-content: center;
    }

    /* ================= PRODUCT CARD ================= */
    .product-item {
        text-decoration: none;
        color: inherit;
        display: block;
        text-align: center;
    }

    .product-item,
    .product-item:hover,
    .product-item:focus,
    .product-item:active,
    .product-item:visited {
        color: var(--text) !important;
        text-decoration: none !important;
    }

    .product-item * {
        color: inherit !important;
    }

    .product-item:focus-visible {
        outline: 2px solid rgba(0, 0, 0, .25);
        outline-offset: 3px;
    }

    .product-thumb {
        width: 100%;
        aspect-ratio: 4/5;
        background: #f3f4f6;
        overflow: hidden;
        position: relative;
        border-radius: 0;
    }

    @supports not (aspect-ratio:4/5) {
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
        margin: 8px 0 0;
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
        color: var(--text);
    }

    /* ⭐ Rating Gold Premium */
    .rating-stars {
        font-size: 14px;
        letter-spacing: 3px;
        background: linear-gradient(180deg, #f59e0b 0%, #f59e0b 50%, #f59e0b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .rating-text {
        font-size: 13px;
        color: #444;
        margin-left: 6px;
    }

    /* ================= STYLE BANNERS (BY KATEGORI) ================= */
    .style-card {
        position: relative;
        border-radius: 0;
        overflow: hidden;
        border: 1px solid #eee;
        background: #f3f3f3;
        display: block;

        aspect-ratio: 2/1;
        /* lock 2:1 */
    }

    @supports not (aspect-ratio:2/1) {
        .style-card {
            height: 0;
            padding-top: 50%;
        }
    }

    /* ✅ IMPORTANT: pakai COVER supaya gak jadi kecil di tengah */
    .style-card img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        /* isi frame 2:1 */
        object-position: center 25%;
        /* agak naik biar wajah gak kepotong */
        transform: none;
        display: block;
    }

    .style-card::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0) 35%, rgba(0, 0, 0, .55) 100%);
    }

    .style-label {
        position: absolute;
        left: 18px;
        bottom: 16px;
        z-index: 2;
        color: #fff;
        font-weight: 900;
        letter-spacing: .06em;
        margin: 0;
        text-transform: uppercase;
    }

    /* ================= HOVER ANIMATION - BROWSE BY DRESS STYLE ================= */

    /* efek judul saat cursor masuk ke section */
    /* ================= FIX TITLE HOVER (NO SIZE CHANGE) ================= */

    #browse-style .section-title {
        transition: color .3s ease;
    }

    #browse-style:hover .section-title {
        color: #000;
    }

    /* animasi card */
    .style-card {
        transition: transform .4s ease, box-shadow .4s ease;
    }

    .style-card img {
        transition: transform .6s cubic-bezier(.2, .8, .2, 1),
            filter .4s ease;
    }

    /* zoom image */
    .style-card:hover img {
        transform: scale(1.08);
        filter: brightness(1.05);
    }

    /* overlay lebih gelap saat hover */
    .style-card::after {
        transition: background .4s ease;
    }

    .style-card:hover::after {
        background: linear-gradient(180deg,
                rgba(0, 0, 0, 0) 30%,
                rgba(0, 0, 0, .75) 100%);
    }

    /* label naik + smooth */
    .style-label {
        transition: transform .4s ease, letter-spacing .4s ease;
    }

    .style-card:hover .style-label {
        transform: translateY(-6px);
        letter-spacing: .12em;
    }

    /* shadow premium */
    .style-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, .15);
    }

    /* ================= TESTIMONIAL ================= */
    .t-card {
        border: 1px solid #eee;
        border-radius: 0;
        background: #fff;
        padding: 22px;
        height: 100%;
        transition: box-shadow .18s ease, transform .18s ease;
    }

    .t-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px rgba(0, 0, 0, .06);
    }

    .t-stars {
        color: #f59e0b;
    }

    .t-text {
        color: #666;
        line-height: 1.7;
        margin: 10px 0 16px;
    }

    .t-name {
        font-weight: 900;
        color: #111;
    }

    @media(max-width:992px) {
        .hero-content {
            padding: 0 24px;
            align-items: flex-start;
            padding-top: 120px;
            max-width: 100%;
        }

        .hero-title {
            font-size: 2.4rem;
        }

        .hero-full::before {
            background-position: center 18%;
        }

        .hero-full::after {
            background: linear-gradient(180deg,
                    rgba(255, 255, 255, 0.98) 0%,
                    rgba(255, 255, 255, 0.92) 50%,
                    rgba(255, 255, 255, 0.2) 80%,
                    rgba(255, 255, 255, 0) 100%);
        }

        .content-wrap {
            padding-left: 18px;
            padding-right: 18px;
            padding-top: 54px;
            padding-bottom: 54px;
        }

        .section {
            padding: 54px 0;
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .hero-full::before,
        .hero-full::after,
        .fade-item,
        #browse-style .section-title,
        .style-card,
        .style-card img,
        .style-card::after,
        .style-label {
            animation: none !important;
            transition: none !important;
        }
    }

</style>

{{-- ================= HERO ================= --}}
<section class="hero-full">
    <div class="hero-content">
        <div class="hero-inner">
            <h1 class="hero-title fade-item">
                Pilihan Fashion<br>
                Terbaik Untuk<br>
                Setiap Momen
            </h1>

            <p class="hero-desc fade-item">
                Koleksi dress elegan dengan sentuhan modern untuk menunjang
                gaya dan kepercayaan dirimu.
            </p>

            <div class="hero-buttons d-flex gap-3 flex-wrap fade-item">
                <a href="{{ route('produk') }}" class="btn btn-dark">Shop Now</a>
            </div>
        </div>
    </div>
</section>

<div class="content-wrap">

    {{-- ================= NEW ARRIVALS (DB) ================= --}}
    <section class="section">
        <div class="section-head">
            <h1 class="section-title">PRODUK TERBARU</h1>
        </div>

        <div class="row g-4">
            @forelse($newArrivals as $produk)
            @php
            $path = (string) ($produk->gambar_produk ?? '');
            $path = ltrim(str_replace(['public/','storage/'],'',$path), '/');

            $imgUrl = $path
            ? asset('storage/'.$path)
            : 'https://via.placeholder.com/900x1200?text=No+Image';

            $avg = round($produk->ulasans_avg_rating ?? 0, 1);
            $count = (int) ($produk->ulasans_count ?? 0);
            $full = floor($avg);
            $half = ($avg - $full) >= 0.5 ? 1 : 0;
            $empty = 5 - $full - $half;
            @endphp

            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('produk.show', $produk->id) }}" class="product-item">
                    <div class="product-thumb">
                        <img src="{{ $imgUrl }}" alt="{{ $produk->nama_produk }}" loading="lazy">
                    </div>

                    <div class="p-title">{{ $produk->nama_produk }}</div>
                    <div class="p-price">Rp {{ number_format($produk->harga, 0, ',', '.') }}</div>

                    <div class="mt-1">
                        @if($count > 0)
                        <span class="rating-stars">
                            @for($i=0;$i<$full;$i++) ★ @endfor @if($half) ☆ @endif @for($i=0;$i<$empty;$i++) ☆ @endfor </span>
                                <span class="rating-text">{{ $avg }}/5 ({{ $count }})</span>
                                @else
                                <span class="rating-text">Belum ada rating</span>
                                @endif
                    </div>
                </a>
            </div>
            @empty
            <div class="col-12 text-center">
                <div class="text-muted">Produk belum tersedia</div>
            </div>
            @endforelse
        </div>

        <div class="section-actions">
            <a href="{{ route('produk') }}" class="btn btn-outline-dark btn-pill">View All</a>
        </div>
    </section>

    {{-- ================= BROWSE BY DRESS STYLE (BY KATEGORI) ================= --}}
    <section class="section" id="browse-style">
        <div class="section-head">
            <h1 class="section-title">JELAJAHI GAYA DRESS</h1>
        </div>

        <div class="row g-4">
            @forelse($kategoris as $kategori)
            @php
            // pakai gambar dari tabel kategoris (field: gambar)
            $kPath = (string) ($kategori->gambar ?? '');
            $kPath = ltrim(str_replace(['public/','storage/'],'',$kPath), '/');

            $imgUrl = $kPath
            ? asset('storage/'.$kPath)
            : 'https://via.placeholder.com/1600x800?text='.urlencode($kategori->nama_kategori);
            @endphp

            <div class="col-lg-6">
                <a href="{{ route('produk', ['kategori[]' => $kategori->id]) }}" class="style-card">
                    <img src="{{ $imgUrl }}" alt="{{ $kategori->nama_kategori }}" loading="lazy">
                    <h4 class="style-label">{{ $kategori->nama_kategori }}</h4>
                </a>
            </div>
            @empty
            <div class="col-12 text-center text-muted">
                Kategori belum tersedia
            </div>
            @endforelse
        </div>
    </section>

    {{-- ================= TESTIMONIAL ================= --}}
    <section class="section" style="padding-bottom: 30px;">
        <div class="section-head">
            <h1 class="section-title">KOMENTAR PELANGGAN</h1>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="t-card">
                    <div class="t-stars">★★★★★</div>
                    <p class="t-text">Dress-nya jatuh banget di badan dan bahannya adem. Packing rapi, pengiriman cepat.</p>
                    <div class="t-name">Sarah M.</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="t-card">
                    <div class="t-stars">★★★★★</div>
                    <p class="t-text">Modelnya premium, potongannya bagus. Cocok dipakai ke acara formal juga.</p>
                    <div class="t-name">Alex K.</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="t-card">
                    <div class="t-stars">★★★★★</div>
                    <p class="t-text">Ukuran sesuai, warna persis foto. Bakal repeat order karena nyaman banget.</p>
                    <div class="t-name">James L.</div>
                </div>
            </div>
        </div>
    </section>

</div>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        document.documentElement.classList.add('loaded');
    });

</script>

@endsection
