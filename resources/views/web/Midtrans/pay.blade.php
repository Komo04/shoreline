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

            <div class="mb-3">
                <small class="text-muted d-block">Total</small>
                <h5 class="fw-bold mb-0">Rp {{ number_format($transaksi->total_pembayaran,0,',','.') }}</h5>
            </div>

            @if($transaksi->payment_deadline)
                <div class="alert alert-warning">
                    Bayar sebelum: <b>{{ $transaksi->payment_deadline->format('d M Y H:i') }}</b>
                </div>
            @endif

            <button id="pay-button" class="btn btn-dark w-100">
                Bayar Sekarang
            </button>

            <a href="{{ route('transaksi.show', $transaksi->id) }}" class="btn btn-outline-secondary w-100 mt-2">
                Kembali ke detail pesanan
            </a>
        </div>
    </div>
</div>

<script src="{{ $snapJsUrl }}" data-client-key="{{ $clientKey }}"></script>
<script>
document.getElementById('pay-button').addEventListener('click', function () {
    window.snap.pay("{{ $snapToken }}", {
        onSuccess: function(result){
            // webhook akan update status, ini hanya UX
            window.location.href = "{{ route('transaksi.show', $transaksi->id) }}";
        },
        onPending: function(result){
            window.location.href = "{{ route('transaksi.show', $transaksi->id) }}";
        },
        onError: function(result){
            alert('Pembayaran gagal. Silakan coba lagi.');
        },
        onClose: function(){
            // user nutup popup
        }
    });
});
</script>
@endsection
