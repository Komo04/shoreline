@extends('layouts.Admin.mainlayout')

@section('title', 'Tambah Varian Produk')

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
        position: relative;
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
            <i class="fas fa-layer-group text-primary me-2"></i>
            Tambah Varian Produk
        </h4>
        <small class="text-muted">
            Tambahkan varian untuk produk <strong>{{ $produk->nama_produk }}</strong>
        </small>
    </div>

    {{-- Alert error --}}
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.varian.store', $produk->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">

            <!-- DATA VARIAN -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <div class="section-title">Informasi Varian</div>

                        <div class="mb-3">
                            <label class="form-label">Berat (gram)</label>
                            <input type="number" name="berat_gram" class="form-control" min="1" value="{{ old('berat_gram', 200) }}" placeholder="Contoh: 500" required>
                            <small class="text-muted">Wajib diisi untuk hitung ongkir.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Warna</label>
                            <input type="text" name="warna" class="form-control" placeholder="Contoh: Hitam, Putih, Beige" value="{{ old('warna') }}" required>
                        </div>

                        @php
                        $sizes = ['XS','S','M','L','XL','One Size'];
                        @endphp

                        <div class="mb-3">
                            <label class="form-label">Stok per Ukuran</label>

                            <div class="d-flex gap-2 mb-3">
                                <button type="button" class="btn btn-sm btn-outline-dark" onclick="checkAllSizes(true)">
                                    Centang Semua Size
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="checkAllSizes(false)">
                                    Batal Centang
                                </button>
                            </div>

                            <div class="row g-2">
                                @foreach($sizes as $size)
                                <div class="col-12 col-md-6">
                                    <div class="border rounded p-2 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="checkbox" class="size-check" data-size="{{ $size }}" name="sizes[{{ $size }}][enabled]" value="1" {{ old("sizes.$size.enabled") ? 'checked' : '' }}>
                                            <strong>{{ $size }}</strong>
                                        </div>

                                        <div style="width: 160px;">
                                            <input type="number" class="form-control size-stok" data-size="{{ $size }}" name="sizes[{{ $size }}][stok]" min="0" placeholder="Stok" value="{{ old("sizes.$size.stok") }}">

                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <small class="text-muted d-block mt-2">
                                Centang ukuran yang ingin dibuat, lalu isi stoknya (boleh 0).
                            </small>
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
                            <img id="previewImage" class="d-none" alt="Preview">
                            <span id="previewText" class="text-muted">Preview gambar varian</span>
                        </div>

                        <label class="form-label">Upload Gambar</label>
                        <input type="file" name="gambar_varian" class="form-control" accept="image/*" onchange="previewVarianImage(this)">

                        <small class="text-muted">
                            Format JPG / PNG, max 2MB
                        </small>

                    </div>
                </div>
            </div>

        </div>

        <!-- ACTION -->
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.varian.index', $produk->id) }}" class="btn btn-light px-4">
                Kembali
            </a>
            <button class="btn btn-success px-4" type="submit">
                Simpan Varian
            </button>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
    function previewVarianImage(input) {
        const preview = document.getElementById('previewImage');
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

    function checkAllSizes(status) {
        const checks = document.querySelectorAll('.size-check');

        checks.forEach(chk => {
            chk.checked = status;

            const size = chk.dataset.size;
            const stokInput = document.querySelector('.size-stok[data-size="' + size + '"]');

            if (!stokInput) return;

            if (status) {
                if (stokInput.value === '') stokInput.value = 0;
            } else {
                stokInput.value = '';
            }
        });

        syncStockInputs();
    }


    function syncStockInputs() {
        const checks = document.querySelectorAll('.size-check');

        checks.forEach(chk => {
            const size = chk.dataset.size;
            const stokInput = document.querySelector('.size-stok[data-size="' + size + '"]');

            if (!stokInput) return;

            if (chk.checked) {
                stokInput.disabled = false;

                // kalau kosong, isi default 0 (opsional)
                if (stokInput.value === '') stokInput.value = 0;
            } else {
                stokInput.disabled = true;
                stokInput.value = ''; // kosongkan biar tidak ikut terkirim
            }
        });
    }

    // panggil saat checkbox berubah
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('size-check')) {
            syncStockInputs();
        }
    });

    // panggil saat load halaman (biar old() juga ikut)
    document.addEventListener('DOMContentLoaded', function() {
        syncStockInputs();
    });

</script>
@endpush
