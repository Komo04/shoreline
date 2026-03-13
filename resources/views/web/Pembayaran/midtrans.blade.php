@extends('layouts.mainlayout')
@section('title','Pembayaran Midtrans')

@section('content')
<div class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h4 class="fw-bold mb-1">Pembayaran Midtrans</h4>

            <div class="text-muted mb-3">
                Kode: <span class="badge bg-dark">{{ $transaksi->kode_transaksi }}</span>
            </div>

            <div class="alert alert-info mb-3">
                Klik tombol di bawah untuk melanjutkan pembayaran.
                <div class="small mt-2">
                    Status akan otomatis berubah setelah webhook diterima.
                </div>
            </div>

            {{-- status info realtime --}}
            <div class="mb-3">
                <small class="text-muted d-block">Status Transaksi</small>
                <span id="statusTrx" class="badge bg-secondary">
                    {{ strtoupper($transaksi->status_transaksi) }}
                </span>

                <small class="text-muted d-block mt-2">Status Pembayaran</small>
                <span id="statusBayar" class="badge bg-warning text-dark">
                    {{ strtoupper(optional($transaksi->pembayaran)->status_pembayaran ?? 'PENDING') }}
                </span>
            </div>

            <button id="payBtn" class="btn btn-dark w-100">
                Bayar Sekarang (Midtrans)
            </button>

            <a class="btn btn-outline-secondary w-100 mt-2"
               href="{{ route('transaksi.show', $transaksi->id) }}">
                Kembali ke Detail Pesanan
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ $snapJsUrl }}" data-client-key="{{ $clientKey }}"></script>
<script>
    const snapToken = @json($snapToken);
    const trxId = @json($transaksi->id);
    const checkUrl = @json(route('transaksi.status', $transaksi->id));
    const backUrl = @json(route('transaksi.show', $transaksi->id));

    const statusTrxEl = document.getElementById('statusTrx');
    const statusBayarEl = document.getElementById('statusBayar');
    const payBtn = document.getElementById('payBtn');

    let polling = null;

    function setBadge(el, text, type) {
        el.className = 'badge bg-' + type;
        el.textContent = text;
    }

    async function checkStatus() {
        try {
            const res = await fetch(checkUrl, { headers: { 'Accept': 'application/json' }});
            const data = await res.json();

            // update UI badge
            setBadge(statusTrxEl, (data.status_transaksi || 'PENDING').toUpperCase(), data.status_transaksi === 'paid' ? 'success' : 'secondary');

            const bayar = (data.status_pembayaran || 'PENDING').toUpperCase();
            if (bayar === 'PAID') {
                setBadge(statusBayarEl, bayar, 'success');

                // stop polling lalu balik ke detail
                clearInterval(polling);
                polling = null;

                window.location.href = backUrl;
                return;
            } else {
                // pending / lainnya
                setBadge(statusBayarEl, bayar, bayar === 'PENDING' ? 'warning text-dark' : 'secondary');
            }
        } catch (e) {
            // kalau error, diam aja (nggak ganggu UX)
        }
    }

    function startPolling() {
        if (polling) return;
        polling = setInterval(checkStatus, 2000);
        checkStatus();
    }

    payBtn.addEventListener('click', function () {
        window.snap.pay(snapToken, {
            onSuccess: function () {
                // jangan langsung redirect, tunggu webhook update (polling)
                startPolling();
            },
            onPending: function () {
                startPolling();
            },
            onError: function () {
                alert('Pembayaran gagal. Coba lagi.');
            },
            onClose: function () {
                // user nutup popup, tetap bisa cek status kalau ternyata sudah bayar
                startPolling();
            }
        });
    });

    // kalau user reload halaman, tetap polling (biar auto update)
    startPolling();
</script>
@endpush
