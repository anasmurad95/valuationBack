<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    use HelperTrait;

    /**
     * تسجيل الدخول
     */
    public function login(LoginRequest $request)
    {
        
        Log::debug('message');
        
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->returnError('بيانات الدخول غير صحيحة', 401, 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->returnDataArray([
            'user'  => $user,
            'token' => $token,
        ], 'تم تسجيل الدخول بنجاح');
    }

    /**
     * عرض بيانات المستخدم الحالي
     */
    public function profile(Request $request)
    {
        return $this->returnDataArray($request->user(), 'بيانات المستخدم');
    }

    /**
     * تحديث الملف الشخصي للمستخدم
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->returnDataArray($user, 'تم تحديث البيانات بنجاح');
    }

    /**
     * تغيير كلمة المرور
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->returnError('كلمة المرور الحالية غير صحيحة', 400, 400);
        }

        $user->update(['password' => bcrypt($request->new_password)]);

        return $this->returnSuccess('تم تغيير كلمة المرور بنجاح');
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->returnSuccess('تم تسجيل الخروج بنجاح');
    }
}
