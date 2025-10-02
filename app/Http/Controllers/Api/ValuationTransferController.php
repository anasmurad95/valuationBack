<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Valuation;
use App\Models\ValuationTransfer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ValuationTransferController extends Controller
{
    /**
     * Get transfers for a valuation.
     */
    public function index(Valuation $valuation)
    {
        $transfers = $valuation->transfers()
                              ->with(['fromUser', 'toUser'])
                              ->orderBy('created_at', 'desc')
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $transfers
        ]);
    }

    /**
     * Create a new transfer request.
     */
    public function store(Request $request, Valuation $valuation)
    {
        $validator = Validator::make($request->all(), [
            'to_user_id' => 'required|exists:users,id|different:from_user_id',
            'reason' => 'required|string|min:10|max:500',
            'priority' => 'in:low,medium,high,urgent',
            'due_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'transfer_type' => 'in:temporary,permanent',
            'requires_approval' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if valuation can be transferred
        $canTransfer = $this->canTransferValuation($valuation, auth()->id());
        if (!$canTransfer['can_transfer']) {
            return response()->json([
                'success' => false,
                'message' => $canTransfer['reason']
            ], 403);
        }

        // Check if there's already a pending transfer
        $pendingTransfer = $valuation->pendingTransfers()->first();
        if ($pendingTransfer) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد طلب تحويل معلق لهذا التقييم'
            ], 409);
        }

        // Check if target user can receive transfers
        $toUser = User::find($request->to_user_id);
        $canReceive = $this->canReceiveTransfer($toUser, $valuation);
        if (!$canReceive['can_receive']) {
            return response()->json([
                'success' => false,
                'message' => $canReceive['reason']
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Create transfer record
            $transfer = ValuationTransfer::create([
                'valuation_id' => $valuation->id,
                'from_user_id' => auth()->id(),
                'to_user_id' => $request->to_user_id,
                'reason' => $request->reason,
                'priority' => $request->input('priority', 'medium'),
                'due_date' => $request->due_date,
                'notes' => $request->notes,
                'transfer_type' => $request->input('transfer_type', 'permanent'),
                'requires_approval' => $request->boolean('requires_approval', true),
                'status' => $request->boolean('requires_approval', true) ? 'pending' : 'approved',
                'requested_at' => now()
            ]);

            // If no approval required, process transfer immediately
            if (!$request->boolean('requires_approval', true)) {
                $this->processTransfer($transfer);
            }

            // Send notification to target user
            $this->sendTransferNotification($transfer);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->boolean('requires_approval', true) 
                    ? 'تم إرسال طلب التحويل بنجاح' 
                    : 'تم تحويل التقييم بنجاح',
                'data' => $transfer->load(['fromUser', 'toUser'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء طلب التحويل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a transfer request.
     */
    public function approve(Request $request, ValuationTransfer $transfer)
    {
        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user can approve this transfer
        $canApprove = $this->canApproveTransfer($transfer, auth()->id());
        if (!$canApprove['can_approve']) {
            return response()->json([
                'success' => false,
                'message' => $canApprove['reason']
            ], 403);
        }

        if ($transfer->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب تم التعامل معه مسبقاً'
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Update transfer status
            $transfer->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes
            ]);

            // Process the transfer
            $this->processTransfer($transfer);

            // Send notifications
            $this->sendApprovalNotification($transfer, 'approved');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم اعتماد طلب التحويل بنجاح',
                'data' => $transfer->fresh()->load(['fromUser', 'toUser', 'approver'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء اعتماد طلب التحويل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a transfer request.
     */
    public function reject(Request $request, ValuationTransfer $transfer)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:10|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user can reject this transfer
        $canReject = $this->canApproveTransfer($transfer, auth()->id());
        if (!$canReject['can_approve']) {
            return response()->json([
                'success' => false,
                'message' => $canReject['reason']
            ], 403);
        }

        if ($transfer->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب تم التعامل معه مسبقاً'
            ], 409);
        }

        $transfer->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason
        ]);

        // Send notifications
        $this->sendApprovalNotification($transfer, 'rejected');

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب التحويل',
            'data' => $transfer->fresh()->load(['fromUser', 'toUser', 'approver'])
        ]);
    }

    /**
     * Cancel a transfer request.
     */
    public function cancel(ValuationTransfer $transfer)
    {
        // Check if user can cancel this transfer
        if ($transfer->from_user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إلغاء هذا الطلب'
            ], 403);
        }

        if ($transfer->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا الطلب'
            ], 409);
        }

        $transfer->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء طلب التحويل',
            'data' => $transfer->fresh()
        ]);
    }

    /**
     * Get pending transfers for current user.
     */
    public function getPendingTransfers()
    {
        $pendingTransfers = ValuationTransfer::where('to_user_id', auth()->id())
                                           ->where('status', 'pending')
                                           ->with(['valuation', 'fromUser'])
                                           ->orderBy('created_at', 'desc')
                                           ->get();

        return response()->json([
            'success' => true,
            'data' => $pendingTransfers
        ]);
    }

    /**
     * Get transfer history for current user.
     */
    public function getTransferHistory(Request $request)
    {
        $query = ValuationTransfer::where(function($q) {
                $q->where('from_user_id', auth()->id())
                  ->orWhere('to_user_id', auth()->id());
            })
            ->with(['valuation', 'fromUser', 'toUser', 'approver']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('transfer_type')) {
            $query->where('transfer_type', $request->transfer_type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transfers = $query->orderBy('created_at', 'desc')
                          ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transfers
        ]);
    }

    /**
     * Get transfer statistics.
     */
    public function getTransferStats()
    {
        $userId = auth()->id();
        
        $stats = [
            'sent_transfers' => [
                'total' => ValuationTransfer::where('from_user_id', $userId)->count(),
                'pending' => ValuationTransfer::where('from_user_id', $userId)->where('status', 'pending')->count(),
                'approved' => ValuationTransfer::where('from_user_id', $userId)->where('status', 'approved')->count(),
                'rejected' => ValuationTransfer::where('from_user_id', $userId)->where('status', 'rejected')->count(),
            ],
            'received_transfers' => [
                'total' => ValuationTransfer::where('to_user_id', $userId)->count(),
                'pending' => ValuationTransfer::where('to_user_id', $userId)->where('status', 'pending')->count(),
                'approved' => ValuationTransfer::where('to_user_id', $userId)->where('status', 'approved')->count(),
                'rejected' => ValuationTransfer::where('to_user_id', $userId)->where('status', 'rejected')->count(),
            ],
            'this_month' => [
                'sent' => ValuationTransfer::where('from_user_id', $userId)
                                         ->whereMonth('created_at', now()->month)
                                         ->count(),
                'received' => ValuationTransfer::where('to_user_id', $userId)
                                             ->whereMonth('created_at', now()->month)
                                             ->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Check if valuation can be transferred.
     */
    private function canTransferValuation(Valuation $valuation, $userId)
    {
        // Check if user is the owner or has permission
        if ($valuation->prepared_by !== $userId && $valuation->inspected_by !== $userId) {
            return [
                'can_transfer' => false,
                'reason' => 'لا يمكنك تحويل هذا التقييم'
            ];
        }

        // Check valuation status
        if (in_array($valuation->status, ['completed', 'approved'])) {
            return [
                'can_transfer' => false,
                'reason' => 'لا يمكن تحويل تقييم مكتمل أو معتمد'
            ];
        }

        return ['can_transfer' => true];
    }

    /**
     * Check if user can receive transfer.
     */
    private function canReceiveTransfer(User $user, Valuation $valuation)
    {
        // Check if user is active
        if (!$user->is_active) {
            return [
                'can_receive' => false,
                'reason' => 'المستخدم المحدد غير نشط'
            ];
        }

        // Check user workload (optional)
        $currentWorkload = Valuation::where('prepared_by', $user->id)
                                  ->whereIn('status', ['draft', 'in_progress'])
                                  ->count();

        if ($currentWorkload >= 10) { // Max 10 active valuations
            return [
                'can_receive' => false,
                'reason' => 'المستخدم المحدد لديه عبء عمل كامل'
            ];
        }

        return ['can_receive' => true];
    }

    /**
     * Check if user can approve transfer.
     */
    private function canApproveTransfer(ValuationTransfer $transfer, $userId)
    {
        // Target user can approve their own transfers
        if ($transfer->to_user_id === $userId) {
            return ['can_approve' => true];
        }

        // Supervisors can approve transfers
        // This would depend on your role system
        // For now, allow any user to approve
        return ['can_approve' => true];
    }

    /**
     * Process the actual transfer.
     */
    private function processTransfer(ValuationTransfer $transfer)
    {
        $valuation = $transfer->valuation;
        
        // Update valuation ownership
        $valuation->update([
            'prepared_by' => $transfer->to_user_id,
            'transferred_at' => now(),
            'transfer_notes' => $transfer->notes
        ]);

        // Update transfer status
        $transfer->update([
            'processed_at' => now()
        ]);
    }

    /**
     * Send transfer notification.
     */
    private function sendTransferNotification(ValuationTransfer $transfer)
    {
        // Here you would implement notification logic
        // For example, email, SMS, or in-app notifications
        
        // Log the notification for now
        \Log::info('Transfer notification sent', [
            'transfer_id' => $transfer->id,
            'from_user' => $transfer->from_user_id,
            'to_user' => $transfer->to_user_id
        ]);
    }

    /**
     * Send approval/rejection notification.
     */
    private function sendApprovalNotification(ValuationTransfer $transfer, $action)
    {
        // Here you would implement notification logic
        
        // Log the notification for now
        \Log::info('Transfer approval notification sent', [
            'transfer_id' => $transfer->id,
            'action' => $action,
            'from_user' => $transfer->from_user_id,
            'to_user' => $transfer->to_user_id
        ]);
    }
}

