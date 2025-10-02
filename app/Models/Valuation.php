<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Valuation extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_source',
        'source_details',
        'client_id',
        'prepared_by',
        'inspected_by',
        'to_whom_type_id',
        'property_type',
        'property_usage',
        'property_description',
        'location_name',
        'coordinates',
        'krooki_path',
        'address_details',
        'latitude',
        'longitude',
        'site_visit_date',
        'report_submitted_at',
        'report_approved_at',
        'status',
        'valuation_number',
        'is_active',
        'referred_by_type',
        'referred_by_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // علاقات

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the sketch for this valuation.
     */
    public function sketch()
    {
        return $this->hasOne(ValuationSketch::class);
    }

    /**
     * Get the to-whom type for this valuation.
     */
    public function toWhomType()
    {
        return $this->belongsTo(ToWhomType::class);
    }

    /**
     * Get the transfers for this valuation.
     */
    public function transfers()
    {
        return $this->hasMany(ValuationTransfer::class);
    }

    /**
     * Get the latest transfer for this valuation.
     */
    public function latestTransfer()
    {
        return $this->hasOne(ValuationTransfer::class)->latest();
    }

    /**
     * Get pending transfers for this valuation.
     */
    public function pendingTransfers()
    {
        return $this->hasMany(ValuationTransfer::class)->where('status', 'pending');
    }

    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function toWhomType()
    {
        return $this->belongsTo(ToWhomType::class);
    }

    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class, 'property_type');
    }

    // العلاقة الذكية مع المحيل (موظف أو عميل)
    public function referredBy()
    {
        return match ($this->referred_by_type) {
            'user' => $this->belongsTo(User::class, 'referred_by_id'),
            'client' => $this->belongsTo(Client::class, 'referred_by_id'),
            default => null,
        };
    }
}
