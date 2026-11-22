<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
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
            'book_id' => [
                'required',
                'integer',
                Rule::exists('books', 'id'),
                Rule::unique('reservations', 'book_id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                        ->where('status', 'pending')
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'book_id.required' => 'Buku yang direservasi wajib dipilih.',
            'book_id.exists' => 'Buku yang dipilih tidak ditemukan.',
            'book_id.unique' => 'Anda sudah memiliki reservasi aktif untuk buku ini.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'book_id' => 'buku',
        ];
    }

    /**
     * Buku yang akan direservasi.
     */
    public function book(): Book
    {
        return Book::query()->findOrFail($this->integer('book_id'));
    }
}
