@extends('layouts.Admin.mainlayout')
@section('title', 'Edit Kategori')

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
            <i class="fas fa-tags text-primary me-2"></i>
            Edit Kategori
        </h4>
        <small class="text-muted">Perbarui informasi kategori produk</small>
    </div>

    <form method="POST" action="{{ route('admin.kategori.update', $kategori->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">

            <!-- DATA -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="section-title">Informasi Kategori</div>

                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="nama_kategori" value="{{ $kategori->nama_kategori }}" class="form-control" placeholder="Contoh: Jaket Pria" required>
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
                            @if($kategori->gambar)
                            <img id="previewKategori" src="{{ asset('storage/'.$kategori->gambar) }}">
                            @else
                            <img id="previewKategori" class="d-none">
                            <span id="previewText" class="text-muted">Preview gambar</span>
                            @endif
                        </div>

                        <label class="form-label">Ganti Gambar</label>
                        <input type="file" name="gambar" class="form-control" accept="image/*" onchange="previewImage(this)">
                        <small class="text-muted">Kosongkan jika tidak ingin mengganti</small>

                    </div>
                </div>
            </div>

        </div>

        <!-- BUTTON -->
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.kategori.index') }}" class="btn btn-light px-4">
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
