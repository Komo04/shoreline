<?php

namespace App\Http\Controllers\Web;

use App\Models\Alamat;
use App\Models\Keranjang;
use App\Models\Pembayaran;
use App\Models\ProdukVarian;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use App\Models\User;
use App\Notifications\AdminPembelianBaru;
use App\Services\Shipping\KomerceOngkirService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Facade as Sentry;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $isDirectCheckout = $request->query('mode') === 'direct';

        if (!$isDirectCheckout) {
            $this->forgetDirectCheckoutItem();
        }

        $keranjangs = $this->resolveCheckoutItems($userId, $isDirectCheckout);

        if ($keranjangs->isEmpty()) {
            return redirect()->route('keranjang')->with('flash', [
                'type' => 'warning',
                'action' => 'empty_cart',
                'entity' => 'Keranjang',
                'timer' => 2500,
            ]);
        }

        $total = $keranjangs->sum(
            fn($item) =>
            ((int) optional($item->produk)->harga) * (int) $item->jumlah_produk
        );

        $alamats = Alamat::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->get();

        $defaultAlamat = $alamats->firstWhere('is_default', true) ?? $alamats->first();
        $defaultPaymentConfig = $this->paymentConfigForProvince($defaultAlamat?->provinsi);
        $paymentProfiles = collect($alamats)
            ->pluck('provinsi')
            ->filter()
            ->unique()
            ->mapWithKeys(fn ($provinsi) => [
                $this->normalizeProvinceKey($provinsi) => $this->paymentConfigForProvince($provinsi),
            ])
            ->all();

        return view('web.Checkout.checkout', compact(
            'keranjangs',
            'total',
            'alamats',
            'defaultPaymentConfig',
            'paymentProfiles',
            'isDirectCheckout'
        ));
    }

    public function direct(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(403);
        }

        $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'varian_id' => 'required|exists:produk_varians,id',
            'jumlah_produk' => 'required|integer|min:1',
        ]);

        $varian = ProdukVarian::with('produk')
            ->where('id', $request->varian_id)
            ->where('produk_id', $request->produk_id)
            ->first();

        if (!$varian || !$varian->produk) {
            return back()->with('error', 'Varian tidak valid untuk produk ini.');
        }

        $stok = (int) $varian->stok;
        $qty = (int) $request->jumlah_produk;

        if ($stok <= 0) {
            return back()->with('error', 'Stok habis untuk varian ini.');
        }

        if ($qty > $stok) {
            return back()->with('error', "Jumlah melebihi stok. Maksimal {$stok}.");
        }

        session([
            'checkout.direct_item' => [
                'produk_id' => (int) $varian->produk_id,
                'varian_id' => (int) $varian->id,
                'jumlah_produk' => $qty,
            ],
        ]);

        return redirect()->route('checkout', ['mode' => 'direct']);
    }

    public function store(Request $request, KomerceOngkirService $svc)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(403);
        }

        $request->validate([
            'alamat_id' => ['required', 'integer', function ($attr, $val, $fail) use ($userId) {
                if (!Alamat::where('id', $val)->where('user_id', $userId)->exists()) {
                    $fail('Alamat tidak valid.');
                }
            }],
            'metode_pembayaran' => 'required|string',
            'kurir_kode' => 'required|string',
            'kurir_layanan' => 'required|string',
        ]);

        $selectedAlamat = Alamat::where('id', $request->alamat_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $allowedPaymentMethods = $this->allowedPaymentMethodsForProvince($selectedAlamat->provinsi);
        if (!in_array($request->metode_pembayaran, $allowedPaymentMethods, true)) {
            throw ValidationException::withMessages([
                'metode_pembayaran' => 'Metode pembayaran tidak tersedia untuk provinsi alamat yang dipilih.',
            ]);
        }

        $isDirectCheckout = $request->input('checkout_mode') === 'direct';
        $keranjangs = $this->resolveCheckoutItems($userId, $isDirectCheckout);

        if ($keranjangs->isEmpty()) {
            return redirect()->route('keranjang')->with('flash', [
                'type' => 'warning',
                'action' => 'empty_cart',
                'entity' => 'Keranjang',
                'timer' => 2500,
            ]);
        }

        $totalWeight = 0;

        foreach ($keranjangs as $item) {
            $qty = (int) $item->jumlah_produk;
            $berat = (int) data_get($item, 'varian.berat_gram', 0);

            if ($berat <= 0) {
                return back()->with('flash', [
                    'type' => 'error',
                    'action' => 'validation',
                    'entity' => 'Produk',
                    'detail' => 'Berat produk belum diisi: ' . (data_get($item, 'produk.nama_produk', 'unknown')),
                ]);
            }

            $totalWeight += ($berat * $qty);
        }

        if ($totalWeight < 1) {
            $totalWeight = 1;
        }

        try {
            return DB::transaction(function () use ($request, $userId, $keranjangs, $svc, $totalWeight, $isDirectCheckout) {
                $subtotal = 0;

                foreach ($keranjangs as $item) {
                    $subtotal += ((int) optional($item->produk)->harga) * (int) $item->jumlah_produk;
                }

                $kode = 'TRX-' . strtoupper(uniqid());

                $alamat = Alamat::where('id', $request->alamat_id)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $originId = (int) config('services.shop.origin_id');
                $courier = strtolower(trim($request->kurir_kode));
                $service = trim($request->kurir_layanan);

                $ongkir = null;
                $selectedEtd = null;
                $selectedService = null;

                if ($alamat->destination_id) {
                    $result = $svc->calculateDomesticCost(
                        $originId,
                        (int) $alamat->destination_id,
                        (int) $totalWeight,
                        $courier
                    );

                    if (!empty($result['success'])) {
                        $options = data_get($result, 'data.data', data_get($result, 'data', []));
                        [$selectedCost, $selectedEtd, $selectedService] =
                            $this->pickShippingFromOptions($options, $service);

                        if ($selectedCost !== null) {
                            $ongkir = (int) $selectedCost;
                        }
                    }
                }

                if ($ongkir === null) {
                    $fallback = $this->fallbackShippingOptions(
                        $alamat->destination_id
                            ? 'Server ongkir sedang gangguan. Menggunakan ongkir fallback.'
                            : 'Alamat belum punya destination_id. Menggunakan ongkir fallback.',
                        $courier
                    );

                    if (empty($fallback['success'])) {
                        throw new \Exception($fallback['message'] ?? 'Gagal menghitung ongkir');
                    }

                    $fallbackOptions = data_get($fallback, 'data.data', []);
                    [$selectedCost, $selectedEtd, $selectedService] =
                        $this->pickShippingFromOptions($fallbackOptions, $service);

                    if ($selectedCost === null && isset($fallbackOptions[0])) {
                        $first = $fallbackOptions[0];
                        $selectedService = (string) ($first['service'] ?? 'FLAT');

                        $cost = data_get($first, 'cost', 0);
                        if (is_array($cost)) {
                            $selectedCost = (int) data_get($cost, '0.value', 0);
                            $selectedEtd = (string) data_get($cost, '0.etd', '');
                        } else {
                            $selectedCost = (int) $cost;
                            $selectedEtd = (string) data_get($first, 'etd', null);
                        }
                    }

                    $ongkir = (int) ($selectedCost ?? 0);
                }

                $grandTotal = $subtotal + (int) $ongkir;

                $transaksi = Transaksi::create([
                    'kode_transaksi' => $kode,
                    'midtrans_order_id' => $request->metode_pembayaran === 'midtrans' ? $kode : null,
                    'user_id' => $userId,

                    'alamat_id' => $alamat->id,

                    'shipping_nama_penerima' => $alamat->nama_penerima,
                    'shipping_nama_pengirim' => $alamat->nama_pengirim,
                    'shipping_no_telp' => $alamat->no_telp,
                    'shipping_kota' => $alamat->kota,
                    'shipping_kecamatan' => $alamat->kecamatan,
                    'shipping_kelurahan' => $alamat->kelurahan,
                    'shipping_provinsi' => $alamat->provinsi,
                    'shipping_kode_pos' => $alamat->kode_pos,
                    'shipping_alamat_lengkap' => $alamat->alamat_lengkap,
                    'shipping_destination_id' => $alamat->destination_id,

                    'metode_pembayaran' => $request->metode_pembayaran,
                    'status_transaksi' => 'pending',

                    'ongkir' => (int) $ongkir,
                    'kurir_kode' => $courier,
                    'kurir_layanan' => $selectedService ?: $service,
                    'kurir_etd' => $selectedEtd ?: null,
                    'kurir_etd_is_business_days' => true,

                    'total_pembayaran' => $grandTotal,

                    'payment_deadline' => now('Asia/Makassar')->addHour(),
                ]);

                foreach ($keranjangs as $item) {
                    $jumlah = (int) $item->jumlah_produk;
                    $produk = $item->produk;

                    if (! $produk) {
                        throw new \Exception('Produk pada keranjang tidak ditemukan');
                    }

                    $varian = ProdukVarian::lockForUpdate()
                        ->find($item->varian_id);

                    if (!$varian || $varian->stok < $jumlah) {
                        throw new \Exception('Stok tidak mencukupi');
                    }

                    TransaksiItem::create([
                        'transaksi_id' => $transaksi->id,
                        'produk_id' => $produk->id,
                        'produk_varian_id' => $item->varian_id,
                        'nama_produk' => $produk->nama_produk,
                        'warna' => $varian->warna ?? null,
                        'ukuran' => $varian->ukuran ?? null,
                        'qty' => $jumlah,
                        'harga_satuan' => $produk->harga,
                        'subtotal' => $produk->harga * $jumlah,
                    ]);
                }

                if ($transaksi->metode_pembayaran === 'midtrans') {
                    Pembayaran::create([
                        'transaksi_id' => $transaksi->id,
                        'metode_pembayaran' => 'midtrans',
                        'total_pembayaran' => $transaksi->total_pembayaran,
                        'status_pembayaran' => 'pending',
                        'tanggal_pembayaran' => now(),
                    ]);
                }

                if ($isDirectCheckout) {
                    $this->forgetDirectCheckoutItem();
                } else {
                    Keranjang::where('user_id', $userId)->delete();
                    Cache::forget("cart_count:user:{$userId}");
                }

                DB::afterCommit(function () use ($transaksi): void {
                    User::where('user_role', 'admin')
                        ->cursor()
                        ->each(fn (User $admin) => $admin->notify(new AdminPembelianBaru($transaksi)));
                });

                if ($transaksi->metode_pembayaran === 'midtrans') {
                    return redirect()->route('midtrans.pay', $transaksi->id);
                }

                return redirect()->route('pembayaran.upload', $transaksi->id)
                    ->with('flash', [
                        'type' => 'success',
                        'action' => 'create',
                        'entity' => 'Transaksi',
                    ]);
            });
        } catch (\Throwable $e) {
            Sentry::withScope(function (\Sentry\State\Scope $scope) use ($request, $userId, $e): void {
                $scope->setTag('module', 'checkout');
                $scope->setTag('feature', 'checkout.store');
                $scope->setExtra('user_id', $userId);
                $scope->setExtra('alamat_id', $request->alamat_id);
                $scope->setExtra('metode_pembayaran', $request->metode_pembayaran);
                $scope->setExtra('kurir_kode', $request->kurir_kode);
                $scope->setExtra('kurir_layanan', $request->kurir_layanan);
                Sentry::captureException($e);
            });

            Log::error('CHECKOUT ERROR', [
                'message' => $e->getMessage(),
                'user_id' => $userId,
                'alamat_id' => $request->alamat_id,
                'metode_pembayaran' => $request->metode_pembayaran,
                'kurir_kode' => $request->kurir_kode,
                'kurir_layanan' => $request->kurir_layanan,
            ]);

            return back()->with('flash', [
                'type' => 'error',
                'action' => 'create',
                'entity' => 'Transaksi',
                'title' => 'Checkout Gagal',
                'message' => 'Checkout gagal diproses. Silakan coba lagi.',
            ]);
        }
    }

    public function shippingOptions(Request $request, KomerceOngkirService $svc)
    {
        $userId = Auth::id();
        if (!$userId) abort(403);

        try {
            $request->validate([
                'alamat_id' => 'required|integer',
                'courier' => 'required|string'
            ]);

            $alamat = Alamat::where('user_id', $userId)
                ->where('id', $request->alamat_id)
                ->firstOrFail();

            $courier = strtolower(trim($request->courier));

            // ✅ jika destination_id kosong → jangan matikan, beri fallback
            if (!$alamat->destination_id) {
                Sentry::withScope(function (\Sentry\State\Scope $scope) use ($userId, $alamat, $courier): void {
                    $scope->setTag('module', 'checkout');
                    $scope->setTag('feature', 'checkout.shippingOptions');
                    $scope->setTag('shipping_mode', 'fallback');
                    $scope->setExtra('user_id', $userId);
                    $scope->setExtra('alamat_id', $alamat->id);
                    $scope->setExtra('destination_id', $alamat->destination_id);
                    $scope->setExtra('courier', $courier);
                    Sentry::captureMessage('Alamat checkout belum memiliki destination_id, fallback ongkir dipakai', \Sentry\Severity::warning());
                });

                return response()->json(
                    $this->fallbackShippingOptions('Alamat belum punya destination_id. Menggunakan ongkir fallback.', $courier)
                );
            }

            $isDirectCheckout = $request->input('checkout_mode') === 'direct';
            $keranjangs = $this->resolveCheckoutItems($userId, $isDirectCheckout);

            if ($keranjangs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keranjang kosong'
                ]);
            }

            $totalWeight = 0;

            foreach ($keranjangs as $item) {
                $qty = (int) $item->jumlah_produk;
                $berat = (int) data_get($item, 'varian.berat_gram', 0);

                if ($berat <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Berat produk belum diisi: ' . data_get($item, 'produk.nama_produk', 'unknown')
                    ]);
                }

                $totalWeight += ($berat * $qty);
            }

            if ($totalWeight < 1) $totalWeight = 1;

            $originId = (int) config('services.shop.origin_id');

            $result = $svc->calculateDomesticCost(
                $originId,
                (int)$alamat->destination_id,
                $totalWeight,
                $courier
            );

            if (empty($result['success'])) {
                Sentry::withScope(function (\Sentry\State\Scope $scope) use ($userId, $alamat, $courier, $totalWeight, $result): void {
                    $scope->setTag('module', 'checkout');
                    $scope->setTag('feature', 'checkout.shippingOptions');
                    $scope->setTag('shipping_mode', 'fallback');
                    $scope->setExtra('user_id', $userId);
                    $scope->setExtra('alamat_id', $alamat->id);
                    $scope->setExtra('destination_id', $alamat->destination_id);
                    $scope->setExtra('courier', $courier);
                    $scope->setExtra('total_weight', $totalWeight);
                    $scope->setExtra('shipping_error_message', $result['message'] ?? 'unknown');
                    Sentry::captureMessage('Checkout shippingOptions menggunakan fallback ongkir', \Sentry\Severity::warning());
                });

                return response()->json(
                    $this->fallbackShippingOptions($result['message'] ?? 'Server ongkir sedang gangguan. Menggunakan ongkir fallback.', $courier)
                );
            }

            $result['degraded'] = false;
            return response()->json($result);
        } catch (\Throwable $e) {
            Sentry::withScope(function (\Sentry\State\Scope $scope) use ($request, $userId, $e): void {
                $scope->setTag('module', 'checkout');
                $scope->setTag('feature', 'checkout.shippingOptions');
                $scope->setExtra('user_id', $userId);
                $scope->setExtra('alamat_id', $request->alamat_id);
                $scope->setExtra('courier', $request->courier);
                Sentry::captureException($e);
            });

            Log::error('CHECKOUT SHIPPING OPTIONS ERROR', [
                'message' => $e->getMessage(),
                'user_id' => $userId,
                'alamat_id' => $request->alamat_id,
                'courier' => $request->courier,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil opsi pengiriman.'
            ], 500);
        }
    }

    /**
     * Helper untuk memilih cost/etd/service dari options (format API maupun fallback).
     * Return: [cost(int|null), etd(string|null), service(string|null)]
     */
    private function pickShippingFromOptions($options, string $service): array
    {
        if (!is_array($options)) return [null, null, null];

        foreach ($options as $opt) {
            if ((string)($opt['service'] ?? '') === (string)$service) {
                $selectedService = (string) ($opt['service'] ?? '');

                $cost = data_get($opt, 'cost', 0);
                if (is_array($cost)) {
                    $selectedCost = (int) data_get($cost, '0.value', 0);
                    $selectedEtd  = (string) data_get($cost, '0.etd', '');
                } else {
                    $selectedCost = (int) $cost;
                    $selectedEtd  = (string) data_get($opt, 'etd', '');
                }

                return [$selectedCost, $selectedEtd ?: null, $selectedService ?: null];
            }
        }

        return [null, null, null];
    }

    /**
     * Fallback ongkir saat API down / destination_id kosong.
     * Format dibuat kompatibel dengan JS checkout kamu (json.data.data).
     */
    private function fallbackShippingOptions(string $msg, string $courier): array
    {
        $enabled = (bool) config('services.shipping.fallback_enabled', true);

        if (!$enabled) {
            return [
                'success' => false,
                'message' => $msg,
            ];
        }

        $courier = strtolower(trim($courier));
        $flat = (int) config('services.shipping.fallback_flat', 20000);
        $etd  = (string) config('services.shipping.fallback_etd', '2-5 hari');

        return [
            'success'  => true,
            'degraded' => true,
            'message'  => $msg,
            'data' => [
                'data' => $this->manualFallbackServices($courier, $flat, $etd),
            ],
        ];

        return [
            'success'  => true,
            'degraded' => true,
            'message'  => $msg,
            'data' => [
                'data' => [
                    [
                        'service'     => strtoupper($courier) . ' FLAT',
                        'description' => 'Tarif sementara (fallback) karena server ongkir sedang gangguan',
                        'cost'        => $flat,
                        'etd'         => $etd,
                        // ✅ ini dipakai JS untuk kurir_kode hidden
                        'code'        => $courier,   // <- jne/jnt/pos (bukan FLAT)
                    ],
                ],
            ],
        ];
    }

    private function manualFallbackServices(string $courier, int $flat, string $defaultEtd): array
    {
        $economy = max(10000, $flat - 5000);
        $regular = max($economy + 5000, $flat);
        $fast = $regular + 10000;

        $map = [
            'jne' => [
                ['service' => 'JNE OKE', 'description' => 'Ongkir manual ekonomi sementara', 'cost' => $economy, 'etd' => '3-5 hari'],
                ['service' => 'JNE REG', 'description' => 'Ongkir manual reguler sementara', 'cost' => $regular, 'etd' => $defaultEtd],
                ['service' => 'JNE YES', 'description' => 'Ongkir manual cepat sementara', 'cost' => $fast, 'etd' => '1-2 hari'],
            ],
            'jnt' => [
                ['service' => 'JNT ECO', 'description' => 'Ongkir manual ekonomi sementara', 'cost' => $economy, 'etd' => '3-5 hari'],
                ['service' => 'JNT EZ', 'description' => 'Ongkir manual reguler sementara', 'cost' => $regular, 'etd' => $defaultEtd],
                ['service' => 'JNT EXPRESS', 'description' => 'Ongkir manual cepat sementara', 'cost' => $fast, 'etd' => '1-2 hari'],
            ],
            'pos' => [
                ['service' => 'POS HEMAT', 'description' => 'Ongkir manual hemat sementara', 'cost' => $economy, 'etd' => '3-6 hari'],
                ['service' => 'POS REGULER', 'description' => 'Ongkir manual reguler sementara', 'cost' => $regular, 'etd' => $defaultEtd],
                ['service' => 'POS KILAT', 'description' => 'Ongkir manual cepat sementara', 'cost' => $fast, 'etd' => '1-2 hari'],
            ],
        ];

        $services = $map[$courier] ?? [
            ['service' => strtoupper($courier) . ' REG', 'description' => 'Ongkir manual reguler sementara', 'cost' => $regular, 'etd' => $defaultEtd],
        ];

        return array_map(function (array $service) use ($courier) {
            $service['code'] = $courier;

            return $service;
        }, $services);
    }

    private function paymentConfigForProvince(?string $province): array
    {
        $defaultConfig = config('payment.default', []);
        $provinceRules = (array) config('payment.province_rules', []);
        $provinceConfig = $provinceRules[$this->normalizeProvinceKey($province)] ?? [];

        return [
            'methods' => array_replace_recursive(
                (array) ($defaultConfig['methods'] ?? []),
                (array) ($provinceConfig['methods'] ?? [])
            ),
            'transfer' => array_replace(
                (array) ($defaultConfig['transfer'] ?? []),
                (array) ($provinceConfig['transfer'] ?? [])
            ),
        ];
    }

    private function allowedPaymentMethodsForProvince(?string $province): array
    {
        return collect($this->paymentConfigForProvince($province)['methods'] ?? [])
            ->filter(fn ($config) => !empty($config['enabled']))
            ->keys()
            ->values()
            ->all();
    }

    private function resolveCheckoutItems(int $userId, bool $preferDirect = false): Collection
    {
        $directItem = $preferDirect ? $this->getDirectCheckoutItem() : null;

        if ($directItem) {
            return collect([$directItem]);
        }

        return Keranjang::with(['produk', 'varian'])
            ->where('user_id', $userId)
            ->get();
    }

    private function getDirectCheckoutItem(): ?object
    {
        $payload = session('checkout.direct_item');

        if (!is_array($payload)) {
            return null;
        }

        $produkId = (int) ($payload['produk_id'] ?? 0);
        $varianId = (int) ($payload['varian_id'] ?? 0);
        $qty = (int) ($payload['jumlah_produk'] ?? 0);

        if ($produkId < 1 || $varianId < 1 || $qty < 1) {
            $this->forgetDirectCheckoutItem();
            return null;
        }

        $varian = ProdukVarian::with('produk')
            ->where('id', $varianId)
            ->where('produk_id', $produkId)
            ->first();

        if (!$varian || !$varian->produk) {
            $this->forgetDirectCheckoutItem();
            return null;
        }

        $stok = (int) $varian->stok;

        if ($stok < 1) {
            $this->forgetDirectCheckoutItem();
            return null;
        }

        if ($qty > $stok) {
            $qty = $stok;

            session([
                'checkout.direct_item' => [
                    'produk_id' => $produkId,
                    'varian_id' => $varianId,
                    'jumlah_produk' => $qty,
                ],
            ]);
        }

        return (object) [
            'id' => null,
            'user_id' => Auth::id(),
            'produk_id' => $produkId,
            'varian_id' => $varianId,
            'jumlah_produk' => $qty,
            'produk' => $varian->produk,
            'varian' => $varian,
        ];
    }
    private function forgetDirectCheckoutItem(): void
    {
        session()->forget('checkout.direct_item');
    }

    private function normalizeProvinceKey(?string $province): string
    {
        $province = strtolower(trim((string) $province));
        $province = preg_replace('/\s+/', ' ', $province);

        return $province;
    }
}
