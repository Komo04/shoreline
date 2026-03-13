@extends('layouts.mainlayout')
@section('title', 'Produk')
@section('content')

<!-- CONTACT SECTION -->
<section class="py-5">
    <div class="container">

        <!-- TITLE -->
        <div class="text-center mb-5">
            <h1 class="fw-bold">Hubungi Kami</h1>
        </div>

        <div class="row g-5">

            <!-- LEFT INFO -->
            <div class="col-lg-4">
                <div class="mb-4">
                    <h6 class="fw-semibold mb-1">
                        <i class="fa-solid fa-location-dot me-2"></i>Alamat
                    </h6>
                    <p class="text-muted mb-0">
                        Jl. Arjuna (Double Six), Legian, Kuta,<br>
                        Badung, Bali
                    </p>
                </div>

                <div class="mb-4">
                    <h6 class="fw-semibold mb-1">
                        <i class="fa-solid fa-phone me-2"></i>Telepon
                    </h6>
                    <p class="text-muted mb-0">
                        +62 133-8187-599<br>
                        +62 881-0380-33616
                    </p>
                </div>

                <div>
                    <h6 class="fw-semibold mb-1">
                        <i class="fa-solid fa-clock me-2"></i>Jam Operasional
                    </h6>
                    <p class="text-muted mb-0">
                        Senin - Jumat : 09.00 - 19.00<br>
                        Sabtu - Minggu : 09.00 - 17.00
                    </p>
                </div>
            </div>

            <!-- RIGHT FORM -->
            <div class="col-lg-8">
                <form method="POST" action="{{ route('kontak.store') }}">
                    @csrf

                    @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" placeholder="Nama Anda" value="{{ old('nama') }}">
                        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="email@example.com" value="{{ old('email') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subjek</label>
                        <input type="text" name="subjek" class="form-control @error('subjek') is-invalid @enderror" placeholder="Subjek pesan" value="{{ old('subjek') }}">
                        @error('subjek') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Pesan</label>
                        <textarea rows="4" name="pesan" class="form-control @error('pesan') is-invalid @enderror" placeholder="Tulis pesan Anda">{{ old('pesan') }}</textarea>
                        @error('pesan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-success px-5">
                        Kirim Pesan
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

@endsection
