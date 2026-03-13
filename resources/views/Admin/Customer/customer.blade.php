@extends('layouts.Admin.mainlayout')
@section('title', 'Customer Management')
@push('styles')
<style>
    .customer-card {
        border: 0;
        border-radius: 24px;
        box-shadow: 0 20px 45px rgba(17, 24, 39, 0.06);
        overflow: hidden;
    }

    .customer-toolbar {
        background: linear-gradient(135deg, #ffffff 0%, #fbfbfa 100%);
    }

    .customer-title {
        font-size: 1.15rem;
        font-weight: 600;
        color: #171717;
    }

    .customer-search {
        min-width: 280px;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: inset 0 1px 2px rgba(17, 24, 39, 0.03);
    }

    .customer-search:focus {
        border-color: #111827;
        box-shadow: 0 0 0 0.15rem rgba(17, 24, 39, 0.08);
    }

    .customer-table {
        --bs-table-hover-bg: #fbfbfb;
        margin-bottom: 0;
    }

    .customer-table thead th {
        padding: 1rem 1.1rem;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .08em;
        white-space: nowrap;
        background: #fcfcfc;
        border-bottom: 1px solid #eceff3;
    }

    .customer-table tbody td {
        padding: 1rem 1.1rem;
        vertical-align: middle;
        font-size: 14px;
        border-bottom: 1px solid #f1f3f5;
    }

    .customer-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .customer-number {
        font-weight: 700;
        color: #111827;
    }

    .customer-name {
        font-weight: 600;
        color: #111827;
        margin-bottom: .2rem;
    }

    .customer-email {
        color: #6b7280;
        font-size: 13px;
        word-break: break-word;
    }

    .tel-badge,
    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: .48rem .85rem;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .tel-badge {
        background: #f3f4f6;
        color: #374151;
    }

    .status-badge.is-active {
        background: #ecfdf3;
        color: #047857;
    }

    .status-badge.is-inactive {
        background: #fff1f2;
        color: #be123c;
    }

    .role-wrapper {
        background: #f9fafb;
        padding: 6px;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        min-width: 205px;
    }

    .role-icon {
        width: 28px;
        height: 28px;
        background: #e5e7eb;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #374151;
        font-size: 14px;
    }

    .role-select {
        border-radius: 10px;
        min-width: 120px;
        border: 1px solid #d1d5db;
        background-color: #fff;
    }

    .role-save {
        border-radius: 10px;
        padding: 6px 10px;
        line-height: 1;
    }

    .action-stack {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        justify-content: center;
    }

    .action-stack .btn {
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        padding: .55rem .8rem;
    }

    .empty-state {
        padding: 3rem 1.5rem;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 2rem;
        color: #9ca3af;
    }

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
        transition: .2s;
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

    @media (max-width: 991px) {
        .customer-toolbar .card-body {
            flex-direction: column;
            align-items: stretch !important;
        }

        .customer-toolbar form {
            width: 100%;
            flex-direction: column;
        }

        .customer-search-wrap,
        .customer-search {
            width: 100%;
            min-width: 0;
        }

        .role-wrapper {
            min-width: 0;
            width: 100%;
        }

        .action-stack {
            justify-content: flex-start;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    {{-- Header --}}
    <div class="card customer-card customer-toolbar mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="customer-title mb-1">
                    <i class="bi bi-people me-2 text-primary"></i>
                    Data Customer
                </h5>
                <small class="text-muted">Kelola akun customer, status, dan peran dengan lebih cepat.</small>
            </div>

            <form method="GET" action="{{ url('/admin/customer') }}" class="d-flex gap-2">
                <div class="position-relative customer-search-wrap">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control customer-search ps-5" placeholder="Cari nama, email, atau telepon">
                </div>

                <button class="btn btn-dark px-4">
                    <i class="bi bi-search me-1"></i>Cari
                </button>

                @if(request('search'))
                <a href="{{ url('/admin/customer') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card customer-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table customer-table table-hover align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th class="text-start">Profil</th>
                            <th>No. Telepon</th>
                            <th width="18%">Role</th>
                            <th width="12%">Status</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">
                        @forelse ($customers as $customer)
                        <tr>
                            <td class="customer-number">
                                {{ $loop->iteration + ($customers->currentPage()-1) * $customers->perPage() }}
                            </td>

                            <td class="text-start">
                                <div class="customer-name">{{ $customer->name }}</div>
                                <div class="customer-email">{{ $customer->email }}</div>
                            </td>

                            <td>
                                <span class="tel-badge">
                                    {{ $customer->no_telp ?? '-' }}
                                </span>
                            </td>

                            {{-- Role --}}
                            <td>
                                <form method="POST" action="{{ route('admin.customer.role', $customer->id) }}" class="d-flex justify-content-center align-items-center">
                                    @csrf
                                    @method('PATCH')

                                    <div class="role-wrapper d-flex align-items-center gap-2">

                                        {{-- Icon --}}
                                        <div class="role-icon">
                                            <i class="bi bi-shield-check"></i>
                                        </div>

                                        {{-- Select --}}
                                        <select name="user_role" class="form-select form-select-sm role-select">
                                            <option value="customer" {{ $customer->user_role === 'customer' ? 'selected' : '' }}>
                                                Customer
                                            </option>
                                            <option value="admin" {{ $customer->user_role === 'admin' ? 'selected' : '' }}>
                                                Admin
                                            </option>
                                        </select>

                                        {{-- Save --}}
                                        <button class="btn btn-sm btn-dark role-save" type="submit">
                                            <i class="bi bi-check-lg"></i>
                                        </button>

                                    </div>
                                </form>
                            </td>

                            {{-- Status --}}
                            <td>
                                @if(($customer->is_active ?? 1) == 1)
                                <span class="status-badge is-active">Aktif</span>
                                @else
                                <span class="status-badge is-inactive">Nonaktif</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td>
                                <div class="action-stack">

                                    <a href="{{ route('admin.customer.edit', $customer->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square me-1"></i>Edit
                                    </a>

                                    <form method="POST" action="{{ route('admin.customer.toggle', $customer->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        @if(($customer->is_active ?? 1) == 1)
                                        <button class="btn btn-sm btn-danger">
                                            <i class="bi bi-x-circle me-1"></i>Nonaktifkan
                                        </button>
                                        @else
                                        <button class="btn btn-sm btn-success">
                                            <i class="bi bi-check-circle me-1"></i>Aktifkan
                                        </button>
                                        @endif
                                    </form>

                                    <form method="POST"
                                          action="{{ route('admin.customer.update', $customer->id) }}"
                                          class="js-delete-customer"
                                          data-customer-name="{{ $customer->name }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="intent" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash me-1"></i>Hapus
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="bi bi-inbox d-block mb-2"></i>
                                Data customer belum tersedia
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="p-3">
                    {{ $customers->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-delete-customer').forEach((form) => {
      form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const customerName = form.dataset.customerName || 'customer ini';
        const result = await SwalConfirm({
          title: 'Hapus customer?',
          text: `Data ${customerName} akan dihapus permanen beserta data terkaitnya.`,
          confirmText: 'Ya, hapus',
          cancelText: 'Batal',
          confirmColor: '#dc3545',
        });

        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
@endpush
