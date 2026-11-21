<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    /**
     * Cache pengaturan pada tingkat request agar tidak query berulang.
     *
     * @var array<string, string>|null
     */
    protected static ?array $cache = null;

    /**
     * Ambil nilai pengaturan berdasarkan key, atau nilai default bila belum diset.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        static::$cache ??= static::query()
            ->pluck('setting_value', 'setting_key')
            ->all();

        return static::$cache[$key] ?? $default;
    }

    /**
     * Ambil nilai pengaturan dalam bentuk numerik.
     */
    public static function getInt(string $key, int $default): int
    {
        $value = static::get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Bersihkan cache pengaturan.
     *
     * Wajib dipanggil setelah nilai pengaturan diubah agar data terbaru
     * langsung dipakai pada request berikutnya.
     */
    public static function flush(): void
    {
        static::$cache = null;
    }

    /**
     * Simpan atau perbarui nilai pengaturan.
     */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => (string) $value]
        );

        static::flush();
    }
}
