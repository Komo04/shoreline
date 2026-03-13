<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Fashion Store</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --radius: 22px;
            --radius-input: 14px;
            --shadow: 0 25px 60px rgba(0, 0, 0, .08);
            --muted: #6c757d;
            --border: #e6e6e6;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
        }

        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 30px 0;
        }

        .auth-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .auth-content {
            padding: 45px 40px;
        }

        .auth-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .auth-subtitle {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 28px;
        }

        .form-control {
            border-radius: var(--radius-input);
            padding: 12px 14px;
            border: 1px solid var(--border);
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #000;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, .05);
        }

        .input-group .form-control {
            border-right: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group .input-group-text {
            background: #fff;
            border: 1px solid var(--border);
            border-left: 0;
            border-radius: 0 var(--radius-input) var(--radius-input) 0;
            cursor: pointer;
            padding: 0 14px;
        }

        .btn-auth {
            border-radius: 50px;
            padding: 12px;
            font-weight: 700;
            transition: .25s;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, .15);
        }

        .auth-error {
            font-size: 13px;
        }

        @media(max-width:576px) {
            .auth-content {
                padding: 35px 25px;
            }
        }

    </style>
</head>

<body>

    <div class="container auth-wrapper">
        <div class="row justify-content-center w-100">
            <div class="col-md-10 col-lg-7 col-xl-6">
                <div class="auth-card">
                    <div class="auth-content">

                        <h2 class="auth-title">Buat Akun</h2>
                        <p class="auth-subtitle">
                            Daftar dan mulai pengalaman belanja fashion terbaik.
                        </p>

                        @if ($errors->any())
                        <div class="alert alert-danger auth-error">
                            {{ $errors->first() }}
                        </div>
                        @endif

                       <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div class="row">

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Nama Lengkap</label>
                                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Nama lengkap" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Email</label>
                                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="Masukkan email" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">No Telepon</label>
                                        <input type="text" name="no_telp" value="{{ old('no_telp') }}" class="form-control" placeholder="08xxxxxxxxxx" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Password</label>
                                        <div class="input-group">
                                            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                                            <button type="button" class="input-group-text" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Confirm Password</label>
                                        <div class="input-group">
                                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Ulangi password" required>
                                            <button type="button" class="input-group-text" id="togglePasswordConfirmation">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <button type="submit" class="btn btn-dark w-100 btn-auth mt-2" id="registerBtn">
                                <span class="btn-text">Register</span>
                                <span class="spinner-border spinner-border-sm d-none"></span>
                            </button>
                        </form>

                        <p class="text-center mt-3 small">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" class="fw-semibold text-dark text-decoration-none">
                                Login disini
                            </a>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const btn = document.getElementById('registerBtn');
        const form = btn.closest("form");
        const togglePasswordConfirmation = document.getElementById('togglePasswordConfirmation');
        const passwordConfirmation = document.getElementById('password_confirmation');

        togglePassword.addEventListener('click', function() {
            const icon = this.querySelector("i");
            const isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !isPassword);
            icon.classList.toggle('bi-eye-slash', isPassword);
        });

        form.addEventListener("submit", function() {
            btn.querySelector(".btn-text").classList.add("d-none");
            btn.querySelector(".spinner-border").classList.remove("d-none");
            btn.disabled = true;
        });

        togglePasswordConfirmation.addEventListener('click', function() {
            const icon = this.querySelector("i");
            const isPassword = passwordConfirmation.type === 'password';
            passwordConfirmation.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !isPassword);
            icon.classList.toggle('bi-eye-slash', isPassword);
        });

    </script>
@include('components.swal')
</body>
</html>
