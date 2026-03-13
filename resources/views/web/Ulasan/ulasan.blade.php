@extends('layouts.mainlayout')

@section('title', 'Ulasan Saya')

@push('styles')
<style>
    :root{
        --border: rgba(0,0,0,.08);
        --muted: #6c757d;
        --radius-card: 16px;
        --radius-btn: 12px;
        --radius-pill: 999px;
    }

    .card{
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-card);
    }

    .btn{
        border-radius: var(--radius-btn);
    }

    .page-title{
        font-weight: 900;
        margin: 0;
    }

    .page-sub{
        color: var(--muted);
        font-size: .9rem;
        margin-top: 4px;
    }

    .muted{ color: var(--muted); }

    .badge-pill{
        border-radius: var(--radius-pill);
        padding: .35rem .65rem;
    }

    /* Rating */
    .stars{
        color: #f59e0b;
        letter-spacing: 1px;
        font-size: .95rem;
    }

    /* ===== Pagination Styling ===== */
    .pagination {
        margin: 0;
        padding-left: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
    }

    .pagination .page-item .page-link {
        border-radius: 10px;
        padding: 6px 14px;
        border: 1px solid #ddd;
        color: #333;
        font-weight: 500;
        transition: 0.2s;
    }

    .pagination .page-item .page-link:hover {
        background-color: #212529;
        color: #fff;
        border-color: #212529;
    }

    .pagination .page-item.active .page-link {
        background-color: #212529;
        border-color: #212529;
        color: #fff;
    }

    .pagination .page-item.disabled .page-link {
        background-color: #f1f1f1;
        color: #aaa;
        border-color: #ddd;
    }
</style>
@endpush

@section('content')
<div class="container py-5">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h4 class="page-title">Ulasan Saya</h4>
            <div class="page-sub">Kelola ulasan produk yang pernah kamu berikan.</div>
        </div>

        @if(method_exists($ulasans, 'total'))
        <div class="text-muted small">
            Total: <strong>{{ $ulasans->total() }}</strong> ulasan
        </div>
        @endif
    </div>

    {{-- LIST --}}
    @forelse($ulasans as $u)
    @php
        $produkNama = $u->produk?->nama_produk ?? '-';
        $rating = (int) ($u->rating ?? 0);
        $komentar = trim((string) ($u->komentar ?? ''));
    @endphp

    <div class="card shadow-sm mb-3">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="fw-semibold fs-5">{{ $produkNama }}</div>

                    <div class="d-flex align-items-center gap-2 mt-1">
                        <div class="stars">
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= $rating)
                                    <i class="fa-solid fa-star"></i>
                                @else
                                    <i class="fa-regular fa-star"></i>
                                @endif
                            @endfor
                        </div>

                        <span class="badge bg-warning text-dark badge-pill">
                            {{ $rating }}/5
                        </span>
                    </div>

                    <div class="small muted mt-2">
                        Dibuat: {{ optional($u->created_at)->format('d M Y H:i') }}
                        @if($u->updated_at && $u->updated_at->ne($u->created_at))
                            • Diperbarui: {{ optional($u->updated_at)->format('d M Y H:i') }}
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('user.ulasans.edit', $u->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                    </a>

                    <form id="delUserUlasan-{{ $u->id }}"
                          action="{{ route('user.ulasans.destroy', $u->id) }}"
                          method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirmDeleteForm('delUserUlasan-{{ $u->id }}', {
                                    title: 'Hapus ulasan?',
                                    text: 'Ulasan ini akan dihapus.',
                                    confirmText: 'Ya, hapus'
                                })">
                            <i class="fa-solid fa-trash me-1"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-3">
                @if($komentar !== '')
                    <div class="text-muted fst-italic">
                        “{{ $komentar }}”
                    </div>
                @else
                    <div class="text-muted">
                        (Tidak ada komentar)
                    </div>
                @endif
            </div>

        </div>
    </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-chat-square-text fs-2 d-block mb-2 text-muted"></i>
                <div class="fw-semibold">Belum ada ulasan</div>
                <div class="text-muted">Kamu belum menambahkan ulasan pada produk mana pun.</div>
            </div>
        </div>
    @endforelse

    {{-- PAGINATION --}}
    <div class="d-flex justify-content-center mt-4">
        {{ $ulasans->withQueryString()->links('pagination::bootstrap-5') }}
    </div>

</div>
@endsection
