@extends('layouts.mainlayout')

@section('title', 'Profil Saya')

@php
  $tab = request('tab', 'akun');
  $isAkun = $tab === 'akun';

  $isPassword = $tab === 'password';

  // Fortify biasanya pakai error bag:
  // - updateProfileInformation
  // - updatePassword
@endphp

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">

      {{-- Header --}}
      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <div>
          <h3 class="fw-bold mb-1">Profil Saya</h3>
          <div class="text-muted">Kelola akun dan keamanan password.</div>
        </div>
        <a href="{{ route('home') }}" class="btn btn-outline-secondary">
          <i class="fa fa-arrow-left me-1"></i> Kembali
        </a>
      </div>

      {{-- Global success --}}
      @if (session('success'))
        <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>{{ session('success') }}</div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      {{-- Email verification banner --}}
      @if (auth()->user() instanceof Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            Email <span class="fw-semibold">{{ auth()->user()->email }}</span> belum terverifikasi.
            <span class="d-block small text-muted">Cek inbox/spam, atau kirim ulang verifikasi.</span>
          </div>
          <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-sm btn-warning">Kirim ulang</button>
          </form>
        </div>
      @endif

      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">

          {{-- Tabs --}}
          <div class="border-bottom px-3 px-md-4 pt-3">
            <ul class="nav nav-pills gap-2 pb-3">
              <li class="nav-item">
                <a class="nav-link {{ $isAkun ? 'active' : '' }}"
                   href="{{ route('user.profile.edit', ['tab' => 'akun']) }}">
                  <i class="fa-solid fa-user me-2"></i> Akun
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link {{ $isPassword ? 'active' : '' }}"
                   href="{{ route('user.profile.edit', ['tab' => 'password']) }}">
                  <i class="fa-solid fa-lock me-2"></i> Password
                </a>
              </li>
            </ul>
          </div>

          <div class="p-3 p-md-4">

            {{-- TAB: AKUN --}}
            @if($isAkun)
              <div class="mb-3">
                <h5 class="fw-bold mb-1">Informasi Akun</h5>
                <div class="text-muted small">Ubah nama, email, dan nomor telepon.</div>
              </div>

              {{-- Error bag khusus profile --}}
              @if ($errors->updateProfileInformation->any())
                <div class="alert alert-danger">
                  <div class="fw-semibold mb-1">Periksa kembali data akun:</div>
                  <ul class="mb-0">
                    @foreach ($errors->updateProfileInformation->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              <form action="{{ route('user.profile.update') }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                  <label class="form-label">Nama</label>
                  <input type="text"
                         class="form-control @error('name','updateProfileInformation') is-invalid @enderror"
                         name="name" value="{{ old('name', $user->name) }}" required>
                  @error('name','updateProfileInformation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label">No. Telepon</label>
                  <input type="text"
                         id="no_telp"
                         class="form-control @error('no_telp','updateProfileInformation') is-invalid @enderror"
                         name="no_telp"
                         value="{{ old('no_telp', $user->no_telp) }}"
                         inputmode="numeric"
                         pattern="[0-9]*"
                         autocomplete="tel"
                         required>
                  @error('no_telp','updateProfileInformation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                  <label class="form-label">Email</label>
                  <input type="email"
                         class="form-control @error('email','updateProfileInformation') is-invalid @enderror"
                         name="email" value="{{ old('email', $user->email) }}" required>
                  @error('email','updateProfileInformation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  <div class="form-text">
                    Jika kamu mengganti email, sistem akan meminta verifikasi ulang ke email baru.
                  </div>
                </div>

                <div class="col-12 d-flex justify-content-end">
                  <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i> Simpan
                  </button>
                </div>
              </form>
            @endif

            {{-- TAB: PASSWORD --}}
            @if($isPassword)
              <div class="mb-3">
                <h5 class="fw-bold mb-1">Ubah Password</h5>
                <div class="text-muted small">Gunakan password kuat agar akun aman.</div>
              </div>

              {{-- Error bag khusus password --}}
              @if ($errors->updatePassword->any())
                <div class="alert alert-danger">
                  <div class="fw-semibold mb-1">Periksa kembali password:</div>
                  <ul class="mb-0">
                    @foreach ($errors->updatePassword->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              <form action="{{ route('user.profile.password.update') }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                  <label class="form-label">Password Saat Ini</label>
                  <div class="input-group">
                    <input type="password"
                           id="current_password"
                           class="form-control @error('current_password','updatePassword') is-invalid @enderror"
                           name="current_password" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary" data-toggle-password="current_password">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                  </div>
                  @error('current_password','updatePassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6"></div>

                <div class="col-md-6">
                  <label class="form-label">Password Baru</label>
                  <div class="input-group">
                    <input type="password"
                           id="password"
                           class="form-control @error('password','updatePassword') is-invalid @enderror"
                           name="password" required autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary" data-toggle-password="password">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                  </div>
                  @error('password','updatePassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label">Konfirmasi Password Baru</label>
                  <div class="input-group">
                    <input type="password"
                           id="password_confirmation"
                           class="form-control"
                           name="password_confirmation" required autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary" data-toggle-password="password_confirmation">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                  </div>
                </div>

                <div class="col-12 d-flex justify-content-end">
                  <button type="submit" class="btn btn-primary">
                    <i class="fa fa-key me-1"></i> Ubah Password
                  </button>
                </div>
              </form>
            @endif

          </div>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function () {
    const telp = document.getElementById('no_telp');
    if (telp) {
      const digitsOnly = (v) => (v || '').replace(/\D/g, '');

      telp.addEventListener('input', (e) => {
        const clean = digitsOnly(e.target.value);
        if (e.target.value !== clean) e.target.value = clean;
      });
    }

    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-toggle-password');
        const input = document.getElementById(targetId);
        const icon = btn.querySelector('i');
        if (!input || !icon) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
      });
    });
  })();
</script>
@endpush
