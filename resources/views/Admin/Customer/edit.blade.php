@extends('layouts.Admin.mainlayout')
@section('title', 'Edit Customer')

@section('content')
<div class="container-fluid px-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="fw-semibold mb-0">
                <i class="bi bi-pencil-square me-2 text-primary"></i>
                Edit Customer
            </h5>
            <small class="text-muted">Ubah data customer</small>
        </div>

        <a href="{{ url('/admin/customer') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:18px;">
        <div class="card-body p-4">

            <form method="POST" action="{{ route('admin.customer.update', $customer->id) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama</label>
                        <input type="text"
                               name="name"
                               value="{{ old('name', $customer->name) }}"
                               class="form-control @error('name') is-invalid @enderror"
                               style="border-radius:12px;">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', $customer->email) }}"
                               class="form-control @error('email') is-invalid @enderror"
                               style="border-radius:12px;">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">No. Telepon</label>
                        <input type="text"
                               id="no_telp"
                               name="no_telp"
                               value="{{ old('no_telp', $customer->no_telp) }}"
                               class="form-control @error('no_telp') is-invalid @enderror"
                               inputmode="numeric"
                               pattern="[0-9]*"
                               autocomplete="tel"
                               style="border-radius:12px;">
                        @error('no_telp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="is_active"
                                   id="is_active"
                                   {{ old('is_active', ($customer->is_active ?? 1)) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">
                                Aktif
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-dark px-4">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                    <a href="{{ url('/admin/customer') }}" class="btn btn-outline-secondary px-4">Batal</a>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
  (function () {
    const telp = document.getElementById('no_telp');
    if (!telp) return;

    const digitsOnly = (v) => (v || '').replace(/\D/g, '');
    telp.addEventListener('input', (e) => {
      const clean = digitsOnly(e.target.value);
      if (e.target.value !== clean) e.target.value = clean;
    });
  })();
</script>
@endpush
