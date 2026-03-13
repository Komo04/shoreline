<style>
  :root{
    --bg: #f5f5f3;
    --sidebar-bg: #f0efec;
    --card: rgba(255,255,255,.75);
    --text: #222;
    --muted: #6b7280;
    --line: rgba(0,0,0,.08);
    --accent: #111827;
    --shadow: 0 10px 30px rgba(17, 24, 39, .08);
    --radius: 14px;
  }

  body{
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
  }

  /* Sidebar container */
  .sidebar{
    width: 280px;
    height: 100vh;
    position: fixed;
    inset: 0 auto 0 0;
    padding: 18px 16px;
    background: linear-gradient(180deg, rgba(240,239,236,1), rgba(240,239,236,.94));
    border-right: 1px solid var(--line);
    backdrop-filter: blur(10px);

    display: flex;
    flex-direction: column;
  }

  /* Brand */
  .sidebar .brand-wrap{
    padding: 14px 14px;
    border-radius: var(--radius);
    background: var(--card);
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    flex-shrink: 0;
  }

  .sidebar .brand{
    font-size: 16px;
    font-weight: 700;
    color: var(--text);
    letter-spacing: .2px;
    line-height: 1.1;
  }

  .sidebar .brand-sub{
    font-size: 11px;
    color: var(--muted);
    margin-top: 2px;
  }

  /* Section title */
  .sidebar .section-title{
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin: 10px 10px 10px;
    flex-shrink: 0;
  }

  /* Menu wrapper (SCROLL HERE) */
  .sidebar-menu{
    flex: 1;
    overflow-y: auto;
    padding-right: 6px;
  }

  /* Custom Scrollbar */
  .sidebar-menu::-webkit-scrollbar{
    width: 6px;
  }

  .sidebar-menu::-webkit-scrollbar-thumb{
    background: rgba(0,0,0,.20);
    border-radius: 20px;
  }

  .sidebar-menu::-webkit-scrollbar-thumb:hover{
    background: rgba(0,0,0,.35);
  }

  /* Nav base */
  .sidebar .nav{
    gap: 6px;
  }

  /* âœ… Animasi ringan */
  .sidebar .nav-link{
    color: #374151;
    font-size: 13px;
    padding: 10px 12px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;

    border: 1px solid transparent;
    position: relative;

    transition: background-color .12s ease,
                transform .12s ease,
                box-shadow .12s ease,
                border-color .12s ease;
  }

  .sidebar .nav-link i{
    font-size: 16px;
    opacity: .95;
  }

  /* Hover subtle */
  .sidebar .nav-link:hover{
    background: rgba(255,255,255,.75);
    border-color: var(--line);
    transform: translateX(1px);
  }

  /* Active */
  .sidebar .nav-link.active{
    background: #fff;
    color: var(--accent);
    font-weight: 700;
    border-color: rgba(17,24,39,.08);
    box-shadow: 0 6px 15px rgba(17,24,39,.06);
  }

  .sidebar .nav-link.active::before{
    left: -4px;
    width: 2px;
    background: rgba(0,0,0,.25);
  }

  /* Collapse header arrow */
  .sidebar .nav-link[data-bs-toggle="collapse"]{
    justify-content: space-between;
  }

  .sidebar .nav-link .label{
    display: inline-flex;
    align-items: center;
    gap: 10px;
  }

  .sidebar .nav-link .chev{
    transition: transform .15s ease;
    opacity: .65;
  }

  .sidebar .nav-link:not(.collapsed) .chev{
    transform: rotate(180deg);
  }

  /* Submenu */
  .sidebar .collapse{
    margin-top: 6px;
    padding-left: 12px;
    border-left: 1px dashed rgba(0,0,0,.12);
  }

  .sidebar .collapse .nav-link{
    margin: 6px 0 0;
    padding: 9px 12px;
    font-size: 12.5px;
    border-radius: 12px;
    color: #4b5563;
  }

  .sidebar .collapse .nav-link i{
    font-size: 15px;
  }

  .sidebar .collapse .nav-link:hover{
    transform: translateX(1px);
  }

  /* Collapse animasi lebih cepat */
  .collapse{
    transition: height .15s ease;
  }
  .collapsing{
    transition: height .15s ease;
  }

  .sidebar-divider{
    height: 1px;
    background: var(--line);
    margin: 14px 10px;
  }

  /* Footer */
  .sidebar-footer{
    padding: 12px 10px 0;
    color: var(--muted);
    flex-shrink: 0;
  }

  .sidebar-footer .box{
    border-top: 1px solid var(--line);
    padding-top: 12px;
    text-align: center;
    font-size: 12px;
  }

  /* Main content spacing */
  .main-content{
    margin-left: 280px;
  }

  /* Responsive */
  @media (max-width: 991px){
    .sidebar{
      position: relative;
      width: 100%;
      height: auto;
      border-right: none;
      border-bottom: 1px solid var(--line);
    }

    .sidebar-menu{
      max-height: 400px;
    }

    .main-content{
      margin-left: 0;
    }
  }
</style>

<div class="sidebar">

  <!-- Brand -->
  <div class="brand-wrap">
    <div>
      <div class="brand">Shoreline</div>
      <div class="brand-sub">Admin Panel</div>
    </div>
  </div>

  <div class="section-title">Menu</div>

  <!-- MENU SCROLLABLE -->
  <div class="sidebar-menu">
    <ul class="nav flex-column">

      <!-- Dashboard -->
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           href="{{ route('admin.dashboard') }}">
          <span class="label"><i class="bi bi-grid-1x2-fill"></i> Dashboard</span>
        </a>
      </li>

      <!-- MASTER DATA -->
      @php
        $isMasterActive = request()->routeIs('admin.produk.*', 'admin.kategori.*', 'admin.customer*');
      @endphp
      <li class="nav-item">
        <a class="nav-link collapsed {{ $isMasterActive ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#masterData"
           role="button"
           aria-expanded="{{ $isMasterActive ? 'true' : 'false' }}">
          <span class="label"><i class="bi bi-database-fill"></i> Master Data</span>
          <i class="bi bi-chevron-down chev"></i>
        </a>

        <div class="collapse {{ $isMasterActive ? 'show' : '' }}" id="masterData">
             <a class="nav-link {{ request()->routeIs('admin.kategori.*') ? 'active' : '' }}"
             href="{{ route('admin.kategori.index') }}">
            <i class="bi bi-tags-fill"></i> Kategori
          </a>
          <a class="nav-link {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}"
             href="{{ route('admin.produk.index') }}">
            <i class="bi bi-box-fill"></i> Produk
          </a>
          <a class="nav-link {{ request()->routeIs('admin.customer*') ? 'active' : '' }}"
             href="{{ url('/admin/customer') }}">
            <i class="bi bi-people-fill"></i> Customer
          </a>
        </div>
      </li>

      <!-- STOK BARANG -->
      @php
        $isStokActive = request()->routeIs('admin.stokin','admin.stokout','admin.jumlah','admin.stoklog');
      @endphp
      <li class="nav-item">
        <a class="nav-link collapsed {{ $isStokActive ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#stokBarang"
           role="button"
           aria-expanded="{{ $isStokActive ? 'true' : 'false' }}">
          <span class="label"><i class="bi bi-box-seam-fill"></i> Stok Barang</span>
          <i class="bi bi-chevron-down chev"></i>
        </a>

        <div class="collapse {{ $isStokActive ? 'show' : '' }}" id="stokBarang">
          <a class="nav-link {{ request()->routeIs('admin.stokin') ? 'active' : '' }}"
             href="{{ route('admin.stokin') }}">
            <i class="bi bi-box-arrow-in-down"></i> Stok In
          </a>
          <a class="nav-link {{ request()->routeIs('admin.stokout') ? 'active' : '' }}"
             href="{{ route('admin.stokout') }}">
            <i class="bi bi-box-arrow-up"></i> Stok Out
          </a>
          <a class="nav-link {{ request()->routeIs('admin.jumlah') ? 'active' : '' }}"
             href="{{ route('admin.jumlah') }}">
            <i class="bi bi-boxes"></i> Jumlah Stok
          </a>
          <a class="nav-link {{ request()->routeIs('admin.stoklog') ? 'active' : '' }}"
             href="{{ route('admin.stoklog') }}">
            <i class="bi bi-clock-history"></i> Stoklog
          </a>
        </div>
      </li>

      <!-- TRANSAKSI -->
      @php
        $isTransaksiActive = request()->routeIs('admin.pembayaran*', 'admin.transaksi*');
      @endphp
      <li class="nav-item">
        <a class="nav-link collapsed {{ $isTransaksiActive ? 'active' : '' }}"
           data-bs-toggle="collapse"
           href="#transaksiMenu"
           role="button"
           aria-expanded="{{ $isTransaksiActive ? 'true' : 'false' }}">
          <span class="label"><i class="bi bi-cash-coin"></i> Transaksi</span>
          <i class="bi bi-chevron-down chev"></i>
        </a>

        <div class="collapse {{ $isTransaksiActive ? 'show' : '' }}" id="transaksiMenu">
          <a class="nav-link {{ request()->routeIs('admin.pembayaran*') ? 'active' : '' }}"
             href="{{ route('admin.pembayaran') }}">
            <i class="bi bi-shield-check"></i> Verif Pembayaran
          </a>
          <a class="nav-link {{ request()->routeIs('admin.transaksi*') ? 'active' : '' }}"
             href="{{ route('admin.transaksi') }}">
            <i class="bi bi-receipt-cutoff"></i> Riwayat Transaksi
          </a>
        </div>
      </li>

      <div class="sidebar-divider"></div>

      <!-- ULASAN -->
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.ulasans.*') ? 'active' : '' }}"
           href="{{ route('admin.ulasans.index') }}">
          <span class="label"><i class="bi bi-chat-square-text-fill"></i> Ulasan</span>
        </a>
      </li>

      <!-- LAPORAN -->
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}"
           href="{{ route('admin.laporan.pendapatan') }}">
          <span class="label"><i class="bi bi-printer-fill"></i> Cetak Laporan Pendapatan</span>
        </a>
      </li>
        <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.kontak.*') ? 'active' : '' }}"
           href="{{ route('admin.kontak.index') }}">
          <span class="label"><i class="bi bi-envelope-fill"></i> Pesan</span>
        </a>
      </li>

    </ul>
  </div>

  <!-- FOOTER -->
  <div class="sidebar-footer">
    <div class="box">
      &copy; 2026 <strong>Shoreline</strong><br>
      Logged in as <span class="fw-semibold">Admin</span>
    </div>
  </div>

</div>
