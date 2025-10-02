<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use HelperTrait;

    /**
     * عرض جميع الإعدادات
     */
    public function index()
    {
        $settings = Setting::all();
        return $this->returnDataArray($settings, 'قائمة الإعدادات');
    }

    /**
     * حفظ إعداد جديد - غير مفعلة حالياً
     */
    public function store(Request $request)
    {
        return $this->returnError('غير مفعلة حالياً', 501, 501);
    }

    /**
     * عرض إعداد حسب المعرف - غير مفعلة حالياً
     */
    public function show(string $id)
    {
        return $this->returnError('غير مفعلة حالياً', 501, 501);
    }

    /**
     * تعديل إعداد - غير مفعلة حالياً
     */
    public function update(Request $request, string $id)
    {
        return $this->returnError('غير مفعلة حالياً', 501, 501);
    }

    /**
     * حذف إعداد - غير مفعلة حالياً
     */
    public function destroy(string $id)
    {
        return $this->returnError('غير مفعلة حالياً', 501, 501);
    }
}
