<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use App\Models\ToWhomType;
use Illuminate\Http\Request;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\Auth;

class ValuationTypeController extends Controller
{
    use HelperTrait;

    /**
     * Display all property types.
     */
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            return $this->unAuthorized();
        }

        $data = PropertyType::with('toWhomTypes')->get();
        return $this->returnDataArray($data);
    }

    /**
     * Store a new property type.
     */
    public function store(Request $request)
    {
       if (Auth::user()->role !== 'admin') {
            return $this->unAuthorized();
        }
        try {
            $validated = $request->validate([
                'nameAr' => 'required|string',
                'nameEn' => 'required|string',
                'to_whom_type_ids' => 'required|array|min:1',
                'to_whom_type_ids.*' => 'exists:to_whom_types,id',
            ]);

            // ✅ جلب REF من أول to_whom_type_id
            $firstToWhomType = ToWhomType::findOrFail($validated['to_whom_type_ids'][0]);
            $ref = $firstToWhomType->REF;

            // إنشاء PropertyType مع REF المستخرج
            $propertyType = PropertyType::create([
                'nameAr' => $validated['nameAr'],
                'nameEn' => $validated['nameEn'],
                'REF'    => $ref,
            ]);

            // ربط العلاقات
            $propertyType->toWhomTypes()->sync($validated['to_whom_type_ids']);

            return $this->returnDataArray($propertyType->load('toWhomTypes'), 'Created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->returnInvalidate($e);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    /**
     * Update a property type.
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            return $this->unAuthorized();
        }
        try {
            $propertyType = PropertyType::findOrFail($id);

            $validated = $request->validate([
                'nameAr' => 'sometimes|required|string',
                'nameEn' => 'sometimes|required|string',
                'REF'    => 'sometimes|required|string|unique:property_types,REF,' . $propertyType->id,
                'to_whom_type_ids' => 'nullable|array',
                'to_whom_type_ids.*' => 'exists:to_whom_types,id',
            ]);

            // Update basic fields
            $propertyType->update($validated);

            // Sync relationship if provided
            if ($request->has('to_whom_type_ids')) {
                $propertyType->toWhomTypes()->sync($validated['to_whom_type_ids']);
            }

            return $this->returnDataArray($propertyType->load('toWhomTypes'), 'Updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->returnInvalidate($e);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    /**
     * Toggle is_active field.
     */
    public function toggleActive($id)
    {
       if (Auth::user()->role !== 'admin') {
            return $this->unAuthorized();
        }

        try {
            $propertyType = PropertyType::findOrFail($id);
            $propertyType->is_active = !$propertyType->is_active;
            $propertyType->save();

            return $this->returnDataArray($propertyType, 'Status updated.');
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
}
