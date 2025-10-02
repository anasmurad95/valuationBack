<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'to_whom_type_id',
        'template_json',
    ];

    protected $casts = [
        'template_json' => 'array',
    ];

    /**
     * العلاقة مع نوع الجهة (to_whom_type)
     */
    public function toWhomType()
    {
        return $this->belongsTo(ToWhomType::class, 'id');
    }
}
