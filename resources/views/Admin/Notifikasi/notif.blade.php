@extends('layouts.Admin.mainlayout')
@section('title', 'Notifikasi Admin')

@push('styles')
<style>
    .admin-notif-wrap {
        max-width: 980px;
        margin: 0 auto;
    }

    .admin-notif-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .admin-notif-title {
        font-weight: 800;
        margin: 0;
    }

    .admin-notif-sub {
        color: #6c757d;
        font-size: .9rem;
    }

    .admin-notif-item {
        border-radius: 14px;
        border: 1px solid rgba(0, 0, 0, .08);
    }

    .admin-notif-item.unread {
        border-color: rgba(13, 110, 253, .4);
        box-shadow: 0 8px 20px rgba(13, 110, 253, .08);
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="admin-notif-wrap">
        <div class="admin-notif-header mb-3">
            <div>
                <h4 class="admin-notif-title">Notifikasi Admin</h4>
                <div class="admin-notif-sub">Update terbaru transaksi, kontak, dan aktivitas toko.</div>
            </div>

            <form method="POST" action="{{ route('admin.notifikasi.readAll') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-dark">Tandai semua dibaca</button>
            </form>
        </div>

        @if($notifications->isEmpty())
            <div class="alert alert-info">Belum ada notifikasi.</div>
        @else
            <div class="d-grid gap-2">
                @foreach($notifications as $n)
                    @php
                        $isUnread = ! $n->read_at;
                        $url = data_get($n->data, 'url', route('admin.notifikasi.index'));
                        $message = data_get($n->data, 'message', 'Notifikasi');
                    @endphp

                    <div class="card admin-notif-item {{ $isUnread ? 'unread' : '' }}">
                        <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <div class="fw-semibold">{{ $message }}</div>
                                    @if($isUnread)
                                        <span class="badge bg-primary">Baru</span>
                                    @else
                                        <span class="badge bg-light text-dark border">Dibaca</span>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $n->created_at->diffForHumans() }}</small>
                            </div>

                            <div class="d-flex gap-2">
                                @if($isUnread)
                                    <form method="POST" action="{{ route('admin.notifikasi.read', $n->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-dark">Tandai dibaca</button>
                                    </form>
                                @endif

                                <a href="{{ $url }}" class="btn btn-sm btn-outline-secondary">Buka</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
