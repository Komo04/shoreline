<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Fashion Store</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
        }

        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .auth-card {
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 25px 60px rgba(0,0,0,0.08);
            padding: 60px 50px;
        }

        .auth-title {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-control {
            border-radius: 14px;
            padding: 13px 16px;
        }

        .btn-auth {
            border-radius: 50px;
            padding: 13px;
            font-weight: 600;
        }

        .back-link {
            font-size: 14px;
            text-decoration: none;
            color: #000;
        }

        .back-link:hover {
            opacity: .7;
        }
    </style>
</head>
<body>

<div class="container auth-wrapper">
    <div class="row justify-content-center w-100">
        <div class="col-lg-6">
            <div class="auth-card">

                <a href="{{ route('login') }}" class="back-link mb-4 d-inline-block">
                    <i class="bi bi-arrow-left me-2"></i>Back to Login
                </a>

                <h2 class="auth-title">Reset Password</h2>
                <p class="auth-subtitle">
                    Masukkan email Anda dan kami akan mengirimkan link reset password.
                </p>

                @if (session('status'))
                    @php
                        $status = session('status');
                        $statusText = match ($status) {
                            'passwords.sent' => 'Link reset password berhasil dikirim ke email kamu. Silakan cek inbox atau folder spam.',
                            default => 'Permintaan reset password berhasil diproses. Silakan cek email kamu.',
                        };
                    @endphp
                    <div class="alert alert-success small">
                        {{ $statusText }}
                    </div>
                @endif

                @if ($errors->any())
                    @php
                        $rawError = $errors->first();
                        $errorText = str_contains(strtolower($rawError), "can't find a user with that email")
                            ? 'Email tidak ditemukan. Pastikan email sudah benar atau sudah terdaftar.'
                            : $rawError;
                    @endphp
                    <div class="alert alert-danger small">
                        {{ $errorText }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Email Address</label>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="form-control"
                               placeholder="Masukkan email"
                               required>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 btn-auth">
                        Kirim Link Reset
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>
@include('components.swal')
</body>
</html>
