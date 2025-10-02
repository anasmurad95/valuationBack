<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['roles']);
            
            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            // Filter by role
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('role_id', $request->role_id);
                });
            }
            
            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('is_active', $request->status === 'active');
            }
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $employees = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $employees,
                'message' => 'تم جلب قائمة الموظفين بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة الموظفين',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'required|string|max:20',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'license_number' => 'nullable|string|max:100',
                'license_expiry' => 'nullable|date',
                'specialization' => 'nullable|string|max:255',
                'experience_years' => 'nullable|integer|min:0',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $employee = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'license_number' => $request->license_number,
                'license_expiry' => $request->license_expiry,
                'specialization' => $request->specialization,
                'experience_years' => $request->experience_years,
                'is_active' => $request->get('is_active', true),
                'email_verified_at' => now()
            ]);

            // Assign role
            if ($request->role_id) {
                $employee->roles()->attach($request->role_id);
            }

            return response()->json([
                'success' => true,
                'data' => $employee->load('roles'),
                'message' => 'تم إنشاء الموظف بنجاح'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الموظف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified employee.
     */
    public function show($id): JsonResponse
    {
        try {
            $employee = User::with(['roles', 'valuations'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => 'تم جلب بيانات الموظف بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الموظف',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $employee = User::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id,
                'phone' => 'required|string|max:20',
                'password' => 'nullable|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'license_number' => 'nullable|string|max:100',
                'license_expiry' => 'nullable|date',
                'specialization' => 'nullable|string|max:255',
                'experience_years' => 'nullable|integer|min:0',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'license_number' => $request->license_number,
                'license_expiry' => $request->license_expiry,
                'specialization' => $request->specialization,
                'experience_years' => $request->experience_years,
                'is_active' => $request->get('is_active', true)
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $employee->update($updateData);

            // Update role
            if ($request->role_id) {
                $employee->roles()->sync([$request->role_id]);
            }

            return response()->json([
                'success' => true,
                'data' => $employee->load('roles'),
                'message' => 'تم تحديث بيانات الموظف بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات الموظف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified employee.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $employee = User::findOrFail($id);
            
            // Check if employee has valuations
            if ($employee->valuations()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الموظف لأنه مرتبط بتقييمات'
                ], 400);
            }
            
            $employee->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الموظف بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الموظف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle employee status.
     */
    public function toggleStatus($id): JsonResponse
    {
        try {
            $employee = User::findOrFail($id);
            $employee->update(['is_active' => !$employee->is_active]);
            
            $status = $employee->is_active ? 'مفعل' : 'معطل';
            
            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => "تم تغيير حالة الموظف إلى {$status}"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة الموظف',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee statistics.
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_employees' => User::count(),
                'active_employees' => User::where('is_active', true)->count(),
                'inactive_employees' => User::where('is_active', false)->count(),
                'employees_by_role' => User::with('roles')
                    ->get()
                    ->groupBy(function($user) {
                        return $user->roles->first()->display_name_ar ?? 'غير محدد';
                    })
                    ->map(function($group) {
                        return $group->count();
                    }),
                'recent_employees' => User::with('roles')
                    ->latest()
                    ->take(5)
                    ->get()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'تم جلب إحصائيات الموظفين بنجاح'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب إحصائيات الموظفين',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

