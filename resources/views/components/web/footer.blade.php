<style>
    .web-footer .nav-link {
        display: inline;
        padding: 0;
        color: inherit;
        text-decoration: none;
    }

    .web-footer .nav-link:hover {
        text-decoration: underline;
    }
</style>

<footer class="web-footer bg-light pt-5 border-top">
    <div class="container">
        <div class="row gy-4 align-items-start">
            <div class="col-lg-4 col-md-6">
                <h4 class="fw-bold">Shoreline</h4>
                <p class="text-muted small">
                    Hadir dengan koleksi busana pilihan berkualitas tinggi untuk setiap perayaan dan momen berharga.
                </p>
            </div>

            <div class="col-lg-4 col-md-6 d-flex justify-content-lg-center">
                <div>
                    <h6 class="fw-semibold text-uppercase mb-3">Quick Links</h6>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-2">
                            <a class="nav-link" href="{{ route('produk') }}">Produk</a>
                        </li>
                        <li class="mb-2">
                            <a class="nav-link" href="{{ route('tentang') }}">Tentang Kami</a>
                        </li>
                        <li class="mb-2">
                            @auth
                                <a class="nav-link" href="{{ route('user.ulasans.index') }}">Ulasan Saya</a>
                            @else
                                <a class="nav-link" href="{{ route('login') }}">Ulasan Saya</a>
                            @endauth
                        </li>
                        <li class="mb-2">
                            <a class="nav-link" href="{{ route('kontak') }}">Kontak</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 text-lg-end">
                <h6 class="fw-semibold text-uppercase mb-3">Hubungi Kami</h6>

                <ul class="list-unstyled small text-muted">
                    <li class="mb-2 d-flex justify-content-lg-end gap-2">
                        <i class="fa-solid fa-phone mt-1"></i>
                        <span>0813-3818-7599</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-lg-end gap-2">
                        <i class="fa-solid fa-location-dot mt-1"></i>
                        <span>
                            Jl. Arjuna (Double Six), Legian, Kuta,<br>
                            Kabupaten Badung, Bali
                        </span>
                    </li>
                    <li class="mb-2 d-flex justify-content-lg-end gap-2">
                        <i class="fa-solid fa-envelope mt-1"></i>
                        <span>shoreline415@gmail.com</span>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="my-1">

        <div class="text-center small text-muted py-2">
            <p class="mb-0 fw-semibold">
                &copy; 2026 <span class="text-dark">Shoreline</span>. All Rights Reserved.
            </p>
        </div>
    </div>
</footer>
