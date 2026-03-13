@extends('layouts.Admin.mainlayout')
@section('title','Detail Transaksi')

@section('content')
<div class="container">
    <div class="card shadow">
        <div class="card-header">
            <h4>Detail Transaksi</h4>
        </div>

        <div class="card-body">
            <p><strong>Nama User:</strong> {{ optional($transaksi->user)->name ?? '-' }}</p>
            <p><strong>Alamat:</strong> {{ optional($transaksi->alamat)->alamat_lengkap ?? '-' }}</p>

            <p><strong>Tanggal Transaksi:</strong>
                {{ $transaksi->created_at->format('d M Y H:i') }}
            </p>

            <p><strong>Metode Pembayaran:</strong>
                {{ $transaksi->metode_pembayaran }}
            </p>

            <p>
                <strong>Status Transaksi:</strong>
                <span class="badge bg-{{ $transaksi->status_pembayaran == 'paid' ? 'success' : 'warning' }}">
                    {{ $transaksi->status_pembayaran }}
                </span>
            </p>

            <p>
                <strong>Total Pembayaran:</strong>
                Rp {{ number_format($transaksi->total_pembayaran,0,',','.') }}
            </p>

            <hr>

            {{-- ================= PEMBAYARAN ================= --}}
            @if($transaksi->pembayaran)
                <p>
                    <strong>Status Pembayaran:</strong>
                    <span class="badge bg-info">
                        {{ $transaksi->pembayaran->status_pembayaran }}
                    </span>
                </p>

                @if($transaksi->pembayaran->bukti_transfer)
                    <p><strong>Bukti Transfer:</strong></p>
                    <img
                        src="{{ asset('storage/'.$transaksi->pembayaran->bukti_transfer) }}"
                        class="img-fluid rounded border"
                        width="300"
                    >
                @else
                    <span class="badge bg-warning">Bukti belum diupload</span>
                @endif
            @else
                <span class="badge bg-danger">Belum ada data pembayaran</span>
            @endif

            <div class="mt-4">
                <a href="{{ route('admin.transaksi') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
