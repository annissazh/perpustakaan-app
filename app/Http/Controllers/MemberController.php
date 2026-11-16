<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    // List Anggota
    public function index()
    {
        $members = User::where('role', 'anggota')->latest()->get();
        return view('members.index', compact('members'));
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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'anggota', // Otomatis dikunci sebagai anggota
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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $member->id,
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'name'  => $request->name,
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