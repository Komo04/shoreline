@extends('layouts.Admin.mainlayout')

@section('title', 'Edit Varian Produk')

@push('styles')
<style>
    .card {
        border-radius: 18px;
    }

    .form-control,
    .form-select {
        border-radius: 12px;
        padding: 10px 14px;
    }

    .section-title {
        font-size: 13px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 15px;
        letter-spacing: .5px;
    }

    .image-box {
        height: 260px;
        border-radius: 18px;
        background: #f8f9fa;
        border: 1px dashed #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .image-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    <!-- HEADER -->
    <div class="mb-4">
        <h4 class="fw-semibold mb-1">
            <i class="bi bi-pencil-square text-primary me-2"></i>
            Edit Varian Produk
        </h4>
        <small class="text-muted">
            Perbarui varian untuk produk <strong>{{ $varian->produk->nama_produk }}</strong>
        </small>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.varian.update', $varian->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">

            <!-- DATA VARIAN -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Berat (gram)</label>
                            <input type="number" name="berat_gram" class="form-control" min="1" value="{{ old('berat_gram', $varian->berat_gram ?? 0) }}" placeholder="Contoh: 500" required>
                        </div>

                        <div class="section-title">Informasi Varian</div>

                        <div class="mb-3">
                            <label class="form-label">Warna</label>
                            <input type="text" name="warna" class="form-control" value="{{ old('warna', $varian->warna) }}" placeholder="Contoh: Hitam, Putih" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ukuran</label>
                            <select name="ukuran" class="form-select" required>
                                @foreach (['XS','S','M','L','XL','One Size'] as $size)
                                <option value="{{ $size }}" {{ old('ukuran', $varian->ukuran) == $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" name="stok" class="form-control" value="{{ old('stok', $varian->stok) }}" placeholder="Contoh: 15" required>
                        </div>

                    </div>
                </div>
            </div>

            <!-- GAMBAR VARIAN -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="section-title">Foto Varian</div>

                        <div class="image-box mb-3">
                            @if($varian->gambar_varian)
                            <img id="previewVarian" src="{{ asset('storage/'.$varian->gambar_varian) }}">
                            @else
                            <img id="previewVarian" class="d-none">
                            <span id="previewVarianText" class="text-muted">
                                Tidak ada gambar
                            </span>
                            @endif
                        </div>

                        <label class="form-label">Ganti Gambar</label>
                        <input type="file" name="gambar_varian" class="form-control" accept="image/*" onchange="previewVarianImage(this)">

                        <small class="text-muted">
                            Kosongkan jika tidak ingin mengganti
                        </small>

                    </div>
                </div>
            </div>

        </div>

        <!-- ACTION -->
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.varian.index', $varian->produk_id) }}" class="btn btn-light px-4">
                Kembali
            </a>
            <button class="btn btn-success px-4">
                Simpan Perubahan
            </button>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
    function previewVarianImage(input) {
        const img = document.getElementById('previewVarian');
        const text = document.getElementById('previewVarianText');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                img.classList.remove('d-none');
                if (text) text.classList.add('d-none');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

</script>
@endpush
