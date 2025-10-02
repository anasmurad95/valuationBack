<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    use HasFactory;

    protected $fillable = [
        'nameAr',
        'nameEn',
        'REF',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relations
     */
    public function valuations()
    {
        return $this->hasMany(Valuation::class);
    }

    public function toWhomTypes()
    {
        return $this->belongsToMany(ToWhomType::class, 'property_type_to_whom_types');
    }


    /**
     * Accessor
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->nameEn} / {$this->nameAr}";
    }
}
