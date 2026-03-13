@extends('layouts.Admin.mainlayout')

@section('title', 'Jumlah Stok Produk')

<style>
    .summary-card {
        border-radius: 16px;
        transition: .2s;
    }

    .summary-card:hover {
        transform: translateY(-3px);
    }

    .table th {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: #6c757d;
    }

    .table td {
        font-size: 14px;
        vertical-align: middle;
    }

    .low-stock-row {
        background-color: #fff5f5;
    }

    .search-box {
        max-width: 320px;
    }

    /* ===== Pagination Styling ===== */
    .pagination {
        margin: 0;
        padding-left: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
    }

    .pagination .page-item .page-link {
        border-radius: 10px;
        padding: 6px 14px;
        border: 1px solid #ddd;
        color: #333;
        font-weight: 500;
        transition: 0.2s;
    }

    .pagination .page-item .page-link:hover {
        background-color: #212529;
        color: #fff;
        border-color: #212529;
    }

    .pagination .page-item.active .page-link {
        background-color: #212529;
        border-color: #212529;
        color: #fff;
    }

    .pagination .page-item.disabled .page-link {
        background-color: #f1f1f1;
        color: #aaa;
        border-color: #ddd;
    }
</style>

@section('content')
<div class="container-fluid px-4">

    {{-- ================= SUMMARY CARDS ================= --}}
    @php
        // Total Produk (semua data) kalau $produks adalah paginator:
        $totalProduk = method_exists($produks, 'total') ? $produks->total() : $produks->count();

        // Ini tetap menghitung data di halaman ini (karena varians sudah diload untuk item halaman)
        $totalVarian = $produks->sum(fn($p) => $p->varians->count());

        // Jika controller pakai withSum('varians as total_stok','stok') maka gunakan total_stok
        $totalStok = $produks->sum(fn($p) => $p->total_stok ?? $p->varians->sum('stok'));
    @endphp

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 summary-card">
                <div class="card-body">
                    <small class="text-muted">Total Produk</small>
                    <h4 class="fw-bold mb-0">{{ $totalProduk }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 summary-card">
                <div class="card-body">
                    <small class="text-muted">Total Varian (Halaman Ini)</small>
                    <h4 class="fw-bold mb-0">{{ $totalVarian }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 summary-card">
                <div class="card-body">
                    <small class="text-muted">Total Stok (Halaman Ini)</small>
                    <h4 class="fw-bold mb-0">{{ $totalStok }}</h4>
                </div>
            </div>
        </div>
    </div>


    {{-- ================= HEADER + ACTION ================= --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="input-group search-box">
            <span class="input-group-text bg-white">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari produk...">
        </div>

        <button onclick="exportTableToExcel()" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </button>
    </div>


    {{-- ================= TABLE ================= --}}
    <div class="card border-0 shadow-sm" style="border-radius:18px;">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="stokTable">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th>Stok Varian</th>
                            <th>Total Stok</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">
                        @forelse ($produks as $produk)

                            @php
                                $totalStokProduk = $produk->total_stok ?? $produk->varians->sum('stok');
                                $jumlahVarian = $produk->varians->count();
                                $lowStock = $totalStokProduk <= 5;
                            @endphp

                            @foreach ($produk->varians as $index => $varian)
                            <tr class="{{ $lowStock ? 'low-stock-row' : '' }}">

                                @if ($index == 0)
                                <td rowspan="{{ $jumlahVarian }}"
                                    class="align-middle text-start fw-semibold product-name">
                                    {{ $produk->nama_produk }}
                                </td>
                                @endif

                                <td>
                                    {{ $varian->warna }} - {{ $varian->ukuran }}
                                </td>

                                <td>
                                    {{ $varian->stok }}
                                </td>

                                @if ($index == 0)
                                <td rowspan="{{ $jumlahVarian }}"
                                    class="align-middle fw-bold">
                                    {{ $totalStokProduk }}
                                </td>
                                @endif

                            </tr>
                            @endforeach

                        @empty
                        <tr>
                            <td colspan="4" class="py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                Tidak ada data stok produk
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                </table>

                {{-- PAGINATION (seperti contoh kamu) --}}
                <div class="p-3">
                    {{ $produks->withQueryString()->links('pagination::bootstrap-5') }}
                </div>

            </div>

        </div>
    </div>

</div>


{{-- ================= SCRIPT ================= --}}
<script>
// 🔎 SEARCH (hanya filter tabel di halaman aktif)
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#stokTable tbody tr");

    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});

// 📥 EXPORT EXCEL (simple HTML export)
function exportTableToExcel() {
    let table = document.getElementById("stokTable");
    let html = table.outerHTML;
    let blob = new Blob([html], { type: "application/vnd.ms-excel" });
    let url = window.URL.createObjectURL(blob);

    let a = document.createElement("a");
    a.href = url;
    a.download = "stok_produk.xls";
    a.click();
}
</script>

@endsection
