<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\EvaluationReportTemplateController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ValuationController;
use App\Http\Controllers\Api\ValuationTypeController;
use App\Http\Controllers\Api\ToWhomTypeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\ValuationTransferController;
use App\Http\Controllers\Api\ValuationSketchController;
use App\Http\Controllers\Api\PdfExportController;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/settings', [SettingController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'profile']);
        Route::post('/user', [AuthController::class, 'updateProfile']);
        Route::post('/user/change-password', [AuthController::class, 'changePassword']);

        // Valuations
        Route::apiResource('valuations', ValuationController::class);
        Route::get('/ValuationwithDraft', [ValuationController::class, 'getDraftValuation']);

        // Valuation Types
        Route::prefix('valuation-types')->group(function () {
            Route::get('/', [ValuationTypeController::class, 'index']);
            Route::post('/', [ValuationTypeController::class, 'store']);
            Route::put('/{id}', [ValuationTypeController::class, 'update']);
            Route::patch('/{id}/toggle-active', [ValuationTypeController::class, 'toggleActive']);
        });

        // To Whom Types
        Route::prefix('to-whom-types')->group(function () {
            Route::get('/', [ToWhomTypeController::class, 'index']);
            Route::post('/', [ToWhomTypeController::class, 'store']);
            Route::put('{id}', [ToWhomTypeController::class, 'update']);
            Route::patch('{id}/toggle-active', [ToWhomTypeController::class, 'toggleActive']);
        });

        // Evaluation Report Templates
        Route::prefix('evaluation-report-templates')->group(function () {
            Route::get('/', [EvaluationReportTemplateController::class, 'index']);
            Route::post('/', [EvaluationReportTemplateController::class, 'store']);
            Route::put('/{id}', [EvaluationReportTemplateController::class, 'update']);
            Route::delete('/{id}', [EvaluationReportTemplateController::class, 'destroy']);
            Route::get('/by-to-whom/{toWhomTypeId}', [EvaluationReportTemplateController::class, 'showByToWhomType']);
        });

        // Clients and Institutions
        Route::get('/institutions', [ClientController::class, 'getinstitutions']);
        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/clients', [ClientController::class, 'store']);

        // Users
        Route::get('/users', [UserController::class, 'index']);

        // Employees Management
        Route::prefix('employees')->group(function () {
            Route::get('/', [EmployeeController::class, 'index']);
            Route::post('/', [EmployeeController::class, 'store']);
            Route::get('/{id}', [EmployeeController::class, 'show']);
            Route::put('/{id}', [EmployeeController::class, 'update']);
            Route::delete('/{id}', [EmployeeController::class, 'destroy']);
            Route::patch('/{id}/toggle-status', [EmployeeController::class, 'toggleStatus']);
            Route::get('/stats/overview', [EmployeeController::class, 'getStats']);
        });

        // Reports
        Route::prefix('reports')->group(function () {
            Route::post('/generate', [ReportController::class, 'generateReport']);
            Route::post('/export', [ReportController::class, 'exportReport']);
        });

        // Statistics
        Route::prefix('statistics')->group(function () {
            Route::get('/overview', [StatisticsController::class, 'getOverview']);
            Route::get('/detailed', [StatisticsController::class, 'getDetailed']);
            Route::get('/comparison', [StatisticsController::class, 'getComparison']);
        });

        // Activity Logs
        Route::prefix('activity-logs')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index']);
            Route::post('/', [ActivityLogController::class, 'store']);
            Route::get('/{id}', [ActivityLogController::class, 'show']);
            Route::get('/stats/overview', [ActivityLogController::class, 'getStats']);
            Route::get('/types/list', [ActivityLogController::class, 'getActivityTypes']);
            Route::delete('/clear-old', [ActivityLogController::class, 'clearOldLogs']);
            Route::post('/export', [ActivityLogController::class, 'export']);
        });

        // Roles and Permissions
        Route::prefix('roles')->group(function () {
            Route::get('/', [RolePermissionController::class, 'getRoles']);
            Route::post('/', [RolePermissionController::class, 'createRole']);
            Route::put('/{id}', [RolePermissionController::class, 'updateRole']);
            Route::delete('/{id}', [RolePermissionController::class, 'deleteRole']);
            Route::get('/{id}/permissions', [RolePermissionController::class, 'getRolePermissions']);
            Route::post('/{id}/permissions', [RolePermissionController::class, 'updateRolePermissions']);
        });

        Route::prefix('permissions')->group(function () {
            Route::get('/', [RolePermissionController::class, 'getPermissions']);
            Route::post('/', [RolePermissionController::class, 'createPermission']);
            Route::put('/{id}', [RolePermissionController::class, 'updatePermission']);
            Route::delete('/{id}', [RolePermissionController::class, 'deletePermission']);
        });

        Route::prefix('user-roles')->group(function () {
            Route::get('/{userId}', [RolePermissionController::class, 'getUserRoles']);
            Route::post('/{userId}', [RolePermissionController::class, 'assignUserRoles']);
            Route::delete('/{userId}/{roleId}', [RolePermissionController::class, 'removeUserRole']);
        });

        // Valuation Transfers
        Route::prefix('valuation-transfers')->group(function () {
            Route::get('/', [ValuationTransferController::class, 'index']);
            Route::post('/', [ValuationTransferController::class, 'store']);
            Route::get('/{id}', [ValuationTransferController::class, 'show']);
            Route::put('/{id}', [ValuationTransferController::class, 'update']);
            Route::delete('/{id}', [ValuationTransferController::class, 'destroy']);
            Route::post('/{id}/approve', [ValuationTransferController::class, 'approve']);
            Route::post('/{id}/reject', [ValuationTransferController::class, 'reject']);
            Route::get('/pending/list', [ValuationTransferController::class, 'getPendingTransfers']);
            Route::get('/user/{userId}', [ValuationTransferController::class, 'getUserTransfers']);
        });

        // Valuation Sketches
        Route::prefix('valuation-sketches')->group(function () {
            Route::get('/valuation/{valuationId}', [ValuationSketchController::class, 'getByValuation']);
            Route::post('/', [ValuationSketchController::class, 'store']);
            Route::put('/{id}', [ValuationSketchController::class, 'update']);
            Route::delete('/{id}', [ValuationSketchController::class, 'destroy']);
            Route::post('/{id}/landmarks', [ValuationSketchController::class, 'addLandmark']);
            Route::delete('/landmarks/{landmarkId}', [ValuationSketchController::class, 'removeLandmark']);
            Route::post('/{id}/comparables', [ValuationSketchController::class, 'addComparable']);
            Route::delete('/comparables/{comparableId}', [ValuationSketchController::class, 'removeComparable']);
        });

        // PDF Export
        Route::prefix('pdf')->group(function () {
            Route::post('/valuation/{id}', [PdfExportController::class, 'generateValuationPdf']);
            Route::post('/report', [PdfExportController::class, 'generateReportPdf']);
            Route::get('/templates', [PdfExportController::class, 'getTemplates']);
        });

        // Chat routes
        Route::prefix('chat')->group(function () {
            Route::get('/messages', [ChatController::class, 'index']);
            Route::post('/send', [ChatController::class, 'sendMessage']);
            Route::delete('/clear', [ChatController::class, 'clearHistory']);
        });

        // Dashboard routes
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [DashboardController::class, 'getStats']);
            Route::get('/recent-transfers', [DashboardController::class, 'getRecentTransfers']);
            Route::get('/valuations-by-status', [DashboardController::class, 'getValuationsByStatus']);
            Route::get('/recent-activities', [DashboardController::class, 'getRecentActivities']);
        });

        // Roles routes (for dashboard)
        Route::get('/roles', [DashboardController::class, 'getRoles']);

        Route::post('/logout', [AuthController::class, 'logout']);
    });


   // Valuation Transfer Routes
   Route::middleware(['auth:sanctum'])->group(function () {

       // Transfer Management
       Route::prefix('transfers')->group(function () {
           Route::get('/pending', [ValuationTransferController::class, 'getPendingTransfers']);
           Route::get('/history', [ValuationTransferController::class, 'getTransferHistory']);
           Route::get('/stats', [ValuationTransferController::class, 'getTransferStats']);
           Route::delete('/{transfer}/cancel', [ValuationTransferController::class, 'cancel']);
           Route::post('/{transfer}/approve', [ValuationTransferController::class, 'approve']);
           Route::post('/{transfer}/reject', [ValuationTransferController::class, 'reject']);
       });

       // Valuation-specific transfer routes
       Route::prefix('valuations/{valuation}')->group(function () {
           Route::get('/transfers', [ValuationTransferController::class, 'index']);
           Route::post('/transfers', [ValuationTransferController::class, 'store']);
       });

       // Valuation Sketch Routes
       Route::prefix('valuations/{valuation}/sketch')->group(function () {
           Route::get('/', [ValuationSketchController::class, 'show']);
           Route::post('/', [ValuationSketchController::class, 'store']);
           Route::put('/', [ValuationSketchController::class, 'update']);
           Route::delete('/', [ValuationSketchController::class, 'destroy']);
           Route::post('/upload-image', [ValuationSketchController::class, 'uploadImage']);
           Route::post('/add-point', [ValuationSketchController::class, 'addPoint']);
           Route::delete('/points/{point}', [ValuationSketchController::class, 'removePoint']);
           Route::post('/add-landmark', [ValuationSketchController::class, 'addLandmark']);
           Route::delete('/landmarks/{landmark}', [ValuationSketchController::class, 'removeLandmark']);
           Route::get('/nearby-valuations', [ValuationSketchController::class, 'getNearbyValuations']);
           Route::put('/settings', [ValuationSketchController::class, 'updateSettings']);
       });

       // PDF Export Routes
       Route::prefix('pdf')->group(function () {
           Route::post('/export/{valuation}', [PdfExportController::class, 'exportValuation']);
           Route::get('/preview/{valuation}', [PdfExportController::class, 'previewPdf']);
           Route::post('/export-multiple', [PdfExportController::class, 'exportMultiple']);
           Route::get('/templates', [PdfExportController::class, 'getAvailableTemplates']);
           Route::get('/templates/{type}', [PdfExportController::class, 'getTemplatesByType']);
       });

       // Role & Permission Management Routes
       Route::middleware(['permission:manage_users,manage_roles'])->group(function () {

           // Roles
           Route::prefix('roles')->group(function () {
               Route::get('/', [RolePermissionController::class, 'getRoles']);
               Route::post('/', [RolePermissionController::class, 'createRole']);
               Route::put('/{role}', [RolePermissionController::class, 'updateRole']);
               Route::delete('/{role}', [RolePermissionController::class, 'deleteRole']);
               Route::get('/{role}/users', [RolePermissionController::class, 'getUsersByRole']);
               Route::get('/stats', [RolePermissionController::class, 'getRoleStats']);
           });

           // Permissions
           Route::get('/permissions', [RolePermissionController::class, 'getPermissions']);

           // User Role Assignment
           Route::prefix('users/{user}')->group(function () {
               Route::get('/roles', [RolePermissionController::class, 'getUserRoles']);
               Route::post('/roles', [RolePermissionController::class, 'assignUserRoles']);
           });

           // Bulk Operations
           Route::post('/users/bulk-assign-roles', [RolePermissionController::class, 'bulkAssignRoles']);

           // System Initialization
           Route::post('/system/initialize', [RolePermissionController::class, 'initializeSystem']);
       });

       // Current User Permissions (available to all authenticated users)
       Route::get('/my-permissions', [RolePermissionController::class, 'getCurrentUserPermissions']);
       Route::post('/check-permission', [RolePermissionController::class, 'checkPermission']);

       // To Whom Types and Report Templates
       Route::get('/to-whom-types', function () {
           return response()->json([
               'success' => true,
               'data' => \App\Models\ToWhomType::with('templates')->get()
           ]);
       });

       Route::get('/report-templates', function () {
           return response()->json([
               'success' => true,
               'data' => \App\Models\ReportTemplate::all()
           ]);
       });

       Route::get('/report-templates/{type}', function ($type) {
           return response()->json([
               'success' => true,
               'data' => \App\Models\ReportTemplate::where('to_whom_type', $type)->get()
           ]);
       });
   });

   // Public routes (no authentication required)
   Route::get('/system/health', function () {
       return response()->json([
           'success' => true,
           'message' => 'System is healthy',
           'timestamp' => now(),
           'features' => [
               'valuation_transfers' => true,
               'valuation_sketches' => true,
               'pdf_export' => true,
               'role_management' => true,
               'permission_system' => true
           ]
       ]);
   });

   // Development/Testing routes (remove in production)
   if (app()->environment(['local', 'testing'])) {
       Route::get('/test/permissions', function () {
           $user = Auth::user();
           if (!$user) {
               return response()->json(['error' => 'Not authenticated'], 401);
           }

           return response()->json([
               'user_id' => $user->id,
               'roles' => $user->roles()->get(),
               'permissions' => $user->roles()->with('permissions')->get()->pluck('permissions')->flatten()->unique('id'),
               'has_create_valuation' => $user->hasPermission('create_valuation'),
               'has_manage_users' => $user->hasPermission('manage_users'),
               'highest_role_level' => $user->getHighestRoleLevel()
           ]);
       });

       Route::get('/test/seed-data', function () {
           // This route can be used to seed test data
           return response()->json([
               'message' => 'Test data seeding endpoint',
               'note' => 'Implement seeding logic here for development'
           ]);
       });
   }



});

