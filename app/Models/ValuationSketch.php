<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ValuationSketch extends Model
{
    use HasFactory;

    protected $fillable = [
        'valuation_id',
        'title',
        'description',
        'sketch_type',
        'sketch_image_path',
        'map_data',
        'center_latitude',
        'center_longitude',
        'zoom_level',
        'bounds',
        'valuation_points',
        'comparable_points',
        'landmarks',
        'display_settings',
        'show_prices',
        'show_valuator_names',
        'show_property_types',
        'show_dates',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'map_data' => 'array',
        'bounds' => 'array',
        'valuation_points' => 'array',
        'comparable_points' => 'array',
        'landmarks' => 'array',
        'display_settings' => 'array',
        'show_prices' => 'boolean',
        'show_valuator_names' => 'boolean',
        'show_property_types' => 'boolean',
        'show_dates' => 'boolean',
        'center_latitude' => 'decimal:8',
        'center_longitude' => 'decimal:8'
    ];

    /**
     * Get the valuation that owns this sketch.
     */
    public function valuation()
    {
        return $this->belongsTo(Valuation::class);
    }

    /**
     * Get the user who created this sketch.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this sketch.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the sketch image URL.
     */
    public function getSketchImageUrlAttribute()
    {
        if (!$this->sketch_image_path) {
            return null;
        }

        return Storage::url($this->sketch_image_path);
    }

    /**
     * Get nearby valuations for the sketch.
     */
    public function getNearbyValuations($radius = 1000)
    {
        if (!$this->center_latitude || !$this->center_longitude) {
            return collect();
        }

        return Valuation::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('id', '!=', $this->valuation_id)
            ->selectRaw("
                *,
                (6371000 * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )) AS distance
            ", [
                $this->center_latitude,
                $this->center_longitude,
                $this->center_latitude
            ])
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get();
    }

    /**
     * Add a valuation point to the sketch.
     */
    public function addValuationPoint($valuation, $customData = [])
    {
        $points = $this->valuation_points ?? [];
        
        $point = [
            'id' => $valuation->id,
            'latitude' => $valuation->latitude,
            'longitude' => $valuation->longitude,
            'final_value' => $valuation->final_value,
            'property_type' => $valuation->property_type,
            'valuator_name' => $valuation->user->name ?? 'غير محدد',
            'valuation_date' => $valuation->valuation_date?->format('Y-m-d'),
            'status' => $valuation->status,
            'reference_number' => $valuation->reference_number,
            'address' => $valuation->address_details ?? $valuation->location_name,
            'custom_data' => $customData
        ];

        $points[] = $point;
        $this->update(['valuation_points' => $points]);

        return $this;
    }

    /**
     * Add a comparable property point to the sketch.
     */
    public function addComparablePoint($data)
    {
        $points = $this->comparable_points ?? [];
        
        $point = [
            'id' => uniqid(),
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'sale_price' => $data['sale_price'] ?? null,
            'sale_date' => $data['sale_date'] ?? null,
            'property_type' => $data['property_type'] ?? null,
            'area' => $data['area'] ?? null,
            'source' => $data['source'] ?? 'manual',
            'notes' => $data['notes'] ?? null
        ];

        $points[] = $point;
        $this->update(['comparable_points' => $points]);

        return $this;
    }

    /**
     * Add a landmark to the sketch.
     */
    public function addLandmark($data)
    {
        $landmarks = $this->landmarks ?? [];
        
        $landmark = [
            'id' => uniqid(),
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'name' => $data['name'],
            'type' => $data['type'], // mosque, school, hospital, mall, etc.
            'icon' => $data['icon'] ?? $this->getLandmarkIcon($data['type']),
            'description' => $data['description'] ?? null
        ];

        $landmarks[] = $landmark;
        $this->update(['landmarks' => $landmarks]);

        return $this;
    }

    /**
     * Get icon for landmark type.
     */
    private function getLandmarkIcon($type)
    {
        $icons = [
            'mosque' => 'mdi-mosque',
            'school' => 'mdi-school',
            'hospital' => 'mdi-hospital-box',
            'mall' => 'mdi-shopping',
            'park' => 'mdi-tree',
            'highway' => 'mdi-highway',
            'bank' => 'mdi-bank',
            'gas_station' => 'mdi-gas-station',
            'restaurant' => 'mdi-silverware-fork-knife',
            'other' => 'mdi-map-marker'
        ];

        return $icons[$type] ?? 'mdi-map-marker';
    }

    /**
     * Update sketch bounds based on points.
     */
    public function updateBounds()
    {
        $allPoints = collect();
        
        // Add valuation points
        if ($this->valuation_points) {
            $allPoints = $allPoints->merge($this->valuation_points);
        }
        
        // Add comparable points
        if ($this->comparable_points) {
            $allPoints = $allPoints->merge($this->comparable_points);
        }
        
        // Add landmarks
        if ($this->landmarks) {
            $allPoints = $allPoints->merge($this->landmarks);
        }

        if ($allPoints->isEmpty()) {
            return $this;
        }

        $latitudes = $allPoints->pluck('latitude')->filter();
        $longitudes = $allPoints->pluck('longitude')->filter();

        if ($latitudes->isEmpty() || $longitudes->isEmpty()) {
            return $this;
        }

        $bounds = [
            'north' => $latitudes->max(),
            'south' => $latitudes->min(),
            'east' => $longitudes->max(),
            'west' => $longitudes->min()
        ];

        // Add some padding
        $latPadding = ($bounds['north'] - $bounds['south']) * 0.1;
        $lngPadding = ($bounds['east'] - $bounds['west']) * 0.1;

        $bounds['north'] += $latPadding;
        $bounds['south'] -= $latPadding;
        $bounds['east'] += $lngPadding;
        $bounds['west'] -= $lngPadding;

        $this->update(['bounds' => $bounds]);

        return $this;
    }

    /**
     * Get default display settings.
     */
    public static function getDefaultDisplaySettings()
    {
        return [
            'map_style' => 'satellite', // satellite, roadmap, hybrid, terrain
            'show_grid' => false,
            'show_scale' => true,
            'show_compass' => true,
            'enable_clustering' => true,
            'cluster_max_zoom' => 15,
            'point_colors' => [
                'current_valuation' => '#ff0000',
                'other_valuations' => '#0066cc',
                'comparable_sales' => '#00cc66',
                'landmarks' => '#cc6600'
            ],
            'point_sizes' => [
                'small' => 8,
                'medium' => 12,
                'large' => 16
            ]
        ];
    }

    /**
     * Scope to filter by sketch type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('sketch_type', $type);
    }

    /**
     * Scope to get sketches within bounds.
     */
    public function scopeWithinBounds($query, $north, $south, $east, $west)
    {
        return $query->whereBetween('center_latitude', [$south, $north])
                    ->whereBetween('center_longitude', [$west, $east]);
    }
}

