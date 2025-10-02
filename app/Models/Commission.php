<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'valuation_id',
        'amount',
        'period',
        'notified',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'notified' => 'boolean',
    ];

    /**
     * الموظف المرتبط بالعمولة
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * التقييم المرتبط (إن وُجد)
     */
    public function valuation()
    {
        return $this->belongsTo(Valuation::class);
    }
}
