<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MemberController extends Controller
{
    /**
     * Daftar anggota dengan pencarian, filter, dan pagination.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:aktif,terlambat,nonaktif'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;

        // Catatan: scope `active()` hanya tersedia pada Eloquent Builder.
        // Karena itu relasi peminjaman dimuat terlebih dahulu (eager load)
        // lalu difilter di level Collection agar tidak memanggil method
        // yang tidak ada pada Query\Builder di dalam closure relasi.
        $members = User::query()
            ->where('role', Role::Anggota->value)
            ->with(['borrowings' => fn ($query) => $query->active()])
            ->withCount(['borrowings as active_borrowings_count' => fn ($query) => $query->active()])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'aktif', fn ($query) => $query->whereHas('borrowings', fn ($query) => $query->whereNull('return_date')))
            ->when($status === 'terlambat', fn ($query) => $query->whereHas(
                'borrowings',
                fn ($query) => $query->whereNull('return_date')
                    ->whereDate('due_date', '<', now()->toDateString())
            ))
            ->when($status === 'nonaktif', fn ($query) => $query->whereDoesntHave('borrowings'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'total' => User::query()->where('role', Role::Anggota->value)->count(),
            'active' => Borrowing::query()
                ->whereHas('user', fn ($query) => $query->where('role', Role::Anggota->value))
                ->active()
                ->distinct('user_id')
                ->count('user_id'),
            'overdue' => Borrowing::query()
                ->whereHas('user', fn ($query) => $query->where('role', Role::Anggota->value))
                ->overdue()
                ->distinct('user_id')
                ->count('user_id'),
        ];

        return view('members.index', compact('members', 'summary'));
    }

    // Form Tambah Anggota
    public function create()
    {
        return view('members.create');
    }

    // Simpan Anggota Baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'anggota', // Otomatis dikunci sebagai anggota
        ]);

        return redirect('/members')->with('success', 'Data anggota berhasil ditambahkan!');
    }

    // Detail Anggota (Beserta Riwayat Peminjamannya)
    public function show($id)
    {
        $member = User::where('role', 'anggota')->findOrFail($id);
        $borrowings = Borrowing::with('book')
            ->where('user_id', $member->id)
            ->latest()
            ->get();

        return view('members.show', compact('member', 'borrowings'));
    }

    // Form Edit Anggota
    public function edit($id)
    {
        $member = User::where('role', 'anggota')->findOrFail($id);

        return view('members.edit', compact('member'));
    }

    // Update Data Anggota
    public function update(Request $request, $id)
    {
        $member = User::where('role', 'anggota')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$member->id,
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $member->update($data);

        return redirect('/members')->with('success', 'Data anggota berhasil diperbarui!');
    }

    // Hapus Anggota
    public function destroy($id)
    {
        $member = User::where('role', 'anggota')->findOrFail($id);
        $member->delete();

        return redirect('/members')->with('success', 'Data anggota berhasil dihapus!');
    }
}
