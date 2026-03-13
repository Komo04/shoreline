@extends('layouts.Admin.mainlayout')
@section('title', 'Tambah Kategori')

@push('styles')
<style>
    .card {
        border-radius: 18px;
    }

    .form-control {
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
        position: relative;
    }

    .image-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #fff;
    }

    .image-box span {
        color: #adb5bd;
        font-size: 14px;
    }

</style>
@endpush


@section('content')
<div class="container-fluid px-4">

    <!-- HEADER -->
    <div class="mb-4">
        <h4 class="fw-semibold mb-1">
            <i class="fas fa-folder-plus text-primary me-2"></i>
            Tambah Kategori
        </h4>
        <small class="text-muted">Tambahkan kategori baru untuk produk fashion</small>
    </div>

    <form method="POST" action="{{ route('admin.kategori.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">

            <!-- DATA -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="section-title">Informasi Kategori</div>

                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Outerwear Wanita" required>
                        </div>

                    </div>
                </div>
            </div>

            <!-- GAMBAR -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="section-title">Gambar Kategori</div>

                        <div class="image-box mb-3">
                            <img id="previewKategori" class="d-none">
                            <span id="previewText">Preview gambar kategori</span>
                        </div>

                        <label class="form-label">Upload Gambar</label>
                        <input type="file" name="gambar" class="form-control" accept="image/*" onchange="previewImage(this)" required>

                        <small class="text-muted">
                            Disarankan rasio 1:1 (persegi)
                        </small>

                    </div>
                </div>
            </div>

        </div>

        <!-- BUTTON -->
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.kategori.index') }}" class="btn btn-light px-4">
                Kembali
            </a>
            <button type="submit" class="btn btn-success px-4">
                Simpan Kategori
            </button>
        </div>

    </form>

</div>
@endsection


@push('scripts')
<script>
    function previewImage(input) {
        const preview = document.getElementById('previewKategori');
        const text = document.getElementById('previewText');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                if (text) text.classList.add('d-none');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

</script>
@endpush
