@extends('layouts.Admin.mainlayout')
@section('title', 'Tambah Produk')

@push('styles')
<style>
    .card { border-radius: 18px; }
    .form-control, .form-select { border-radius: 12px; padding: 10px 14px; }
    .form-label { font-weight: 500; font-size: 14px; }
    .section-title {
        font-size: 13px; font-weight: 600; color: #6c757d;
        text-transform: uppercase; margin-bottom: 15px; letter-spacing: .5px;
    }
    .image-box {
        height: 260px; border-radius: 18px; background: #f8f9fa;
        border: 1px dashed #dee2e6; display: flex; align-items: center;
        justify-content: center; overflow: hidden; position: relative;
    }
    .image-box img { width: 100%; height: 100%; object-fit: cover; }
    .image-box span { position: absolute; color: #adb5bd; font-size: 14px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    <div class="mb-4">
        <h4 class="fw-semibold mb-1">
            <i class="fas fa-plus-circle text-success me-2"></i>
            Tambah Produk Fashion
        </h4>
        <small class="text-muted">Tambahkan produk pakaian baru ke dalam katalog</small>
    </div>

    <form method="POST" action="{{ route('admin.produk.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="section-title">Informasi Produk</div>

                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" name="nama_produk" value="{{ old('nama_produk') }}"
                                   class="form-control" placeholder="Contoh: Kemeja Linen Pria" required>
                            @error('nama_produk') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi Produk</label>
                            <textarea name="deskripsi_produk" class="form-control" rows="4"
                                      placeholder="Contoh: Bahan linen premium..." required>{{ old('deskripsi_produk') }}</textarea>
                            @error('deskripsi_produk') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="harga" value="{{ old('harga') }}"
                                       class="form-control js-rupiah" inputmode="numeric" autocomplete="off"
                                       placeholder="Contoh: 299.000" required>
                            </div>
                            @error('harga') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2"
                                      placeholder="Contoh: Ready stock..." required>{{ old('keterangan') }}</textarea>
                            @error('keterangan') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kategori Pakaian</label>
                            <select name="kategori_id" class="form-select" required>
                                <option value="">-- Pilih Kategori Pakaian --</option>
                                @foreach ($kategoris as $kategori)
                                    <option value="{{ $kategori->id }}" {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                        {{ $kategori->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                            @error('kategori_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="section-title">Foto Produk</div>

                        <div class="image-box mb-3">
                            <img id="previewProduk" class="d-none">
                            <span id="previewText">Preview gambar produk</span>
                        </div>

                        <input type="file" name="gambar_produk" class="form-control" accept="image/*"
                               onchange="previewImage(this,'previewProduk')" required>

                        @error('gambar_produk') <small class="text-danger">{{ $message }}</small> @enderror

                        <small class="text-muted">Format JPG / PNG • Maks 2MB</small>

                    </div>
                </div>
            </div>

        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.produk.index') }}" class="btn btn-light px-4">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <button class="btn btn-success px-4">
                <i class="fas fa-save me-1"></i> Simpan Produk
            </button>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const text = document.getElementById('previewText');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            if (text) text.classList.add('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<script>
(function () {
    function formatRupiah(val) {
        val = (val || '').toString().replace(/[^\d]/g, '');
        if (!val) return '';
        return val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    document.querySelectorAll('.js-rupiah').forEach(function (input) {
        input.value = formatRupiah(input.value);

        input.addEventListener('input', function () {
            const start = input.selectionStart;
            const before = input.value;

            input.value = formatRupiah(input.value);

            const diff = input.value.length - before.length;
            const pos = Math.max(0, start + diff);
            input.setSelectionRange(pos, pos);
        });

        input.addEventListener('blur', function () {
            input.value = formatRupiah(input.value);
        });
    });
})();
</script>
@endpush
