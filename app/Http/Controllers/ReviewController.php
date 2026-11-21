<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Menampilkan seluruh ulasan untuk sebuah buku.
     */
    public function index(Book $book): View
    {
        $reviews = $book->reviews()
            ->with('user')
            ->latest()
            ->paginate(10);

        $averageRating = $book->reviews()->avg('rating');
        $totalReviews = $book->reviews()->count();

        return view('reviews.index', compact('book', 'reviews', 'averageRating', 'totalReviews'));
    }

    /**
     * Menyimpan ulasan baru dari seorang anggota.
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $user = $request->user();

        // Satu anggota hanya boleh mengulas satu buku satu kali.
        $existing = Review::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();

        if ($existing) {
            return back()->with('error', 'Anda sudah memberikan ulasan untuk buku ini.');
        }

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $request->integer('rating'),
            'comment' => $request->filled('comment') ? $request->string('comment')->toString() : null,
        ]);

        // Petugas/admin(if any) diberi tahu adanya ulasan baru.
        $staff = User::query()
            ->where('role', Role::Petugas->value)
            ->orWhere('role', Role::Admin->value)
            ->orWhere('role', Role::Superadmin->value)
            ->get();

        foreach ($staff as $staffMember) {
            $staffMember->notify(new ReviewReceived($review));
        }

        return redirect()->route('books.show', $book)
            ->with('success', 'Ulasan Anda untuk "'.$book->title.'" telah tersimpan.');
    }

    /**
     * Memperbarui ulasan milik anggota sendiri.
     */
    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $review->update([
            'rating' => $request->integer('rating'),
            'comment' => $request->filled('comment') ? $request->string('comment')->toString() : null,
        ]);

        return back()->with('success', 'Ulasan berhasil diperbarui.');
    }

    /**
     * Menghapus ulasan milik anggota sendiri.
     */
    public function destroy(Review $review): RedirectResponse
    {
        if ((int) $review->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $bookId = $review->book_id;
        $review->delete();

        return redirect()->route('books.show', $bookId)
            ->with('success', 'Ulasan berhasil dihapus.');
    }
}
