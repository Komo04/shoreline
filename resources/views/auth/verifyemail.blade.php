@extends('layouts.mainlayout')

@section('title', 'Verifikasi Email')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">
          <h3 class="fw-bold mb-2">Verifikasi Email</h3>
          <p class="text-muted">
            Kami sudah mengirim link verifikasi ke email kamu. Silakan cek inbox/spam, lalu klik link verifikasinya.
          </p>

          @if (session('status') === 'verification-link-sent')
            <div class="alert alert-success">
              Link verifikasi baru sudah dikirim ke email kamu.
            </div>
          @endif

          <div class="d-flex flex-column gap-2">
            <form method="POST" action="{{ route('verification.send') }}">
              @csrf
              <button type="submit" class="btn btn-primary w-100">
                Kirim ulang email verifikasi
              </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="btn btn-outline-secondary w-100">
                Logout
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
