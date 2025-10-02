use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

public function store(StoreValuationRequest $request)
{
    $validated = $request->validated();

    // توليد رقم التقييم
    $date = now()->format('Ymd');
    $randomDigits = substr(str_shuffle(str_repeat('0123456789', 16)), 0, 16);
    $validated['valuation_number'] = 'VAL-' . $date . '-' . $randomDigits;

    // إنشاء التقييم
    $valuation = Valuation::create($validated);

    // تحميل العلاقات المطلوبة
    $valuation->load(['client', 'inspector', 'preparer']);

    // توليد PDF من View
    $pdf = Pdf::loadView('pdfs.valuation', compact('valuation'));

    // حفظ PDF مؤقتًا
    $pdfPath = storage_path("app/public/reports/{$valuation->valuation_number}.pdf");
    $pdf->save($pdfPath);


    // إرجاع الـ base64
    $base64 = base64_encode(file_get_contents($pdfPath));

    // إرسال بريد إلكتروني (إذا أردت)
    Mail::raw('تم إنشاء تقرير تقييم جديد.', function ($message) use ($valuation, $pdfPath) {
        $message->to('client@example.com')
                ->subject("تقرير التقييم {$valuation->valuation_number}")
                ->attach($pdfPath);
    });

    return $this->returnDataArray([
        'valuation' => $valuation,
        'pdf_base64' => $base64,
    ], 'تم إنشاء التقييم وإرسال التقرير بنجاح');
}
