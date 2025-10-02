<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Valuation;
use App\Models\ReportTemplate;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PdfExportController extends Controller
{
    protected $pdfGenerator;

    public function __construct(PdfGeneratorService $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
    }

    /**
     * Export valuation as PDF.
     */
    public function exportValuation(Request $request, Valuation $valuation)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'nullable|exists:report_templates,id',
            'download' => 'boolean',
            'save_to_storage' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate PDF
            $pdf = $this->pdfGenerator->generateValuationReport(
                $valuation, 
                $request->input('template_id')
            );

            $filename = $this->generateFilename($valuation);

            // Save to storage if requested
            if ($request->boolean('save_to_storage', true)) {
                $path = $this->pdfGenerator->savePdf($pdf, $filename);
                
                // Update valuation with PDF path
                $valuation->update(['pdf_report_path' => $path]);
            }

            // Return PDF for download
            if ($request->boolean('download', true)) {
                return $pdf->download($filename);
            }

            // Return PDF inline
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء التقرير',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview PDF before export.
     */
    public function previewValuation(Request $request, Valuation $valuation)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'nullable|exists:report_templates,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate PDF for preview
            $pdf = $this->pdfGenerator->generateValuationReport(
                $valuation, 
                $request->input('template_id')
            );

            $filename = 'preview_' . $this->generateFilename($valuation);

            // Return PDF inline for preview
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة التقرير',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available templates for valuation.
     */
    public function getAvailableTemplates(Valuation $valuation)
    {
        $templates = ReportTemplate::active()->get();

        // Add recommended template based on to_whom_type
        $recommendedTemplate = null;
        if ($valuation->toWhomType) {
            $recommendedTemplate = $valuation->toWhomType->reportTemplate;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'templates' => $templates,
                'recommended_template' => $recommendedTemplate
            ]
        ]);
    }

    /**
     * Bulk export multiple valuations.
     */
    public function bulkExport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'valuation_ids' => 'required|array|min:1',
            'valuation_ids.*' => 'exists:valuations,id',
            'template_id' => 'nullable|exists:report_templates,id',
            'format' => 'in:individual,combined'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $valuationIds = $request->input('valuation_ids');
            $templateId = $request->input('template_id');
            $format = $request->input('format', 'individual');

            if ($format === 'combined') {
                return $this->exportCombinedPdf($valuationIds, $templateId);
            } else {
                return $this->exportIndividualPdfs($valuationIds, $templateId);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التصدير المجمع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export template preview.
     */
    public function exportTemplatePreview(ReportTemplate $template)
    {
        try {
            // Create sample data for template preview
            $sampleData = $this->createSampleData($template);
            
            // Generate PDF with sample data
            $pdf = $this->pdfGenerator->generatePdf($sampleData, $template);
            
            $filename = 'template_preview_' . $template->slug . '.pdf';
            
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة القالب',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate filename for PDF.
     */
    private function generateFilename(Valuation $valuation)
    {
        $prefix = 'تقرير_تقييم';
        $reference = $valuation->reference_number ?: $valuation->id;
        $date = now()->format('Y-m-d');
        
        return "{$prefix}_{$reference}_{$date}.pdf";
    }

    /**
     * Export combined PDF for multiple valuations.
     */
    private function exportCombinedPdf($valuationIds, $templateId = null)
    {
        $valuations = Valuation::whereIn('id', $valuationIds)
                              ->with(['client', 'user', 'toWhomType'])
                              ->get();

        // Generate combined PDF
        $combinedData = [
            'valuations' => $valuations,
            'export_date' => now(),
            'total_count' => $valuations->count()
        ];

        $pdf = Pdf::loadView('pdf.valuation.combined', $combinedData)
                  ->setPaper('A4', 'portrait');

        $filename = 'تقارير_تقييم_مجمعة_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export individual PDFs as ZIP.
     */
    private function exportIndividualPdfs($valuationIds, $templateId = null)
    {
        $valuations = Valuation::whereIn('id', $valuationIds)->get();
        
        $zip = new \ZipArchive();
        $zipFilename = 'تقارير_تقييم_' . now()->format('Y-m-d') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFilename);

        // Create temp directory if not exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            foreach ($valuations as $valuation) {
                $pdf = $this->pdfGenerator->generateValuationReport($valuation, $templateId);
                $filename = $this->generateFilename($valuation);
                
                $zip->addFromString($filename, $pdf->output());
            }
            $zip->close();

            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend();
        }

        throw new \Exception('فشل في إنشاء ملف ZIP');
    }

    /**
     * Create sample data for template preview.
     */
    private function createSampleData(ReportTemplate $template)
    {
        return [
            'basic_info' => [
                'valuator_name' => 'أحمد محمد السالم',
                'valuator_license' => '12345',
                'license_date' => '2020-01-01',
                'client_name' => 'شركة الأمل العقارية',
                'client_phone' => '+966501234567',
                'valuation_date' => now()->format('Y-m-d'),
                'inspection_date' => now()->format('Y-m-d'),
                'reference_number' => 'VAL-2025-001',
                'valuation_purpose' => 'تقييم لأغراض البيع',
            ],
            'to_whom_info' => [
                'type' => $template->type_label,
                'entity' => 'البنك الأهلي السعودي',
                'branch_details' => 'فرع الرياض الرئيسي',
                'urgency_level' => 'متوسط',
            ],
            'property_info' => [
                'type' => 'فيلا',
                'usage' => 'سكني',
                'condition' => 'جيد',
                'location' => 'الرياض - حي النرجس - شارع الأمير سلطان',
                'coordinates' => [
                    'latitude' => 24.7136,
                    'longitude' => 46.6753
                ],
                'areas' => [
                    'land_area' => 600,
                    'building_area' => 400,
                    'basement_area' => 0,
                    'attachments_area' => 50,
                    'total_building_area' => 450
                ]
            ],
            'results' => [
                'market_value' => 1200000,
                'land_value' => 800000,
                'building_value' => 400000,
                'final_value' => 1200000,
                'value_in_words' => 'مليون ومائتا ألف ريال سعودي',
            ],
            'template' => $template,
            'report_date' => now()->format('Y-m-d'),
            'report_time' => now()->format('H:i:s'),
        ];
    }
}

