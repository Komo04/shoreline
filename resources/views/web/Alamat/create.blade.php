@extends('layouts.mainlayout')

@section('title', 'Alamat')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">

      <div class="text-center mb-4">
        <h3 class="fw-bold mb-1">Alamat Pengiriman</h3>
        <p class="text-muted mb-0">Isi manual atau cari wilayah agar lebih cepat</p>
      </div>

      @if (session('success'))
        <div class="alert alert-success shadow-sm border-0">
          {{ session('success') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger shadow-sm border-0">
          <div class="fw-semibold mb-2">Periksa kembali input Anda:</div>
          <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">

          <form action="{{ route('alamat.store', request()->query('mode') === 'direct' ? ['mode' => 'direct'] : []) }}" method="POST">
            @csrf
            <input type="hidden" name="checkout_mode" value="{{ old('checkout_mode', request()->query('mode') === 'direct' ? 'direct' : 'cart') }}">

            <div class="row g-4">

              {{-- KIRI: Kontak + Alamat --}}
              <div class="col-lg-6">
                <div class="mb-3">
                  <div class="fw-bold">Data Kontak</div>
                  <div class="text-muted small">Nama & nomor telepon untuk kurir</div>
                </div>

                <div class="row g-3">
                  <div class="col-md-6 col-lg-12">
                    <label class="form-label fw-semibold">Nama Penerima <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="nama_penerima"
                      class="form-control form-control-lg"
                      value="{{ old('nama_penerima', $user->name ?? '') }}"
                      required
                    >
                  </div>

                  <div class="col-md-6 col-lg-12">
                    <label class="form-label fw-semibold">Nama Pengirim <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="nama_pengirim"
                      class="form-control form-control-lg"
                      value="{{ old('nama_pengirim', $user->name ?? '') }}"
                      required
                    >
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold">Nomor Telepon <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="no_telp"
                      id="no_telp"
                      class="form-control form-control-lg"
                      value="{{ old('no_telp', $user->no_telp ?? '') }}"
                      inputmode="numeric"
                      autocomplete="tel"
                      placeholder="Contoh: 08xxxxxxxxxx"
                      required
                    >
                    <div class="form-text">Hanya angka.</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold">Alamat Lengkap <span class="text-danger">*</span></label>
                    <textarea
                      name="alamat_lengkap"
                      rows="4"
                      class="form-control form-control-lg"
                      placeholder="Nama jalan, nomor rumah, RT/RW, patokan, dll"
                      required
                    >{{ old('alamat_lengkap') }}</textarea>
                  </div>
                </div>
              </div>

              {{-- KANAN: Wilayah --}}
              <div class="col-lg-6">
                <div class="mb-3">
                  <div class="fw-bold">Wilayah</div>
                  <div class="text-muted small">Bisa isi manual atau pakai pencarian (opsional)</div>
                </div>

                <div class="row g-3">

                  {{-- Search Wilayah (OPSIONAL) --}}
                  <div class="col-12 position-relative">
                    <label class="form-label fw-semibold">Cari Wilayah (Opsional)</label>
                    <input
                      type="text"
                      id="search_wilayah"
                      class="form-control form-control-lg"
                      placeholder="Ketik minimal 3 huruf, contoh: Bandung / Makassar"
                      autocomplete="off"
                      value="{{ old('search_wilayah') }}"
                    >

                    <div
                      id="result_wilayah"
                      class="list-group position-absolute w-100 shadow-sm"
                      style="display:none; z-index: 1050; max-height: 280px; overflow:auto;"
                    ></div>

                    {{-- destination_id sekarang OPSIONAL --}}
                    <input type="hidden" name="destination_id" id="destination_id" value="{{ old('destination_id') }}">

                    <div class="form-text">Jika tidak mencari, isi field wilayah di bawah secara manual.</div>
                  </div>

                  {{-- Field wilayah (tetap wajib kalau kamu butuh untuk pengiriman) --}}
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Provinsi <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="provinsi"
                      id="provinsi"
                      class="form-control form-control-lg"
                      value="{{ old('provinsi') }}"
                      required
                    >
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Kota <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="kota"
                      id="kota"
                      class="form-control form-control-lg"
                      value="{{ old('kota') }}"
                      required
                    >
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Kecamatan <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="kecamatan"
                      id="kecamatan"
                      class="form-control form-control-lg"
                      value="{{ old('kecamatan') }}"
                      required
                    >
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Kelurahan <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="kelurahan"
                      id="kelurahan"
                      class="form-control form-control-lg"
                      value="{{ old('kelurahan') }}"
                      required
                    >
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Pos <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="kode_pos"
                      id="kode_pos"
                      class="form-control form-control-lg"
                      value="{{ old('kode_pos') }}"
                      inputmode="numeric"
                      pattern="[0-9]*"
                      required
                    >
                  </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                  <button type="submit" class="btn btn-dark btn-lg rounded-pill">
                    Simpan Alamat
                  </button>
                  <a href="{{ route('checkout', request()->query('mode') === 'direct' ? ['mode' => 'direct'] : []) }}" class="btn btn-outline-secondary btn-lg rounded-pill">
                    Kembali
                  </a>
                </div>
              </div>

            </div>
          </form>

        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // === No Telp hanya angka ===
  const telp = document.getElementById('no_telp');
  const kodePos = document.getElementById('kode_pos');
  if (telp) {
    const sanitizeDigits = (v) => (v || '').replace(/\D/g, '');

    telp.addEventListener('input', (e) => {
      const cur = e.target.value;
      const clean = sanitizeDigits(cur);
      if (cur !== clean) e.target.value = clean;
    });

    telp.addEventListener('paste', (e) => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text');
      const clean = sanitizeDigits(text);

      const start = telp.selectionStart || 0;
      const end   = telp.selectionEnd || 0;

      telp.value = telp.value.slice(0, start) + clean + telp.value.slice(end);
    });
  }

  if (kodePos) {
    const sanitizeDigits = (v) => (v || '').replace(/\D/g, '');
    kodePos.addEventListener('input', (e) => {
      const cur = e.target.value;
      const clean = sanitizeDigits(cur);
      if (cur !== clean) e.target.value = clean;
    });
  }

  // === Komerce Search (OPSIONAL) ===
  const input  = document.getElementById('search_wilayah');
  const box    = document.getElementById('result_wilayah');
  const hidden = document.getElementById('destination_id');

  const prov = document.getElementById('provinsi');
  const kota = document.getElementById('kota');
  const kec  = document.getElementById('kecamatan');
  const kel  = document.getElementById('kelurahan');
  const kode = document.getElementById('kode_pos');

  let timer;

  const hideBox = () => { if (box) box.style.display = 'none'; };

  const makeShortText = (item) => {
    const parts = [];
    if (item.subdistrict_name && item.subdistrict_name !== '-') parts.push(item.subdistrict_name);
    if (item.district_name) parts.push(item.district_name);
    if (item.city_name) parts.push(item.city_name);
    return parts.join(', ');
  };

  const render = (items) => {
    if (!box) return;

    box.innerHTML = '';
    if (!items || items.length === 0) return hideBox();

    items.forEach((item) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'list-group-item list-group-item-action';
      btn.textContent = item.label;

      btn.addEventListener('click', () => {
        if (hidden) hidden.value = item.id || '';
        if (input)  input.value  = makeShortText(item);

        // auto isi
        if (prov) prov.value = item.province_name || '';
        if (kota) kota.value = item.city_name || '';
        if (kec)  kec.value  = item.district_name || '';
        if (kel)  kel.value  = item.subdistrict_name || '';
        if (kode) kode.value = item.zip_code || '';

        hideBox();
      });

      box.appendChild(btn);
    });

    box.style.display = 'block';
  };

  const doSearch = (q) => {
    const url =
      "{{ route('komerce.destination.search') }}" +
      "?search=" + encodeURIComponent(q) +
      "&limit=10&offset=0";

    fetch(url)
      .then((res) => res.json())
      .then((data) => render(Array.isArray(data.data) ? data.data : []))
      .catch((err) => console.error(err));
  };

  if (input) {
    input.addEventListener('input', () => {
      clearTimeout(timer);

      const q = input.value.trim();
      if (q.length < 3) return hideBox();

      timer = setTimeout(() => doSearch(q), 350);
    });

    document.addEventListener('click', (e) => {
      if (!box) return;
      if (e.target === input || box.contains(e.target)) return;
      hideBox();
    });
  }
</script>
@endpush
