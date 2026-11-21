<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * Menampilkan daftar buku.
     */
    public function index(): View
    {
        $books = Book::with('category')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('books.index', compact('books'));
    }

    /**
     * Menampilkan detail buku.
     */
    public function show(Book $book): View
    {
        $book->load('category');

        $reviews = $book->reviews()->with('user')->latest()->take(5)->get();
        $averageRating = $book->reviews()->avg('rating');
        $totalReviews = $book->reviews()->count();
        $myReview = auth()->check()
            ? $book->reviews()->where('user_id', auth()->id())->first()
            : null;

        return view('books.show', compact('book', 'reviews', 'averageRating', 'totalReviews', 'myReview'));
    }

    /**
     * Menampilkan form tambah buku.
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('books.create', compact('categories'));
    }

    /**
     * Menyimpan buku baru.
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $book = new Book([
            'category_id' => $request->integer('category_id'),
            'title' => $request->string('title')->toString(),
            'author' => $request->string('author')->toString(),
            'publisher' => $request->filled('publisher') ? $request->string('publisher')->toString() : null,
            'isbn' => $request->string('isbn')->toString(),
            'published_year' => $request->integer('published_year'),
            'total_stock' => $request->integer('total_stock'),
            'fine_per_day' => $request->filled('fine_per_day')
                ? $request->integer('fine_per_day')
                : SystemSetting::getInt('denda_per_hari', 1000),
            'cover_image' => $this->storeCoverImage($request),
        ]);

        // Stok tersedia selalu sama dengan stok total saat buku baru dibuat.
        $book->available_stock = $book->total_stock;
        $book->save();

        return redirect()->route('books.index')
            ->with('success', 'Buku berhasil ditambahkan!');
    }

    /**
     * Menampilkan form edit buku.
     */
    public function edit(Book $book): View
    {
        $categories = Category::orderBy('name')->get();

        return view('books.edit', compact('book', 'categories'));
    }

    /**
     * Memperbarui data buku.
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $coverImagePath = $book->cover_image;

        if ($request->hasFile('cover_image')) {
            $this->deleteCoverImage($book);
            $coverImagePath = $request->file('cover_image')->store('covers', 'public');
        }

        $borrowedCopies = $book->activeBorrowings()->count();

        $book->fill([
            'category_id' => $request->integer('category_id'),
            'title' => $request->string('title')->toString(),
            'author' => $request->string('author')->toString(),
            'publisher' => $request->filled('publisher') ? $request->string('publisher')->toString() : null,
            'isbn' => $request->string('isbn')->toString(),
            'published_year' => $request->integer('published_year'),
            'total_stock' => $request->integer('total_stock'),
            'fine_per_day' => $request->filled('fine_per_day')
                ? $request->integer('fine_per_day')
                : $book->fine_per_day,
            'cover_image' => $coverImagePath,
        ]);

        // Stok tersedia diturunkan dari stok total dikurangi buku yang sedang dipinjam
        // sehingga tidak pernah menjadi negatif.
        $book->available_stock = max(0, $book->total_stock - $borrowedCopies);
        $book->save();

        return redirect()->route('books.index')
            ->with('success', 'Data buku berhasil diperbarui!');
    }

    /**
     * Menghapus buku.
     */
    public function destroy(Book $book): RedirectResponse
    {
        if ($book->activeBorrowings()->exists()) {
            return redirect()->route('books.index')
                ->with('error', 'Buku masih dipinjam sehingga tidak dapat dihapus.');
        }

        $this->deleteCoverImage($book);
        $book->delete();

        return redirect()->route('books.index')
            ->with('success', 'Buku beserta sampulnya berhasil dihapus!');
    }

    /**
     * Katalog buku yang dapat dicari dan difilter.
     */
    public function katalog(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $books = Book::with('category')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                })
            )
            ->when(
                $filters['category'] ?? null,
                fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId)
            )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();
        $maxLoanDays = SystemSetting::getInt('maksimal_hari_pinjam', 7);

        return view('katalog', compact('books', 'categories', 'maxLoanDays'));
    }

    /**
     * Respon JSON untuk live search pada katalog.
     */
    public function searchAjax(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        if (mb_strlen($search) < 3) {
            return response()->json([]);
        }

        $books = Book::query()
            ->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            })
            ->select(['id', 'title', 'author'])
            ->orderBy('title')
            ->take(5)
            ->get();

        return response()->json($books);
    }

    /**
     * Menyimpan file sampul buku.
     */
    private function storeCoverImage(Request $request): ?string
    {
        if (! $request->hasFile('cover_image')) {
            return null;
        }

        return $request->file('cover_image')->store('covers', 'public');
    }

    /**
     * Menghapus file sampul buku.
     */
    private function deleteCoverImage(Book $book): void
    {
        if ($book->cover_image && Storage::disk('public')->exists($book->cover_image)) {
            Storage::disk('public')->delete($book->cover_image);
        }
    }
}
