<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValuationTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'valuation_id',
        'from_user_id',
        'to_user_id',
        'transfer_reason',
        'transfer_notes',
        'status',
        'transferred_at',
        'responded_at',
        'created_by',
        'rejection_reason'
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'responded_at' => 'datetime'
    ];

    /**
     * Get the valuation that was transferred.
     */
    public function valuation()
    {
        return $this->belongsTo(Valuation::class);
    }

    /**
     * Get the user who transferred the valuation.
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the user who received the valuation.
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Get the user who created the transfer.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get pending transfers.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted transfers.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get rejected transfers.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get transfers for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('to_user_id', $userId);
    }

    /**
     * Scope to get transfers from a specific user.
     */
    public function scopeFromUser($query, $userId)
    {
        return $query->where('from_user_id', $userId);
    }

    /**
     * Accept the transfer.
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now()
        ]);

        // Update the valuation's assigned user
        $this->valuation->update([
            'assigned_to' => $this->to_user_id
        ]);

        return $this;
    }

    /**
     * Reject the transfer.
     */
    public function reject($reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'responded_at' => now(),
            'rejection_reason' => $reason
        ]);

        return $this;
    }

    /**
     * Get the status label in Arabic.
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'في الانتظار',
            'accepted' => 'مقبول',
            'rejected' => 'مرفوض'
        ];

        return $labels[$this->status] ?? 'غير محدد';
    }

    /**
     * Get the transfer reason label in Arabic.
     */
    public function getReasonLabelAttribute()
    {
        $reasons = [
            'employee_leave' => 'إجازة الموظف',
            'specialization' => 'تخصص في نوع العقار',
            'workload_distribution' => 'توزيع الأعباء',
            'conflict_of_interest' => 'تضارب المصالح',
            'other' => 'أسباب أخرى'
        ];

        return $reasons[$this->transfer_reason] ?? $this->transfer_reason;
    }

    /**
     * Check if the transfer is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the transfer is accepted.
     */
    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if the transfer is rejected.
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Get the response time in hours.
     */
    public function getResponseTimeAttribute()
    {
        if (!$this->responded_at) {
            return null;
        }

        return $this->transferred_at->diffInHours($this->responded_at);
    }
}

