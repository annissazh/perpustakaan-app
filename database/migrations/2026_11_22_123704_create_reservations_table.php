<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Satu anggota hanya boleh memiliki satu reservasi aktif per buku.
            $table->unique(['user_id', 'book_id']);
            $table->index(['book_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
