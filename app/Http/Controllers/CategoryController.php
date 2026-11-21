<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::orderBy('name')->paginate(15);

        return view('categories.index', compact('categories'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create(['name' => $request->string('name')->toString()]);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori baru berhasil ditambahkan!');
    }

    /**
     * Menampilkan form edit kategori.
     */
    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Menyimpan perubahan kategori dari modal.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update(['name' => $request->string('name')->toString()]);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui!');
    }

    /**
     * Menghapus kategori.
     */
    public function destroy(Category $category): RedirectResponse
    {
        // Kategori yang masih memiliki buku tidak boleh dihapus karena akan
        // ikut menghapus seluruh buku di dalamnya (cascade).
        if ($category->books()->exists()) {
            return redirect()->route('categories.index')
                ->with('error', 'Kategori masih memiliki buku sehingga tidak dapat dihapus.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus!');
    }
}
