@extends('layouts.mainlayout')

@section('content')
<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <h4 class="fw-bold mb-4 text-center">
                        Edit Ulasan
                    </h4>

                    <form action="{{ route('user.ulasans.update', $ulasan->id) }}"
                          method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Rating -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Rating
                            </label>

                            <select name="rating"
                                    class="form-select @error('rating') is-invalid @enderror">
                                @for ($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}"
                                        {{ old('rating', $ulasan->rating) == $i ? 'selected' : '' }}>
                                        {{ $i }} Bintang
                                    </option>
                                @endfor
                            </select>

                            @error('rating')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Komentar -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Komentar
                            </label>

                            <textarea name="komentar"
                                      rows="4"
                                      class="form-control @error('komentar') is-invalid @enderror"
                                      placeholder="Tulis ulasan Anda...">{{ old('komentar', $ulasan->komentar) }}</textarea>

                            @error('komentar')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Tombol -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('user.ulasans.index') }}"
                               class="btn btn-outline-secondary">
                                ← Kembali
                            </a>

                            <button class="btn btn-success px-4">
                                <i class="fa-solid fa-check me-1"></i> Simpan
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection
