@php
    $user = Auth::user();
    $roleBadgeClass = $user->role->badgeClass();
@endphp

<aside id="sidebar" aria-label="Navigasi utama">
    <div class="perpus-sidebar">
        <div class="perpus-sidebar__brand">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                    <div class="perpus-brand-logo">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <span class="perpus-brand-text">PerpusApp</span>
                </a>
                <button type="button" class="sidebar-close-btn d-xl-none sidebar-hide" aria-label="Tutup menu">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <div class="perpus-sidebar__profile d-flex align-items-center gap-3">
            <div class="avatar avatar-md">
                <span class="avatar-content text-uppercase">{{ substr($user->name, 0, 1) }}</span>
            </div>
            <div class="overflow-hidden">
                <h6 class="mb-1 fw-bold text-truncate text-dark" title="{{ $user->name }}">{{ $user->name }}</h6>
                <span class="badge {{ $roleBadgeClass }} fw-semibold" style="font-size: 0.68rem;">
                    {{ strtoupper($user->role->label()) }}
                </span>
            </div>
        </div>

        <nav class="perpus-sidebar__nav">
            <ul class="list-unstyled mb-0">
                <li class="perpus-nav-label">Menu Utama</li>

                <li class="perpus-nav-item {{ Request::is('/') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="perpus-nav-link">
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="perpus-nav-item {{ Request::is('katalog*') ? 'active' : '' }}">
                    <a href="{{ route('katalog.index') }}" class="perpus-nav-link">
                        <i class="bi bi-journal-bookmark-fill"></i>
                        <span>Katalog Buku</span>
                    </a>
                </li>

                <li class="perpus-nav-item {{ Request::is('riwayat*') ? 'active' : '' }}">
                    <a href="{{ route('riwayat.index') }}" class="perpus-nav-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Riwayat Saya</span>
                    </a>
                </li>

                @if($user->role->isStaff())
                <li class="perpus-nav-label">Layanan & Koleksi</li>

                <li class="perpus-nav-item {{ Request::is('categories*') ? 'active' : '' }}">
                    <a href="{{ route('categories.index') }}" class="perpus-nav-link">
                        <i class="bi bi-tags-fill"></i>
                        <span>Kategori Buku</span>
                    </a>
                </li>

                <li class="perpus-nav-item {{ Request::is('books*') ? 'active' : '' }}">
                    <a href="{{ route('books.index') }}" class="perpus-nav-link">
                        <i class="bi bi-book-half"></i>
                        <span>Data Buku</span>
                    </a>
                </li>

                <li class="perpus-nav-item {{ Request::is('members*') ? 'active' : '' }}">
                    <a href="{{ route('members.index') }}" class="perpus-nav-link">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Data Anggota</span>
                    </a>
                </li>

                <li class="perpus-nav-item {{ Request::is('borrowings*') ? 'active' : '' }}">
                    <a href="{{ route('borrowings.index') }}" class="perpus-nav-link">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Sirkulasi</span>
                    </a>
                </li>
                @endif

                @if($user->role->canManageUsers())
                <li class="perpus-nav-label">Administrasi</li>

                <li class="perpus-nav-item {{ Request::is('users*') ? 'active' : '' }}">
                    <a href="{{ route('users.index') }}" class="perpus-nav-link">
                        <i class="bi bi-people-fill"></i>
                        <span>Manajemen User</span>
                    </a>
                </li>
                @endif
            </ul>

            <form action="{{ route('logout') }}" method="POST" class="no-spinner mt-3">
                @csrf
                <button type="submit" class="perpus-btn-logout d-flex justify-content-center align-items-center">
                    <i class="bi bi-box-arrow-left me-2"></i> Logout
                </button>
            </form>
        </nav>
    </div>
</aside>
