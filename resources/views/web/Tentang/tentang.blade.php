@extends('layouts.mainlayout')
@section('title', 'Produk')
@section('content')
<div class="container py-5">

    <!-- HEADER -->
    <div class="text-center mb-5">
        <h1 class="fw-bold">Tentang Kami</h1>
    </div>

    <!-- ABOUT SECTION -->
    <div class="row align-items-center mb-5">
        <div class="col-md-6 mb-4 mb-md-0">
            <img src="{{ asset('assets/images/corousel/shoreline.jpeg') }}"
                 class="img-fluid rounded shadow-sm"
                 alt="Tentang Shoreline">
        </div>
        <div class="col-md-6">
            <h3 class="fw-semibold mb-3">Siapa Kami?</h3>
            <p class="text-muted">
                <strong>Shoreline</strong> adalah platform e-commerce yang hadir untuk
                memberikan pengalaman belanja online yang mudah, aman, dan terpercaya.
                Kami percaya bahwa setiap pelanggan berhak mendapatkan produk berkualitas
                dengan pelayanan terbaik.
            </p>
            <p class="text-muted">
                Sejak awal berdiri, kami berkomitmen untuk menghadirkan koleksi produk
                pilihan yang mengikuti tren, harga yang kompetitif, serta sistem transaksi
                yang transparan dan cepat.
            </p>
        </div>
    </div>

    <!-- VISION & MISSION -->
    <div class="row mb-5">
        <div class="col-md-6 mb-4">
            <div class="p-4 border rounded h-100">
                <h4 class="fw-semibold mb-3">Visi</h4>
                <p class="text-muted">
                    Menjadi platform e-commerce terpercaya yang mampu memberikan solusi
                    belanja digital terbaik dan berkelanjutan bagi masyarakat Indonesia.
                </p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="p-4 border rounded h-100">
                <h4 class="fw-semibold mb-3">Misi</h4>
                <ul class="text-muted ps-3">
                    <li>Menyediakan produk berkualitas dengan harga yang kompetitif</li>
                    <li>Memberikan pengalaman belanja yang nyaman dan aman</li>
                    <li>Mengutamakan kepuasan dan kepercayaan pelanggan</li>
                    <li>Mengembangkan teknologi untuk kemudahan transaksi</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- VALUES -->
    <div class="text-center mb-4">
        <h3 class="fw-semibold mb-4">Nilai Kami</h3>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="p-4 border rounded h-100">
                    <h5 class="fw-semibold">Kepercayaan</h5>
                    <p class="text-muted">
                        Kami membangun hubungan jangka panjang dengan pelanggan
                        berdasarkan kejujuran dan transparansi.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border rounded h-100">
                    <h5 class="fw-semibold">Kualitas</h5>
                    <p class="text-muted">
                        Setiap produk melalui proses seleksi agar sesuai dengan standar
                        kualitas Shoreline.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border rounded h-100">
                    <h5 class="fw-semibold">Pelayanan</h5>
                    <p class="text-muted">
                        Kami selalu siap membantu dan memberikan layanan terbaik
                        untuk setiap pelanggan.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
