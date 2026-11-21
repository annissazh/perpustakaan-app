<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->isStaff() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', Role::Anggota->value)
                ),
            ],
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'duration_days' => [
                'required',
                'integer',
                'min:1',
                'max:'.SystemSetting::getInt('maksimal_hari_pinjam', 7),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Anggota peminjam wajib dipilih.',
            'user_id.exists' => 'Anggota peminjam tidak valid.',
            'book_id.required' => 'Buku yang dipinjam wajib dipilih.',
            'book_id.exists' => 'Buku yang dipilih tidak ditemukan.',
            'duration_days.required' => 'Durasi peminjaman wajib diisi.',
            'duration_days.integer' => 'Durasi peminjaman harus berupa angka.',
            'duration_days.min' => 'Durasi peminjaman minimal 1 hari.',
            'duration_days.max' => 'Durasi peminjaman melebihi batas maksimum sistem.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'anggota peminjam',
            'book_id' => 'buku',
            'duration_days' => 'durasi peminjaman',
        ];
    }
}
