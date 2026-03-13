@extends('layouts.mainlayout')
@section('title', $produk->nama_produk)

@push('styles')
<style>
    :root{
        --soft-border:#eef0f3;
        --soft-bg:#f8f9fb;
        --muted:#6c757d;
        --text:#111;
        --radius:18px;
        --radius-sm:14px;
    }


    .card-soft { border:0; border-radius:var(--radius); background:#fff; }
    .shadow-soft { box-shadow: 0 10px 28px rgba(0,0,0,.06); }
    .badge-soft { background:#f2f4f7; color:var(--text); border:1px solid #e6e8ec; }
    .text-small { font-size:12px; }
    .divider { border-top:1px solid var(--soft-border); }

    .content-box{
        background:var(--soft-bg);
        border:1px solid var(--soft-border);
        border-radius:var(--radius-sm);
        padding:14px;
    }
    .desc-text { color:var(--muted); line-height:1.85; white-space:pre-line; }
    .pretty-list { margin:0; padding-left:1.1rem; color:var(--muted); line-height:1.85; }
    .pretty-list li { margin-bottom: 6px; }

    .kv-grid { display:grid; grid-template-columns: 170px 1fr; gap:8px 14px; }
    .kv-key { color:var(--text); font-weight:800; font-size:13px; opacity:.85; }
    .kv-val { color:var(--muted); font-size:13px; line-height:1.7; white-space:pre-line; }

    .chips { display:flex; flex-wrap:wrap; gap:8px; }
    .chip {
        display:inline-flex; align-items:center; gap:6px;
        padding:6px 10px;
        border-radius:999px;
        border:1px solid #e6e8ec;
        background:#fff;
        font-size:12px;
        color:var(--text);
        white-space:nowrap;
    }
    .chip-dot { width:8px; height:8px; border-radius:999px; background:var(--text); opacity:.25; }

    .review-card { border:1px solid var(--soft-border); border-radius:16px; padding:14px; background:#fff; }

    .drop-wrap{
        background:#fff;
        border:1px solid var(--soft-border);
        border-radius:var(--radius);
        overflow:hidden;
    }
    .drop-wrap .accordion-item{ border:0; border-top:1px solid var(--soft-border); }
    .drop-wrap .accordion-item:first-child{ border-top:0; }

    .drop-wrap .accordion-button{
        background:#fff;
        color:var(--text);
        padding:18px 18px;
        font-weight:700;
        box-shadow:none !important;
    }
    .drop-wrap .accordion-button:focus{ border-color:transparent; box-shadow:none; }
    .drop-wrap .accordion-body{ padding: 0 18px 18px 18px; }

    .star-row{ display:inline-flex; align-items:center; gap:4px; line-height:1; vertical-align:middle; }
    .star{ font-size:18px; position:relative; display:inline-block; color:#e5e7eb; }
    .star.filled{ color:#f59e0b; }
    .star.half{ color:#e5e7eb; }
    .star.half::before{
        content:"★";
        position:absolute;
        left:0; top:0;
        width:50%;
        overflow:hidden;
        color:#f59e0b;
    }
    .star-sm{ font-size:14px; }

    /* ================== CHIP STYLE (SEPERTI FILTER) ================== */
    .chip-check{ position:relative; display:inline-flex; }
    .chip-check input{ position:absolute; opacity:0; pointer-events:none; }
    .chip-ui{
        display:inline-flex; align-items:center; gap:.5rem;
        padding:.45rem .8rem;
        border-radius:999px;
        border:1px solid rgba(0,0,0,.12);
        background:#fff;
        font-size:.85rem;
        cursor:pointer;
        user-select:none;
        transition: all .15s ease;
        color:#111827;
    }
    .chip-ui:hover{ transform: translateY(-1px); box-shadow:0 10px 18px rgba(0,0,0,.06); }
    .chip-check input:checked + .chip-ui{
        border-color: rgba(13,110,253,.55);
        box-shadow: 0 0 0 .2rem rgba(13,110,253,.15);
    }
    .dot-ui{
        width:14px;height:14px;border-radius:999px;
        border:1px solid rgba(0,0,0,.15);
        background:#e5e7eb;
        flex:0 0 auto;
    }

    .option-row{ display:flex; flex-wrap:wrap; gap:10px; }
    .select-hidden{ position:absolute; left:-9999px; width:1px; height:1px; opacity:0; pointer-events:none; }
</style>
@endpush

@section('content')
<div class="container py-5">

    {{-- NOTIFIKASI --}}
   @php
        $deskripsiRaw  = trim((string) ($produk->deskripsi_produk ?? ''));
        $keteranganRaw = trim((string) ($produk->keterangan ?? ''));

        $linesOf = function(string $text): array {
            $lines = preg_split("/\r\n|\n|\r/", $text);
            $lines = array_map(fn($l) => trim($l), $lines);
            return array_values(array_filter($lines, fn($l) => $l !== ''));
        };

        $stripBullet = fn(string $s) => preg_replace('/^[-*]\s+/', '', $s);
        $isBulletLine = fn(string $s) => (bool) preg_match('/^[-*]\s+/', $s);

        $isKeyValue = function(string $s, &$m = null): bool {
            return (bool) preg_match('/^(.{1,50})\s*:\s*(.+)$/', $s, $m);
        };

        $parseKeyValueLines = function(array $lines) use ($stripBullet, $isKeyValue) {
            $pairs = [];
            $notes = [];
            foreach ($lines as $l) {
                $clean = trim($stripBullet($l));
                if ($clean === '') continue;
                $m = [];
                if ($isKeyValue($clean, $m)) {
                    $pairs[] = ['k' => trim($m[1]), 'v' => trim($m[2])];
                } else {
                    $notes[] = $clean;
                }
            }
            return [$pairs, $notes];
        };

        $makeChips = function(array $pairs): array {
            $wanted = ['Bahan','Material','Berat','Ukuran','Garansi','Warna','Model','Jenis'];
            $out = [];
            foreach ($pairs as $p) {
                if (in_array($p['k'], $wanted)) $out[] = $p;
                if (count($out) >= 4) break;
            }
            return $out;
        };

        $renderStars = function($rating, $size = 'md') {
            $rating = max(0, min(5, (float)$rating));
            $full = floor($rating);
            $decimal = $rating - $full;
            $half = ($decimal >= 0.25 && $decimal < 0.75) ? 1 : 0;
            $full = ($decimal >= 0.75) ? $full + 1 : $full;
            $empty = 5 - $full - $half;

            $cls = $size === 'sm' ? 'star star-sm' : 'star';

            $html = '<span class="star-row" aria-label="Rating '.$rating.' dari 5">';
            for ($i=0; $i<$full; $i++) $html .= '<span class="'.$cls.' filled">★</span>';
            if ($half) $html .= '<span class="'.$cls.' half">★</span>';
            for ($i=0; $i<$empty; $i++) $html .= '<span class="'.$cls.'">★</span>';
            $html .= '</span>';

            return $html;
        };

        $dLines = $deskripsiRaw !== '' ? $linesOf($deskripsiRaw) : [];
        $kLines = $keteranganRaw !== '' ? $linesOf($keteranganRaw) : [];

        $dHasBullet = false;
        foreach ($dLines as $l) { if ($isBulletLine($l)) { $dHasBullet = true; break; } }

        [$kPairs, $kNotes] = $parseKeyValueLines($kLines);
        $kUseGrid = (count($kPairs) >= 2 && count($kPairs) >= count($kNotes));
        $kHasBullet = false;
        foreach ($kLines as $l) { if ($isBulletLine($l)) { $kHasBullet = true; break; } }

        $kChips = $makeChips($kPairs);

        // map warna -> hex (seperti filter)
        $warnaMap = [
            'Merah' => '#ef4444', 'Red' => '#ef4444',
            'Biru' => '#3b82f6', 'Blue' => '#3b82f6',
            'Biru Muda' => '#00ffff','Aqua' => '#00ffff',
            'Biru Tua' => '#000080','Navy' => '#000080',
            'Hitam' => '#111827', 'Black' => '#111827',
            'Putih' => '#ffffff', 'White' => '#ffffff',
            'Hijau' => '#22c55e', 'Green' => '#22c55e',
            'Kuning' => '#f59e0b','Yellow' => '#f59e0b',
            'Orange' => '#f97316','Oranye' => '#f97316',
            'Pink' => '#ec4899',
            'Ungu' => '#8b5cf6','Purple' => '#8b5cf6',
            'Coklat' => '#92400e','Brown' => '#92400e',
            'Abu' => '#9ca3af','Grey' => '#9ca3af','Gray' => '#9ca3af',
        ];
    @endphp

    <div class="row">
        {{-- GAMBAR --}}
        <div class="col-md-6 mb-4">
            @php $firstVarian = $produk->varians->first(); @endphp

            <img id="mainImage"
                 src="{{ $firstVarian && $firstVarian->gambar_varian
                        ? asset('storage/'.$firstVarian->gambar_varian)
                        : 'https://via.placeholder.com/500x500?text=No+Image' }}"
                 class="img-fluid rounded shadow"
                 alt="{{ $produk->nama_produk }}">
        </div>

        {{-- DETAIL + CART --}}
        <div class="col-md-6">
            <h3 class="fw-bold">{{ $produk->nama_produk }}</h3>
            <p class="text-muted">{{ $produk->deskripsi ?? '' }}</p>

            <h4 class="fw-bold text-dark mb-3">
                Rp {{ number_format($produk->harga, 0, ',', '.') }}
            </h4>

            <hr>

            @if($produk->varians->isEmpty())
                <div class="alert alert-warning">
                    Produk ini belum memiliki varian.
                </div>
            @else
                <form action="{{ route('keranjang.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="produk_id" value="{{ $produk->id }}">

                    {{-- SELECT HIDDEN (biar JS lama tetap jalan) --}}
                    <select id="warnaSelect" class="select-hidden" required></select>
                    <select name="varian_id" id="ukuranSelect" class="select-hidden" required></select>

                    {{-- WARNA CHIP --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Warna</label>
                        <div id="warnaChips" class="option-row"></div>
                    </div>

                    {{-- UKURAN CHIP --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Ukuran</label>
                        <div id="ukuranChips" class="option-row"></div>
                    </div>

                    <p class="text-muted mb-2">
                        Stok tersisa:
                        <strong><span id="stokText">0</span></strong>
                    </p>

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <button type="button" class="btn btn-outline-dark" id="btnMinus">-</button>

                        <input type="number"
                               name="jumlah_produk"
                               id="qtyInput"
                               class="form-control text-center"
                               style="max-width: 100px;"
                               value="1"
                               min="1"
                               inputmode="numeric"
                               required>

                        <button type="button" class="btn btn-outline-dark" id="btnPlus">+</button>

                        <small class="text-muted ms-2">
                            Max: <span id="maxQtyText">0</span>
                        </small>
                    </div>

                    <button type="submit" id="btnCart" class="btn btn-dark w-100 rounded-pill">
                        Tambah ke Keranjang
                    </button>
                </form>

                <a href="{{ route('keranjang') }}" class="btn btn-outline-dark w-100 rounded-pill mt-2">
                    Checkout
                </a>
            @endif

            @if(!empty($kChips))
                <div class="card card-soft shadow-soft mt-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Highlight</div>
                        <div class="chips">
                            @foreach($kChips as $c)
                                <div class="chip">
                                    <span class="chip-dot"></span>
                                    <span class="fw-semibold">{{ $c['k'] }}</span>
                                    <span class="text-muted">•</span>
                                    <span>{{ $c['v'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- DESKRIPSI + KETERANGAN --}}
    <div class="drop-wrap shadow-soft mt-4">
        <div class="accordion" id="infoDrop">

            <div class="accordion-item">
                <h2 class="accordion-header" id="headDesc">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dropDesc">
                        Deskripsi
                    </button>
                </h2>
                <div id="dropDesc" class="accordion-collapse collapse" aria-labelledby="headDesc" data-bs-parent="#infoDrop">
                    <div class="accordion-body">
                        <div class="content-box">
                            @if($deskripsiRaw === '')
                                <div class="desc-text">Produk ini belum memiliki deskripsi.</div>
                            @else
                                @if($dHasBullet && count($dLines) >= 2)
                                    <ul class="pretty-list">
                                        @foreach($dLines as $line)
                                            <li>{{ $stripBullet($line) }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="desc-text">{{ $deskripsiRaw }}</div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headKet">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dropKet">
                        Keterangan
                    </button>
                </h2>
                <div id="dropKet" class="accordion-collapse collapse" aria-labelledby="headKet" data-bs-parent="#infoDrop">
                    <div class="accordion-body">
                        <div class="content-box">
                            @if($keteranganRaw === '')
                                <div class="desc-text">Belum ada keterangan tambahan.</div>
                            @else
                                @if($kUseGrid)
                                    <div class="kv-grid">
                                        @foreach($kPairs as $p)
                                            <div class="kv-key">{{ $p['k'] }}</div>
                                            <div class="kv-val">{{ $p['v'] }}</div>
                                        @endforeach

                                        @if(!empty($kNotes))
                                            <div class="kv-key">Catatan</div>
                                            <div class="kv-val">{{ implode("\n", $kNotes) }}</div>
                                        @endif
                                    </div>
                                @else
                                    @if($kHasBullet && count($kLines) >= 2)
                                        <ul class="pretty-list">
                                            @foreach($kLines as $line)
                                                <li>{{ $stripBullet($line) }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="desc-text">{{ $keteranganRaw }}</div>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
{{-- ============================
    RATING SUMMARY
============================= --}}
<div class="card card-soft shadow-soft mt-3">
    <div class="card-body d-flex align-items-center justify-content-between">
        <div>
            <div class="fw-semibold">Rating Produk</div>
            <div class="text-muted text-small">Berdasarkan ulasan pembeli</div>
        </div>
        <div class="text-end">
            <div class="d-flex align-items-center gap-2 justify-content-end">
                {!! $renderStars($ratingAvg ?? 0, 'md') !!}
                <div class="fw-bold" style="font-size:18px;">
                    {{ number_format($ratingAvg ?? 0, 1) }}
                </div>
            </div>
            <div class="text-muted text-small">{{ $ratingCount ?? 0 }} ulasan</div>
        </div>
    </div>
</div>

{{-- ============================
    ULASAN + FORM
============================= --}}
<div class="row g-4 mt-4">

    {{-- LIST ULASAN --}}
    <div class="col-lg-7">
        <div class="card card-soft shadow-soft">
            <div class="card-body p-4">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Ulasan Pembeli</h5>
                        <div class="text-muted text-small">Terbaru ditampilkan di atas</div>
                    </div>
                    <span class="badge badge-soft rounded-pill px-3 py-2">
                        {{ $ratingCount ?? 0 }} ulasan
                    </span>
                </div>

                <div class="divider mb-3"></div>

                @forelse($produk->ulasans ?? [] as $u)
                    <div class="review-card mb-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="fw-semibold">{{ $u->user?->name ?? 'User' }}</div>
                                <div class="text-muted text-small">{{ $u->created_at->format('d-m-Y H:i') }}</div>
                            </div>
                            <div class="text-end">
                                {!! $renderStars($u->rating ?? 0, 'sm') !!}
                                <div class="text-muted text-small mt-1">{{ $u->rating ?? 0 }}/5</div>
                            </div>
                        </div>
                        <div class="mt-2 desc-text">{{ $u->komentar ?? '-' }}</div>
                    </div>
                @empty
                    <div class="alert alert-secondary mb-0">
                        Belum ada ulasan untuk produk ini.
                    </div>
                @endforelse

            </div>
        </div>
    </div>

    {{-- FORM ULASAN --}}
    <div class="col-lg-5">
        <div class="card card-soft shadow-soft">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-1">{{ !empty($myUlasan) ? 'Update Ulasan Saya' : 'Tulis Ulasan' }}</h5>
                <div class="text-muted text-small mb-3">
                    Hanya pembeli yang bisa mengulas (dicek oleh middleware).
                </div>

                @if(!\Illuminate\Support\Facades\Auth::check())
                    <div class="alert alert-info mb-0">
                        Silakan login untuk memberi ulasan.
                    </div>
                @else
                    <form action="{{ route('produk.ulasan.store', $produk->id) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rating (1-5)</label>
                            <input type="number"
                                   name="rating"
                                   min="1" max="5"
                                   class="form-control"
                                   value="{{ old('rating', $myUlasan->rating ?? 5) }}"
                                   required>
                            @error('rating') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Komentar</label>
                            <textarea name="komentar"
                                      rows="4"
                                      class="form-control"
                                      placeholder="Tulis pengalaman kamu...">{{ old('komentar', $myUlasan->komentar ?? '') }}</textarea>
                            @error('komentar') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <button class="btn btn-dark w-100 rounded-pill">
                            {{ !empty($myUlasan) ? 'Update Ulasan' : 'Kirim Ulasan' }}
                        </button>
                    </form>
                @endif

            </div>
        </div>
    </div>

</div>

</div>
@endsection

@push('scripts')
@php
    $variansJson = $produk->varians->map(function($v){
        return [
            'id'     => $v->id,
            'warna'  => $v->warna,
            'ukuran' => $v->ukuran,
            'stok'   => (int)$v->stok,
            'image'  => $v->gambar_varian ? asset('storage/'.$v->gambar_varian) : '',
        ];
    })->values();
@endphp

<script>
const varians = @json($variansJson);

const warnaSelect  = document.getElementById('warnaSelect');
const ukuranSelect = document.getElementById('ukuranSelect');
const stokText     = document.getElementById('stokText');
const mainImage    = document.getElementById('mainImage');
const btnCart      = document.getElementById('btnCart');

const qtyInput     = document.getElementById('qtyInput');
const btnMinus     = document.getElementById('btnMinus');
const btnPlus      = document.getElementById('btnPlus');
const maxQtyText   = document.getElementById('maxQtyText');

const warnaChipsWrap  = document.getElementById('warnaChips');
const ukuranChipsWrap = document.getElementById('ukuranChips');

let currentStok = 0;

/* map warna -> hex (sama seperti blade) */
const warnaMap = {
  'Merah':'#ef4444','Red':'#ef4444',
  'Biru':'#3b82f6','Blue':'#3b82f6',
  'Biru Muda':'#00ffff','Aqua':'#00ffff',
  'Biru Tua':'#000080','Navy':'#000080',
  'Hitam':'#111827','Black':'#111827',
  'Putih':'#ffffff','White':'#ffffff',
  'Hijau':'#22c55e','Green':'#22c55e',
  'Kuning':'#f59e0b','Yellow':'#f59e0b',
  'Orange':'#f97316','Oranye':'#f97316',
  'Pink':'#ec4899',
  'Ungu':'#8b5cf6','Purple':'#8b5cf6',
  'Coklat':'#92400e','Brown':'#92400e',
  'Abu':'#9ca3af','Grey':'#9ca3af','Gray':'#9ca3af',
};

function getWarnaList() {
  return [...new Set(varians.map(v => v.warna))];
}

/* ====== SELECT (tetap dipakai oleh logic lama) ====== */
function renderWarna() {
  if (!warnaSelect) return;
  warnaSelect.innerHTML = '';
  getWarnaList().forEach(w => {
    const opt = document.createElement('option');
    opt.value = w;
    opt.textContent = w;
    warnaSelect.appendChild(opt);
  });
}

/* ====== CHIPS WARNA ====== */
function renderWarnaChips() {
  if (!warnaChipsWrap) return;
  warnaChipsWrap.innerHTML = '';

  const list = getWarnaList();
  list.forEach((w, idx) => {
    const id = `warna-chip-${idx}`;

    const label = document.createElement('label');
    label.className = 'chip-check';

    const input = document.createElement('input');
    input.type = 'radio';
    input.name = 'warna_chip';
    input.value = w;
    input.id = id;
    if (idx === 0) input.checked = true;

    const span = document.createElement('span');
    span.className = 'chip-ui';

    const dot = document.createElement('span');
    dot.className = 'dot-ui';
    dot.style.background = (warnaMap[w] || '#e5e7eb');

    // kalau putih, kasih border agak gelap biar kelihatan
    if ((warnaMap[w] || '').toLowerCase() === '#ffffff') {
      dot.style.borderColor = 'rgba(0,0,0,.25)';
    }

    const text = document.createTextNode(w);

    span.appendChild(dot);
    span.appendChild(text);

    label.appendChild(input);
    label.appendChild(span);
    warnaChipsWrap.appendChild(label);

    input.addEventListener('change', () => {
      // sinkron ke select hidden + pakai logic lama
      warnaSelect.value = w;
      renderUkuran(w);
    });
  });
}

/* ====== SELECT ukuran dibuat oleh logic lama ====== */
function renderUkuran(warna) {
  if (!ukuranSelect) return;
  ukuranSelect.innerHTML = '';

  const list = varians.filter(v => v.warna === warna);
  const order = {'XS':1,'S':2,'M':3,'L':4,'XL':5,'One Size':6};
  list.sort((a,b) => ((order[a.ukuran] ?? 99) - (order[b.ukuran] ?? 99)));

  list.forEach(v => {
    const opt = document.createElement('option');
    opt.value = v.id;
    opt.textContent = v.ukuran + (v.stok <= 0 ? ' (Habis)' : '');
    opt.dataset.stok = v.stok;
    opt.dataset.image = v.image;
    opt.disabled = v.stok <= 0;
    ukuranSelect.appendChild(opt);
  });

  let idx = 0;
  while (idx < ukuranSelect.options.length && ukuranSelect.options[idx].disabled) idx++;

  if (ukuranSelect.options.length === 0 || idx >= ukuranSelect.options.length) {
    setOutOfStockUI();
    renderUkuranChips([]); // kosong
    return;
  }

  ukuranSelect.selectedIndex = idx;
  renderUkuranChipsFromSelect(); // ✅ buat chips dari select
  updateDetail();
}

/* ====== CHIPS ukuran dibuat dari select (biar stok/disabled ikut) ====== */
function renderUkuranChipsFromSelect(){
  if (!ukuranChipsWrap || !ukuranSelect) return;
  ukuranChipsWrap.innerHTML = '';

  const opts = Array.from(ukuranSelect.options);
  if (opts.length === 0) return;

  opts.forEach((opt, idx) => {
    const id = `ukuran-chip-${idx}`;

    const label = document.createElement('label');
    label.className = 'chip-check';

    const input = document.createElement('input');
    input.type = 'radio';
    input.name = 'ukuran_chip';
    input.value = opt.value;
    input.id = id;
    input.checked = idx === ukuranSelect.selectedIndex;
    input.disabled = opt.disabled;

    const span = document.createElement('span');
    span.className = 'chip-ui';
    span.textContent = opt.textContent.replace(' (Habis)', '');

    // kalau habis, kasih efek disabled halus
    if (opt.disabled) {
      span.style.opacity = '0.45';
      span.style.cursor = 'not-allowed';
    }

    label.appendChild(input);
    label.appendChild(span);
    ukuranChipsWrap.appendChild(label);

    input.addEventListener('change', () => {
      ukuranSelect.value = opt.value;
      updateDetail();
    });
  });
}

/* helper jika butuh kosong */
function renderUkuranChips(list){ /* keep for compatibility */ }

/* ===== stock/ui logic kamu (tetap) ===== */
function setOutOfStockUI() {
  currentStok = 0;
  stokText && (stokText.innerText = 0);
  maxQtyText && (maxQtyText.innerText = 0);

  if (qtyInput) {
    qtyInput.value = 1;
    qtyInput.min = 1;
    qtyInput.max = 1;
    qtyInput.disabled = true;
  }

  btnMinus && (btnMinus.disabled = true);
  btnPlus  && (btnPlus.disabled = true);

  if (btnCart) {
    btnCart.disabled = true;
    btnCart.innerText = 'Stok Habis';
  }
}

function clampQty() {
  if (!qtyInput) return;
  let qty = parseInt(qtyInput.value || '1', 10);
  if (isNaN(qty) || qty < 1) qty = 1;
  if (currentStok > 0 && qty > currentStok) qty = currentStok;
  qtyInput.value = qty;
}

function updateQtyControls() {
  if (!qtyInput) return;

  qtyInput.disabled = currentStok <= 0;
  qtyInput.min = 1;
  qtyInput.max = currentStok > 0 ? currentStok : 1;
  maxQtyText && (maxQtyText.innerText = currentStok);

  clampQty();

  const v = parseInt(qtyInput.value || '1', 10);
  btnMinus && (btnMinus.disabled = currentStok <= 0 || v <= 1);
  btnPlus  && (btnPlus.disabled  = currentStok <= 0 || v >= currentStok);
}

function updateDetail() {
  if (!ukuranSelect) return;
  const opt = ukuranSelect.options[ukuranSelect.selectedIndex];
  if (!opt) return;

  const stok = parseInt(opt.dataset.stok || '0', 10);
  const img  = opt.dataset.image || '';

  currentStok = stok;
  stokText && (stokText.innerText = stok);

  if (img && mainImage) mainImage.src = img;

  if (stok <= 0) {
    setOutOfStockUI();
    return;
  }

  if (btnCart) {
    btnCart.disabled = false;
    btnCart.innerText = 'Tambah ke Keranjang';
  }

  updateQtyControls();

  // sync chips check untuk ukuran
  const radios = document.querySelectorAll('input[name="ukuran_chip"]');
  radios.forEach(r => r.checked = (r.value === ukuranSelect.value));
}

btnMinus && btnMinus.addEventListener('click', () => {
  const v = parseInt(qtyInput.value || '1', 10);
  qtyInput.value = Math.max(1, v - 1);
  updateQtyControls();
});

btnPlus && btnPlus.addEventListener('click', () => {
  const v = parseInt(qtyInput.value || '1', 10);
  qtyInput.value = Math.min(currentStok, v + 1);
  updateQtyControls();
});

qtyInput && qtyInput.addEventListener('input', () => {
  qtyInput.value = (qtyInput.value || '').replace(/[^\d]/g, '');
  updateQtyControls();
});

document.addEventListener('DOMContentLoaded', () => {
  if (!varians || varians.length === 0) return;

  renderWarna();        // select hidden
  renderWarnaChips();   // UI chips

  // set default warna dari chip pertama
  const firstWarna = getWarnaList()[0];
  if (warnaSelect) warnaSelect.value = firstWarna;

  renderUkuran(firstWarna);
});
</script>
@endpush
