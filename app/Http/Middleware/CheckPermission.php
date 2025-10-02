<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول'
            ], 401);
        }

        // Check if user has any of the required permissions
        $hasPermission = false;
        
        foreach ($permissions as $permission) {
            if ($this->userHasPermission($user, $permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للقيام بهذا الإجراء',
                'required_permissions' => $permissions
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user has specific permission.
     */
    private function userHasPermission($user, $permission)
    {
        // Get user roles with permissions
        $userRoles = $user->roles()->with('permissions')->get();
        
        foreach ($userRoles as $role) {
            foreach ($role->permissions as $rolePermission) {
                if ($rolePermission->name === $permission) {
                    return true;
                }
            }
        }

        return false;
    }
}

