<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    /**
     * Get all roles with permissions.
     */
    public function getRoles()
    {
        $roles = Role::with('permissions')->orderBy('level', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Get all permissions grouped by module.
     */
    public function getPermissions()
    {
        $permissions = Permission::getGroupedPermissions();

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Create a new role.
     */
    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name|max:255',
            'display_name_ar' => 'required|string|max:255',
            'display_name_en' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'level' => 'required|integer|min:1|max:10|unique:roles,level',
            'color' => 'nullable|string|max:7',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $role = Role::create($validator->validated());

            // Assign permissions
            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الدور بنجاح',
                'data' => $role->load('permissions')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الدور',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a role.
     */
    public function updateRole(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name_ar' => 'required|string|max:255',
            'display_name_en' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'level' => 'required|integer|min:1|max:10|unique:roles,level,' . $role->id,
            'color' => 'nullable|string|max:7',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $role->update($validator->validated());

            // Update permissions
            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدور بنجاح',
                'data' => $role->fresh()->load('permissions')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الدور',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role)
    {
        // Check if role is in use
        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن حذف هذا الدور لأنه مستخدم من قبل {$usersCount} مستخدم"
            ], 409);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الدور بنجاح'
        ]);
    }

    /**
     * Assign roles to user.
     */
    public function assignUserRoles(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if current user can manage target user
        if (!auth()->user()->canManageUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإدارة هذا المستخدم'
            ], 403);
        }

        $user->roles()->sync($request->roles);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث أدوار المستخدم بنجاح',
            'data' => $user->fresh()->load('roles')
        ]);
    }

    /**
     * Get user roles and permissions.
     */
    public function getUserRoles(User $user)
    {
        $user->load(['roles.permissions']);

        $allPermissions = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $allPermissions[$permission->name] = $permission;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'roles' => $user->roles,
                'permissions' => array_values($allPermissions)
            ]
        ]);
    }

    /**
     * Get current user permissions.
     */
    public function getCurrentUserPermissions()
    {
        $user = auth()->user();
        $user->load(['roles.permissions']);

        $allPermissions = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $allPermissions[$permission->name] = $permission;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $user->roles,
                'permissions' => array_values($allPermissions),
                'permission_names' => array_keys($allPermissions)
            ]
        ]);
    }

    /**
     * Initialize default roles and permissions.
     */
    public function initializeSystem()
    {
        DB::beginTransaction();
        try {
            // Create permissions if they don't exist
            $defaultPermissions = Permission::getDefaultPermissions();
            foreach ($defaultPermissions as $permissionData) {
                Permission::firstOrCreate(
                    ['name' => $permissionData['name']],
                    $permissionData
                );
            }

            // Create default roles if they don't exist
            $defaultRoles = Role::getDefaultRoles();
            foreach ($defaultRoles as $roleData) {
                $permissions = $roleData['permissions'] ?? [];
                unset($roleData['permissions']);

                $role = Role::firstOrCreate(
                    ['name' => $roleData['name']],
                    $roleData
                );

                // Assign permissions to role
                if (!empty($permissions)) {
                    $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
                    $role->permissions()->sync($permissionIds);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تهيئة النظام بنجاح'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تهيئة النظام',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role statistics.
     */
    public function getRoleStats()
    {
        $stats = [
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'roles_with_users' => Role::has('users')->count(),
            'users_with_roles' => User::has('roles')->count(),
            'role_distribution' => Role::withCount('users')->get()->map(function ($role) {
                return [
                    'role' => $role->display_name,
                    'users_count' => $role->users_count,
                    'level' => $role->level
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Check user permission.
     */
    public function checkPermission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission' => 'required|string',
            'user_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user_id ? User::find($request->user_id) : auth()->user();
        $hasPermission = $user->hasPermission($request->permission);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'permission' => $request->permission,
                'has_permission' => $hasPermission
            ]
        ]);
    }

    /**
     * Get users by role.
     */
    public function getUsersByRole(Role $role)
    {
        $users = $role->users()->with('roles')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $role,
                'users' => $users,
                'count' => $users->count()
            ]
        ]);
    }

    /**
     * Bulk assign roles to users.
     */
    public function bulkAssignRoles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'action' => 'required|in:assign,remove,replace'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $users = User::whereIn('id', $request->user_ids)->get();
        $roleIds = $request->role_ids;
        $action = $request->action;

        $updatedCount = 0;

        foreach ($users as $user) {
            // Check if current user can manage target user
            if (!auth()->user()->canManageUser($user)) {
                continue;
            }

            switch ($action) {
                case 'assign':
                    $currentRoleIds = $user->roles()->pluck('roles.id')->toArray();
                    $newRoleIds = array_unique(array_merge($currentRoleIds, $roleIds));
                    $user->roles()->sync($newRoleIds);
                    break;

                case 'remove':
                    $user->roles()->detach($roleIds);
                    break;

                case 'replace':
                    $user->roles()->sync($roleIds);
                    break;
            }

            $updatedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "تم تحديث أدوار {$updatedCount} مستخدم بنجاح",
            'data' => [
                'updated_count' => $updatedCount,
                'total_requested' => count($request->user_ids)
            ]
        ]);
    }
}

