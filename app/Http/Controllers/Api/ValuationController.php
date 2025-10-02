<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Valuations\StoreValuationRequest;
use App\Http\Requests\Valuations\UpdateValuationRequest;
use App\Models\Valuation;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Str;

class ValuationController extends Controller
{
    use HelperTrait;

    /**
     * قائمة كل التقييمات (مع إمكانية التوسعة للفلترة)
     */
    public function index(Request $request)
    {
        $valuations = Valuation::with([
            'client',
            'toWhomType',
            'propertyType',
            'preparer',
            'inspector'
        ])->where('status', '==', 'draft')->where('is_active', true)->get();

        return $this->returnDataArray($valuations, 'تم جلب التقييمات بنجاح');
    }

    public function getDraftValuation(Request $request)
    {
        $valuations = Valuation::with([
            'client',
            'toWhomType',
            'propertyType',
            'preparer',
            'inspector'
        ])->where('status', 'draft')->where('is_active', true)->get();

        return $this->returnDataArray($valuations, 'تم جلب التقييمات بنجاح');
    }
    /**
     * إنشاء تقييم جديد
     */
    public function store(StoreValuationRequest $request)
    {
        $validated = $request->validated();

        // توليد رقم التقييم الفريد
        $date = now()->format('Ymd');
        $randomDigits = substr(str_shuffle(str_repeat('0123456789', 16)), 0, 16);
        $validated['valuation_number'] = 'VAL-' . $date . '-' . $randomDigits;

        // إنشاء التقييم
        $valuation = Valuation::create($validated);

        // اختيار القالب المناسب
        $view = match ($valuation->to_whom_type_id) {
            1 => 'pdfs.short_report',
            2 => 'pdfs.bank_report',
            default => 'pdfs.default_report',
        };

        // توليد PDF
        $pdf = Pdf::loadView($view, compact('valuation'));
        $pdfPath = storage_path("app/public/reports/valuations/{$valuation->valuation_number}.pdf");
        $pdf->save($pdfPath);

        // تحويل إلى base64
        $pdfContent = file_get_contents($pdfPath);
        $valuation->pdf_base64 = base64_encode($pdfContent);

        // إرسال الإيميل
        Mail::send([], [], function ($message) use ($valuation, $pdfPath) {
            $message->to('anas.murad2524@gmaail.com') // لاحقًا: $valuation->client->email
                ->subject('تقرير التقييم الخاص بك')
                ->attach($pdfPath)
                ->html(new HtmlString('يرجى مرفق تقرير التقييم بصيغة PDF.'));
        });

        return $this->returnDataArray($valuation, 'تم إنشاء التقييم بنجاح');
    }


    /**
     * عرض تفاصيل تقييم معين
     */
    public function show(Valuation $valuation)
    {
        return $this->returnDataArray($valuation->load(['client', 'preparedBy', 'inspectedBy']), 'تفاصيل التقييم');
    }

    /**
     * تعديل تقييم
     */
    public function update(UpdateValuationRequest $request, Valuation $valuation)
    {
        $valuation->update($request->validated());
        return $this->returnDataArray($valuation, 'تم تحديث التقييم');
    }

    /**
     * حذف تقييم
     */
    public function destroy(Valuation $valuation)
    {
        // $valuation->delete();
        // return $this->returnSuccess('تم حذف التقييم');
        $valuation->update(['is_active' => false]);
        return $this->returnSuccess('تم إلغاء تفعيل التقييم بنجاح');
    }
}
