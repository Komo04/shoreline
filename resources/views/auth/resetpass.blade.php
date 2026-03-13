<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Shoreline</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
        }
        .card {
            border-radius: 20px;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #111827;
        }
        .btn-dark {
            background: #111827;
            border: none;
        }
        .btn-dark:hover {
            background: #000;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="col-md-6 col-lg-5">

        <div class="card border-0 shadow-sm p-4 bg-white">

            <!-- HEADER -->
            <div class="text-center mb-4">
                <i class="fa-solid fa-shield-halved fa-2x text-dark mb-2"></i>
                <h4 class="fw-bold mb-1">Reset Password</h4>
                <p class="text-muted small mb-0">
                    Silakan buat password baru untuk akun kamu
                </p>
            </div>

            <!-- ERROR -->
            @if ($errors->any())
                <div class="alert alert-danger small">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- FORM -->
            <form method="POST" action="{{ route('password.update.secure') }}">
                @csrf

                <!-- Hidden Token -->
                <input type="hidden" name="token" value="{{ old('token', $token ?? request()->route('token')) }}">

                <!-- Hidden Email -->
                <input type="hidden" name="email" value="{{ old('email', $email ?? request('email')) }}">

                <!-- Password -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fa-solid fa-key text-muted"></i>
                        </span>

                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control border-start-0 border-end-0"
                               placeholder="Masukkan password baru"
                               required>

                        <button type="button"
                                class="btn btn-outline-secondary border-start-0"
                                onclick="togglePassword('password', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fa-solid fa-lock text-muted"></i>
                        </span>

                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               class="form-control border-start-0 border-end-0"
                               placeholder="Ulangi password baru"
                               required>

                        <button type="button"
                                class="btn btn-outline-secondary border-start-0"
                                onclick="togglePassword('password_confirmation', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- BUTTON -->
                <button type="submit"
                        class="btn btn-dark w-100 py-2 rounded-pill fw-semibold">
                    Reset Password
                </button>

                <!-- BACK -->
                <div class="text-center mt-4">
                    <a href="{{ route('login') }}"
                       class="text-decoration-none text-muted small">
                        <i class="fa-solid fa-arrow-left me-1"></i>
                        Kembali ke Login
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector("i");

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>
@include('components.swal')
</body>
</html>
