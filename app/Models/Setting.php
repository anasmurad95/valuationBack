<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public $timestamps = true;

    /**
     * استرجاع إعداد حسب المفتاح
     */
    public static function get($key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * تحديث إعداد أو إنشاؤه
     */
    public static function set($key, $value)
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
