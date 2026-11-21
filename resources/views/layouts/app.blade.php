<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PerpusApp - Sistem Informasi Perpustakaan')</title>

    <!-- Bootstrap & Icons (kompatibel DataTables & komponen halaman) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body>
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <div id="app">
        @include('layouts.sidebar')

        <div id="main">
            <div class="perpus-topbar">
                <div class="d-flex align-items-center gap-3">
                    <a href="#" class="burger-btn" id="burgerBtn" aria-label="Buka menu">
                        <i class="bi bi-list fs-4"></i>
                    </a>
                    <div>
                        <h1 class="perpus-page-title">@yield('page-title')</h1>
                        <p class="perpus-page-title-sub mb-0 d-none d-md-block">Sistem Informasi Perpustakaan</p>
                    </div>
                </div>
            </div>

            <div class="flex-grow-1">
                <section class="section">
                    @yield('content')
                </section>
            </div>

            <footer class="perpus-footer d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <p class="mb-0">&copy; 2026 <strong>PerpusApp</strong> &mdash; Perpustakaan Digital</p>
                <p class="mb-0">Dibangun dengan <span class="text-danger"><i class="bi bi-heart-fill"></i></span> untuk edukasi</p>
            </footer>
        </div>
    </div>

    <!-- Wadah Modal Global (mencegah konflik z-index / backdrop) -->
    @stack('modals')

    <!-- Penampung Flash Message (agar bebas warning linter JS di VS Code) -->
    <div id="flash-messages"
         data-success="{{ session('success') }}"
         data-error="{{ session('error') }}"
         data-warning="{{ session('warning') }}"
         data-validation="{{ $errors->any() ? $errors->first() : '' }}"
         class="d-none">
    </div>

    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables Scripts -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script Global UI/UX -->
    <script>
        $(document).ready(function () {
            $('.section .card').each(function (index) {
                $(this).addClass('perpus-animate-in').css('animation-delay', (index * 0.06) + 's');
            });

            // 1. Inisialisasi Aman DataTables (anti-error double init & anti-warning colspan)
            if ($('#table1').length && !$.fn.DataTable.isDataTable('#table1')) {
                if ($('#table1 tbody tr td[colspan]').length === 0) {
                    $('#table1').DataTable({
                        language: {
                            url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
                        },
                        ordering: true,
                        info: true,
                        autoWidth: false,
                        pageLength: 10
                    });
                }
            }

            // 2. Toggle Sidebar Mobile & Tutup Otomatis Saat Klik di Luar
            const sidebar = $('#sidebar');
            const backdrop = $('#sidebarBackdrop');

            function openSidebar() {
                sidebar.addClass('active');
                backdrop.addClass('show');
            }

            function closeSidebar() {
                sidebar.removeClass('active');
                backdrop.removeClass('show');
            }

            $('.burger-btn').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                sidebar.hasClass('active') ? closeSidebar() : openSidebar();
            });

            $('.sidebar-close-btn, .sidebar-hide').on('click', function (e) {
                e.preventDefault();
                closeSidebar();
            });

            backdrop.on('click', function () {
                closeSidebar();
            });

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });

            // 3. Aktifkan Semua Tooltip Bootstrap
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (el) {
                return new bootstrap.Tooltip(el);
            });

            // 4. Notifikasi Toast Otomatis (Success, Error, Warning, Validasi)
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3200,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });

            const flashEl = document.getElementById('flash-messages');
            if (flashEl) {
                const { success, error, warning, validation } = flashEl.dataset;

                if (success) Toast.fire({ icon: 'success', title: success });
                if (error) Toast.fire({ icon: 'error', title: error });
                if (warning) Toast.fire({ icon: 'warning', title: warning });
                if (validation) Toast.fire({ icon: 'error', title: validation });
            }

            // 5. Konfirmasi Hapus Data Elegan (tambahkan class="form-delete" pada form hapus)
            $(document).on('submit', '.form-delete', function (e) {
                e.preventDefault();
                const form = this;
                Swal.fire({
                    title: 'Yakin ingin menghapus?',
                    text: 'Data yang dihapus tidak dapat dikembalikan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // 6. Auto Spinner pada Tombol Submit Form POST/PUT/DELETE (anti double-click)
            $('form:not(.form-delete):not(.no-spinner)').on('submit', function () {
                const method = ($(this).attr('method') || 'GET').toUpperCase();
                if (method === 'GET') return;

                const submitBtn = $(this).find('button[type="submit"]');
                if (submitBtn.length && !submitBtn.prop('disabled')) {
                    submitBtn.prop('disabled', true);
                    submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...');
                }
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>