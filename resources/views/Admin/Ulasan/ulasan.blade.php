@extends('layouts.Admin.mainlayout')

@section('title', 'Data Ulasan (Admin)')

@push('styles')
<style>
    :root {
        --border: rgba(0, 0, 0, .08);
        --muted: #6c757d;
        --radius-card: 16px;
        --radius-btn: 12px;
        --radius-pill: 999px;
    }

    .card {
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-card);
    }

    .btn {
        border-radius: var(--radius-btn);
    }

    .page-title { font-weight: 900; margin: 0; }
    .page-sub { color: var(--muted); font-size: .9rem; margin-top: 4px; }

    .table th, .table td { vertical-align: middle; }
    .table thead th {
        font-size: .8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
        border-bottom: 1px solid var(--border);
        padding: 12px;
    }
    .table tbody td { padding: 12px; }
    .table tbody tr:hover { background: rgba(0,0,0,.015); }

    .badge-pill {
        border-radius: var(--radius-pill);
        padding: .35rem .65rem;
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
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h4 class="page-title">Data Ulasan</h4>
            <div class="page-sub">Kelola ulasan produk dari pelanggan.</div>
        </div>

        <a href="{{ route('admin.ulasans.trash') }}" class="btn btn-outline-secondary">
            <i class="bi bi-trash3 me-1"></i> Lihat Trash
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width:70px;">No</th>
                            <th class="text-start">User</th>
                            <th class="text-start">Produk</th>
                            <th>Rating</th>
                            <th class="text-start">Komentar</th>
                            <th style="width:130px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">
                        @forelse($ulasans as $i => $u)
                        <tr>
                            <td>{{ $ulasans->firstItem() + $i }}</td>

                            <td class="text-start fw-semibold">
                                {{ $u->user?->name ?? '-' }}
                            </td>

                            <td class="text-start">
                                {{ $u->produk?->nama_produk ?? '-' }}
                            </td>

                            <td>
                                <span class="badge bg-warning text-dark badge-pill">
                                    ⭐ {{ $u->rating ?? 0 }}/5
                                </span>
                            </td>

                            <td class="text-start">
                                {{ \Illuminate\Support\Str::limit($u->komentar, 100) }}
                            </td>

                            <td>
                                <form id="trashUlasan-{{ $u->id }}"
                                      action="{{ route('admin.ulasans.destroy', $u->id) }}"
                                      method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-warning btn-sm"
                                        onclick="return confirmDeleteForm('trashUlasan-{{ $u->id }}', {
                                            title: 'Pindahkan ke trash?',
                                            text: 'Ulasan akan dipindahkan ke trash.',
                                            confirmText: 'Ya, pindahkan',
                                            confirmColor: '#f59e0b'
                                        })">
                                        Trash
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-5 text-center text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                Belum ada ulasan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top">
                {{ $ulasans->withQueryString()->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>
</div>
@endsection
