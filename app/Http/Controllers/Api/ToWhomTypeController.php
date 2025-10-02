<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ToWhomType;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ToWhomTypeController extends Controller
{
    use HelperTrait;

    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            return $this->unAuthorized();
        }

       $items = ToWhomType::with(['propertyTypes', 'evaluationReportTemplate'])->get();
        Log::debug($items);
        return $this->returnDataArray($items);
    }

    public function store(Request $request)
    {
      if (Auth::user()->role !== 'admin') {
            return $this->unAuthorized();
        }

        try {
            $validated = $request->validate([
                'nameAr' => 'required|string|max:255',
                'nameEn' => 'required|string|max:255',
                'REF'    => 'nullable|string|max:255'
            ]);

            // أولاً ننشئ العنصر بدون REF كاملة
            $item = ToWhomType::create([
                'nameAr' => $validated['nameAr'],
                'nameEn' => $validated['nameEn'],
                'REF'    => null, // مؤقتاً فارغة
            ]);

            // إذا تم إدخال REF، نركّب الصيغة الجديدة
            if (!empty($validated['REF'])) {
                $item->REF = $validated['REF'] . '-' . $item->id . '-' . now()->year;
                $item->save();
            }

            return $this->returnDataArray($item, __('messages.created_successfully'));
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return $this->returnInvalidate($ex);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return $this->unAuthorized();
        }

        try {
            $item = ToWhomType::findOrFail($id);

            $validated = $request->validate([
                'nameAr' => 'required|string|max:255',
                'nameEn' => 'required|string|max:255',
                'REF'    => 'nullable|string|max:255',
            ]);

            // تحديث الحقول العادية فقط
            $item->update([
                'nameAr' => $validated['nameAr'],
                'nameEn' => $validated['nameEn'],
            ]);

            // فقط إذا تم إرسال REF، قم بتحديثه بصيغة جديدة
            if ($item -> REF != $validated['REF']) {
                $item->REF = $validated['REF'] . '-' . $item->id . '-' . now()->year;
                $item->save();
            }

            return $this->returnDataArray($item, __('messages.updated_successfully'));
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return $this->returnInvalidate($ex);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getMessage());
        }
    }

    public function toggleActive($id)
    {
        if (Auth::user()->role !== 'admin') {
            return $this->unAuthorized();
        }

        try {
            $item = ToWhomType::findOrFail($id);
            $item->is_active = !$item->is_active;
            $item->save();

            return $this->returnDataArray($item, __('messages.status_updated'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getMessage());
        }
    }
}
