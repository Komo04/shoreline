@extends('layouts.mainlayout')

@section('title', 'Notifikasi')

@push('styles')
<style>
    .notif-wrap {
        max-width: 920px;
        margin: 0 auto;
    }

    .notif-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .notif-title {
        font-weight: 800;
        margin: 0;
    }

    .notif-sub {
        color: #6c757d;
        font-size: .92rem;
        margin-top: 2px;
    }

    .notif-card {
        border-radius: 14px;
        border: 1px solid rgba(0, 0, 0, .08);
    }

    .notif-card.unread {
        border-color: rgba(25, 135, 84, .45);
        box-shadow: 0 8px 22px rgba(25, 135, 84, .08);
    }

    .notif-time {
        font-size: .82rem;
        color: #6c757d;
    }

    .notif-message {
        font-weight: 600;
        line-height: 1.35;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="notif-wrap">
        <div class="notif-head mb-3">
            <div>
                <h3 class="notif-title">Notifikasi</h3>
                <div class="notif-sub">Semua update pesanan dan aktivitas akun kamu.</div>
            </div>

            <form method="POST" action="{{ route('notifikasi.readAll') }}">
                @csrf
                <button class="btn btn-sm btn-outline-dark">Tandai semua dibaca</button>
            </form>
        </div>

        @forelse ($notifications as $n)
            @php
                $type = data_get($n->data, 'type', '');
                $trxId = data_get($n->data, 'transaksi_id');
                $isUnread = ! $n->read_at;
            @endphp

            <div class="card notif-card mb-2 {{ $isUnread ? 'unread' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-3 flex-wrap">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="notif-message">{{ data_get($n->data, 'message', 'Notifikasi') }}</div>
                                @if($isUnread)
                                    <span class="badge bg-success">Baru</span>
                                @else
                                    <span class="badge bg-light text-dark border">Dibaca</span>
                                @endif
                            </div>

                            <div class="notif-time mt-1">
                                {{ $n->created_at->diffForHumans() }}
                            </div>
                        </div>

                        @if($isUnread)
                            <form method="POST" action="{{ route('notifikasi.read', $n->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-dark">Tandai dibaca</button>
                            </form>
                        @endif
                    </div>

                    @if($trxId && $type === 'user_status_update')
                        <div class="mt-2">
                            <a href="{{ route('transaksi.show', $trxId) }}" class="text-decoration-none fw-semibold">
                                Lihat pesanan
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="alert alert-info">Belum ada notifikasi.</div>
        @endforelse

        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection
