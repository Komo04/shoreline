{{-- resources/views/components/swal.blade.php --}}

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  window.hasSwal = () => typeof window.Swal !== 'undefined' && typeof window.Swal.fire === 'function';

  // Global helper (desain awal kamu)
  window.SwalToast = (icon, message, title = null, opts = {}) => {
    if (!window.hasSwal()) {
      const prefix = title ? `${title}: ` : '';
      alert(`${prefix}${message ?? ''}`);
      return;
    }

    const timer = (typeof opts.timer === 'number')
      ? opts.timer
      : (icon === 'success' ? 1400 : undefined);

    const showConfirmButton = (typeof opts.showConfirmButton === 'boolean')
      ? opts.showConfirmButton
      : (icon !== 'success');

    Swal.fire({
      icon,
      title: title ?? (icon === 'success'
        ? 'Berhasil'
        : icon === 'error'
          ? 'Gagal'
          : icon === 'warning'
            ? 'Perhatian'
            : 'Info'
      ),
      text: message,
      confirmButtonColor: opts.confirmButtonColor || '#111',
      timer,
      timerProgressBar: icon === 'success',
      showConfirmButton,
    });
  };

  // Confirm helper (logout/delete) (desain awal kamu)
  window.SwalConfirm = ({
    title = 'Yakin?',
    text = 'Tindakan ini tidak bisa dibatalkan.',
    icon = 'warning',
    confirmText = 'Ya',
    cancelText = 'Batal',
    confirmColor = '#111',
    cancelColor = '#6c757d',
  } = {}) => {
    if (!window.hasSwal()) {
      return Promise.resolve({
        isConfirmed: confirm(`${title}\n\n${text}`),
      });
    }

    return Swal.fire({
      title,
      text,
      icon,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      confirmButtonColor: confirmColor,
      cancelButtonColor: cancelColor,
      reverseButtons: true,
    });
  };

  /**
   * ========= Flexible message generator (biar gak monoton) =========
   * Bisa dipakai via:
   * ->with('flash', ['type'=>'success','action'=>'create','entity'=>'Produk'])
   * atau custom:
   * ->with('flash', ['type'=>'error','message'=>'Nama produk sudah ada'])
   */
  window.FlashMessage = (flash = {}) => {
    const type   = flash.type || flash.icon || 'info';
    const action = flash.action || null;
    const entity = flash.entity || 'Data';

    if (flash.message) return String(flash.message);

    const pick = (arr) => arr[Math.floor(Math.random() * arr.length)];

    const templates = {
      success: {
        create: [
          `${entity} berhasil ditambahkan.`,
          `Berhasil! ${entity} sudah tersimpan.`,
          `${entity} sukses dibuat.`,
          `${entity} sudah masuk daftar.`,
        ],
        update: [
          `${entity} berhasil diperbarui.`,
          `Perubahan ${entity} berhasil disimpan.`,
          `${entity} sukses diupdate.`,
          `${entity} berhasil disegarkan.`,
        ],
        delete: [
          `${entity} berhasil dihapus.`,
          `${entity} sukses dihapus dari sistem.`,
          `Berhasil menghapus ${entity}.`,
          `${entity} sudah dihapus.`,
        ],
        login: [
          `Selamat datang! Login berhasil.`,
          `Login sukses. Selamat beraktivitas.`,
          `Berhasil masuk. Yuk lanjut.`,
        ],
        logout: [
          `Kamu sudah logout.`,
          `Logout berhasil. Sampai jumpa!`,
          `Berhasil keluar dari akun.`,
        ],
        reply: [
          `Balasan berhasil dikirim.`,
          `Pesan balasan sudah terkirim.`,
          `Berhasil mengirim balasan.`,
        ],
        default: [
          `Berhasil.`,
          `Sukses diproses.`,
          `Operasi berhasil.`,
        ],
      },
      error: {
        validation: [
          `Ada input yang belum benar. Cek lagi ya.`,
          `Form masih ada yang salah. Mohon periksa kembali.`,
          `Beberapa field perlu diperbaiki.`,
        ],
        forbidden: [
          `Akses ditolak. Kamu tidak punya izin.`,
          `Kamu tidak diizinkan melakukan aksi ini.`,
          `Maaf, akses kamu terbatas.`,
        ],
        notfound: [
          `${entity} tidak ditemukan.`,
          `Data yang kamu cari tidak ada.`,
          `Maaf, ${entity} tidak tersedia.`,
        ],
        payment: [
          `Pembayaran belum berhasil. Coba lagi ya.`,
          `Transaksi gagal diproses. Periksa metode pembayaran.`,
          `Pembayaran gagal. Silakan ulangi.`,
        ],
        default: [
          `Terjadi kesalahan. Coba lagi.`,
          `Gagal memproses permintaan.`,
          `Oops, ada error. Silakan ulangi.`,
        ],
      },
      warning: {
        expired: [
          `Sesi kamu sudah habis. Silakan login lagi.`,
          `Sesi berakhir. Untuk keamanan, login kembali ya.`,
          `Kamu terlalu lama tidak aktif. Login lagi yuk.`,
        ],
        stock: [
          `Stok tidak mencukupi. Pilih varian lain ya.`,
          `Stok habis/kurang. Coba cek pilihan lainnya.`,
          `Maaf, stok belum tersedia.`,
        ],
        empty_cart: [
          `Keranjang kamu masih kosong.`,
          `Keranjang kosong. Yuk tambah produk dulu.`,
          `Belum ada item di keranjang.`,
        ],
        payment: [
          `Transaksi belum bisa dilanjutkan.`,
          `Ada kendala transaksi. Coba lagi ya.`,
          `Pembayaran/Transaksi sedang bermasalah.`,
        ],
        default: [
          `Perhatian: ada sesuatu yang perlu kamu cek.`,
          `Ada peringatan. Mohon perhatikan.`,
        ],
      },
      info: {
        default: [
          `Info: proses sedang berjalan.`,
          `Informasi: ada pembaruan.`,
          `Catatan: lihat detailnya ya.`,
        ],
      },
    };

    const bucket = templates[type] || templates.info;
    const list = (action && bucket[action]) ? bucket[action] : (bucket.default || templates.info.default);

    if (flash.detail) return `${pick(list)}\n${flash.detail}`;
    return pick(list);
  };

  // Render flash -> pakai SwalToast (desain awal)
  window.RenderFlash = (flash) => {
    if (!flash) return;

    const type  = flash.type || flash.icon || 'info';
    const title = flash.title || null;

    const msg = FlashMessage(flash);

    return SwalToast(type, msg, title, {
      timer: flash.timer,
      showConfirmButton: flash.showConfirmButton,
      confirmButtonColor: flash.confirmColor || '#111',
    });
  };
</script>

{{-- ========== FLASH FLEXIBLE (utama) ========== --}}
@if(session()->has('flash'))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    RenderFlash(@json(session('flash')));
  });
</script>
@endif

{{-- ========== VALIDATION ERROR ($errors) ==========
     tampil kalau tidak ada flash biar tidak dobel
--}}
@if ($errors->any() && !session()->has('flash'))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    RenderFlash({
      type: 'error',
      action: 'validation',
      detail: @json($errors->first()),
      // optional: title bisa kamu set kalau mau
      // title: 'Validasi Gagal'
    });
  });
</script>
@endif

{{-- ========== BACKWARD COMPAT (session success/error lama) ==========
     tampil kalau tidak ada flash biar tidak dobel
--}}
@if(session('success') && !session()->has('flash'))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    SwalToast('success', @json(session('success')));
  });
</script>
@endif

@if(session('error') && !session()->has('flash'))
<script>
  document.addEventListener('DOMContentLoaded', () => {
    SwalToast('error', @json(session('error')));
  });
</script>
@endif
