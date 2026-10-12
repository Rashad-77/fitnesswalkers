<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['is_dummy' => 'boolean'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::find($key)?->value ?? $default;
    }

    /** Saving through the admin screen means the owner has replaced the placeholder. */
    public static function put(string $key, ?string $value, bool $dummy = false): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'is_dummy' => $dummy]);
    }

    /** Secret in the door QR link. Created on first use and never a placeholder. */
    public static function checkinCode(bool $renew = false): string
    {
        $code = $renew ? null : static::get('checkin_code');
        if (! $code) {
            $code = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
            static::put('checkin_code', $code, false);
        }
        return $code;
    }
}
