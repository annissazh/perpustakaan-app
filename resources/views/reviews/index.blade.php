@extends('layouts.app')

@section('title', 'Ulasan Buku - PerpusApp')
@section('page-title', 'Ulasan: ' . $book->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="card mb-4">
            <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-4">
                <div class="text-center">
                    <div class="display-4 fw-bold text-primary">{{ $averageRating ? number_format($averageRating, 1, ',', '.') : '0,0' }}</div>
                    <div class="text-warning fs-5">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= round($averageRating ?? 0) ? 'bi-star-fill' : 'bi-star' }}"></i>
                        @endfor
                    </div>
                    <small class="text-muted">{{ $totalReviews }} ulasan</small>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold mb-1">{{ $book->title }}</h5>
                    <p class="text-muted small mb-2">{{ $book->author }} &bull; {{ $book->category->name ?? 'Tanpa Kategori' }}</p>
                    <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-light-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Detail Buku
                    </a>
                </div>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-chat-square-text text-primary me-1"></i> Semua Ulasan</h5>
            </div>
            <div class="card-body">
                @forelse($reviews as $review)
                    <div class="d-flex gap-3 py-3 border-bottom">
                        <div class="avatar avatar-md flex-shrink-0">
                            <span class="avatar-content text-uppercase">{{ substr($review->user?->name ?? 'A', 0, 1) }}</span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="fw-semibold">{{ $review->user?->name ?? 'Anggota' }}</span>
                                    <span class="text-warning ms-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        @endfor
                                    </span>
                                </div>
                                <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                            </div>
                            @if($review->comment)
                                <p class="mb-1 mt-1">{{ $review->comment }}</p>
                            @else
                                <p class="mb-1 mt-1 text-muted fst-italic">Tanpa komentar.</p>
                            @endif
                            @if(auth()->id() === $review->user_id)
                                <form action="{{ route('reviews.destroy', $review) }}" method="POST" class="form-delete no-spinner d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                                        <i class="bi bi-trash me-1"></i> Hapus Ulasan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4 mb-0">
                        <i class="bi bi-chat-square d-block fs-2 mb-2 opacity-50"></i>
                        Belum ada ulasan untuk buku ini.
                    </p>
                @endforelse
            </div>
            @if($reviews->hasPages())
                <div class="card-footer bg-transparent border-0">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection