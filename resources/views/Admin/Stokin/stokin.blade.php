@extends('layouts.Admin.mainlayout')
@section('title', 'Stok In')

@push('styles')
<style>
    .stock-card { border-radius: 20px; }

    .preview-box {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        height: 100%;
    }

    .preview-image-wrapper {
        width: 220px;
        height: 220px;
        border-radius: 20px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 15px 35px rgba(0,0,0,.08);
        margin: auto;
    }

    .preview-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: .3s ease;
    }

    .preview-image-wrapper:hover img { transform: scale(1.05); }

    .stock-badge {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 50px;
    }

    .form-control, .form-select {
        border-radius: 12px;
        padding: 10px 14px;
    }

    .btn-stock {
        border-radius: 12px;
        padding: 12px;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    <!-- HEADER -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-box-arrow-in-down fs-5 text-success"></i>
            <h4 class="fw-semibold mb-0">Stok In Produk</h4>
        </div>
        <small class="text-muted ms-4">Tambahkan stok ke varian produk</small>
    </div>


    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold mb-2">Periksa input:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card stock-card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <div class="row g-5 align-items-center">

                <!-- ================= LEFT ================= -->
                <div class="col-lg-5">
                    <div class="preview-box">

                        <div class="preview-image-wrapper mb-3">
                            <img id="previewImage"
                                 src="https://via.placeholder.com/300x300?text=Pilih+Varian"
                                 alt="Preview">
                        </div>

                        <h6 id="previewName" class="fw-semibold mb-2">Pilih Varian</h6>

                        <div id="previewStockWrapper">
                            <span id="previewStock" class="badge bg-secondary-subtle text-dark stock-badge">
                                Stok: -
                            </span>
                        </div>

                    </div>
                </div>

                <!-- ================= RIGHT ================= -->
                <div class="col-lg-7">

                    <form id="stokForm" action="{{ route('admin.stokin.store') }}" method="POST">
                        @csrf

                        {{-- DROPDOWN KATEGORI --}}
                        <div class="mb-4">
                            <label class="form-label">Kategori</label>
                            <select id="kategoriSelect" class="form-select">
                                <option value="">-- Semua Kategori --</option>
                                @foreach ($kategoris as $kat)
                                    <option value="{{ $kat->id }}">
                                        {{ $kat->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Pilih kategori untuk memfilter varian.</div>
                        </div>

                        {{-- DROPDOWN VARIAN --}}
                        <div class="mb-4">
                            <label class="form-label">Pilih Varian Produk</label>
                            <select name="varian_id" id="varianSelect" class="form-select" required>
                                <option value="">-- Pilih Varian --</option>

                                @foreach ($produks as $produk)
                                    @foreach ($produk->varians ?? [] as $varian)
                                        <option value="{{ $varian->id }}"
                                            data-kategori="{{ $produk->kategori_id ?? '' }}"
                                            data-image="{{
                                                $varian->gambar_varian
                                                    ? asset('storage/'.$varian->gambar_varian)
                                                    : asset('storage/'.$produk->gambar_produk)
                                            }}"
                                            data-name="{{ $produk->nama_produk }} - {{ $varian->warna }} {{ $varian->ukuran }}"
                                            data-stock="{{ $varian->stok }}">
                                            {{ $produk->nama_produk }} -
                                            {{ $varian->warna }} {{ $varian->ukuran }}
                                            (Stok: {{ $varian->stok }})
                                        </option>
                                    @endforeach
                                @endforeach

                            </select>
                        </div>

                        {{-- JUMLAH --}}
                        <div class="mb-4">
                            <label class="form-label">Jumlah Stok</label>
                            <input type="number"
                                   name="jumlah"
                                   class="form-control"
                                   min="1"
                                   placeholder="Masukkan jumlah stok"
                                   required>
                        </div>

                        <div class="d-grid">
                            <button type="button"
                                    class="btn btn-success btn-stock"
                                    data-bs-toggle="modal"
                                    data-bs-target="#confirmModal">
                                <i class="bi bi-plus-circle me-1"></i>
                                Tambah Stok
                            </button>
                        </div>

                    </form>

                </div>

            </div>
        </div>
    </div>

</div>

<!-- ================= MODAL CONFIRM ================= -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">Konfirmasi Tambah Stok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menambahkan stok ke varian ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" class="btn btn-success"
                        onclick="document.getElementById('stokForm').submit();">
                    Ya, Tambah
                </button>
            </div>
        </div>
    </div>
</div>

<script>
  const kategoriSelect = document.getElementById('kategoriSelect');
  const varianSelect   = document.getElementById('varianSelect');

  const previewImage = document.getElementById('previewImage');
  const previewName  = document.getElementById('previewName');
  const previewStock = document.getElementById('previewStock');

  const resetPreview = () => {
    previewImage.src = "https://via.placeholder.com/300x300?text=Pilih+Varian";
    previewName.innerText = "Pilih Varian";
    previewStock.innerText = "Stok: -";
    previewStock.className = "badge bg-secondary-subtle text-dark stock-badge";
  };

  const applyKategoriFilter = () => {
    const kat = kategoriSelect.value;

    // reset pilihan varian
    varianSelect.value = "";
    resetPreview();

    Array.from(varianSelect.options).forEach((opt, idx) => {
      if (idx === 0) return; // placeholder

      const optKat = opt.getAttribute('data-kategori') || "";
      opt.hidden = !(kat === "" || optKat === kat);
    });
  };

  const updatePreview = () => {
    const opt = varianSelect.options[varianSelect.selectedIndex];
    if (!opt || !opt.value) return resetPreview();

    const imageUrl = opt.getAttribute('data-image');
    const name     = opt.getAttribute('data-name');
    const stockRaw = opt.getAttribute('data-stock');

    if (imageUrl) previewImage.src = imageUrl;
    previewName.innerText = name || "Pilih Varian";

    const stock = stockRaw !== null ? parseInt(stockRaw, 10) : null;

    if (stock !== null && !Number.isNaN(stock)) {
      previewStock.innerText = "Stok Saat Ini: " + stock + " pcs";

      if (stock <= 5) {
        previewStock.className = "badge bg-danger-subtle text-danger stock-badge";
      } else {
        previewStock.className = "badge bg-success-subtle text-success stock-badge";
      }
    } else {
      resetPreview();
    }
  };

  if (kategoriSelect) kategoriSelect.addEventListener('change', applyKategoriFilter);
  if (varianSelect) varianSelect.addEventListener('change', updatePreview);

  // init
  if (kategoriSelect) applyKategoriFilter();
</script>
@endsection
