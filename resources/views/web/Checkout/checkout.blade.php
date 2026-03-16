@extends('layouts.mainlayout')
@section('title', 'Checkout')

@section('content')
<div class="container py-5">

    <style>
        :root{
            --bd: rgba(0,0,0,.08);
            --muted:#6c757d;
            --radius: 16px;
        }
        .x-card{
            border:1px solid var(--bd);
            border-radius: var(--radius);
            background:#fff;
            overflow:hidden;
        }
        .x-head{
            padding:16px 18px;
            border-bottom:1px solid rgba(0,0,0,.06);
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
        }
        .x-title{ margin:0; font-weight:900; font-size:1rem; }
        .x-sub{ color:var(--muted); font-size:.9rem; }
        .pill{ border-radius:999px; }

        .sticky{ position:sticky; top:18px; }
        @media(max-width:991px){ .sticky{ position:static; top:auto; } }

        /* Produk list */
        .line{
            display:grid;
            grid-template-columns: 56px 1fr auto;
            gap:14px;
            padding:14px 18px;
            align-items:center;
        }
        .line + .line{ border-top:1px solid rgba(0,0,0,.06); }

        .thumb{
            width:56px; height:56px;
            border-radius:14px;
            overflow:hidden;
            background:#f1f3f5;
            border:1px solid rgba(0,0,0,.06);
        }
        .thumb img{ width:100%; height:100%; object-fit:cover; display:block; }

        .p-name{ font-weight:800; margin:0; line-height:1.2; }
        .p-meta{ margin-top:4px; color:var(--muted); font-size:.86rem; }

        .right{ text-align:right; }
        .price{ font-weight:900; }
        .unit{ color:var(--muted); font-size:.85rem; margin-top:4px; }

        /* Address */
        .addr{
            border:1px solid rgba(0,0,0,.10);
            border-radius: 14px;
            padding:14px;
            display:flex;
            gap:12px;
            align-items:flex-start;
            transition:.12s;
            background:#fff;
        }
        .addr:hover{ border-color: rgba(0,0,0,.18); }
        .addr.active{
            border-color:#212529;
            box-shadow:0 0 0 .20rem rgba(33,37,41,.10);
        }
        .addr .form-check{ margin:0; }
        .addr .form-check-input{ margin-top:.35rem; }
        .addr-name{ font-weight:900; margin:0; }
        .addr-text{ margin-top:6px; color:var(--muted); font-size:.9rem; line-height:1.35; }

        /* Payment (segmented) */
        .seg{
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:10px;
        }
        .seg label{
            border:1px solid rgba(0,0,0,.10);
            border-radius:14px;
            padding:14px;
            display:flex;
            gap:12px;
            align-items:flex-start;
            background:#fff;
            cursor:pointer;
            transition:.12s;
            width:100%;
        }
        .seg label:hover{ border-color: rgba(0,0,0,.18); }
        .seg label.active{
            border-color:#212529;
            box-shadow:0 0 0 .20rem rgba(33,37,41,.10);
        }
        .seg .form-check{ margin:0; }
        .seg .form-check-input{ margin-top:.35rem; }
        .seg .pt{ margin:0; font-weight:900; }
        .seg .ps{ color:var(--muted); font-size:.9rem; margin-top:4px; line-height:1.25; }

        /* Transfer info box */
        .tbox{
            border:1px solid rgba(0,0,0,.10);
            border-radius:14px;
            background:#fff;
            padding:14px;
        }
        .tmuted{ color:var(--muted); font-size:.92rem; }
        .tlabel{ color:var(--muted); font-size:.85rem; }
        .tno{ font-weight:900; letter-spacing:.06em; }

        /* QRIS box */
        .qrbox{
            border:1px solid rgba(0,0,0,.10);
            border-radius:14px;
            background:#fff;
            padding:14px;
            text-align:center;
        }
        .qrimg{
            width:220px;
            height:220px;
            object-fit:contain;
            border-radius:14px;
            border:1px solid rgba(0,0,0,.10);
            padding:10px;
            background:#fff;
        }

        .summary-row{
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:10px 0;
        }
        .divider{ border-top:1px solid rgba(0,0,0,.06); margin:10px 0; }

        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
            margin-bottom:18px;
        }
        .topbar h3{ margin:0; font-weight:900; }
        .muted{ color:var(--muted); }

        @media(max-width:575px){
            .line{ grid-template-columns:56px 1fr; }
            .right{ grid-column:1 / -1; text-align:left; }
            .seg{ grid-template-columns: 1fr; }
        }
    </style>

    {{-- TOP BAR --}}
    <div class="topbar">
        <div>
            <h3>Checkout</h3>
            <div class="muted">Periksa produk, pilih alamat, lalu lanjut pembayaran.</div>
        </div>

        <a href="{{ route('keranjang') }}" class="btn btn-outline-secondary btn-sm pill">
            ← Keranjang
        </a>
    </div>


    <div class="row g-4">

        {{-- LEFT (PRODUK) --}}
        <div class="col-lg-7">
            <div class="x-card shadow-sm">
                <div class="x-head">
                    <div>
                        <p class="x-title mb-0">Produk</p>
                        <div class="x-sub">{{ $keranjangs->count() }} item</div>
                    </div>

                    <span class="badge bg-dark pill px-3 py-2">
                        Total: Rp {{ number_format($total, 0, ',', '.') }}
                    </span>
                </div>

                @foreach ($keranjangs as $item)
                    @php
                        $img = ($item->varian && $item->varian->gambar_varian)
                            ? asset('storage/'.$item->varian->gambar_varian)
                            : 'https://via.placeholder.com/300x300?text=No+Image';

                        $harga = (int) optional($item->produk)->harga;
                        $qty   = (int) $item->jumlah_produk;
                        $sub   = $harga * $qty;
                    @endphp

                    <div class="line">
                        <div class="thumb">
                            <img src="{{ $img }}" alt="Produk">
                        </div>

                        <div>
                            <p class="p-name">{{ optional($item->produk)->nama_produk ?? 'Produk' }}</p>
                            <div class="p-meta">
                                Varian:
                                <span class="text-dark fw-semibold">
                                    {{ optional($item->varian)->warna ?? '-' }} / {{ optional($item->varian)->ukuran ?? '-' }}
                                </span>
                                • Qty <span class="text-dark fw-semibold">{{ $qty }}</span>
                            </div>
                        </div>

                        <div class="right">
                            <div class="price">Rp {{ number_format($sub, 0, ',', '.') }}</div>
                            <div class="unit">Rp {{ number_format($harga, 0, ',', '.') }} / item</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="col-lg-5">
            <div class="sticky">

                <form action="{{ route('checkout.store') }}" method="POST" id="formCheckout">
                    @csrf

                    {{-- HIDDEN ONGKIR --}}
                    <input type="hidden" name="ongkir" id="ongkir" value="">
                    <input type="hidden" name="kurir_kode" id="kurir_kode" value="">
                    <input type="hidden" name="kurir_layanan" id="kurir_layanan" value="">

                    {{-- ALAMAT --}}
                    <div class="x-card shadow-sm mb-4">
                        <div class="x-head">
                            <div>
                                <p class="x-title mb-0">Alamat</p>
                                <div class="x-sub">Pilih alamat pengiriman.</div>
                            </div>

                            <a href="{{ route('alamat.create') }}" class="btn btn-dark btn-sm pill">
                                + Tambah
                            </a>
                        </div>

                        <div class="p-3">
                            @forelse($alamats as $alamat)
                                @php
                                    $checked = old('alamat_id', $alamat->is_default ? $alamat->id : null) == $alamat->id;
                                @endphp

                                <div class="addr mb-3 {{ $checked ? 'active' : '' }}">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="alamat_id"
                                               value="{{ $alamat->id }}"
                                               {{ $checked ? 'checked' : '' }}
                                               required>
                                    </div>

                                    <div style="flex:1;">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div>
                                                <p class="addr-name mb-0">
                                                    {{ $alamat->nama_penerima }}
                                                    @if($alamat->is_default)
                                                        <span class="badge bg-dark pill ms-2">Default</span>
                                                    @endif
                                                </p>

                                                <div class="addr-text">
                                                    {{ $alamat->alamat_lengkap }},
                                                    {{ $alamat->kota }},
                                                    {{ $alamat->provinsi }} - {{ $alamat->kode_pos }}
                                                </div>
                                            </div>

                                            <div class="dropdown">
                                                <button class="btn btn-outline-secondary btn-sm pill"
                                                        type="button"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                    ⋯
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if(!$alamat->is_default)
                                                        <li>
                                                            <button type="submit"
                                                                    class="dropdown-item"
                                                                    form="setDefaultForm-{{ $alamat->id }}">
                                                                Jadikan Default
                                                            </button>
                                                        </li>
                                                    @endif

                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('alamat.edit', $alamat->id) }}">Edit</a>
                                                    </li>

                                                    <li>
                                                        <button type="submit"
                                                                class="dropdown-item text-danger"
                                                                form="deleteAlamatForm-{{ $alamat->id }}"
                                                                onclick="return confirm('Hapus alamat ini?')">
                                                            Hapus
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="muted">
                                    Belum ada alamat. Klik <b>+ Tambah</b>.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- PAYMENT --}}
                    <div class="x-card shadow-sm mb-4">
                        <div class="x-head">
                            <div>
                                <p class="x-title mb-0">Pembayaran</p>
                                <div class="x-sub">Pilih metode pembayaran.</div>
                            </div>
                        </div>

                        <div class="p-3">
                            <div class="seg">
                                <label class="seg-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metode_pembayaran" value="transfer" required>
                                    </div>
                                    <div style="flex:1;">
                                        <p class="pt mb-0">Transfer</p>
                                        <div class="ps">Bank Danamon</div>
                                    </div>
                                </label>

                                <label class="seg-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metode_pembayaran" value="qris">
                                    </div>
                                    <div style="flex:1;">
                                        <p class="pt mb-0">QRIS</p>
                                        <div class="ps">Scan QR untuk bayar</div>
                                    </div>
                                </label>

                                <label class="seg-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="metode_pembayaran" value="midtrans">
                                    </div>
                                    <div style="flex:1;">
                                        <p class="pt mb-0">Midtrans</p>
                                        <div class="ps">Virtual Account</div>
                                    </div>
                                </label>
                            </div>

                            {{-- TRANSFER --}}
                            <div id="transferInfo" class="mt-3" style="display:none;">
                                <div class="tbox">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <div class="fw-bold">Informasi Rekening Transfer</div>
                                            <div class="tmuted">Silakan transfer ke rekening berikut, lalu upload bukti transfer.</div>
                                        </div>
                                        <span class="badge bg-dark pill px-3 py-2">TRANSFER</span>
                                    </div>

                                    <hr class="my-3">

                                    <div class="mb-2">
                                        <div class="tlabel">Bank</div>
                                        <div class="fw-semibold" id="bankText">Danamon</div>
                                    </div>

                                    <div class="mb-2">
                                        <div class="tlabel">No Rekening</div>
                                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                            <div class="tno" id="noRekText">003612077192</div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm pill" id="btnCopyRek">
                                                Salin
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <div class="tlabel"> Ni Luh Yaniati</div>
                                        <div class="fw-semibold" id="namaRekText">Toko Shoreline</div>
                                    </div>
                                </div>
                            </div>

                            {{-- QRIS --}}
                            <div id="qrisInfo" class="mt-3" style="display:none;">
                                <div class="qrbox">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div class="text-start">
                                            <div class="fw-bold">Scan QRIS</div>
                                            <div class="tmuted">Gunakan e-wallet / m-banking untuk scan QR.</div>
                                        </div>
                                        <span class="badge bg-dark pill px-3 py-2">QRIS</span>
                                    </div>

                                    <hr class="my-3">

                                    <img src="{{ asset('assets/images/corousel/Qris.png') }}" class="qrimg" alt="QRIS">

                                    <div class="mt-3 small text-muted">
                                        Pastikan nominal sesuai total checkout.
                                    </div>
                                </div>
                            </div>
                            {{-- MIDTRANS --}}
                            <div id="midtransInfo" class="mt-3" style="display:none;">
                                <div class="qrbox">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div class="text-start">
                                            <div class="fw-bold">Pembayaran Midtrans</div>
                                            <div class="tmuted">Lanjut ke halaman pembayaran Midtrans.</div>
                                        </div>
                                        <span class="badge bg-dark pill px-3 py-2">MIDTRANS</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- PENGIRIMAN (ONGKIR) --}}
                    <div class="x-card shadow-sm mb-4">
                        <div class="x-head">
                            <div>
                                <p class="x-title mb-0">Pengiriman</p>
                                <div class="x-sub">Pilih kurir & layanan ongkir.</div>
                            </div>
                            <span class="badge bg-dark pill px-3 py-2" id="badgeOngkir">Rp 0</span>
                        </div>

                        <div class="p-3">
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-1">Kurir</label>
                                <select id="courier" class="form-select">
                                    <option value="jne">JNE</option>
                                    <option value="jnt">JNT</option>
                                    <option value="pos">POS</option>
                                </select>
                                <div class="form-text">Pilih kurir lalu pilih layanan.</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold mb-1">Layanan</label>
                                <select id="shipping_service" class="form-select">
                                    <option value="">-- Pilih alamat dulu --</option>
                                </select>
                            </div>

                            <div class="small text-muted" id="shippingHint">
                                Ongkir akan muncul setelah alamat dipilih.
                            </div>
                        </div>
                    </div>

                    {{-- SUMMARY --}}
                    <div class="x-card shadow-sm">
                        <div class="x-head">
                            <div>
                                <p class="x-title mb-0">Ringkasan</p>
                                <div class="x-sub">Total pembayaran.</div>
                            </div>
                        </div>

                        <div class="p-3">
                            <div class="summary-row">
                                <span class="muted">Subtotal</span>
                                <span class="fw-semibold">Rp {{ number_format($total, 0, ',', '.') }}</span>
                            </div>

                            <div class="summary-row">
                                <span class="muted">Ongkir</span>
                                <span class="fw-semibold" id="ongkirRowText">Rp 0</span>
                            </div>

                            <div class="divider"></div>

                            <div class="summary-row">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold fs-5" id="grandTotalText">Rp {{ number_format($total, 0, ',', '.') }}</span>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 pill mt-2">
                                Buat Transaksi
                            </button>

                            <div class="x-sub mt-3">
                                Pesanan akan dibuat dan menunggu pembayaran.
                            </div>
                        </div>
                    </div>

                </form>

                {{-- FORM TERSEMBUNYI UNTUK ACTION DROPDOWN ALAMAT (DI LUAR formCheckout) --}}
                @foreach($alamats as $alamat)

                    @if(!$alamat->is_default)
                        <form id="setDefaultForm-{{ $alamat->id }}"
                              action="{{ route('alamat.setDefault', $alamat->id) }}"
                              method="POST"
                              class="d-none">
                            @csrf
                            @method('PUT')
                        </form>
                    @endif

                    <form id="deleteAlamatForm-{{ $alamat->id }}"
                          action="{{ route('alamat.destroy', $alamat->id) }}"
                          method="POST"
                          class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>

                @endforeach

            </div>
        </div>

    </div>

</div>

<script>
    function toggleTransferInfo(){
    var radio = document.querySelector('input[name="metode_pembayaran"]:checked');
    var selected = radio ? radio.value : null;

    var transferBox = document.getElementById('transferInfo');
    var qrisBox = document.getElementById('qrisInfo');
    var midtransBox = document.getElementById('midtransInfo');

    if (transferBox) transferBox.style.display = (selected === 'transfer') ? 'block' : 'none';
    if (qrisBox) qrisBox.style.display = (selected === 'qris') ? 'block' : 'none';
    if (midtransBox) midtransBox.style.display = (selected === 'midtrans') ? 'block' : 'none';
}


    function copyTextFallback(text){
        var temp = document.createElement('input');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        try { document.execCommand('copy'); } catch(e) {}
        document.body.removeChild(temp);
    }

    document.addEventListener('DOMContentLoaded', function(){
        toggleTransferInfo();

        var btnCopy = document.getElementById('btnCopyRek');
        if(btnCopy){
            btnCopy.addEventListener('click', function(){
                var el = document.getElementById('noRekText');
                var rek = el ? (el.innerText || el.textContent || '').trim() : '';
                if(!rek) return;

                copyTextFallback(rek);

                btnCopy.innerText = 'Tersalin ✔';
                setTimeout(function(){ btnCopy.innerText = 'Salin'; }, 1200);
            });
        }
    });

    document.addEventListener('change', function(e){
        if(!e.target || e.target.type !== 'radio') return;

        if(e.target.name === 'alamat_id'){
            var addrs = document.querySelectorAll('.addr');
            for(var i=0;i<addrs.length;i++){ addrs[i].classList.remove('active'); }

            var wrapAddr = e.target.closest ? e.target.closest('.addr') : null;
            if(wrapAddr) wrapAddr.classList.add('active');
        }

        if(e.target.name === 'metode_pembayaran'){
            var segs = document.querySelectorAll('.seg-item');
            for(var j=0;j<segs.length;j++){ segs[j].classList.remove('active'); }

            var wrapSeg = e.target.closest ? e.target.closest('.seg-item') : null;
            if(wrapSeg) wrapSeg.classList.add('active');

            toggleTransferInfo();
        }
    });
</script>

{{-- SCRIPT ONGKIR --}}
<script>
(function () {
  const totalBarang = {{ (int) $total }};

  const form = document.getElementById('formCheckout');
  const alamatRadios = document.querySelectorAll('input[name="alamat_id"]');

  const courierSelect = document.getElementById('courier');
  const serviceSelect = document.getElementById('shipping_service');

  const ongkirHidden = document.getElementById('ongkir');
  const kurirKodeHidden = document.getElementById('kurir_kode');
  const kurirLayananHidden = document.getElementById('kurir_layanan');

  const badgeOngkir = document.getElementById('badgeOngkir');
  const ongkirRowText = document.getElementById('ongkirRowText');
  const grandTotalText = document.getElementById('grandTotalText');
  const shippingHint = document.getElementById('shippingHint');

  function rupiah(n){
    n = parseInt(n || 0, 10);
    return 'Rp ' + n.toLocaleString('id-ID');
  }

  function setTotals(ongkir){
    const o = parseInt(ongkir || 0, 10);
    badgeOngkir.textContent = rupiah(o);
    ongkirRowText.textContent = rupiah(o);
    grandTotalText.textContent = rupiah(totalBarang + o);
  }

  function getSelectedAlamatId(){
    const checked = document.querySelector('input[name="alamat_id"]:checked');
    return checked ? checked.value : '';
  }

  function resetShippingUI(message, hard = false){
    serviceSelect.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = message || '-- Pilih alamat dulu --';
    serviceSelect.appendChild(opt);

    if (hard) {
      ongkirHidden.value = '';
      kurirKodeHidden.value = '';
      kurirLayananHidden.value = '';
      setTotals(0);
    }

    if (shippingHint) shippingHint.textContent = message || 'Ongkir akan muncul setelah alamat dipilih.';
  }

  async function loadOngkir(){
    const alamatId = getSelectedAlamatId();
    const courier = courierSelect.value;

    if(!alamatId){
      resetShippingUI('-- Pilih alamat dulu --', true);
      return;
    }

    resetShippingUI('Memuat layanan...', false);

    // wajib pilih ulang layanan setelah ganti alamat/kurir
    kurirLayananHidden.value = '';
    kurirKodeHidden.value = '';
    ongkirHidden.value = '';
    setTotals(0);

    const res = await fetch("{{ route('checkout.shippingOptions') }}", {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': "{{ csrf_token() }}"
      },
      body: JSON.stringify({ alamat_id: alamatId, courier })
    });

    const json = await res.json().catch(() => null);

    if(!res.ok || !json || !json.success){
      resetShippingUI('-- Gagal memuat layanan --', true);
      if (json && json.degraded) {
      // jangan alert, cukup info halus
        if (shippingHint) shippingHint.textContent =
          json.message || 'Mode fallback ongkir aktif karena server ongkir sedang gangguan.';
      }
      const msg = (json && (json.message || json.body?.meta?.message))
        ? (json.message || json.body?.meta?.message)
        : 'Gagal mengambil ongkir';
      alert(msg);
      return;
    }

    const list = json.data?.data || [];
    if(!list.length){
      resetShippingUI('-- Tidak ada layanan tersedia --', true);
      return;
    }

    serviceSelect.innerHTML = '';

    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = '-- Pilih layanan --';
    serviceSelect.appendChild(ph);

    list.forEach(item => {
      let cost = item.cost;

      if (Array.isArray(cost)) {
        cost = cost?.[0]?.value ?? 0;
      } else if (typeof cost === 'object' && cost !== null) {
        cost = cost?.value ?? 0;
      }

      cost = parseInt(cost || 0, 10);

      const opt = document.createElement('option');
      opt.value = item.service;
      opt.dataset.cost = cost;
      opt.dataset.code = item.code || courierSelect.value;

      const etd = item.etd ? `(${item.etd})` : '';
      opt.textContent = `${item.service} - ${rupiah(cost)} ${etd}`;
      serviceSelect.appendChild(opt);
    });

    if (shippingHint) shippingHint.textContent = 'Pilih layanan untuk mengatur ongkir.';
  }

  function applySelectedService(){
    const opt = serviceSelect.options[serviceSelect.selectedIndex];

    if(!opt || !opt.value){
      ongkirHidden.value = '';
      kurirKodeHidden.value = '';
      kurirLayananHidden.value = '';
      setTotals(0);
      return;
    }

    const cost = parseInt(opt.dataset.cost || 0, 10);

    ongkirHidden.value = cost;
    kurirKodeHidden.value = opt.dataset.code || courierSelect.value;
    kurirLayananHidden.value = opt.value;

    setTotals(cost);
  }

  // ✅ event listener HARUS di luar function
  courierSelect.addEventListener('change', loadOngkir);
  serviceSelect.addEventListener('change', applySelectedService);
  alamatRadios.forEach(r => r.addEventListener('change', loadOngkir));

  form.addEventListener('submit', function(e){
    if(!getSelectedAlamatId()){
      e.preventDefault();
      alert('Silakan pilih alamat dulu.');
      return;
    }
    if(!ongkirHidden.value || !kurirKodeHidden.value || !kurirLayananHidden.value){
      e.preventDefault();
      alert('Silakan pilih kurir & layanan pengiriman dulu.');
      return;
    }
  });

  setTotals(0);
  if(getSelectedAlamatId()){
    loadOngkir();
  }
})();
</script>
@endsection
