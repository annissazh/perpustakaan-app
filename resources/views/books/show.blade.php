@extends('layouts.app')

@section('title', 'Detail Buku - PerpusApp')
@section('page-title', 'Detail Informasi Buku')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card overflow-hidden">
            <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div>
                    <h4 class="card-title mb-1">{{ $book->title }}</h4>
                    <p class="text-muted small mb-0">Informasi lengkap koleksi buku.</p>
                </div>
                <a href="{{ route(auth()->user()->role->isStaff() ? 'books.index' : 'katalog.index') }}" class="btn btn-light-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="row g-4 align-items-start">
                    <div class="col-md-4 col-lg-3 text-center">
                        @if($book->cover_image)
                            <img src="{{ asset('storage/'.$book->cover_image) }}" class="img-fluid rounded-3 shadow-sm" alt="Sampul {{ $book->title }}" style="max-height: 360px; object-fit: cover;">
                        @else
                            <div class="book-cover-wrapper rounded-3 d-flex justify-content-center align-items-center mx-auto" style="max-width: 260px; height: 320px;">
                                <i class="bi bi-journal-richtext text-secondary opacity-50" style="font-size: 4rem;"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-8 col-lg-9">
                        <ul class="perpus-info-list">
                            <li>
                                <span class="label">Kategori</span>
                                <span class="value">
                                    <span class="badge bg-light-primary text-primary fw-semibold">{{ $book->category->name ?? 'Tanpa Kategori' }}</span>
                                </span>
                            </li>
                            <li>
                                <span class="label">Penulis</span>
                                <span class="value">{{ $book->author }}</span>
                            </li>
                            <li>
                                <span class="label">Penerbit</span>
                                <span class="value">{{ $book->publisher ?? '-' }}</span>
                            </li>
                            <li>
                                <span class="label">ISBN</span>
                                <span class="value"><code class="text-dark">{{ $book->isbn }}</code></span>
                            </li>
                            <li>
                                <span class="label">Tahun Terbit</span>
                                <span class="value">{{ $book->published_year }}</span>
                            </li>
                            <li>
                                <span class="label">Total Stok</span>
                                <span class="value">{{ $book->total_stock }} eksemplar</span>
                            </li>
                            <li>
                                <span class="label">Stok Tersedia</span>
                                <span class="value">
                                    @if($book->available_stock > 0)
                                        <span class="badge bg-light-success text-success fw-semibold">{{ $book->available_stock }} tersedia</span>
                                    @else
                                        <span class="badge bg-light-danger text-danger fw-semibold">Habis</span>
                                    @endif
                                </span>
                            </li>
                        </ul>
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            @if(auth()->user()->role->isStaff())
                                <a href="{{ route('books.edit', $book) }}" class="btn btn-primary">
                                    <i class="bi bi-pencil-square me-1"></i> Edit Buku
                                </a>
                                <a href="{{ route('books.index') }}" class="btn btn-light-secondary">Daftar Buku</a>
                            @else
                                <a href="{{ route('katalog.index') }}" class="btn btn-light-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog
                                </a>
                                @if($book->available_stock > 0)
                                    <form action="{{ route('katalog.pinjam', $book) }}" method="POST" class="no-spinner d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-journal-plus me-1"></i> Pinjam Buku Ini
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <div class="card mb-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1"><i class="bi bi-chat-square-text text-primary me-1"></i> Ulasan Pembaca</h5>
                    <p class="text-muted small mb-0">
                        {{ $totalReviews }} ulasan
                        @if($averageRating)
                            &bull; rata-rata {{ number_format($averageRating, 1, ',', '.') }}/5
                        @endif
                    </p>
                </div>
                <a href="{{ route('reviews.index', $book) }}" class="btn btn-sm btn-light-secondary">Lihat Semua</a>
            </div>
            <div class="card-body">
                @if(auth()->user()->role === \App\Enums\Role::Anggota)
                    @if($myReview)
                        <div class="perpus-preview-box p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="fw-semibold">Ulasan Anda</span>
                                    <div class="text-warning my-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="bi {{ $i <= $myReview->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        @endfor
                                    </div>
                                    <p class="mb-0 small">{{ $myReview->comment ?: 'Tanpa komentar.' }}</p>
                                </div>
                                <form action="{{ route('reviews.destroy', $myReview) }}" method="POST" class="form-delete no-spinner">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <form action="{{ route('reviews.store', $book) }}" method="POST" class="mb-4" id="reviewForm">
                            @csrf
                            <label class="form-label fw-semibold d-flex align-items-center justify-content-between">
                                <span>Berikan Rating</span>
                                <span id="ratingPreview" class="perpus-rating-preview text-muted small"></span>
                            </label>
                            <div class="perpus-star-picker mb-2" role="radiogroup" aria-label="Rating buku">
                                @for($star = 5; $star >= 1; $star--)
                                    <input type="radio" name="rating" id="rating{{ $star }}" value="{{ $star }}"
                                           {{ old('rating') == $star ? 'checked' : '' }} required>
                                    <label for="rating{{ $star }}" title="{{ $star }} bintang">
                                        <i class="bi bi-star-fill"></i>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                            <label class="form-label fw-semibold" for="comment">Ulasan (opsional)</label>
                            <textarea name="comment" id="comment" rows="3" maxlength="1000"
                                      class="form-control @error('comment') is-invalid @enderror"
                                      placeholder="Bagaimana pengalaman Anda membaca buku ini?">{{ old('comment') }}</textarea>
                            <div class="d-flex justify-content-between align-items-center">
                                @error('comment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <small class="text-muted ms-auto" id="commentCounter">0/1000</small>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">
                                <i class="bi bi-send me-1"></i> Kirim Ulasan
                            </button>
                        </form>
                    @endif
                @endif

                @forelse($reviews as $review)
                    <div class="d-flex gap-3 py-3 border-bottom">
                        <div class="avatar avatar-md flex-shrink-0">
                            <span class="avatar-content text-uppercase">{{ substr($review->user?->name ?? 'A', 0, 1) }}</span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <span class="fw-semibold">{{ $review->user?->name ?? 'Anggota' }}</span>
                                <span class="text-warning">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                </span>
                            </div>
                            <p class="mb-0 small mt-1">{{ $review->comment ?: 'Tanpa komentar.' }}</p>
                            <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4 mb-0">Belum ada ulasan untuk buku ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-0">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle text-primary me-1"></i> Info Peminjaman</h5>
            </div>
            <div class="card-body">
                <ul class="perpus-info-list mb-0">
                    <li>
                        <span class="label">Denda per hari</span>
                        <span class="value text-danger fw-semibold">Rp{{ number_format($book->fine_per_day ?? 0, 0, ',', '.') }}</span>
                    </li>
                    <li>
                        <span class="label">Total dipinjam</span>
                        <span class="value">{{ $book->borrowings()->count() }} kali</span>
                    </li>
                    <li>
                        <span class="label">Status</span>
                        <span class="value">
                            @if($book->available_stock > 0)
                                <span class="badge bg-light-success text-success">Tersedia</span>
                            @else
                                <span class="badge bg-light-danger text-danger">Habis</span>
                            @endif
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Live preview rating: menampilkan label deskriptif saat bintang dipilih.
        const ratingPreview = document.getElementById('ratingPreview');
        const ratingInputs = document.querySelectorAll('.perpus-star-picker input[name="rating"]');
        const ratingLabels = {
            1: 'Sangat buruk',
            2: 'Kurang baik',
            3: 'Cukup',
            4: 'Baik',
            5: 'Sangat bagus'
        };

        function updateRatingPreview() {
            const checked = document.querySelector('.perpus-star-picker input[name="rating"]:checked');

            if (!ratingPreview) return;

            if (!checked) {
                ratingPreview.textContent = 'Pilih 1 - 5 bintang';
                ratingPreview.className = 'perpus-rating-preview text-muted small';
                return;
            }

            const value = checked.value;
            ratingPreview.innerHTML = '<strong class="text-warning">' + value + ' bintang</strong> · ' + (ratingLabels[value] || '');
            ratingPreview.className = 'perpus-rating-preview small';
        }

        ratingInputs.forEach(function (input) {
            input.addEventListener('change', updateRatingPreview);
        });

        updateRatingPreview();

        // Penghitung karakter ulasan.
        const commentField = document.getElementById('comment');
        const commentCounter = document.getElementById('commentCounter');

        if (commentField && commentCounter) {
            const maxLength = commentField.getAttribute('maxlength') || 1000;

            function updateCounter() {
                const length = commentField.value.length;
                commentCounter.textContent = length + '/' + maxLength;
                commentCounter.classList.toggle('text-danger', length > maxLength * 0.9);
                commentCounter.classList.toggle('text-muted', length <= maxLength * 0.9);
            }

            commentField.addEventListener('input', updateCounter);
            updateCounter();
        }
    });
</script>
@endpush
