<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',
            'birth_date.before' => 'Tanggal lahir tidak boleh di masa depan.',
            'gender.in' => 'Jenis kelamin tidak valid.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nomor telepon',
            'address' => 'alamat',
            'institution' => 'instansi',
            'birth_date' => 'tanggal lahir',
            'gender' => 'jenis kelamin',
        ];
    }
}
