<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f5f5f3; }
        .main-content { margin-left: 260px; min-height: 100vh; display: flex; flex-direction: column; }
        @media (max-width: 991px) { .main-content { margin-left: 0; } }
        .content-wrapper { flex: 1; }
    </style>

    @stack('styles')
</head>
<body>

    @include('components.Admin.sidebar')

    <div class="main-content">
        @include('components.Admin.navbar')

        <div class="content-wrapper container-fluid p-4">
            @yield('content')
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    {{-- SWEET ALERT GLOBAL --}}
    @include('components.swal')

    <script>
      // Confirm delete (tanpa modal bootstrap)
      function confirmDelete(formIdOrUrl) {
        SwalConfirm({
          title: 'Yakin ingin menghapus?',
          text: 'Data yang sudah dihapus tidak dapat dikembalikan.',
          icon: 'warning',
          confirmText: 'Ya, hapus',
          cancelText: 'Batal',
          confirmColor: '#dc3545'
        }).then((result) => {
          if (!result.isConfirmed) return;

          // 1) kalau kamu kirim ID form
          const form = document.getElementById(formIdOrUrl);
          if (form) {
            form.submit();
            return;
          }

          // 2) kalau kamu kirim URL (opsional)
          // buat form delete otomatis
          const f = document.createElement('form');
          f.method = 'POST';
          f.action = formIdOrUrl;
          f.innerHTML = `
            @csrf
            @method('DELETE')
          `;
          document.body.appendChild(f);
          f.submit();
        });
      }
  // Confirm submit form (paling aman & gampang)
  function confirmDeleteForm(formId, options = {}) {
    const {
      title = 'Yakin ingin menghapus?',
      text  = 'Data yang sudah dihapus tidak dapat dikembalikan.',
      confirmText = 'Ya, hapus',
      cancelText  = 'Batal',
      confirmColor = '#dc3545'
    } = options;

    SwalConfirm({
      title, text,
      icon: 'warning',
      confirmText,
      cancelText,
      confirmColor
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById(formId).submit();
      }
    });

    return false; // penting: cegah submit langsung
  }
    </script>

    @stack('scripts')
</body>
</html>
