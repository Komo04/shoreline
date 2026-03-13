@extends('layouts.mainlayout')
@section('title','Upload Bukti Pembayaran')

@section('content')
<div class="container py-5" style="max-width:720px;">

    <a href="{{ route('transaksi.show', $transaksi->id) }}" class="btn btn-outline-secondary btn-sm mb-3">
        ← Kembali
    </a>

   
    @php
        $deadline  = $transaksi->payment_deadline; // casts datetime di model
        $isExpired = $deadline ? now('Asia/Makassar')->greaterThan($deadline) : false;
    @endphp

    @if($deadline)
        <div class="text-muted small mt-1">
            Sisa waktu:
            @if(!$isExpired)
                <span id="countdown"
                      data-deadline-ms="{{ $deadline->timestamp * 1000 }}"></span>
            @else
                <span class="text-danger fw-bold">Waktu habis</span>
            @endif
        </div>
    @endif

    <div class="card border-0 shadow-sm mt-2">
        <div class="card-body">
            <h4 class="fw-bold mb-1">Upload Bukti Pembayaran</h4>
            <div class="text-muted mb-3">
                Kode: <span class="badge bg-dark">{{ $transaksi->kode_transaksi }}</span>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <small class="text-muted d-block">Total Pembayaran</small>
                    <div class="fw-bold fs-5">
                        Rp {{ number_format($transaksi->total_pembayaran,0,',','.') }}
                    </div>
                </div>

                <div class="col-md-6 text-md-end">
                    <small class="text-muted d-block">Payment Deadline</small>

                    <div id="deadlineWrap" class="fw-semibold {{ $isExpired ? 'text-danger' : '' }}">
                        @if($deadline)
                            {{ $deadline->timezone('Asia/Makassar')->format('d M Y H:i') }}

                            @if($isExpired)
                                <span id="statusBadge" class="badge bg-danger ms-2">EXPIRED</span>
                            @else
                                <span id="statusBadge" class="badge bg-success ms-2">AKTIF</span>
                            @endif
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>

            <hr>

            {{-- Alert realtime saat countdown habis --}}
            <div id="expiredAlert" class="alert alert-warning d-none mt-3">
                Transaksi sudah melewati batas waktu pembayaran. Upload bukti tidak dapat dilakukan.
            </div>

            <form action="{{ route('pembayaran.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="transaksi_id" value="{{ $transaksi->id }}">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Bukti Transfer (gambar)</label>
                    <input id="buktiInput"
                           type="file"
                           name="bukti_transfer"
                           class="form-control"
                           required
                           {{ $isExpired ? 'disabled' : '' }}>
                    <div class="form-text">Max 2MB. Format: jpg/png.</div>

                    @error('bukti_transfer')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button id="uploadBtn"
                        class="btn btn-dark w-100"
                        {{ $isExpired ? 'disabled' : '' }}>
                    Upload Bukti
                </button>

                @if($isExpired)
                    <div class="alert alert-warning mt-3 mb-0">
                        Transaksi sudah melewati batas waktu pembayaran. Upload bukti tidak dapat dilakukan.
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>

{{-- Countdown realtime (aman timezone: pakai timestamp ms) --}}
<script>
document.addEventListener("DOMContentLoaded", function () {
    const countdownEl = document.getElementById("countdown");
    if (!countdownEl) return;

    const deadline = Number(countdownEl.dataset.deadlineMs);

    const uploadBtn    = document.getElementById("uploadBtn");
    const buktiInput   = document.getElementById("buktiInput");
    const statusBadge  = document.getElementById("statusBadge");
    const deadlineWrap = document.getElementById("deadlineWrap");
    const expiredAlert = document.getElementById("expiredAlert");

    function pad(n) { return String(n).padStart(2, '0'); }

    function setExpiredUI() {
        countdownEl.textContent = "Waktu habis";
        countdownEl.classList.add("text-danger", "fw-bold");

        if (statusBadge) {
            statusBadge.textContent = "EXPIRED";
            statusBadge.classList.remove("bg-success");
            statusBadge.classList.add("bg-danger");
        }

        if (deadlineWrap) deadlineWrap.classList.add("text-danger");

        if (uploadBtn) uploadBtn.disabled = true;
        if (buktiInput) buktiInput.disabled = true;

        if (expiredAlert) {
            expiredAlert.classList.remove("d-none");
        }
    }

    function updateCountdown() {
        const now = Date.now();
        const distance = deadline - now;

        if (distance <= 0) {
            setExpiredUI();
            clearInterval(interval);
            return;
        }

        const totalSeconds = Math.floor(distance / 1000);

        // Countdown 1 jam (format: HH:MM:SS)
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        countdownEl.textContent = pad(hours) + "j " + pad(minutes) + "m " + pad(seconds) + "d";
    }

    updateCountdown();
    const interval = setInterval(updateCountdown, 1000);
});
</script>
@endsection
