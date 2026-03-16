@php
$user = auth()->user();
$unreadCount = $user?->unreadNotifications()->count() ?? 0;
$notifications = $user?->notifications()->latest()->take(5)->get() ?? collect();

$initial = strtoupper(substr($user->name ?? 'A', 0, 1));
@endphp

<div class="top-navbar bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between">

    <!-- LEFT -->
    <div class="d-flex flex-column">
        <h5 class="fw-semibold mb-0">Dashboard</h5>
        <small class="text-muted">Welcome back, {{ $user->name ?? 'Admin' }}</small>
    </div>

    <!-- RIGHT -->
    <div class="d-flex align-items-center gap-3">

        <!-- NOTIFICATIONS -->
        <div class="dropdown">
            <button class="btn btn-light position-relative rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                <i class="bi bi-bell fs-5"></i>

                @if($unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge bg-danger rounded-pill">
                    {{ $unreadCount }}
                </span>
                @endif
            </button>

            <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 overflow-hidden" style="width: 380px;">
                <!-- Header -->
                <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex flex-column">
                        <strong class="mb-0">Notifikasi Admin</strong>
                        <small class="text-muted">Terbaru (maks. 5)</small>
                    </div>

                    <form method="POST" action="{{ route('admin.notifikasi.readAll') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            Tandai semua
                        </button>
                    </form>
                </div>

                <!-- Body -->
                <div style="max-height: 340px; overflow:auto;">
                    @if($notifications->isEmpty())
                    <div class="text-center text-muted py-4">
                        Tidak ada notifikasi
                    </div>
                    @else
                    @foreach($notifications as $n)
                    @php
                    $type = $n->data['type'] ?? 'info';

                    $label = match($type) {
                    'admin_pembelian_baru' => 'Pembelian',
                    'admin_ulasan_baru' => 'Ulasan',
                    'admin_kontak_masuk' => 'Kontak',
                    default => 'Info',
                    };

                    $link = match($type) {
                    'admin_pembelian_baru' => route('admin.transaksi.show', $n->data['transaksi_id'] ?? 0),
                    'admin_ulasan_baru' => route('admin.ulasans.index'),
                    'admin_kontak_masuk' => !empty($n->data['kontak_id'])
                        ? route('admin.kontak.show', $n->data['kontak_id'])
                        : route('admin.kontak.index'),
                    default => route('admin.notifikasi.index'),
                    };

                    $isUnread = !$n->read_at;
                    @endphp

                    <a href="{{ $link }}" class="dropdown-item px-3 py-3 border-bottom {{ $isUnread ? 'bg-light' : '' }}" style="white-space: normal;">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="grow">
                                <div class="small text-muted">{{ $label }}</div>
                                <div class="{{ $isUnread ? 'fw-semibold' : '' }}">
                                    {{ $n->data['message'] ?? 'Notifikasi' }}
                                </div>
                                <div class="small text-muted mt-1">
                                    {{ $n->created_at->diffForHumans() }}
                                </div>
                            </div>

                            @if($isUnread)
                            <span class="badge bg-primary align-self-start">Baru</span>
                            @endif
                        </div>
                    </a>
                    @endforeach
                    @endif
                </div>

                <!-- Footer -->
                <div class="px-3 py-2 text-center bg-white">
                    <a href="{{ route('admin.kontak.index') }}" class="text-decoration-none fw-semibold">
                        Buka inbox kontak
                    </a>
                </div>
            </div>
        </div>

        <!-- ADMIN DROPDOWN -->
        <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 px-3 py-2" style="border-radius: 12px;" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center" style="width:34px; height:34px; font-size:13px;">
                    {{ $initial }}
                </div>

                <span class="fw-medium text-truncate" style="max-width: 140px;">
                    {{ $user->name ?? 'Admin' }}
                </span>

                <i class="bi bi-chevron-down small text-muted"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2 custom-dropdown">

                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2
           {{ request()->routeIs('admin.profile.*') ? 'fw-semibold text-dark' : 'text-dark' }}" href="{{ route('admin.profile.edit') }}">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Profil</span>
                    </a>
                </li>

                <li>
                    <hr class="dropdown-divider my-2">
                </li>

                <li>
                    <form id="adminLogoutForm" method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button type="button" class="dropdown-item text-danger" onclick="confirmAdminLogout()">
                            <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                        </button>
                    </form>

                    <script>
                        function confirmAdminLogout() {
                            SwalConfirm({
                                title: 'Logout Admin?'
                                , text: 'Kamu akan keluar dari panel admin.'
                                , icon: 'warning'
                                , confirmText: 'Ya, logout'
                                , cancelText: 'Batal'
                                , confirmColor: '#111'
                            }).then((result) => {
                                if (result.isConfirmed) document.getElementById('adminLogoutForm').submit();
                            });
                        }

                    </script>
                </li>

            </ul>
        </div>

    </div>
</div>
