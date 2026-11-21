<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Menampilkan daftar semua pengguna sistem.
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Menampilkan form tambah user.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        $assignableRoles = app(UserPolicy::class)->assignableRoles(request()->user());

        return view('users.create', compact('assignableRoles'));
    }

    /**
     * Menyimpan data user baru dengan validasi hierarki role.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => $request->string('role')->toString(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Data user berhasil ditambahkan!');
    }

    /**
     * Menampilkan form edit user.
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $assignableRoles = app(UserPolicy::class)->assignableRoles(request()->user(), $user);

        return view('users.edit', compact('user', 'assignableRoles'));
    }

    /**
     * Memproses pembaruan data user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'role' => $request->string('role')->toString(),
        ];

        // Password hanya diubah bila field dikirimkan.
        if ($request->filled('password')) {
            $data['password'] = $request->string('password')->toString();
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'Data user berhasil diperbarui!');
    }

    /**
     * Menghapus user dengan proteksi hierarki.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Data user berhasil dihapus!');
    }
}
