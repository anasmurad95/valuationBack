<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValuationAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'valuation_id',
        'type',
        'file_path',
        'file_name',
        'uploaded_by',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    /**
     * التقييم المرتبط بالمرفق
     */
    public function valuation()
    {
        return $this->belongsTo(Valuation::class);
    }

    /**
     * المستخدم الذي رفع المرفق (اختياري)
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
