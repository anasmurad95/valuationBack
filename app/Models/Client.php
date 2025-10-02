<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nameAr',
        'nameEn',
        'email',
        'phone',
        'gender',
        'verified_at',
        'referred_type',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays and JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relations
     */
    public function valuations()
    {
        return $this->morphMany(Valuation::class, 'source');
    }

    /**
     * Get full name (optional accessor)
     */
    public function getFullNameAttribute()
    {
        return "{$this->nameEn} / {$this->nameAr}";
    }
}
