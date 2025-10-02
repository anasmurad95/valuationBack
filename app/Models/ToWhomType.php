<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToWhomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'type',
        'template_id',
        'description',
        'features',
        'is_active'
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the report template associated with this to-whom type.
     */
    public function reportTemplate()
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    /**
     * Get all valuations for this to-whom type.
     */
    public function valuations()
    {
        return $this->hasMany(Valuation::class);
    }

    /**
     * Scope to get only active types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the display name based on locale.
     */
    public function getDisplayNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Get the type icon.
     */
    public function getTypeIconAttribute()
    {
        $icons = [
            'bank' => 'mdi-bank',
            'government' => 'mdi-city',
            'private_company' => 'mdi-office-building',
            'court' => 'mdi-gavel',
            'individual' => 'mdi-account',
            'other' => 'mdi-domain'
        ];

        return $icons[$this->type] ?? 'mdi-domain';
    }

    /**
     * Get the type label in Arabic.
     */
    public function getTypeLabelAttribute()
    {
        $labels = [
            'bank' => 'البنوك',
            'government' => 'الجهات الحكومية',
            'private_company' => 'الشركات الخاصة',
            'court' => 'المحاكم',
            'individual' => 'الأفراد',
            'other' => 'أخرى'
        ];

        return $labels[$this->type] ?? 'غير محدد';
    }
}

