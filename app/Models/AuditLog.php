<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'valuation_id',
        'performed_by',
        'action',
        'reason',
    ];

    /**
     * التقييم المرتبط بالسجل
     */
    public function valuation()
    {
        return $this->belongsTo(Valuation::class);
    }

    /**
     * الموظف الذي قام بالفعل
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
