<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PerpusApp</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css'])
</head>
<body>
    <div class="perpus-auth">
        <div class="perpus-auth__hero">
            <div class="perpus-auth__hero-content">
                <div class="perpus-brand-logo mb-4 d-inline-flex">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <h2>Perpustakaan Digital</h2>
                <p class="mb-0 opacity-90">Kelola koleksi, sirkulasi, dan layanan membaca dalam satu platform yang modern.</p>
            </div>
        </div>

        <div class="perpus-auth__panel">
            <div class="perpus-auth__card">
                <h1 class="h3 fw-bold mb-1">Selamat datang</h1>
                <p class="text-muted mb-4">Masuk ke akun PerpusApp Anda</p>

                @if($errors->any())
                    <div class="alert alert-danger border-0 d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST" class="d-flex flex-column gap-3">
                    @csrf
                    <div class="perpus-auth__input-wrap">
                        <i class="bi bi-envelope perpus-auth__input-icon"></i>
                        <input type="email" name="email" class="form-control" placeholder="Alamat email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="perpus-auth__input-wrap">
                        <i class="bi bi-shield-lock perpus-auth__input-icon"></i>
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>