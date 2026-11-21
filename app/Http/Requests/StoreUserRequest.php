<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Policies\UserPolicy;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->canManageUsers() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => [
                'required',
                Rule::enum(Role::class),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! in_array($value, $this->assignableRoles(), true)) {
                        $fail('Anda tidak memiliki wewenang untuk menetapkan hak akses tersebut.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email tersebut sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'role.required' => 'Hak akses wajib dipilih.',
            'role.enum' => 'Hak akses tidak valid.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
            'password' => 'password',
            'role' => 'hak akses',
        ];
    }

    /**
     * Role yang boleh ditetapkan oleh pengguna yang sedang login.
     *
     * @return array<int, string>
     */
    private function assignableRoles(): array
    {
        return app(UserPolicy::class)->assignableRoles($this->user());
    }
}
