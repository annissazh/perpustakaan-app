@extends('layouts.app')

@section('title', 'Katalog Buku - PerpusApp')
@section('page-title', 'Katalog Koleksi Buku')

@section('content')
<!-- Filter & Pencarian -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body p-3 p-md-4">
                <form action="{{ route('katalog.index') }}" method="GET" class="row g-3 align-items-center">
                    <!-- Input Pencarian + Live Search AJAX -->
                    <div class="col-12 col-md-5 position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control border-start-0"
                                   id="searchInput"
                                   name="search"
                                   placeholder="Ketik minimal 3 huruf judul / penulis..."
                                   value="{{ request('search') }}"
                                   autocomplete="off">
                        </div>

                        <!-- Dropdown Hasil Live Search -->
                        <ul id="searchResults"
                            class="dropdown-menu w-100 shadow mt-1 py-0"
                            style="display: none; position: absolute; z-index: 1050; max-height: 320px; overflow-y: auto;">
                        </ul>
                    </div>

                    <!-- Filter Kategori Otomatis Submit saat Dipilih -->
                    <div class="col-12 col-md-4">
                        <select class="form-select" name="category" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tombol Cari & Reset -->
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-search me-1"></i> Cari
                        </button>
                        @if(request('search') || request('category'))
                            <a href="{{ route('katalog.index') }}" class="btn btn-light-secondary" data-bs-toggle="tooltip" title="Reset Filter">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Info Hasil Pencarian Aktif -->
@if(request('search') || request('category'))
    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
        <span class="text-muted small">
            Menampilkan <strong>{{ $books->total() }}</strong> hasil koleksi
            @if(request('search'))
                untuk kata kunci <strong>"{{ request('search') }}"</strong>
            @endif
        </span>
    </div>
@endif

<!-- Grid Koleksi Buku -->
<div class="row g-4">
    @forelse ($books as $book)
        @php
            $coverUrl = $book->cover_image ? asset('storage/' . $book->cover_image) : '';
            $categoryName = $book->category->name ?? 'Tanpa Kategori';
            $fineFormatted = 'Rp' . number_format($book->fine_per_day ?? 1000, 0, ',', '.');
        @endphp

        <div class="col-xl-3 col-lg-4 col-sm-6">
            <div class="card book-card h-100 mb-0">
                <!-- Sampul Buku -->
                <div class="book-cover-wrapper">
                    @if($book->available_stock > 0)
                        <span class="badge bg-success book-status-badge shadow-sm">
                            <i class="bi bi-check-circle me-1"></i>Tersedia
                        </span>
                    @else
                        <span class="badge bg-danger book-status-badge shadow-sm">
                            <i class="bi bi-x-circle me-1"></i>Habis
                        </span>
                    @endif

                    @if($book->cover_image)
                        <img src="{{ $coverUrl }}" class="book-cover-img" alt="{{ $book->title }}">
                    @else
                        <div class="w-100 h-100 d-flex flex-column justify-content-center align-items-center text-secondary">
                            <i class="bi bi-journal-richtext mb-2" style="font-size: 3.5rem; opacity: 0.45;"></i>
                            <small class="text-muted">Tanpa Sampul</small>
                        </div>
                    @endif
                </div>

                <!-- Informasi Buku -->
                <div class="card-body d-flex flex-column p-3 p-md-4">
                    <div class="mb-2">
                        <span class="badge bg-light-primary text-primary fw-semibold" style="font-size: 0.72rem;">
                            {{ $categoryName }}
                        </span>
                    </div>

                    <h5 class="card-title fw-bold mb-1 book-title-clamp" title="{{ $book->title }}">
                        <a href="{{ route('books.show', $book) }}" class="text-dark text-decoration-none stretched-link-off">
                            {{ $book->title }}
                        </a>
                    </h5>

                    <p class="card-text text-muted small mb-2">
                        <i class="bi bi-person me-1"></i>{{ $book->author }}
                    </p>

                    @if($book->reviews_avg_rating)
                        <div class="perpus-book-rating mb-2" title="{{ number_format($book->reviews_avg_rating, 1, ',', '.') }} dari 5">
                            @for($star = 1; $star <= 5; $star++)
                                <i class="bi {{ $star <= round($book->reviews_avg_rating) ? 'bi-star-fill' : 'bi-star' }}"></i>
                            @endfor
                            <span class="text-muted ms-1">{{ number_format($book->reviews_avg_rating, 1, ',', '.') }}</span>
                            <span class="text-muted">({{ $book->reviews_count }})</span>
                        </div>
                    @endif

                    <!-- Footer Kartu: Stok & Tombol Detail -->
                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="small fw-semibold">
                            Stok:
                            <span class="{{ $book->available_stock > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $book->available_stock }}
                            </span>
                            <span class="text-muted fw-normal">/ {{ $book->total_stock }}</span>
                        </div>

                        <button type="button"
                                class="btn btn-sm btn-light-primary fw-semibold btn-detail-buku"
                                data-bs-toggle="modal"
                                data-bs-target="#detailBukuModal"
                                data-title="{{ $book->title }}"
                                data-author="{{ $book->author }}"
                                data-isbn="{{ $book->isbn }}"
                                data-year="{{ $book->published_year }}"
                                data-category="{{ $categoryName }}"
                                data-stock="{{ $book->available_stock }}"
                                data-total="{{ $book->total_stock }}"
                                data-fine="{{ $fineFormatted }}"
                                data-cover="{{ $coverUrl }}">
                            <i class="bi bi-info-circle me-1"></i>Detail
                        </button>
                    </div>

                    @if(auth()->user()->role === \App\Enums\Role::Anggota)
                        <div class="mt-3">
                            @if($book->available_stock > 0)
                                <form action="{{ route('katalog.pinjam', $book) }}" method="POST" class="w-100">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-primary btn-sm w-100 fw-semibold"
                                            onclick="return confirm('Pinjam buku {{ addslashes($book->title) }} selama {{ $maxLoanDays }} hari?')">
                                        <i class="bi bi-book-half me-1"></i> Pinjam Buku
                                    </button>
                                </form>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm w-100" disabled>
                                    <i class="bi bi-x-circle me-1"></i> Stok Habis
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <img src="{{ asset('assets/compiled/svg/error-404.svg') }}" width="180" alt="Tidak ada data" class="mb-3">
                    <h5 class="fw-bold">Buku tidak ditemukan</h5>
                    <p class="text-muted mb-3">Coba gunakan kata kunci judul/penulis yang lain atau pilih semua kategori.</p>
                    <a href="{{ route('katalog.index') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Tampilkan Semua Buku
                    </a>
                </div>
            </div>
        </div>
    @endforelse
</div>

<!-- Pagination Otomatis jika Controller Menggunakan paginate() -->
@if(method_exists($books, 'links'))
<div class="mt-4 d-flex justify-content-center">
    {{ $books->links() }}
</div>
@endif
@endsection

<!-- Modal Detail Informasi Buku -->
@push('modals')
<div class="modal fade" id="detailBukuModal" tabindex="-1" aria-labelledby="detailBukuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="detailBukuModalLabel">
                    <i class="bi bi-book me-2"></i>Detail Informasi Buku
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-4 text-center">
                        <img id="modalBookCover" src="" alt="Sampul" class="img-fluid rounded shadow-sm d-none" style="max-height: 175px; object-fit: cover;">
                        <div id="modalBookNoCover" class="bg-light rounded d-flex flex-column justify-content-center align-items-center text-secondary" style="height: 165px;">
                            <i class="bi bi-journal-richtext fs-1 opacity-50"></i>
                            <small class="text-muted mt-1" style="font-size: 0.7rem;">Tanpa Sampul</small>
                        </div>
                    </div>
                    <div class="col-8">
                        <span id="modalBookCategory" class="badge bg-light-primary text-primary mb-2"></span>
                        <h5 id="modalBookTitle" class="fw-bold mb-1"></h5>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-person me-1"></i><span id="modalBookAuthor"></span>
                        </p>

                        <ul class="list-unstyled small mb-0">
                            <li class="mb-1"><span class="text-muted">ISBN:</span> <strong id="modalBookIsbn"></strong></li>
                            <li class="mb-1"><span class="text-muted">Tahun Terbit:</span> <strong id="modalBookYear"></strong></li>
                            <li class="mb-1"><span class="text-muted">Ketersediaan:</span> <strong id="modalBookStock"></strong></li>
                            <li><span class="text-muted">Denda Terlambat:</span> <strong class="text-danger" id="modalBookFine"></strong> / hari</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const searchUrl = @json(route('katalog.index'));
    const searchAjaxUrl = @json(route('katalog.search'));
    let debounceTimer;

    // 1. Live Search AJAX (Minimal 3 Huruf + Debounce)
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length < 3) {
                searchResults.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                searchResults.innerHTML = '<li class="dropdown-item text-muted py-2 small"><span class="spinner-border spinner-border-sm me-2"></span>Mencari buku...</li>';
                searchResults.style.display = 'block';

                fetch(`${searchAjaxUrl}?search=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';

                        if (data.length > 0) {
                            data.forEach(book => {
                                const li = document.createElement('li');
                                const link = document.createElement('a');
                                link.className = 'dropdown-item py-2 border-bottom';
                                link.href = `${searchUrl}?search=${encodeURIComponent(book.title)}`;

                                const titleDiv = document.createElement('div');
                                titleDiv.className = 'fw-bold text-primary text-truncate';
                                titleDiv.textContent = book.title;

                                const authorSmall = document.createElement('small');
                                authorSmall.className = 'text-muted';
                                authorSmall.innerHTML = '<i class="bi bi-pen me-1"></i>';
                                authorSmall.appendChild(document.createTextNode(book.author));

                                link.appendChild(titleDiv);
                                link.appendChild(authorSmall);
                                li.appendChild(link);
                                searchResults.appendChild(li);
                            });
                        } else {
                            searchResults.innerHTML = '<li class="dropdown-item text-muted py-2 fst-italic small">Buku tidak ditemukan...</li>';
                        }
                        searchResults.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        searchResults.style.display = 'none';
                    });
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    // 2. Populasi Data ke Modal Detail Buku
    document.querySelectorAll('.btn-detail-buku').forEach(btn => {
        btn.addEventListener('click', function () {
            const { title, author, isbn, year, category, stock, total, fine, cover } = this.dataset;

            document.getElementById('modalBookTitle').textContent = title;
            document.getElementById('modalBookAuthor').textContent = author;
            document.getElementById('modalBookIsbn').textContent = isbn || '-';
            document.getElementById('modalBookYear').textContent = year || '-';
            document.getElementById('modalBookCategory').textContent = category;
            document.getElementById('modalBookStock').textContent = `${stock} dari ${total} buku tersedia`;
            document.getElementById('modalBookFine').textContent = fine;

            const coverImg = document.getElementById('modalBookCover');
            const noCoverDiv = document.getElementById('modalBookNoCover');

            if (cover) {
                coverImg.src = cover;
                coverImg.classList.remove('d-none');
                noCoverDiv.classList.add('d-none');
            } else {
                coverImg.classList.add('d-none');
                noCoverDiv.classList.remove('d-none');
            }
        });
    });
});
</script>
@endpush