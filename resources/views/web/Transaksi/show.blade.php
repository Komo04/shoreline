@extends('layouts.mainlayout')
@section('title','Detail Pesanan')

@section('content')
<style>
    .trx-item-thumb {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        flex-shrink: 0;
    }

    .trx-item-thumb-fallback {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #111827;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
</style>
<div class="container py-5">

    <a href="{{ route('transaksi.index') }}" class="btn btn-outline-secondary btn-sm mb-3">
        ← Kembali
    </a>

    @php
        $trxBadge = match($transaksi->status_transaksi){
            'paid' => 'success',
            'pending' => 'secondary',
            'menunggu_verifikasi' => 'warning',
            'diproses' => 'info',
            'dikirim' => 'primary',
            'selesai' => 'dark',
            'expired' => 'secondary',
            'dibatalkan' => 'danger',
            'refund' => 'danger',
            'refund_processing' => 'info',
            'partial_refund' => 'warning',
            default => 'secondary',
        };

        $payStatus = optional($transaksi->pembayaran)->status_pembayaran;
        $payBadge = match($payStatus){
            'paid' => 'success',
            'pending' => 'warning',
            'menunggu_verifikasi' => 'warning',
            'expired' => 'secondary',
            'ditolak' => 'danger',
            'refund' => 'danger',
            'refund_processing' => 'info',
            'partial_refund' => 'warning',
            default => 'light'
        };

        $isMidtrans = $transaksi->metode_pembayaran === 'midtrans';

        $ongkir = (int) ($transaksi->ongkir ?? 0);
        $subtotalItems = (int) collect($transaksi->items ?? [])->sum('subtotal');
        $grandTotal = (int) ($transaksi->total_pembayaran ?? 0);

        $refund = $transaksi->latestRefund ?? null;

        // ✅ tambahin partial_refunded biar aman kalau dipakai di DB
        $refundBadge = $refund ? match($refund->status){
            'requested' => 'warning',
            'processing' => 'info',
            'refunded' => 'success',
            'partial_refunded' => 'warning',
            'failed' => 'danger',
            default => 'secondary'
        } : null;

        // ✅ tombol refund hanya muncul saat SELESAI
        $canRefund = $transaksi->status_transaksi === 'selesai'
            && (!$refund || in_array($refund->status, ['failed'], true));

        $canTrack = !empty($transaksi->no_resi)
            && !empty($transaksi->kurir_kode)
            && in_array($transaksi->status_transaksi, ['dikirim','selesai'], true);

        $canConfirmArrived = $transaksi->status_transaksi === 'dikirim';

        // ===== Midtrans payment type =====
        $paymentType = (string) ($transaksi->midtrans_payment_type ?? '');

        $midtransPaymentTypeUnknown = $isMidtrans && $paymentType === '';
    @endphp

    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1">Detail Pesanan</h4>
                <div class="text-muted">
                    Kode: <span class="badge bg-dark">{{ $transaksi->kode_transaksi }}</span>
                </div>
                <div class="small text-muted mt-1">
                    Dibuat {{ $transaksi->created_at?->timezone('Asia/Makassar')->format('d M Y H:i') }}
                </div>
            </div>

            <div class="text-end">
                <span class="badge bg-{{ $trxBadge }} px-3 py-2">
                    {{ strtoupper($transaksi->status_transaksi) }}
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Left --}}
        <div class="col-lg-7">

            {{-- Info transaksi --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Info Transaksi</h6>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Metode Pembayaran</small>
                            <span class="badge bg-info text-dark px-3 py-2">
                                {{ strtoupper($transaksi->metode_pembayaran) }}
                            </span>

                            @if($isMidtrans)
                                <div class="small text-muted mt-1">
                                    Payment Type:
                                    <b>{{ $paymentType ?: '-' }}</b>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Payment deadline</small>
                            <div class="fw-semibold">
                                {{ $transaksi->payment_deadline ? $transaksi->payment_deadline->timezone('Asia/Makassar')->format('d M Y H:i') : '-' }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Subtotal Item</small>
                            <div class="fw-semibold">Rp {{ number_format($subtotalItems,0,',','.') }}</div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block">Ongkir</small>
                            <div class="fw-semibold">Rp {{ number_format($ongkir,0,',','.') }}</div>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-2">Alamat Pengiriman</h6>
                    <div class="text-muted">
                        <div class="fw-semibold text-dark">{{ $transaksi->shipping_nama_penerima ?? '-' }}</div>
                        <div>{{ $transaksi->shipping_no_telp ?? '-' }}</div>
                        <div class="mt-1">{{ $transaksi->shipping_alamat_lengkap ?? '-' }}</div>
                        <div>
                            {{ $transaksi->shipping_kelurahan ?? '-' }},
                            {{ $transaksi->shipping_kecamatan ?? '-' }},
                            {{ $transaksi->shipping_kota ?? '-' }},
                            {{ $transaksi->shipping_provinsi ?? '-' }}
                            - {{ $transaksi->shipping_kode_pos ?? '-' }}
                        </div>
                    </div>

                    @if($transaksi->no_resi)
                        <hr>
                        <small class="text-muted d-block">Resi</small>

                        <div class="fw-semibold d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                {{ $transaksi->ekspedisi ?? '-' }} - {{ $transaksi->no_resi }}
                                <div class="small text-muted">
                                    Kurir: {{ strtoupper($transaksi->kurir_kode ?? '-') }}
                                    @if($transaksi->kurir_layanan) / {{ $transaksi->kurir_layanan }} @endif
                                    • Dikirim:
                                    {{ $transaksi->tanggal_dikirim ? $transaksi->tanggal_dikirim->timezone('Asia/Makassar')->format('d M Y H:i') : '-' }}
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                @if($canTrack)
                                    <button type="button"
                                        class="btn btn-sm btn-outline-success btnTrackUser"
                                        data-track-url="{{ route('transaksi.tracking.json', $transaksi->id) }}"
                                        data-awb="{{ $transaksi->no_resi }}"
                                        data-courier="{{ $transaksi->kurir_kode }}">
                                        Lacak Resi
                                    </button>
                                @endif

                                @if($canConfirmArrived)
                                    <form method="POST" action="{{ route('transaksi.diterima', $transaksi->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Yakin barang sudah sampai?')">
                                            Pesanan Diterima
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- BOX hasil tracking --}}
                        <div id="trackBox" class="mt-3" style="display:none;">
                            <div id="trackAlert"></div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <div class="fw-bold mb-2">Alamat Pesanan</div>
                                        <div id="trackAlamatPesanan" class="small text-muted">-</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <div class="fw-bold mb-2">Alamat dari Kurir</div>
                                        <div id="trackAlamatKurir" class="small text-muted">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="border rounded p-3 mt-3">
                                <div class="fw-bold mb-2">Ringkasan</div>
                                <div id="trackSummary" class="small">-</div>
                            </div>

                            <div class="border rounded p-3 mt-3">
                                <div class="fw-bold mb-2">Timeline</div>
                                <ul class="list-group" id="trackTimeline"></ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Items --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Item Pesanan</h6>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th class="text-start">Produk</th>
                                    <th>Varian</th>
                                    <th>Qty</th>
                                    <th>Harga</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @foreach($transaksi->items as $item)
                                    @php
                                        $gambarVarian = data_get($item, 'produkVarian.gambar_varian');
                                        $gambarProduk = data_get($item, 'produk.gambar_produk');
                                        $gambarSource = $gambarVarian ?: $gambarProduk;
                                        $gambarUrl = $gambarSource ? asset('storage/' . ltrim($gambarSource, '/')) : null;
                                    @endphp
                                    <tr>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-3">
                                                @if($gambarUrl)
                                                    <img
                                                        src="{{ $gambarUrl }}"
                                                        alt="{{ $item->nama_produk ?? 'Produk' }}"
                                                        class="trx-item-thumb"
                                                        loading="lazy"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                    >
                                                    <span class="trx-item-thumb-fallback" style="display:none;">
                                                        <i class="fa-solid fa-bag-shopping"></i>
                                                    </span>
                                                @else
                                                    <span class="trx-item-thumb-fallback">
                                                        <i class="fa-solid fa-bag-shopping"></i>
                                                    </span>
                                                @endif

                                                <div>
                                                    <div class="fw-semibold">{{ $item->nama_produk ?? '-' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $item->warna ?? '-' }} / {{ $item->ukuran ?? '-' }}</td>
                                        <td>{{ $item->qty }}</td>
                                        <td>Rp {{ number_format($item->harga_satuan,0,',','.') }}</td>
                                        <td class="fw-bold">Rp {{ number_format($item->subtotal,0,',','.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <div style="min-width:280px">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-semibold">Rp {{ number_format($subtotalItems,0,',','.') }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted">Ongkir</span>
                                <span class="fw-semibold">Rp {{ number_format($ongkir,0,',','.') }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold">Rp {{ number_format($grandTotal,0,',','.') }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- Right --}}
        <div class="col-lg-5">

            {{-- Pembayaran --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Pembayaran</h6>

                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Metode</span>
                        <span class="fw-semibold">{{ strtoupper($transaksi->metode_pembayaran) }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">Status Pembayaran</span>
                        <span class="badge bg-{{ $payBadge }} {{ in_array($payBadge,['warning','light'],true) ? 'text-dark' : '' }} px-3 py-2">
                            {{ $payStatus ? strtoupper($payStatus) : 'BELUM' }}
                        </span>
                    </div>

                    <hr>

                    @if($transaksi->pembayaran && $transaksi->pembayaran->bukti_transfer)
                        <img class="img-fluid rounded border" src="{{ asset('storage/'.$transaksi->pembayaran->bukti_transfer) }}" alt="Bukti Transfer">
                        <a class="btn btn-outline-dark w-100 mt-3" target="_blank" href="{{ asset('storage/'.$transaksi->pembayaran->bukti_transfer) }}">
                            Buka Bukti Transfer
                        </a>
                    @endif

                    @if(!$isMidtrans && !$transaksi->pembayaran && in_array($transaksi->status_transaksi, ['pending'], true))
                        <a href="{{ route('pembayaran.upload', $transaksi->id) }}" class="btn btn-dark w-100">
                            Upload Bukti Pembayaran
                        </a>
                        <div class="small text-muted mt-2">
                            Setelah upload, admin akan memverifikasi pembayaran.
                        </div>
                    @endif

                    @if($transaksi->pembayaran && $transaksi->pembayaran->status_pembayaran === 'menunggu_verifikasi')
                        <div class="alert alert-warning mt-3 mb-0">
                            Bukti sudah diupload, menunggu verifikasi admin.
                        </div>
                    @endif

                    @if($isMidtrans)
                        @php
                            $midtransLabel = match($transaksi->status_transaksi){
                                'paid' => 'PAID',
                                'pending' => 'MENUNGGU PEMBAYARAN',
                                'diproses' => 'DIPROSES',
                                'dikirim' => 'DIKIRIM',
                                'selesai' => 'SELESAI',
                                'expired' => 'EXPIRED',
                                'dibatalkan' => 'DIBATALKAN',
                                'refund_processing' => 'REFUND DIPROSES',
                                'partial_refund' => 'PARTIAL REFUND',
                                'refund' => 'REFUND',
                                default => strtoupper($transaksi->status_transaksi),
                            };

                            $midtransBadge = match($transaksi->status_transaksi){
                                'paid' => 'success',
                                'pending' => 'warning',
                                'diproses' => 'info',
                                'dikirim' => 'primary',
                                'selesai' => 'dark',
                                'expired' => 'secondary',
                                'dibatalkan' => 'danger',
                                'refund_processing' => 'info',
                                'partial_refund' => 'warning',
                                'refund' => 'danger',
                                default => 'secondary',
                            };

                            $canPay = $transaksi->status_transaksi === 'pending';
                        @endphp

                        <div class="mb-2">
                            <small class="text-muted d-block">Pembayaran Midtrans</small>
                            <span class="badge bg-{{ $midtransBadge }} {{ $midtransBadge=='warning' ? 'text-dark' : '' }}">
                                {{ $midtransLabel }}
                            </span>
                        </div>

                        @if($canPay)
                            <a href="{{ route('midtrans.pay', $transaksi->id) }}" class="btn btn-dark w-100 mt-2">
                                Bayar Sekarang (Midtrans)
                            </a>
                            <div class="small text-muted mt-2">
                                Status otomatis berubah saat webhook diterima.
                            </div>
                        @endif

                        @if($midtransPaymentTypeUnknown)
                            <div class="alert alert-warning mt-3 mb-0">
                                <b>Info payment type belum tersedia.</b> Jika nanti refund, sistem akan menyesuaikan (otomatis/manual).
                            </div>
                        @endif
                    @endif

                </div>
            </div>

            {{-- Refund (DIATAS CATATAN) --}}
            <div id="refund" class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Refund</h6>

                    @if($refund)
                        <div class="alert alert-info mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Status</span>
                                <span class="badge bg-{{ $refundBadge }} {{ in_array($refundBadge,['warning','light'],true) ? 'text-dark' : '' }}">
                                    {{ strtoupper($refund->status) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <span>Metode</span>
                                <span class="fw-semibold">{{ strtoupper($refund->method) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <span>Nominal</span>
                                <span class="fw-semibold">Rp {{ number_format($refund->amount,0,',','.') }}</span>
                            </div>

                            <hr class="my-2">

                            @if($refund->method === 'manual')
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Bank</span>
                                    <span class="fw-semibold">{{ $refund->bank_name ?? '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>No Rekening</span>
                                    <span class="fw-semibold">{{ $refund->account_number ?? '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>Atas Nama</span>
                                    <span class="fw-semibold">{{ $refund->account_name ?? '-' }}</span>
                                </div>
                            @else
                                <div class="alert alert-light mb-0 mt-2">
                                    Refund via <b>Midtrans</b>. Dana akan dikembalikan lewat channel pembayaran Midtrans.
                                </div>
                            @endif

                            @if($refund->reason)
                                <div class="mt-2"><b>Alasan:</b> {{ $refund->reason }}</div>
                            @endif
                        </div>
                    @endif

                    @if($canRefund)
                        <form method="POST" action="{{ route('refund.request', $transaksi->id) }}" enctype="multipart/form-data">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Alasan Refund</label>
                                <textarea name="reason" class="form-control" rows="3" required>{{ old('reason') }}</textarea>
                                @error('reason') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            {{-- ✅ MIDTRANS --}}
                            @if($isMidtrans)

                                @if(true)
                                    <div class="alert alert-warning">
                                        Pembayaran via <b>Midtrans</b>
                                        @if($paymentType)
                                            ({{ $paymentType }})
                                        @endif
                                        akan diproses dengan <b>manual finalize</b>. Isi rekening tujuan di bawah.
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">Nama Bank</label>
                                        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">No Rekening</label>
                                        <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nama Pemilik Rekening</label>
                                        <input type="text" name="account_name" class="form-control" value="{{ old('account_name') }}" required>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        Refund via <b>Midtrans</b> tetap diproses dengan <b>manual finalize</b>.
                                    </div>
                                @endif

                            {{-- ✅ NON MIDTRANS --}}
                            @else
                                <div class="alert alert-warning">
                                    Refund diproses <b>manual finalize</b>. Admin akan transfer dana ke rekening tujuan berikut.
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Nama Bank</label>
                                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">No Rekening</label>
                                    <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Pemilik Rekening</label>
                                    <input type="text" name="account_name" class="form-control" value="{{ old('account_name') }}" required>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Bukti (opsional)</label>
                                <input type="file" name="proof" class="form-control">
                                @error('proof') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Ajukan refund untuk transaksi ini?')">
                                Ajukan Refund
                            </button>
                        </form>
                    @else
                        @if($transaksi->status_transaksi !== 'selesai')
                            <div class="alert alert-secondary mb-0">
                                Refund hanya bisa diajukan setelah barang sampai (status <b>SELESAI</b>).
                            </div>
                        @elseif($refund && $refund->status !== 'failed')
                            <div class="alert alert-secondary mb-0">
                                Refund sudah diajukan. Silakan menunggu proses.
                            </div>
                        @endif
                    @endif

                </div>
            </div>

            {{-- Catatan (DIBAWAH REFUND) --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Catatan</h6>
                    <div class="text-muted">
                        Refund hanya bisa diajukan saat status <b>SELESAI</b>.
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

{{-- TRACKING SCRIPT kamu tetap (tidak kuubah) --}}
<script>
(function() {
    const buttons = document.querySelectorAll('.btnTrackUser');
    if (!buttons.length) return;

    const box = document.getElementById('trackBox');
    const alertEl = document.getElementById('trackAlert');
    const alamatPesananEl = document.getElementById('trackAlamatPesanan');
    const alamatKurirEl = document.getElementById('trackAlamatKurir');
    const summaryEl = document.getElementById('trackSummary');
    const timelineEl = document.getElementById('trackTimeline');

    function safe(v) { return (v === null || v === undefined || v === '') ? '-' : v; }
    function escapeHtml(s) {
        s = String(s ?? '');
        return s.replace(/[&<>"']/g, (m) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
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
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit',
            hour12: false
        }).format(d).replace('T', ' ');
    }

    function setLoading(awb, courier) {
        box.style.display = 'block';
        alertEl.innerHTML = `<div class="alert alert-info mb-3">
            Mengambil data tracking untuk <b>${escapeHtml(safe(awb))}</b> (${escapeHtml(safe(courier).toUpperCase())})...
        </div>`;
        alamatPesananEl.textContent = '-';
        alamatKurirEl.textContent = '-';
        summaryEl.textContent = '-';
        timelineEl.innerHTML = '';
    }

    async function fetchTracking(url, awb, courier) {
        setLoading(awb, courier);

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
            const s = t.summary || {};
            const d = t.details || {};
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
            timeline.sort((a, b) => (parseDate(pickDatetime(b))?.getTime() ?? 0) - (parseDate(pickDatetime(a))?.getTime() ?? 0));

            timelineEl.innerHTML = '';
            if (!timeline.length) {
                timelineEl.innerHTML = `<li class="list-group-item text-muted">Belum ada event manifest.</li>`;
                return;
            }

            timeline.forEach(ev => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.innerHTML = `
                    <div class="fw-semibold">
                        ${escapeHtml(formatWita(pickDatetime(ev)))} — ${escapeHtml(safe(ev?.city))}
                    </div>
                    <div class="text-muted">${escapeHtml(safe(ev?.description))}</div>
                `;
                timelineEl.appendChild(li);
            });

        } catch (e) {
            alertEl.innerHTML = `<div class="alert alert-danger mb-3">Gagal mengambil tracking: ${escapeHtml(e.message)}</div>`;
        }
    }

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            fetchTracking(btn.dataset.trackUrl, btn.dataset.awb || '', btn.dataset.courier || '');
        });
    });
})();
</script>
@endsection
