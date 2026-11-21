<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;

class SelfBorrowBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Anggota;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'duration_days' => [
                'nullable',
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
            'duration_days.integer' => 'Durasi peminjaman harus berupa angka.',
            'duration_days.min' => 'Durasi peminjaman minimal 1 hari.',
            'duration_days.max' => 'Durasi peminjaman melebihi batas maksimum sistem.',
        ];
    }
}
