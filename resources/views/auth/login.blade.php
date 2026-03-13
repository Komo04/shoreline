<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login - Fashion Store</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" />
  <!-- Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <style>
    :root{
      --radius: 22px;
      --radius-input: 14px;
      --shadow: 0 25px 60px rgba(0,0,0,.08);
      --muted: #6c757d;
      --border: #e6e6e6;
    }

    body{
      font-family: "Poppins", sans-serif;
      background: #f8f9fa;
    }

    .auth-wrapper{
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding: 24px 0;
    }

    .auth-card{
      background: #fff;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .auth-content{
      padding: 52px 44px;
    }

    .back-link{
      font-size: 13px;
      font-weight: 600;
      color: #000;
      display: inline-flex;
      align-items: center;
      text-decoration: none;
      transition: .2s ease;
    }
    .back-link:hover{
      transform: translateX(-3px);
      opacity: .75;
    }

    .auth-title{
      font-size: 32px;
      font-weight: 800;
      margin: 0 0 8px;
      letter-spacing: -.5px;
    }

    .auth-subtitle{
      color: var(--muted);
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 26px;
    }

    .form-control{
      border-radius: var(--radius-input);
      padding: 12px 14px;
      border: 1px solid var(--border);
      font-size: 14px;
    }
    .form-control:focus{
      border-color: #111;
      box-shadow: 0 0 0 3px rgba(0,0,0,.06);
    }

    /* Password group */
    .input-group .form-control{
      border-right: 0;
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
    }
    .input-group .input-group-text{
      border-radius: 0 var(--radius-input) var(--radius-input) 0;
      background: #fff;
      border: 1px solid var(--border);
      border-left: 0;
      cursor: pointer;
      padding: 0 14px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-auth{
      border-radius: 999px;
      padding: 12px 14px;
      font-weight: 700;
      transition: .25s ease;
    }
    .btn-auth:hover{
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(0,0,0,.15);
    }

    .auth-footer{
      font-size: 14px;
      color: var(--muted);
      margin-top: 18px;
    }

    .login-error{
      animation: shake .4s ease;
    }

    @keyframes shake {
      0% { transform: translateX(0) }
      25% { transform: translateX(-6px) }
      50% { transform: translateX(6px) }
      75% { transform: translateX(-4px) }
      100% { transform: translateX(0) }
    }

    @media (max-width: 576px){
      .auth-content{ padding: 40px 26px; }
    }
  </style>
</head>

<body>
  <div class="container auth-wrapper">
    <div class="row justify-content-center w-100">
      <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="auth-card">
          <div class="auth-content">

            <a href="{{ url('/') }}" class="back-link mb-3">
              <i class="bi bi-arrow-left me-2"></i>
              Kembali ke home
            </a>

            <h2 class="auth-title">Selamat Datang</h2>
            <p class="auth-subtitle">
              Masuk untuk melanjutkan pengalaman belanja fashion terbaik.
            </p>

            @if ($errors->any())
              <div class="alert alert-danger small login-error mb-3">
                {{ $errors->first() }}
              </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
              @csrf

              <!-- Email -->
              <div class="mb-3">
                <label class="form-label fw-semibold small">Email</label>
                <input
                  type="email"
                  name="email"
                  value="{{ old('email') }}"
                  class="form-control"
                  placeholder="Masukkan email"
                  required
                  autofocus
                />
              </div>

              <!-- Password -->
              <div class="mb-3">
                <label class="form-label fw-semibold small">Password</label>
                <div class="input-group">
                  <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control"
                    placeholder="Masukkan password"
                    required
                  />
                  <button type="button" class="input-group-text" id="togglePassword" aria-label="Toggle password">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <!-- Remember & Forgot -->
              <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                  <input type="checkbox" name="remember" class="form-check-input" id="remember" />
                  <label class="form-check-label small" for="remember">Ingat saya</label>
                </div>

                <a href="{{ route('password.request') }}" class="small text-dark text-decoration-none">
                  Lupa password?
                </a>
              </div>

              <button type="submit" class="btn btn-dark w-100 btn-auth" id="loginBtn">
                <span class="btn-text">Login</span>
                <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
              </button>
            </form>

            <p class="text-center auth-footer">
              Belum punya akun?
              <a href="{{ route('register') }}" class="fw-semibold text-dark text-decoration-none">
                Buat akun sekarang
              </a>
            </p>

          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const togglePassword = document.getElementById("togglePassword");
    const password = document.getElementById("password");
    const btn = document.getElementById("loginBtn");
    const form = btn.closest("form");

    togglePassword.addEventListener("click", function () {
      const icon = this.querySelector("i");
      const isPassword = password.type === "password";
      password.type = isPassword ? "text" : "password";
      icon.classList.toggle("bi-eye", !isPassword);
      icon.classList.toggle("bi-eye-slash", isPassword);
    });

    form.addEventListener("submit", function () {
      btn.querySelector(".btn-text").classList.add("d-none");
      btn.querySelector(".spinner-border").classList.remove("d-none");
      btn.disabled = true;
    });
  </script>
  @include('components.swal')
</body>
</html>
