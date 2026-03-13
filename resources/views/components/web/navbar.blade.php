<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
    <div class="container-fluid px-lg-5">

        <!-- BRAND -->
        <a class="navbar-brand fw-bold fs-4" href="{{ route('home') }}">
            Shoreline
        </a>

        <!-- TOGGLER -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- COLLAPSE -->
        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- LEFT MENU -->
            <ul class="navbar-nav ms-lg-4 gap-lg-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active fw-semibold' : '' }}" href="{{ route('home') }}">
                        Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('produk*') ? 'active fw-semibold' : '' }}" href="{{ route('produk') }}">
                        Produk
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('transaksi.*') ? 'active fw-semibold' : '' }}" href="{{ route('transaksi.index') }}">
                        Pesanan Saya
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('user.ulasans.*') ? 'active fw-semibold' : '' }}" href="{{ route('user.ulasans.index') }}">
                        Ulasan Saya
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tentang') ? 'active fw-semibold' : '' }}" href="{{ route('tentang') }}">
                        Tentang Kami
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('kontak') ? 'active fw-semibold' : '' }}" href="{{ route('kontak') }}">
                        Kontak
                    </a>
                </li>


            </ul>

            <!-- RIGHT SIDE -->
            <div class="ms-lg-auto mt-3 mt-lg-0 d-flex align-items-center gap-3">

                @auth
                @php
                $unreadCount = auth()->user()->unreadNotifications()->count();
                $notifications = auth()->user()->notifications()->latest()->take(5)->get();
                @endphp

                <!-- 🔔 NOTIFICATION DROPDOWN -->
                <div class="dropdown">
                    <button class="btn p-0 border-0 bg-transparent shadow-none position-relative rounded-circle" style="width:40px; height:40px;" data-bs-toggle="dropdown" aria-expanded="false">

                        <i class="fa-solid fa-bell fs-5"></i>

                        @if($unreadCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge bg-danger rounded-pill">
                            {{ $unreadCount }}
                        </span>
                        @endif
                    </button>

                    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0" style="width:360px;">

                        <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <strong>Notifikasi</strong>

                            <form method="POST" action="{{ route('notifikasi.readAll') }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">
                                    Tandai semua
                                </button>
                            </form>
                        </div>

                        <div style="max-height:320px; overflow:auto;">
                            @if($notifications->count() === 0)
                            <div class="text-center text-muted py-3">
                                Tidak ada notifikasi
                            </div>
                            @else
                            @foreach($notifications as $n)
                            @php
                            $type = $n->data['type'] ?? 'info';
                            $label = $type === 'user_status_update' ? 'Status Pesanan' : 'Info';

                            $trxId = data_get($n->data, 'transaksi_id');
                            $link = ($type === 'user_status_update' && $trxId)
                                ? route('transaksi.show', $trxId)
                                : route('notifikasi.index');
                            @endphp

                            <a href="{{ $link }}" class="dropdown-item {{ $n->read_at ? '' : 'bg-light' }}" style="white-space:normal;">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="grow">
                                        <div class="small text-muted">{{ $label }}</div>
                                        <div class="{{ $n->read_at ? '' : 'fw-semibold' }}">
                                            {{ $n->data['message'] ?? 'Notifikasi' }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $n->created_at->diffForHumans() }}
                                        </small>
                                    </div>

                                    @if(!$n->read_at)
                                    <span class="badge bg-primary">Baru</span>
                                    @endif
                                </div>
                            </a>
                            @endforeach
                            @endif
                        </div>

                        <div class="border-top py-2 text-center">
                            <a href="{{ route('notifikasi.index') }}" class="text-decoration-none fw-semibold">
                                Lihat semua notifikasi
                            </a>
                        </div>

                    </div>
                </div>
                @endauth

                <!-- CART (dengan badge) -->
                <a href="{{ route('keranjang') }}" class="text-dark position-relative">
                    <i class="fa-solid fa-bag-shopping fs-5"></i>

                    @auth
                    @if(($cartCount ?? 0) > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $cartCount }}
                    </span>
                    @endif
                    @endauth
                </a>

                @guest
                <!-- LOGIN -->
                <a href="{{ route('login') }}" class="btn btn-outline-dark px-4 rounded-pill">
                    Login
                </a>

                <!-- REGISTER -->
                <a href="{{ route('register') }}" class="btn btn-dark px-4 rounded-pill">
                    Register
                </a>
                @endguest

                @auth
                <!-- USER DROPDOWN -->
                <div class="dropdown">
                    <button class="btn btn-outline-dark rounded-pill px-3 d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-user"></i>
                        {{ auth()->user()->name }}
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li>
                            <a class="dropdown-item" href="{{ route('user.profile.edit') }}">
                                <i class="fa-solid fa-id-card me-2"></i> Profil
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form id="logoutForm" method="POST" action="{{ url('/logout') }}">
                                @csrf
                                <button type="button" class="dropdown-item text-danger" onclick="confirmLogout()">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                                </button>
                            </form>

                            <script>
                                function confirmLogout() {
                                    SwalConfirm({
                                        title: 'Logout?'
                                        , text: 'Kamu akan keluar dari akun.'
                                        , icon: 'warning'
                                        , confirmText: 'Ya, logout'
                                        , cancelText: 'Batal'
                                        , confirmColor: '#111'
                                    }).then((result) => {
                                        if (result.isConfirmed) document.getElementById('logoutForm').submit();
                                    });
                                }

                            </script>
                        </li>
                    </ul>
                </div>
                @endauth

            </div>

        </div>
    </div>
</nav>
