@extends('layouts.Admin.mainlayout')

@section('title', 'Inbox Kontak')

@push('styles')
<style>
    .page-title {
        font-weight: 900;
        margin: 0;
    }

    .page-sub {
        color: #6c757d;
        font-size: .9rem;
        margin-top: 4px;
    }

    .card {
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,.08) !important;
    }

    .table th {
        font-size: .8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    .table td {
        vertical-align: middle;
    }

    .badge-pill {
        border-radius: 999px;
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

    {{-- HEADER --}}
    <div class="mb-4">
        <h4 class="page-title">Inbox Kontak</h4>
        <div class="page-sub">Daftar pesan masuk dari halaman kontak website.</div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Status</th>
                            <th class="text-start">Nama</th>
                            <th class="text-start">Email</th>
                            <th class="text-start">Subjek</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">
                        @forelse($kontaks as $k)
                        <tr class="{{ !$k->dibaca_pada ? 'fw-semibold' : '' }}">

                            <td>
                                @if($k->dibaca_pada)
                                    <span class="badge bg-secondary badge-pill">Dibaca</span>
                                @else
                                    <span class="badge bg-success badge-pill">Baru</span>
                                @endif
                            </td>

                            <td class="text-start">{{ $k->nama }}</td>

                            <td class="text-start text-muted">{{ $k->email }}</td>

                            <td class="text-start">
                                {{ \Illuminate\Support\Str::limit($k->subjek, 40) }}
                            </td>

                            <td>
                                <small>
                                    {{ $k->created_at->format('d M Y') }}
                                    <br>
                                    <span class="text-muted">
                                        {{ $k->created_at->format('H:i') }}
                                    </span>
                                </small>
                            </td>

                            <td>
                                <a href="{{ route('admin.kontak.show', $k->id) }}"
                                   class="btn btn-sm btn-primary">
                                    Lihat
                                </a>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-5 text-center text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                Belum ada pesan masuk.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="p-3 border-top">
                {{ $kontaks->withQueryString()->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>

</div>
@endsection
