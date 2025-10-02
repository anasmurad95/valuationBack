<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'valuation_id',
        'channel',
        'recipient',
        'status',
        'message',
        'error_message',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * التقييم المرتبط بالإشعار (إن وُجد)
     */
    public function valuation()
    {
        return $this->belongsTo(Valuation::class);
    }
}
