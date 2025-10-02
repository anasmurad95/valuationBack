<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'to_whom_type_id',
        'template_type',
        'description',
        'template_content',
        'template_file_path',
        'features',
        'sections',
        'styling',
        'is_active'
    ];

    protected $casts = [
        'features' => 'array',
        'sections' => 'array',
        'styling' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the to-whom type that owns this template.
     */
    public function toWhomType()
    {
        return $this->belongsTo(ToWhomType::class);
    }

    /**
     * Get all valuations using this template.
     */
    public function valuations()
    {
        return $this->hasMany(Valuation::class, 'template_id');
    }

    /**
     * Scope to get only active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by template type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Get the display name based on locale.
     */
    public function getDisplayNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Get the template type label in Arabic.
     */
    public function getTypeLabelAttribute()
    {
        $labels = [
            'bank' => 'قالب البنوك',
            'government' => 'قالب حكومي',
            'private_company' => 'قالب الشركات',
            'court' => 'قالب المحاكم',
            'individual' => 'قالب الأفراد',
            'general' => 'قالب عام'
        ];

        return $labels[$this->template_type] ?? 'قالب عام';
    }

    /**
     * Get default sections for a template type.
     */
    public static function getDefaultSections($type)
    {
        $sections = [
            'bank' => [
                'cover_page' => 'الصفحة الأولى',
                'executive_summary' => 'الملخص التنفيذي',
                'property_description' => 'وصف العقار',
                'market_analysis' => 'تحليل السوق',
                'valuation_methods' => 'طرق التقييم',
                'risk_assessment' => 'تقييم المخاطر',
                'recommendations' => 'التوصيات',
                'appendices' => 'الملاحق'
            ],
            'government' => [
                'cover_page' => 'الصفحة الأولى',
                'legal_framework' => 'الإطار القانوني',
                'property_description' => 'وصف العقار',
                'valuation_methods' => 'طرق التقييم',
                'compliance' => 'التوافق مع الأنظمة',
                'conclusions' => 'الخلاصة',
                'appendices' => 'الملاحق'
            ],
            'court' => [
                'cover_page' => 'الصفحة الأولى',
                'legal_basis' => 'الأساس القانوني',
                'property_description' => 'وصف العقار',
                'valuation_methods' => 'طرق التقييم',
                'expert_opinion' => 'رأي الخبير',
                'legal_references' => 'المراجع القانونية',
                'appendices' => 'الملاحق'
            ],
            'private_company' => [
                'cover_page' => 'الصفحة الأولى',
                'investment_analysis' => 'تحليل الاستثمار',
                'property_description' => 'وصف العقار',
                'market_analysis' => 'تحليل السوق',
                'valuation_methods' => 'طرق التقييم',
                'roi_analysis' => 'تحليل العائد',
                'recommendations' => 'التوصيات',
                'appendices' => 'الملاحق'
            ],
            'individual' => [
                'cover_page' => 'الصفحة الأولى',
                'property_description' => 'وصف العقار',
                'valuation_methods' => 'طرق التقييم',
                'market_comparison' => 'المقارنة السوقية',
                'conclusions' => 'الخلاصة',
                'appendices' => 'الملاحق'
            ]
        ];

        return $sections[$type] ?? $sections['individual'];
    }

    /**
     * Get default styling for a template type.
     */
    public static function getDefaultStyling($type)
    {
        return [
            'font_family' => 'Arial, sans-serif',
            'font_size' => [
                'title' => '18px',
                'heading' => '16px',
                'body' => '12px'
            ],
            'colors' => [
                'primary' => '#1976d2',
                'secondary' => '#424242',
                'text' => '#333333'
            ],
            'margins' => [
                'top' => '2cm',
                'bottom' => '2cm',
                'left' => '2cm',
                'right' => '2cm'
            ],
            'header_footer' => true,
            'page_numbers' => true,
            'watermark' => false
        ];
    }
}

