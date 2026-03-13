@extends('layouts.Admin.mainlayout')
@section('title', 'Detail Pembayaran')

@push('styles')
<style>
    :root{
        --border: rgba(0,0,0,.07);
        --muted: #6c757d;
        --radius-card: 18px;
        --radius-btn: 14px;
    }

    .wrap{max-width:1050px;margin:0 auto}
    .title{font-weight:900;margin:0}
    .sub{color:var(--muted);font-size:.9rem;margin-top:4px}

    .card{border:1px solid var(--border)!important;border-radius:var(--radius-card)}
    .btn{border-radius:var(--radius-btn)}

    .kv{
        display:flex;justify-content:space-between;gap:14px;
        padding:10px 0;border-bottom:1px solid var(--border)
    }
    .kv:last-child{border-bottom:0}
    .k{color:var(--muted);font-size:.85rem}
    .v{font-weight:700;text-align:right}

    .item{padding:14px 0;border-bottom:1px solid var(--border)}
    .item:last-child{border-bottom:0}

    .item-thumb{
        width:72px;height:72px;object-fit:cover;flex-shrink:0;
        border-radius:16px;border:1px solid rgba(0,0,0,.08);background:#f8f9fa
    }

    .item-thumb-fallback{
        width:72px;height:72px;display:flex;align-items:center;justify-content:center;
        flex-shrink:0;border-radius:16px;border:1px solid rgba(0,0,0,.08);
        background:#f8f9fa;color:#9ca3af;font-size:1.35rem
    }

    .thumb{
        width:100%;height:220px;object-fit:cover;
        border-radius:16px;border:1px solid rgba(0,0,0,.08)
    }

    .divider{height:1px;background:var(--border);margin:14px 0}
    .sticky{position:sticky;top:90px}
    .header-actions-offset{ margin-top: 42px; }
</style>
@endpush

@section('content')
@php
    $trx = $pembayaran->transaksi;
    $alamat = $trx->alamat ?? null;

    $statusBayar = $pembayaran->status_pembayaran ?? '-';
    $isMidtrans = ($pembayaran->metode_pembayaran ?? null) === 'midtrans';

    $canAction = ($statusBayar === 'menunggu_verifikasi') && ! $isMidtrans;
@endphp

<div class="container-fluid py-4">
    <div class="wrap">
        <div class="row mb-4 g-4">
            <div class="col-lg-7">
                <a href="{{ route('admin.pembayaran') }}" class="btn btn-primary mb-2">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <h4 class="title">Detail Pembayaran</h4>

                <div class="sub">
                    {{ $trx->kode_transaksi ?? '-' }}
                    • {{ optional($trx->created_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="col-lg-5 d-flex justify-content-end">
                @if($canAction)
                    <div class="d-flex gap-2 align-items-center header-actions-offset flex-wrap">
                        <form method="POST" action="{{ route('admin.pembayaran.konfirmasi', $pembayaran->id) }}">
                            @csrf
                            <button class="btn btn-success" onclick="return confirm('Verifikasi pembayaran ini?')">
                                Verifikasi
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.pembayaran.tolak', $pembayaran->id) }}">
                            @csrf
                            <button class="btn btn-outline-danger" onclick="return confirm('Tolak pembayaran ini?')">
                                Tolak
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Customer</div>

                        <div class="kv">
                            <div class="k">Nama</div>
                            <div class="v">{{ optional($trx->user)->name ?? '-' }}</div>
                        </div>
                        <div class="kv">
                            <div class="k">Email</div>
                            <div class="v">{{ optional($trx->user)->email ?? '-' }}</div>
                        </div>
                        <div class="kv">
                            <div class="k">No Telp</div>
                            <div class="v">{{ $alamat->no_telp ?? '-' }}</div>
                        </div>

                        <div class="divider"></div>

                        <div class="fw-bold mb-1">Alamat</div>
                        @if($alamat)
                            <div class="text-muted small">
                                <strong class="text-dark">{{ $alamat->nama_penerima ?? '-' }}</strong><br>
                                {{ $alamat->alamat_lengkap ?? '-' }}<br>
                                {{ $alamat->kelurahan ?? '-' }},
                                {{ $alamat->kecamatan ?? '-' }},
                                {{ $alamat->kota ?? '-' }},
                                {{ $alamat->provinsi ?? '-' }}
                                - {{ $alamat->kode_pos ?? '-' }}
                            </div>
                        @else
                            <div class="text-muted small">Alamat belum tersedia</div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Items</div>

                        @forelse(($trx->items ?? []) as $item)
                            @php
                                $gambarVarian = data_get($item, 'produkVarian.gambar_varian');
                                $gambarProduk = data_get($item, 'produk.gambar_produk');
                                $gambarItem = $gambarVarian ?: $gambarProduk;
                            @endphp
                            <div class="item d-flex justify-content-between align-items-start gap-3">
                                <div class="d-flex align-items-start gap-3">
                                    @if($gambarItem)
                                        <img src="{{ asset('storage/' . $gambarItem) }}" alt="{{ $item->nama_produk ?? 'Produk' }}" class="item-thumb">
                                    @else
                                        <div class="item-thumb-fallback">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    @endif

                                    <div>
                                        <strong>{{ $item->nama_produk ?? '-' }}</strong>
                                        <div class="text-muted small">
                                            {{ $item->warna ?? '-' }} / {{ $item->ukuran ?? '-' }} • Qty {{ $item->qty ?? 0 }}
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <div class="text-muted small">
                                        Rp {{ number_format($item->harga_satuan ?? 0,0,',','.') }}
                                    </div>
                                    <strong>
                                        Rp {{ number_format($item->subtotal ?? 0,0,',','.') }}
                                    </strong>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted py-3">Tidak ada item.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="sticky">
                    <div class="card">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Pembayaran</div>

                            <div class="kv">
                                <div class="k">ID</div>
                                <div class="v">#{{ $pembayaran->id }}</div>
                            </div>
                            <div class="kv">
                                <div class="k">Metode</div>
                                <div class="v">{{ strtoupper($pembayaran->metode_pembayaran ?? '-') }}</div>
                            </div>
                            <div class="kv">
                                <div class="k">Status</div>
                                <div class="v">{{ strtoupper($pembayaran->status_pembayaran ?? '-') }}</div>
                            </div>
                            <div class="kv">
                                <div class="k">Tanggal Bayar</div>
                                <div class="v">{{ optional($pembayaran->tanggal_pembayaran)->format('d M Y H:i') ?? '-' }}</div>
                            </div>
                            <div class="kv">
                                <div class="k">Total</div>
                                <div class="v">Rp {{ number_format($trx->total_pembayaran ?? 0,0,',','.') }}</div>
                            </div>

                            <div class="divider"></div>

                            <div class="fw-bold mb-2">Bukti Transfer</div>
                            @if(!$isMidtrans && $pembayaran->bukti_transfer)
                                <img src="{{ asset('storage/'.$pembayaran->bukti_transfer) }}" class="thumb" alt="Bukti Transfer">

                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalBukti">
                                        Preview
                                    </button>

                                    <a class="btn btn-dark" target="_blank" href="{{ asset('storage/'.$pembayaran->bukti_transfer) }}">
                                        Buka Tab Baru
                                    </a>
                                </div>
                            @else
                                <div class="text-muted small">Tidak ada bukti transfer.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!$isMidtrans && $pembayaran->bukti_transfer)
            <div class="modal fade" id="modalBukti" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Bukti Transfer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <img src="{{ asset('storage/'.$pembayaran->bukti_transfer) }}" class="img-fluid rounded" alt="Bukti Transfer">
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

