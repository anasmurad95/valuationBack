<?php

namespace App\Services;

use App\Models\Valuation;
use App\Models\ReportTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class PdfGeneratorService
{
    /**
     * Generate PDF report for valuation.
     */
    public function generateValuationReport(Valuation $valuation, $templateId = null)
    {
        // Load valuation with all relationships
        $valuation->load([
            'client',
            'user',
            'toWhomType',
            'sketch',
            'transfers.fromUser',
            'transfers.toUser'
        ]);

        // Get template
        $template = $this->getTemplate($valuation, $templateId);
        
        // Prepare data for PDF
        $data = $this->prepareValuationData($valuation, $template);
        
        // Generate PDF based on template type
        return $this->generatePdf($data, $template);
    }

    /**
     * Get appropriate template for valuation.
     */
    private function getTemplate(Valuation $valuation, $templateId = null)
    {
        if ($templateId) {
            return ReportTemplate::find($templateId);
        }

        // Auto-select template based on to_whom_type
        if ($valuation->toWhomType) {
            $template = $valuation->toWhomType->reportTemplate;
            if ($template) {
                return $template;
            }
        }

        // Fallback to general template
        return ReportTemplate::where('template_type', 'general')
                             ->where('is_active', true)
                             ->first();
    }

    /**
     * Prepare data for PDF generation.
     */
    private function prepareValuationData(Valuation $valuation, ReportTemplate $template = null)
    {
        $data = [
            'valuation' => $valuation,
            'template' => $template,
            'report_date' => now()->format('Y-m-d'),
            'report_time' => now()->format('H:i:s'),
            
            // Basic information
            'basic_info' => [
                'valuator_name' => $valuation->valuator_name,
                'valuator_license' => $valuation->valuator_license_number,
                'license_date' => $valuation->valuator_license_date?->format('Y-m-d'),
                'client_name' => $valuation->client?->name,
                'client_phone' => $valuation->client?->phone,
                'valuation_date' => $valuation->valuation_date?->format('Y-m-d'),
                'inspection_date' => $valuation->inspection_date?->format('Y-m-d'),
                'reference_number' => $valuation->reference_number,
                'valuation_purpose' => $valuation->valuation_purpose,
            ],
            
            // To whom information
            'to_whom_info' => [
                'type' => $valuation->toWhomType?->display_name,
                'entity' => $valuation->specific_entity,
                'branch_details' => $valuation->branch_details,
                'urgency_level' => $this->getUrgencyLevelLabel($valuation->urgency_level),
            ],
            
            // Property details
            'property_info' => [
                'type' => $this->getPropertyTypeLabel($valuation->property_type),
                'usage' => $this->getPropertyUsageLabel($valuation->current_usage),
                'condition' => $this->getPropertyConditionLabel($valuation->property_condition),
                'location' => $this->formatLocation($valuation),
                'coordinates' => [
                    'latitude' => $valuation->latitude,
                    'longitude' => $valuation->longitude
                ],
                'areas' => [
                    'land_area' => $valuation->land_area,
                    'building_area' => $valuation->building_area,
                    'basement_area' => $valuation->basement_area,
                    'attachments_area' => $valuation->attachments_area,
                    'total_building_area' => $valuation->building_area + $valuation->basement_area + $valuation->attachments_area
                ],
                'boundaries' => $valuation->boundaries,
                'components' => $this->formatComponents($valuation),
                'utilities' => $this->formatUtilities($valuation),
                'surroundings' => $valuation->surroundings,
            ],
            
            // Valuation methods and results
            'valuation_methods' => [
                'market_approach' => [
                    'used' => $valuation->market_approach_used,
                    'data' => $valuation->market_approach_data,
                    'value' => $valuation->market_value
                ],
                'income_approach' => [
                    'used' => $valuation->income_approach_used,
                    'data' => $valuation->income_approach_data
                ],
                'cost_approach' => [
                    'used' => $valuation->cost_approach_used,
                    'data' => $valuation->cost_approach_data
                ],
                'weighting' => $valuation->weighting_data
            ],
            
            // Final results
            'results' => [
                'market_value' => $valuation->market_value,
                'land_value' => $valuation->land_value,
                'building_value' => $valuation->building_value,
                'final_value' => $valuation->final_value,
                'value_in_words' => $valuation->value_in_words,
            ],
            
            // Additional information
            'additional_info' => [
                'inspection_limitations' => $valuation->inspection_limitations,
                'special_assumptions' => $valuation->special_assumptions,
                'report_restrictions' => $valuation->report_restrictions,
                'work_scope' => $valuation->work_scope,
            ],
            
            // Sketch information
            'sketch' => $valuation->sketch ? [
                'image_url' => $valuation->sketch->sketch_image_url,
                'valuation_points' => $valuation->sketch->valuation_points,
                'comparable_points' => $valuation->sketch->comparable_points,
                'landmarks' => $valuation->sketch->landmarks,
            ] : null,
            
            // Formatting helpers
            'helpers' => [
                'currency_format' => function($value) {
                    return number_format($value, 0, '.', ',') . ' ريال سعودي';
                },
                'date_format' => function($date) {
                    return $date ? $date->format('d/m/Y') : '';
                },
                'arabic_date' => function($date) {
                    return $date ? $this->formatArabicDate($date) : '';
                }
            ]
        ];

        return $data;
    }

    /**
     * Generate PDF from data and template.
     */
    private function generatePdf($data, ReportTemplate $template = null)
    {
        // Determine view based on template type
        $viewName = $this->getViewName($template);
        
        // Set PDF options
        $options = [
            'format' => 'A4',
            'orientation' => 'portrait',
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ];

        // Apply template styling if available
        if ($template && $template->styling) {
            $options = array_merge($options, $template->styling);
        }

        // Generate PDF
        $pdf = Pdf::loadView($viewName, $data)
                  ->setPaper('A4', $options['orientation'] ?? 'portrait')
                  ->setOptions([
                      'defaultFont' => 'DejaVu Sans',
                      'isHtml5ParserEnabled' => true,
                      'isPhpEnabled' => true,
                      'isRemoteEnabled' => true,
                  ]);

        return $pdf;
    }

    /**
     * Get view name based on template.
     */
    private function getViewName(ReportTemplate $template = null)
    {
        if (!$template) {
            return 'pdf.valuation.general';
        }

        $viewMap = [
            'bank' => 'pdf.valuation.bank',
            'government' => 'pdf.valuation.government',
            'private_company' => 'pdf.valuation.private_company',
            'court' => 'pdf.valuation.court',
            'individual' => 'pdf.valuation.individual',
            'general' => 'pdf.valuation.general'
        ];

        return $viewMap[$template->template_type] ?? 'pdf.valuation.general';
    }

    /**
     * Save PDF to storage.
     */
    public function savePdf($pdf, $filename)
    {
        $path = 'reports/' . date('Y/m/') . $filename;
        Storage::disk('public')->put($path, $pdf->output());
        return $path;
    }

    /**
     * Format location string.
     */
    private function formatLocation(Valuation $valuation)
    {
        $parts = array_filter([
            $valuation->city,
            $valuation->district,
            $valuation->street_name,
            $valuation->location_name
        ]);

        return implode(' - ', $parts);
    }

    /**
     * Format property components.
     */
    private function formatComponents(Valuation $valuation)
    {
        return [
            'bedrooms' => $valuation->bedrooms_count,
            'living_rooms' => $valuation->living_rooms_count,
            'dining_rooms' => $valuation->dining_rooms_count,
            'bathrooms' => $valuation->bathrooms_count,
            'kitchens' => $valuation->kitchens_count,
            'maid_rooms' => $valuation->maid_rooms_count,
            'driver_rooms' => $valuation->driver_rooms_count,
            'parking_spaces' => $valuation->parking_spaces,
            'has_garden' => $valuation->has_garden,
            'has_pool' => $valuation->has_pool,
            'has_elevator' => $valuation->has_elevator,
            'has_basement' => $valuation->has_basement,
        ];
    }

    /**
     * Format utilities.
     */
    private function formatUtilities(Valuation $valuation)
    {
        return [
            'electricity' => $valuation->has_electricity,
            'water' => $valuation->has_water,
            'sewage' => $valuation->has_sewage,
            'phone' => $valuation->has_phone,
        ];
    }

    /**
     * Get property type label.
     */
    private function getPropertyTypeLabel($type)
    {
        $labels = [
            'land' => 'أرض فاضية',
            'villa' => 'فيلا',
            'apartment' => 'شقة',
            'floor' => 'دور في فيلا',
            'residential_building' => 'مبنى سكني',
            'commercial_building' => 'مبنى تجاري',
            'office_building' => 'مبنى إداري',
            'shop' => 'محل تجاري',
            'warehouse' => 'مستودع',
            'factory' => 'مصنع'
        ];

        return $labels[$type] ?? $type;
    }

    /**
     * Get property usage label.
     */
    private function getPropertyUsageLabel($usage)
    {
        $labels = [
            'residential' => 'سكني',
            'commercial' => 'تجاري',
            'industrial' => 'صناعي',
            'agricultural' => 'زراعي',
            'mixed' => 'مختلط',
            'vacant' => 'فاضي'
        ];

        return $labels[$usage] ?? $usage;
    }

    /**
     * Get property condition label.
     */
    private function getPropertyConditionLabel($condition)
    {
        $labels = [
            'new' => 'جديد',
            'excellent' => 'ممتاز',
            'good' => 'جيد',
            'average' => 'متوسط',
            'needs_maintenance' => 'يحتاج صيانة',
            'under_construction' => 'تحت الإنشاء'
        ];

        return $labels[$condition] ?? $condition;
    }

    /**
     * Get urgency level label.
     */
    private function getUrgencyLevelLabel($level)
    {
        $labels = [
            'urgent' => 'عاجل',
            'high' => 'عالي',
            'medium' => 'متوسط',
            'low' => 'منخفض'
        ];

        return $labels[$level] ?? $level;
    }

    /**
     * Format Arabic date.
     */
    private function formatArabicDate($date)
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];

        $day = $date->format('d');
        $month = $months[(int)$date->format('m')];
        $year = $date->format('Y');

        return "{$day} {$month} {$year}";
    }
}

