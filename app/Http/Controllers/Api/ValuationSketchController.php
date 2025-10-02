<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Valuation;
use App\Models\ValuationSketch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ValuationSketchController extends Controller
{
    /**
     * Get sketch for a valuation.
     */
    public function show(Valuation $valuation)
    {
        $sketch = $valuation->sketch()->with(['creator', 'updater'])->first();
        
        if (!$sketch) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على كروكي لهذا التقييم'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sketch
        ]);
    }

    /**
     * Create or update sketch for a valuation.
     */
    public function store(Request $request, Valuation $valuation)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sketch_type' => 'required|in:image,digital_map,both',
            'sketch_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'center_latitude' => 'nullable|numeric|between:-90,90',
            'center_longitude' => 'nullable|numeric|between:-180,180',
            'zoom_level' => 'nullable|integer|between:1,20',
            'map_data' => 'nullable|array',
            'valuation_points' => 'nullable|array',
            'comparable_points' => 'nullable|array',
            'landmarks' => 'nullable|array',
            'display_settings' => 'nullable|array',
            'show_prices' => 'boolean',
            'show_valuator_names' => 'boolean',
            'show_property_types' => 'boolean',
            'show_dates' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['valuation_id'] = $valuation->id;
        $data['updated_by'] = auth()->id();

        // Handle image upload
        if ($request->hasFile('sketch_image')) {
            $imagePath = $request->file('sketch_image')->store('sketches', 'public');
            $data['sketch_image_path'] = $imagePath;
        }

        // Set default coordinates from valuation if not provided
        if (!$data['center_latitude'] && $valuation->latitude) {
            $data['center_latitude'] = $valuation->latitude;
        }
        if (!$data['center_longitude'] && $valuation->longitude) {
            $data['center_longitude'] = $valuation->longitude;
        }

        // Create or update sketch
        $sketch = $valuation->sketch()->updateOrCreate(
            ['valuation_id' => $valuation->id],
            array_merge($data, ['created_by' => auth()->id()])
        );

        // Auto-populate nearby valuations if requested
        if ($request->boolean('auto_populate_nearby')) {
            $this->populateNearbyValuations($sketch, $request->input('nearby_radius', 1000));
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الكروكي بنجاح',
            'data' => $sketch->load(['creator', 'updater'])
        ]);
    }

    /**
     * Upload sketch image.
     */
    public function uploadImage(Request $request, Valuation $valuation)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ملف الصورة غير صحيح',
                'errors' => $validator->errors()
            ], 422);
        }

        $imagePath = $request->file('image')->store('sketches', 'public');

        $sketch = $valuation->sketch()->updateOrCreate(
            ['valuation_id' => $valuation->id],
            [
                'sketch_image_path' => $imagePath,
                'sketch_type' => 'image',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفع صورة الكروكي بنجاح',
            'data' => [
                'image_url' => $sketch->sketch_image_url,
                'image_path' => $imagePath
            ]
        ]);
    }

    /**
     * Add valuation point to sketch.
     */
    public function addValuationPoint(Request $request, Valuation $valuation)
    {
        $validator = Validator::make($request->all(), [
            'target_valuation_id' => 'required|exists:valuations,id',
            'custom_data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $sketch = $valuation->sketch;
        if (!$sketch) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على كروكي لهذا التقييم'
            ], 404);
        }

        $targetValuation = Valuation::with('user')->find($request->target_valuation_id);
        $sketch->addValuationPoint($targetValuation, $request->input('custom_data', []));

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة نقطة التقييم بنجاح',
            'data' => $sketch->fresh()
        ]);
    }

    /**
     * Add comparable point to sketch.
     */
    public function addComparablePoint(Request $request, Valuation $valuation)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_date' => 'nullable|date',
            'property_type' => 'nullable|string',
            'area' => 'nullable|numeric|min:0',
            'source' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $sketch = $valuation->sketch;
        if (!$sketch) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على كروكي لهذا التقييم'
            ], 404);
        }

        $sketch->addComparablePoint($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة نقطة المقارنة بنجاح',
            'data' => $sketch->fresh()
        ]);
    }

    /**
     * Add landmark to sketch.
     */
    public function addLandmark(Request $request, Valuation $valuation)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'name' => 'required|string|max:255',
            'type' => 'required|in:mosque,school,hospital,mall,park,highway,bank,gas_station,restaurant,other',
            'icon' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $sketch = $valuation->sketch;
        if (!$sketch) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على كروكي لهذا التقييم'
            ], 404);
        }

        $sketch->addLandmark($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المعلم بنجاح',
            'data' => $sketch->fresh()
        ]);
    }

    /**
     * Get nearby valuations for a location.
     */
    public function getNearbyValuations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:100|max:10000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->input('radius', 1000);

        $nearbyValuations = Valuation::with(['user', 'client'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("
                *,
                (6371000 * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )) AS distance
            ", [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $nearbyValuations
        ]);
    }

    /**
     * Update sketch display settings.
     */
    public function updateDisplaySettings(Request $request, Valuation $valuation)
    {
        $validator = Validator::make($request->all(), [
            'show_prices' => 'boolean',
            'show_valuator_names' => 'boolean',
            'show_property_types' => 'boolean',
            'show_dates' => 'boolean',
            'display_settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $sketch = $valuation->sketch;
        if (!$sketch) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على كروكي لهذا التقييم'
            ], 404);
        }

        $sketch->update(array_merge(
            $validator->validated(),
            ['updated_by' => auth()->id()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعدادات العرض بنجاح',
            'data' => $sketch->fresh()
        ]);
    }

    /**
     * Delete sketch.
     */
    public function destroy(Valuation $valuation)
    {
        $sketch = $valuation->sketch;
        if (!$sketch) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على كروكي لهذا التقييم'
            ], 404);
        }

        // Delete image file if exists
        if ($sketch->sketch_image_path) {
            Storage::disk('public')->delete($sketch->sketch_image_path);
        }

        $sketch->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الكروكي بنجاح'
        ]);
    }

    /**
     * Auto-populate nearby valuations.
     */
    private function populateNearbyValuations(ValuationSketch $sketch, $radius = 1000)
    {
        $nearbyValuations = $sketch->getNearbyValuations($radius);
        
        foreach ($nearbyValuations as $valuation) {
            $sketch->addValuationPoint($valuation);
        }

        $sketch->updateBounds();
    }
}

