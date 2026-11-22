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
        Schema::table('borrowings', function (Blueprint $table) {
            // Jumlah kali peminjaman diperpanjang (batas diatur dari pengaturan).
            $table->unsignedTinyInteger('extension_count')->default(0)->after('duration_days');
            $table->timestamp('extended_at')->nullable()->after('extension_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropColumn(['extension_count', 'extended_at']);
        });
    }
};
