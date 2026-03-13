@extends('layouts.mainlayout')
@section('title', 'Cart')

@section('content')
<div class="container py-5">

    <style>
        .cart-card {
            border-radius: 18px;
            border: 1px solid rgba(0, 0, 0, .06);
        }

        .cart-row {
            display: grid;
            grid-template-columns: 88px 1fr 220px;
            gap: 18px;
            align-items: start;
        }

        .thumb {
            width: 88px;
            height: 88px;
            border-radius: 14px;
            overflow: hidden;
            background: #f3f4f6;
        }

        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .title {
            font-weight: 800;
            font-size: 1rem;
            margin: 0;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .muted {
            color: #6c757d;
            font-size: .875rem;
        }

        .controls {
            display: grid;
            gap: 10px;
        }

        .selects {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            max-width: 420px;
        }

        .selects .form-select {
            border-radius: 14px;
            padding: .45rem .75rem;
        }

        .right {
            display: grid;
            gap: 12px;
            justify-items: end;
            text-align: right;
        }

        .subtotal {
            font-weight: 900;
            font-size: 1rem;
        }

        .stepper {
            border: 1px solid rgba(0, 0, 0, .12);
            border-radius: 999px;
            padding: 6px 10px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .stepper .btn {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .stepper .num {
            min-width: 24px;
            text-align: center;
            font-weight: 800;
        }

        .remove {
            font-size: .9rem;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .cart-row {
                grid-template-columns: 72px 1fr;
                grid-template-areas:
                    "thumb info"
                    "right right";
            }

            .thumb {
                width: 72px;
                height: 72px;
            }

            .g-thumb {
                grid-area: thumb;
            }

            .g-info {
                grid-area: info;
            }

            .g-right {
                grid-area: right;
                border-top: 1px solid rgba(0, 0, 0, .06);
                padding-top: 12px;
            }

            .right {
                grid-template-columns: 1fr auto;
                justify-items: start;
                text-align: left;
                align-items: center;
            }

            .subtotal {
                justify-self: end;
            }

            .remove {
                justify-self: end;
            }

            .selects {
                grid-template-columns: 1fr;
                max-width: 100%;
            }
        }

    </style>

    {{-- FLASH --}}

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">Shopping Cart</h3>
        <a href="{{ route('produk') }}" class="btn btn-outline-secondary btn-sm">← Lanjut Belanja</a>
    </div>

    <div class="row g-4">
        {{-- LEFT --}}
        <div class="col-lg-8">
            @forelse ($keranjangs as $item)
            @php
            $produk = $item->produk;
            $varian = $item->varian;

            $harga = (int) optional($produk)->harga;
            $qty = (int) $item->jumlah_produk;
            $subtotal = $harga * $qty;

            $img = ($varian && $varian->gambar_varian)
            ? asset('storage/'.$varian->gambar_varian)
            : 'https://via.placeholder.com/300x300?text=No+Image';

            $daftarVarian = $variansByProduk[$item->produk_id] ?? collect();

            // bikin list warna unik & size unik (untuk dropdown terpisah)
            $warnaList = $daftarVarian->pluck('warna')->filter()->unique()->values();
            $ukuranList = $daftarVarian->pluck('ukuran')->filter()->unique()->values();

            $warnaAktif = optional($varian)->warna ?? ($warnaList->first() ?? null);
            $ukuranAktif = optional($varian)->ukuran ?? ($ukuranList->first() ?? null);
            @endphp

            <div class="card cart-card shadow-sm mb-3">
                <div class="card-body p-3 p-md-4">
                    <div class="cart-row">

                        {{-- THUMB --}}
                        <div class="g-thumb">
                            <div class="thumb">
                                <img src="{{ $img }}" alt="Produk">
                            </div>
                        </div>

                        {{-- INFO --}}
                        <div class="g-info">
                            <h6 class="title">
                                {{ $produk->nama_produk ?? $produk->nama ?? 'Produk tidak ditemukan' }}
                            </h6>
                            <div class="muted mt-1">
                                Harga: <span class="fw-semibold text-dark">Rp {{ number_format($harga,0,',','.') }}</span>
                            </div>

                            {{-- VARIAN (2 dropdown) --}}
                            <div class="controls mt-3">
                                <div class="muted">Varian</div>

                                <form action="{{ route('keranjang.updateVarian', $item->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="selects">
                                        <select name="warna" class="form-select form-select-sm" onchange="this.form.submit()">
                                            @foreach($warnaList as $w)
                                            <option value="{{ $w }}" {{ $w == $warnaAktif ? 'selected' : '' }}>
                                                {{ $w }}
                                            </option>
                                            @endforeach
                                        </select>

                                        <select name="ukuran" class="form-select form-select-sm" onchange="this.form.submit()">
                                            @foreach($ukuranList as $u)
                                            <option value="{{ $u }}" {{ $u == $ukuranAktif ? 'selected' : '' }}>
                                                {{ $u }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- supaya saat submit salah satu, yang lain ikut terbawa --}}
                                    <input type="hidden" name="warna_current" value="{{ $warnaAktif }}">
                                    <input type="hidden" name="ukuran_current" value="{{ $ukuranAktif }}">
                                </form>

                                <div class="muted">
                                    Stok varian: <span class="fw-semibold text-dark">{{ (int)($varian->stok ?? 0) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT --}}
                        <div class="g-right right">
                            <div>
                                <div class="subtotal">Rp {{ number_format($subtotal,0,',','.') }}</div>
                                <div class="muted">Subtotal</div>
                            </div>

                            <div class="stepper">
                                <form action="{{ route('keranjang.update', $item->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="minus">
                                    <button class="btn btn-outline-secondary" type="submit">−</button>
                                </form>

                                <span class="num">{{ $qty }}</span>

                                <form action="{{ route('keranjang.update', $item->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="plus">
                                    <button class="btn btn-outline-secondary" type="submit">+</button>
                                </form>
                            </div>

                            <form id="delCart-{{ $item->id }}" action="{{ route('keranjang.destroy', $item->id) }}" method="POST" class="remove">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger p-0 text-decoration-none" onclick="return confirmDeleteForm('delCart-{{ $item->id }}', {
            title: 'Hapus dari keranjang?',
            text: 'Produk ini akan dihapus dari keranjang.',
            confirmText: 'Ya, hapus'
          })">
                                    Remove
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            @empty
            <div class="alert alert-warning text-center">
                Keranjang masih kosong
            </div>
            @endforelse
        </div>

        {{-- SUMMARY --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0" style="border-radius:18px;border:1px solid rgba(0,0,0,.06);">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Order Summary</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total</span>
                        <span class="fw-bold">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>

                    <hr>

                    @if ($keranjangs->isEmpty())
                    <button class="btn btn-secondary w-100 rounded-pill" disabled>
                        Keranjang Kosong
                    </button>
                    @else
                    <a href="{{ route('checkout') }}" class="btn btn-dark w-100 rounded-pill">
                        Checkout
                    </a>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
