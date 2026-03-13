<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminKontakController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\JumlahController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\LaporanPendapatanController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\ProdukController as AdminProdukController;
use App\Http\Controllers\Admin\ProdukVarianController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\StokinController;
use App\Http\Controllers\Admin\StokLogController;
use App\Http\Controllers\Admin\StokoutController;
use App\Http\Controllers\Admin\TransaksiController;
use App\Http\Controllers\Admin\UlasanAdminController;
use App\Http\Controllers\Auth\ResetPasswordSecureController;
use App\Http\Controllers\Web\AlamatController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\KeranjangController;
use App\Http\Controllers\Web\KomerceController;
use App\Http\Controllers\Web\KontakController;
use App\Http\Controllers\Web\MidtransPayController;
use App\Http\Controllers\Web\MidtransWebhookController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PembayaranUserController;
use App\Http\Controllers\Web\ProdukController as WebProdukController;
use App\Http\Controllers\Web\ProfileController as WebProfileController;
use App\Http\Controllers\Web\ProfileUlasanController;
use App\Http\Controllers\Web\RefundController;
use App\Http\Controllers\Web\TentangController;
use App\Http\Controllers\Web\TransaksiUserController;
use App\Http\Controllers\Web\UlasanController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
/* =====================================================
   PUBLIC ROUTES (BISA DIAKSES TANPA LOGIN)
===================================================== */

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/produk', [WebProdukController::class, 'index'])->name('produk');
Route::get('/produk/{produk}', [WebProdukController::class, 'show'])->name('produk.show');

Route::get('/tentang', [TentangController::class, 'index'])->name('tentang');

Route::get('/kontak', [KontakController::class, 'index'])->name('kontak');
Route::post('/kontak', [KontakController::class, 'store'])->name('kontak.store');


// Midtrans webhook (cukup 1 endpoint, pilih salah satu nama)
Route::post('/midtrans/webhook', [MidtransWebhookController::class, 'handle'])->name('midtrans.webhook');
// Kalau kamu masih butuh alias "notification", boleh tambahkan alias ini (tidak duplikat handle):
Route::post('/midtrans/notification', [MidtransWebhookController::class, 'handle'])->name('midtrans.notification');

Route::post('/reset-password-secure', [ResetPasswordSecureController::class, 'update'])
    ->middleware('guest')
    ->name('password.update.secure');
// USER logout

/* =====================================================
   CUSTOMER ROUTES (WAJIB LOGIN)
===================================================== */
Route::middleware(['auth', 'active'])->group(function () {

    /* =========================
   PROFILE (USER)
========================== */

    Route::prefix('profil')->name('user.')->group(function () {
        Route::get('/akun', [WebProfileController::class, 'edit'])->name('profile.edit');

        Route::put('/akun', [WebProfileController::class, 'updateAccount'])
            ->middleware('throttle:profile-update')
            ->name('profile.update');

        Route::put('/password', [WebProfileController::class, 'updatePassword'])
            ->middleware('throttle:password-update')
            ->name('profile.password.update');
    });
    Route::get('/transaksi/{transaksi}/tracking-json', [TransaksiUserController::class, 'trackingJson'])
        ->name('transaksi.tracking.json');



    // NOTIFIKASI
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifikasi.read');
    Route::post('/notifikasi/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifikasi.readAll');

    // KERANJANG
    Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang');
    Route::post('/keranjang', [KeranjangController::class, 'store'])->name('keranjang.store');
    Route::put('/keranjang/{id}', [KeranjangController::class, 'update'])->name('keranjang.update');
    Route::delete('/keranjang/{id}', [KeranjangController::class, 'destroy'])->name('keranjang.destroy');
    Route::put('/keranjang/{id}/varian', [KeranjangController::class, 'updateVarian'])->name('keranjang.updateVarian');

    // CHECKOUT
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/shipping-options', [CheckoutController::class, 'shippingOptions'])->name('checkout.shippingOptions');

    // ALAMAT
    Route::resource('alamat', AlamatController::class);
    Route::put('/alamat/{alamat}/default', [AlamatController::class, 'setDefault'])->name('alamat.setDefault');
    Route::get('/komerce/destination/search', [KomerceController::class, 'searchDestination'])->name('komerce.destination.search');

    // PEMBAYARAN MANUAL (TRANSFER/QRIS)
    Route::get('/pembayaran/{transaksi}/upload', [PembayaranUserController::class, 'create'])->name('pembayaran.upload');
    Route::post('/pembayaran/upload', [PembayaranUserController::class, 'store'])->name('pembayaran.store');

    // MIDTRANS PAY PAGE
    Route::get('/midtrans/pay/{transaksi}', [MidtransPayController::class, 'pay'])->name('midtrans.pay');

    // PESANAN (USER)
    Route::get('/pesanan', [TransaksiUserController::class, 'index'])->name('transaksi.index');
    Route::get('/pesanan/{transaksi}', [TransaksiUserController::class, 'show'])->name('transaksi.show');

    // ✅ tombol "Pesanan diterima" (user)
    Route::put('/pesanan/{transaksi}/diterima', [TransaksiUserController::class, 'diterima'])
        ->name('transaksi.diterima');

    // status json polling (opsional)
    Route::get('/pesanan/{transaksi}/status', function (\App\Models\Transaksi $transaksi) {
        abort_unless($transaksi->user_id === Auth::id(), 403);

        $transaksi->loadMissing('pembayaran');

        return response()->json([
            'status_transaksi' => $transaksi->status_transaksi,
            'status_pembayaran' => optional($transaksi->pembayaran)->status_pembayaran,
            'paid_at' => optional($transaksi->paid_at)?->toDateTimeString(),
        ]);
    })->name('transaksi.status');

    // REFUND (USER AJUKAN)
    Route::post('/refund/{id}', [RefundController::class, 'request'])
        ->name('refund.request');

    // PROFIL - ULASAN
    Route::prefix('profil')->name('user.')->group(function () {
        Route::get('/ulasans', [ProfileUlasanController::class, 'index'])->name('ulasans.index');
        Route::get('/ulasans/{ulasan}/edit', [ProfileUlasanController::class, 'edit'])->name('ulasans.edit');
        Route::put('/ulasans/{ulasan}', [ProfileUlasanController::class, 'update'])->name('ulasans.update');
        Route::delete('/ulasans/{ulasan}', [ProfileUlasanController::class, 'destroy'])->name('ulasans.destroy');
    });

});


/* =====================================================
   ADMIN ROUTES
===================================================== */
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {


        /* =========================
   PROFILE (ADMIN)
========================== */
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');

        Route::put('/profile', [AdminProfileController::class, 'updateAccount'])
            ->middleware('throttle:profile-update')
            ->name('profile.update');

        Route::post('/transaksi/{id}/refund/process', [TransaksiController::class, 'processRefund'])
            ->name('transaksi.refund.process');

        Route::post('/transaksi/{id}/refund/finalize', [TransaksiController::class, 'finalizeManualRefund'])
            ->name('transaksi.refund.finalize');

        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])
            ->middleware('throttle:password-update')
            ->name('profile.password.update');

        Route::post('/notifikasi/{id}/read', [AdminNotificationController::class, 'markAsRead'])->name('notifikasi.read');
        Route::post('/notifikasi/read-all', [AdminNotificationController::class, 'markAllAsRead'])->name('notifikasi.readAll');
        Route::get('/notifikasi', [AdminNotificationController::class, 'index'])->name('notifikasi.index');

        Route::get('/kontak', [AdminKontakController::class, 'index'])->name('kontak.index');
        Route::get('/kontak/{kontak}', [AdminKontakController::class, 'show'])->name('kontak.show');
        Route::get('/kontak/{kontak}/reply', [AdminKontakController::class, 'replyForm'])->name('kontak.replyForm');
        Route::post('/kontak/{kontak}/reply', [AdminKontakController::class, 'replySend'])->name('kontak.replySend');

        Route::get('/transaksi/{transaksi}/tracking-json', [TransaksiController::class, 'trackingJson'])
            ->name('transaksi.tracking.json');

        // DASHBOARD
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // KATEGORI
        Route::resource('kategori', KategoriController::class);

        // PRODUK
        Route::resource('produk', AdminProdukController::class);

        // VARIAN PRODUK
        Route::get('produk/{produk}/varian', [ProdukVarianController::class, 'index'])->name('varian.index');
        Route::get('produk/{produk}/varian/create', [ProdukVarianController::class, 'create'])->name('varian.create');
        Route::post('produk/{produk}/varian', [ProdukVarianController::class, 'store'])->name('varian.store');
        Route::get('varian/{varian}/edit', [ProdukVarianController::class, 'edit'])->name('varian.edit');
        Route::put('varian/{varian}', [ProdukVarianController::class, 'update'])->name('varian.update');
        Route::delete('varian/{varian}', [ProdukVarianController::class, 'destroy'])->name('varian.destroy');

        // STOK & LOG
        Route::get('/stokin', [StokinController::class, 'index'])->name('stokin');
        Route::post('/stokin', [StokinController::class, 'store'])->name('stokin.store');

        Route::get('/stokout', [StokoutController::class, 'index'])->name('stokout');
        Route::get('/stoklog', [StokLogController::class, 'index'])->name('stoklog');

        Route::get('/jumlah', [JumlahController::class, 'index'])->name('jumlah');

        // CUSTOMER
        Route::get('/customer', [CustomerController::class, 'index'])->name('customer.index');
        Route::patch('/customer/{id}/toggle-active', [CustomerController::class, 'toggleActive'])->name('customer.toggle');
        Route::patch('/customer/{id}/role', [CustomerController::class, 'updateRole'])->name('customer.role');
        Route::get('/customer/{id}/edit', [CustomerController::class, 'edit'])->name('customer.edit');
        Route::put('/customer/{id}', [CustomerController::class, 'update'])->name('customer.update');

        // PEMBAYARAN (ADMIN)
        Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran');
        Route::get('pembayaran/{id}', [PembayaranController::class, 'show'])->name('pembayaran.show');
        Route::post('/pembayaran/{id}/konfirmasi', [PembayaranController::class, 'konfirmasi'])->name('pembayaran.konfirmasi');
        Route::post('/pembayaran/{id}/tolak', [PembayaranController::class, 'tolak'])->name('pembayaran.tolak');

        // TRANSAKSI (ADMIN)
        Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi');
        Route::get('/transaksi/{transaksi}', [TransaksiController::class, 'show'])->name('transaksi.show');
        Route::put('/transaksi/{id}/status', [TransaksiController::class, 'updateStatus'])->name('transaksi.updateStatus');
        Route::post('/transaksi/{id}/kirim', [TransaksiController::class, 'kirim'])->name('transaksi.kirim');

        // ULASAN
        Route::get('/ulasans', [UlasanAdminController::class, 'index'])->name('ulasans.index');
        Route::delete('/ulasans/{ulasan}', [UlasanAdminController::class, 'destroy'])->name('ulasans.destroy');
        Route::get('/ulasans/trash', [UlasanAdminController::class, 'trash'])->name('ulasans.trash');
        Route::patch('/ulasans/{id}/restore', [UlasanAdminController::class, 'restore'])->name('ulasans.restore');
        Route::delete('/ulasans/{id}/force', [UlasanAdminController::class, 'forceDelete'])->name('ulasans.forceDelete');

        // LAPORAN PENDAPATAN
        Route::get('/laporan/pendapatan', [LaporanPendapatanController::class, 'index'])->name('laporan.pendapatan');
        Route::get('/laporan/pendapatan/cetak', [LaporanPendapatanController::class, 'cetak'])->name('laporan.pendapatan.cetak');
    });

/* =====================================================
   PURCHASED MIDDLEWARE
===================================================== */
Route::middleware(['auth', 'purchased'])->group(function () {
    Route::post('/produk/{produk}/ulasan', [UlasanController::class, 'storeOrUpdate'])
        ->name('produk.ulasan.store');
});
