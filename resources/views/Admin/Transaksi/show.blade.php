@extends('layouts.Admin.mainlayout')
@php use App\Http\Controllers\Admin\TransaksiController; @endphp
@section('title','Detail Transaksi')

@push('styles')
<style>
    :root{
        --border: rgba(0,0,0,.08);
        --muted:#6c757d;
        --radius-card:16px;
        --radius-btn:12px;
        --radius-pill:999px;
    }
    .wrap{max-width:1250px;margin:0 auto}
    .card{border:1px solid var(--border)!important;border-radius:var(--radius-card)}
    .btn{border-radius:var(--radius-btn)}
    .badge-pill{border-radius:var(--radius-pill);padding:.42rem .8rem;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .page-title{font-weight:900;margin:0}
    .page-sub{color:var(--muted);font-size:.9rem;margin-top:4px}
    .sec-title{font-weight:900;margin:0}
    .sec-sub{color:var(--muted);font-size:.85rem;margin-top:3px}
    .kv{display:flex;justify-content:space-between;gap:14px;padding:10px 0;border-bottom:1px solid var(--border)}
    .kv:last-child{border-bottom:0}
    .k{color:var(--muted);font-size:.85rem}
    .v{font-weight:800;text-align:right;word-break:break-word}
    .sticky{position:sticky;top:90px}

    .item{display:flex;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid var(--border)}
    .item:last-child{border-bottom:0}
    .item-left{display:flex;gap:12px;min-width:0;align-items:flex-start}
    .product-img-wrapper{
        width:80px;height:80px;border-radius:12px;overflow:hidden;background:#f8f9fa;
        box-shadow:0 6px 15px rgba(0,0,0,.08);transition:.3s;flex:0 0 auto
    }
    .product-img{width:100%;height:100%;object-fit:cover;transition:transform .3s ease}
    .product-img-wrapper:hover .product-img{transform:scale(1.08)}
    .item-name{font-weight:900;line-height:1.2}
    .item-meta{color:var(--muted);font-size:.85rem;margin-top:4px}
    .item-meta b{color:#111;font-weight:800}

    .thumb{width:100%;max-height:260px;object-fit:cover;border-radius:14px;border:1px solid var(--border)}

    .btn-back-modern{
        background:#2563eb;border:1px solid #2563eb;color:#fff;font-weight:600;
    }
    .btn-back-modern:hover{background:#1d4ed8;border-color:#1d4ed8;color:#fff}

    .update-status-select{direction:rtl}
    .update-status-select option{direction:ltr}

    .hint{font-size:.85rem;color:var(--muted)}
    .divider{border-top:1px solid var(--border);margin:14px 0}

    @media (max-width:576px){.item{flex-direction:column}}
</style>
@endpush

@section('content')
@php
    $trxColor = match($transaksi->status_transaksi){
        'pending' => 'secondary',
        'menunggu_verifikasi' => 'warning',
        'paid' => 'success',
        'diproses' => 'info',
        'dikirim' => 'primary',
        'selesai' => 'dark',
        'expired' => 'secondary',
        'dibatalkan' => 'danger',
        'refund' => 'danger',
        'refund_processing' => 'info',
        'partial_refund' => 'warning',
        'chargeback' => 'danger',
        default => 'secondary'
    };

    $methodColor = match($transaksi->metode_pembayaran){
        'midtrans' => 'info',
        'transfer' => 'dark',
        'qris' => 'primary',
        default => 'secondary'
    };

    $pembayaran = $transaksi->pembayaran ?? null;
    $payStatus  = optional($pembayaran)->status_pembayaran;

    $payColor   = match($payStatus){
        'paid' => 'success',
        'pending' => 'warning',
        'menunggu_verifikasi' => 'warning',
        'ditolak' => 'danger',
        'expired' => 'secondary',
        'dibatalkan' => 'danger',
        'refund' => 'danger',
        'refund_processing' => 'info',
        'partial_refund' => 'warning',
        default => 'light',
    };

    $alamat = $transaksi->alamat ?? null;
    $items  = $transaksi->items ?? [];
    $subtotalItems = (int) collect($items)->sum('subtotal');

    $refund = $transaksi->latestRefund ?? null;
    $refundColor = $refund ? match($refund->status){
        'requested' => 'warning',
        'processing' => 'info',
        'refunded' => 'success',
        'failed' => 'danger',
        'partial_refunded' => 'warning',
        default => 'secondary'
    } : null;

    $isMidtrans = ($transaksi->metode_pembayaran === 'midtrans');
    $paymentType = (string) ($transaksi->midtrans_payment_type ?? '');
    $refundableTypes = ['credit_card','gopay','shopeepay'];

    $midtransAutoRefundSupported = $isMidtrans && $paymentType !== '' && in_array($paymentType, $refundableTypes, true);
    $midtransPaymentTypeUnknown  = $isMidtrans && $paymentType === '';
    $midtransNeedsManualRefund   = $isMidtrans && $paymentType !== '' && !$midtransAutoRefundSupported;

    $showRefundSection = in_array($transaksi->status_transaksi, [
        'selesai','refund_processing','partial_refund','refund',
    ], true);

    $canProcessRefund  = $refund && ($refund->status === 'requested');
    $canFinalizeManual = $refund
        && (($refund->method ?? '') === 'manual')
        && (($refund->status ?? '') === 'processing');

    $statusOptions = ['paid','diproses','dikirim','selesai','dibatalkan'];
@endphp

<div class="container-fluid py-4">
    <div class="wrap">

        {{-- HEADER --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h4 class="page-title">{{ $transaksi->kode_transaksi }}</h4>

                        <span class="badge bg-{{ $trxColor }} badge-pill {{ $trxColor==='warning' ? 'text-dark' : '' }}">
                            {{ strtoupper($transaksi->status_transaksi) }}
                        </span>

                        <span class="badge bg-{{ $methodColor }} badge-pill {{ in_array($methodColor,['info','warning','light'],true) ? 'text-dark' : '' }}">
                            {{ strtoupper($transaksi->metode_pembayaran) }}
                        </span>
                    </div>

                    <div class="page-sub">
                        Dibuat: {{ $transaksi->created_at?->timezone('Asia/Makassar')->format('d M Y H:i') ?? '-' }}
                    </div>
                </div>

                <div class="text-end">
                    <div class="text-muted small">Total</div>
                    <div class="fw-bold fs-3">Rp {{ number_format($transaksi->total_pembayaran ?? 0,0,',','.') }}</div>
                    <div class="small text-muted">Ongkir: Rp {{ number_format($transaksi->ongkir ?? 0,0,',','.') }}</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- LEFT --}}
            <div class="col-lg-8">

                {{-- ITEMS --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-2">
                            <div>
                                <h6 class="sec-title">Detail Produk</h6>
                                <div class="sec-sub">Item yang dibeli customer.</div>
                            </div>
                            <div>
                                <div class="small text-muted">Subtotal Items</div>
                                <div class="fw-bold">Rp {{ number_format($subtotalItems ?? 0,0,',','.') }}</div>
                            </div>
                        </div>

                        @forelse($items as $item)
                            @php
                                $imgPath = $item->gambar
                                    ?? optional($item->produk)->gambar_produk
                                    ?? optional($item->produk)->gambar
                                    ?? optional($item->produk)->foto
                                    ?? null;

                                $img = $imgPath
                                    ? (str_starts_with($imgPath,'http') ? $imgPath : asset('storage/'.$imgPath))
                                    : 'https://via.placeholder.com/80?text=IMG';

                                $sku = $item->sku ?? optional($item->produk)->sku ?? null;
                            @endphp

                            <div class="item">
                                <div class="item-left">
                                    <div class="product-img-wrapper">
                                        <img src="{{ $img }}" alt="{{ $item->nama_produk ?? 'Produk' }}" class="product-img">
                                    </div>

                                    <div style="min-width:0">
                                        <div class="item-name">{{ $item->nama_produk ?? '-' }}</div>

                                        <div class="item-meta">
                                            <b>Varian:</b> {{ $item->warna ?? '-' }} / {{ $item->ukuran ?? '-' }}
                                            <span class="mx-1">•</span>
                                            <b>Qty:</b> {{ $item->qty ?? 0 }}
                                            @if($sku)
                                                <span class="mx-1">•</span>
                                                <b>SKU:</b> {{ $sku }}
                                            @endif
                                        </div>

                                        <div class="item-meta">
                                            <b>Harga:</b> Rp {{ number_format($item->harga_satuan ?? 0,0,',','.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted py-3">Tidak ada item.</div>
                        @endforelse
                    </div>
                </div>

                {{-- CUSTOMER & ADDRESS --}}
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="sec-title">Customer & Alamat</h6>
                        <div class="sec-sub mb-2">Kontak dan alamat pengiriman.</div>

                        <div class="row g-4">
                            <div class="col-md-5">
                                <div class="kv">
                                    <div class="k">Nama</div>
                                    <div class="v">{{ $transaksi->user->name ?? '-' }}</div>
                                </div>
                                <div class="kv">
                                    <div class="k">Email</div>
                                    <div class="v">{{ $transaksi->user->email ?? '-' }}</div>
                                </div>
                                <div class="kv">
                                    <div class="k">No Telp</div>
                                    <div class="v">{{ optional($alamat)->no_telp ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <div class="kv">
                                    <div class="k">Penerima</div>
                                    <div class="v">{{ optional($alamat)->nama_penerima ?? '-' }}</div>
                                </div>

                                <div class="kv">
                                    <div class="k">Alamat</div>
                                    <div class="v">
                                        @if($alamat)
                                            {{ $alamat->alamat_lengkap ?? '-' }}<br>
                                            <span class="text-muted fw-normal">
                                                {{ $alamat->kelurahan ?? '-' }},
                                                {{ $alamat->kecamatan ?? '-' }},
                                                {{ $alamat->kota ?? '-' }},
                                                {{ $alamat->provinsi ?? '-' }}
                                                - {{ $alamat->kode_pos ?? '-' }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            {{-- RIGHT --}}
            <div class="col-lg-4">
                <div class="sticky">

                    {{-- PEMBAYARAN --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="sec-title">Pembayaran</h6>
                            <div class="sec-sub mb-2">Status, waktu, dan bukti.</div>

                            <div class="kv">
                                <div class="k">Status</div>
                                <div class="v">
                                    <span class="badge bg-{{ $payColor }} badge-pill {{ in_array($payColor,['warning','light'],true) ? 'text-dark' : '' }}">
                                        {{ $payStatus ? strtoupper($payStatus) : 'BELUM ADA' }}
                                    </span>
                                </div>
                            </div>

                            <div class="kv">
                                <div class="k">Tanggal Bayar</div>
                                <div class="v">
                                    {{ optional($pembayaran?->tanggal_pembayaran)->timezone('Asia/Makassar')->format('d M Y H:i') ?? '-' }}
                                </div>
                            </div>

                            @if($isMidtrans)
                                @if($transaksi->midtrans_payment_type)
                                    <div class="kv">
                                        <div class="k">Payment Type</div>
                                        <div class="v">{{ $transaksi->midtrans_payment_type }}</div>
                                    </div>
                                @endif
                                @if($transaksi->midtrans_order_id)
                                    <div class="kv">
                                        <div class="k">Order ID</div>
                                        <div class="v">{{ $transaksi->midtrans_order_id }}</div>
                                    </div>
                                @endif
                                @if($transaksi->midtrans_transaction_id)
                                    <div class="kv">
                                        <div class="k">Transaction ID</div>
                                        <div class="v">{{ $transaksi->midtrans_transaction_id }}</div>
                                    </div>
                                @endif

                                @if($midtransPaymentTypeUnknown && $transaksi->status_transaksi !== 'pending')
                                    <div class="alert alert-warning mt-3 mb-0">
                                        <b>Payment type kosong.</b> Ini biasanya berarti webhook belum menyimpan payment_type.
                                        Jika refund diminta, sistem akan arahkan ke <b>manual refund</b>.
                                    </div>
                                @endif
                            @else
                                @if($pembayaran && $pembayaran->bukti_transfer)
                                    <div class="mt-3">
                                        <div class="small text-muted mb-2">Bukti Transfer</div>
                                        <img src="{{ asset('storage/'.$pembayaran->bukti_transfer) }}" class="thumb" alt="Bukti">
                                        <a target="_blank" class="btn btn-outline-dark w-100 mt-2" href="{{ asset('storage/'.$pembayaran->bukti_transfer) }}">
                                            Buka Bukti
                                        </a>
                                    </div>
                                @endif
                            @endif

                            @if($pembayaran)
                                <div class="mt-3">
                                    <a href="{{ route('admin.pembayaran.show', $pembayaran->id) }}" class="btn btn-outline-secondary btn-sm w-100">
                                        Detail Pembayaran
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- PENGIRIMAN --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="sec-title">Pengiriman</h6>
                            <div class="sec-sub mb-2">Kurir, resi, dan tanggal kirim.</div>

                            <div class="kv">
                                <div class="k">Kurir</div>
                                <div class="v">
                                    {{ strtoupper($transaksi->kurir_kode ?? '-') }}
                                    @if($transaksi->kurir_layanan) / {{ $transaksi->kurir_layanan }} @endif
                                </div>
                            </div>

                            <div class="kv">
  <div class="k">Pengiriman</div>
  <div class="v">
    {{ strtoupper($transaksi->kurir_kode ?? '-') }}
    @if($transaksi->kurir_layanan) / {{ $transaksi->kurir_layanan }} @endif
  </div>
</div>
                            <div class="kv">
                                <div class="k">No Resi</div>
                                <div class="v">{{ $transaksi->no_resi ?? '-' }}</div>
                            </div>

                            <div class="kv">
                                <div class="k">Tanggal Dikirim</div>
                                <div class="v">
                                    {{ $transaksi->tanggal_dikirim ? $transaksi->tanggal_dikirim->timezone('Asia/Makassar')->format('d M Y H:i') : '-' }}
                                </div>
                            </div>

                            @if($transaksi->no_resi && $transaksi->kurir_kode)
                                <hr class="my-3">
                                <button type="button"
                                    class="btn btn-outline-success w-100 btn-track-admin"
                                    data-track-url="{{ route('admin.transaksi.tracking.json', $transaksi->id) }}"
                                    data-awb="{{ $transaksi->no_resi }}"
                                    data-courier="{{ $transaksi->kurir_kode }}">
                                    Lacak Resi (Admin)
                                </button>
                            @endif

                            @if($transaksi->status_transaksi === 'paid')
                                <div class="alert alert-warning mt-3 mb-0">
                                    <b>Belum bisa kirim.</b> Ubah status transaksi ke <b>DIPROSES</b> terlebih dahulu.
                                </div>
                            @endif

                            @if($transaksi->status_transaksi === 'diproses')
                                <hr class="my-3">
                               <form method="POST" action="{{ route('admin.transaksi.kirim', $transaksi->id) }}">
  @csrf

  <div class="alert alert-light border mt-2">
    <div class="small text-muted">Kurir terpilih dari checkout</div>
    <div class="fw-semibold">
      {{ strtoupper($transaksi->kurir_kode ?? '-') }}
      @if($transaksi->kurir_layanan) / {{ $transaksi->kurir_layanan }} @endif
    </div>
  </div>

  @php
    $dummyResiExample = TransaksiController::suggestedDummyResi($transaksi->kurir_kode, $transaksi->kurir_layanan);
  @endphp

  <div class="mb-2">
    <label class="form-label small text-muted">No Resi</label>
    <input name="no_resi" class="form-control" placeholder="Masukkan no resi" required>
    @if($dummyResiExample)
      <div class="form-text">
        Contoh format dummy untuk pengiriman ini: <b>{{ $dummyResiExample }}</b>
      </div>
    @endif
  </div>

  <button class="btn btn-primary w-100" onclick="return confirm('Kirim pesanan ini?')">
    Simpan Resi & Kirim
  </button>
</form>
                            @endif
                        </div>
                    </div>
                      @if($showRefundSection)
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h6 class="sec-title">Refund</h6>
                                <div class="sec-sub mb-2">Pengajuan refund customer.</div>

                                @if(!$refund)
                                    <div class="text-muted">Belum ada pengajuan refund.</div>
                                @else
                                    <div class="kv">
                                        <div class="k">Status</div>
                                        <div class="v">
                                            <span class="badge bg-{{ $refundColor }} badge-pill {{ in_array($refundColor,['warning','light','info'],true) ? 'text-dark' : '' }}">
                                                {{ strtoupper($refund->status) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="kv">
                                        <div class="k">Method</div>
                                        <div class="v">{{ strtoupper($refund->method ?? '-') }}</div>
                                    </div>

                                    <div class="kv">
                                        <div class="k">Nominal</div>
                                        <div class="v">Rp {{ number_format($refund->amount ?? 0,0,',','.') }}</div>
                                    </div>

                                    @if($refund->reason)
                                        <div class="mt-2">
                                            <div class="small text-muted">Alasan</div>
                                            <div class="fw-semibold">{{ $refund->reason }}</div>
                                        </div>
                                    @endif

                                    @if($isMidtrans)
                                        <div class="divider"></div>
                                        <div class="hint">
                                            <div><b>Midtrans Payment Type:</b> {{ $paymentType !== '' ? $paymentType : '-' }}</div>
                                            @if($paymentType === '')
                                                <div class="text-warning fw-semibold mt-1">
                                                    Payment type belum tersimpan → refund akan diarahkan ke <b>manual</b>.
                                                </div>
                                            @elseif($midtransAutoRefundSupported)
                                                <div class="text-success fw-semibold mt-1">
                                                    Channel mendukung <b>auto refund</b> (menunggu webhook setelah proses).
                                                </div>
                                            @else
                                                <div class="text-warning fw-semibold mt-1">
                                                    Channel tidak mendukung auto refund → perlu <b>manual finalize</b>.
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="divider"></div>
                                    <div class="small text-muted mb-1">Rekening Tujuan Refund</div>

                                    <div class="kv">
                                        <div class="k">Bank</div>
                                        <div class="v">{{ $refund->bank_name ?? '-' }}</div>
                                    </div>
                                    <div class="kv">
                                        <div class="k">No Rekening</div>
                                        <div class="v">{{ $refund->account_number ?? '-' }}</div>
                                    </div>
                                    <div class="kv">
                                        <div class="k">Atas Nama</div>
                                        <div class="v">{{ $refund->account_name ?? '-' }}</div>
                                    </div>

                                    @if($refund->proof_path)
                                        <div class="mt-3">
                                            <a target="_blank" class="btn btn-outline-dark w-100" href="{{ asset('storage/'.$refund->proof_path) }}">
                                                Lihat Bukti Refund
                                            </a>
                                        </div>
                                    @endif

                                    <div class="divider"></div>

                                    @if($canProcessRefund)
                                        <form method="POST"
                                            action="{{ route('admin.transaksi.refund.process', $transaksi->id) }}"
                                            onsubmit="return confirm('Proses refund ini?');">
                                            @csrf
                                            <button class="btn btn-warning w-100">Proses Refund</button>
                                        </form>
                                    @endif

                                    @if($canFinalizeManual)
                                        <form method="POST"
                                            action="{{ route('admin.transaksi.refund.finalize', $transaksi->id) }}"
                                            onsubmit="return confirm('Dana sudah ditransfer? Finalize refund?');">
                                            @csrf
                                            <button class="btn btn-danger w-100">Finalize Refund (Restore Stok)</button>
                                        </form>
                                    @endif

                                    @if(($refund->method ?? '') === 'midtrans' && ($refund->status ?? '') === 'processing')
                                        <div class="alert alert-info mt-3 mb-0">
                                            Refund via Midtrans sedang <b>processing</b>. Menunggu <b>webhook</b> untuk finalisasi.
                                        </div>
                                    @endif

                                    @if(($refund->status ?? '') === 'failed')
                                        <div class="alert alert-danger mt-3 mb-0">
                                            Refund <b>gagal</b>. Kamu bisa klik <b>Proses Refund</b> lagi (retry) atau ubah ke manual bila perlu.
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- UPDATE STATUS --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="sec-title">Update Status</h6>
                            <div class="sec-sub mb-2">Ubah status transaksi (manual).</div>

                            <form method="POST" action="{{ route('admin.transaksi.updateStatus', $transaksi->id) }}">
                                @csrf
                                @method('PUT')

                                <select name="status_transaksi" class="form-select mb-2 update-status-select">
                                    @foreach($statusOptions as $status)
                                        <option value="{{ $status }}" {{ $transaksi->status_transaksi === $status ? 'selected' : '' }}>
                                            {{ strtoupper($status) }}
                                        </option>
                                    @endforeach
                                </select>

                                <button class="btn btn-dark w-100" onclick="return confirm('Update status transaksi?')">
                                    Update Status
                                </button>
                            </form>

                            <a href="{{ route('admin.transaksi') }}" class="btn btn-back-modern w-100 mt-2 rounded-pill">
                                Kembali
                            </a>
                        </div>
                    </div>

                    {{-- REFUND --}}


                </div>
            </div>
        </div>

        {{-- MODAL TRACKING ADMIN --}}
        <div class="modal fade" id="trackModalAdmin" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Tracking Pengiriman (Admin)</h5>
                            <div class="small text-muted" id="trackAdminSub">-</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div id="trackAdminAlert"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-2">Customer</h6>
                                        <div id="trackAdminCustomer" class="small text-muted">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-2">Alamat Pesanan</h6>
                                        <div id="trackAdminAlamatPesanan" class="small text-muted">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-2">Ringkasan</h6>
                                <div id="trackAdminSummary" class="small">-</div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-2">Alamat dari Kurir</h6>
                                <div id="trackAdminAlamatKurir" class="small text-muted">-</div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-2">Timeline</h6>
                                <ul class="list-group" id="trackAdminTimeline"></ul>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('trackModalAdmin');
    if (!modalEl) return;

    const bsModal = new bootstrap.Modal(modalEl);

    const subEl = document.getElementById('trackAdminSub');
    const alertEl = document.getElementById('trackAdminAlert');
    const customerEl = document.getElementById('trackAdminCustomer');
    const alamatPesananEl = document.getElementById('trackAdminAlamatPesanan');
    const alamatKurirEl = document.getElementById('trackAdminAlamatKurir');
    const summaryEl = document.getElementById('trackAdminSummary');
    const timelineEl = document.getElementById('trackAdminTimeline');

    function safe(v) {
        return (v === null || v === undefined || v === '') ? '-' : v;
    }

    function escapeHtml(s) {
        s = String(s ?? '');
        return s.replace(/[&<>"']/g, (m) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[m]));
    }

    function pickDatetime(ev) {
        return (ev && (ev.datetime_iso || ev.datetime_wita || ev.datetime || ev.datetime_raw)) || '';
    }

    function parseDate(dtStr) {
        if (!dtStr) return null;
        const s = String(dtStr).trim();
        if (s.includes('T')) {
            const d = new Date(s);
            return isNaN(d.getTime()) ? null : d;
        }
        const iso = s.replace(' ', 'T') + ':00+08:00';
        const d = new Date(iso);
        return isNaN(d.getTime()) ? null : d;
    }

    function formatWita(dtStr) {
        const d = parseDate(dtStr);
        if (!d) return safe(dtStr);
        return new Intl.DateTimeFormat('sv-SE', {
            timeZone: 'Asia/Makassar',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(d).replace('T', ' ');
    }

    function setLoading(awb, courier) {
        subEl.textContent = `Resi: ${awb || '-'} • Kurir: ${(courier || '-').toUpperCase()}`;
        alertEl.innerHTML = `<div class="alert alert-info mb-3">Mengambil data tracking...</div>`;
        customerEl.textContent = '-';
        alamatPesananEl.textContent = '-';
        alamatKurirEl.textContent = '-';
        summaryEl.textContent = '-';
        timelineEl.innerHTML = '';
    }

    async function loadTracking(url, awb, courier) {
        setLoading(awb, courier);
        bsModal.show();

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                throw new Error(`Response bukan JSON (HTTP ${res.status}). Cek route/middleware.`);
            }

            const json = await res.json();

            if (!json || json.success !== true) {
                alertEl.innerHTML = `<div class="alert alert-warning mb-3">${escapeHtml(safe(json?.message || 'Tracking gagal.'))}</div>`;
                return;
            }

            alertEl.innerHTML = '';

            const c = json.customer || {};
            customerEl.innerHTML = `
                <div class="fw-semibold">${escapeHtml(safe(c.name))}</div>
                <div>${escapeHtml(safe(c.email))}</div>
            `;

            const a = json.alamat || {};
            alamatPesananEl.innerHTML = `
                <div class="fw-semibold">${escapeHtml(safe(a.nama_penerima))}</div>
                <div>${escapeHtml(safe(a.no_telp))}</div>
                <div class="mt-1">${escapeHtml(safe(a.alamat_lengkap))}</div>
                <div class="text-muted">
                    ${escapeHtml(safe(a.kelurahan))}, ${escapeHtml(safe(a.kecamatan))}, ${escapeHtml(safe(a.kota))}<br>
                    ${escapeHtml(safe(a.provinsi))} ${escapeHtml(safe(a.kode_pos))}
                </div>
            `;

            const t = json.tracking || {};
            const d = t.details || {};
            const s = t.summary || {};
            const ds = t.delivery_status || {};

            summaryEl.innerHTML = `
                <div><b>Status:</b> ${escapeHtml(safe(s.status))}</div>
                <div><b>Asal:</b> ${escapeHtml(safe(s.origin))}</div>
                <div><b>Tujuan:</b> ${escapeHtml(safe(s.destination))}</div>
                <div><b>Delivered:</b> ${t.delivered ? 'YA' : 'BELUM'}</div>
                <div><b>POD Receiver:</b> ${escapeHtml(safe(ds.pod_receiver))}</div>
            `;

            alamatKurirEl.innerHTML = `
                <div><b>Shipper:</b> ${escapeHtml(safe(d.shipper_name))}</div>
                <div class="text-muted">${escapeHtml(safe(d.shipper_address1))} ${escapeHtml(safe(d.shipper_address2))} ${escapeHtml(safe(d.shipper_address3))}</div>
                <hr class="my-2">
                <div><b>Receiver:</b> ${escapeHtml(safe(d.receiver_name))}</div>
                <div class="text-muted">${escapeHtml(safe(d.receiver_address1))} ${escapeHtml(safe(d.receiver_address2))} ${escapeHtml(safe(d.receiver_address3))}</div>
                <div class="text-muted">${escapeHtml(safe(d.receiver_city))}</div>
            `;

            let timeline = Array.isArray(t.timeline) ? t.timeline.slice() : [];
            timeline.sort((a, b) => {
                const tb = parseDate(pickDatetime(b))?.getTime() ?? 0;
                const ta = parseDate(pickDatetime(a))?.getTime() ?? 0;
                return tb - ta;
            });

            timelineEl.innerHTML = '';
            if (!timeline.length) {
                timelineEl.innerHTML = `<li class="list-group-item text-muted">Belum ada event manifest.</li>`;
                return;
            }

            timeline.forEach(ev => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.innerHTML = `
                    <div class="fw-semibold">${escapeHtml(formatWita(pickDatetime(ev)))} — ${escapeHtml(safe(ev?.city))}</div>
                    <div class="text-muted">${escapeHtml(safe(ev?.description))}</div>
                `;
                timelineEl.appendChild(li);
            });

        } catch (e) {
            alertEl.innerHTML = `<div class="alert alert-danger mb-3">Gagal mengambil tracking: ${escapeHtml(e.message)}</div>`;
        }
    }

    document.querySelectorAll('.btn-track-admin').forEach(btn => {
        btn.addEventListener('click', () => loadTracking(btn.dataset.trackUrl, btn.dataset.awb, btn.dataset.courier));
    });
})();
</script>
@endpush
