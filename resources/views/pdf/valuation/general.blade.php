<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير التقييم العقاري</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans Arabic', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            direction: rtl;
            text-align: right;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #1976d2;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            font-size: 14px;
            color: #888;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background-color: #f5f5f5;
            padding: 10px 15px;
            font-size: 16px;
            font-weight: 600;
            color: #1976d2;
            border-right: 4px solid #1976d2;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 30%;
            padding: 8px 12px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            font-weight: 600;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            width: 70%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .two-column {
            display: table;
            width: 100%;
        }
        
        .column {
            display: table-cell;
            width: 50%;
            padding: 0 10px;
            vertical-align: top;
        }
        
        .column:first-child {
            padding-right: 0;
        }
        
        .column:last-child {
            padding-left: 0;
        }
        
        .value-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .value-table th,
        .value-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        
        .value-table th {
            background-color: #1976d2;
            color: white;
            font-weight: 600;
        }
        
        .value-table .final-value {
            background-color: #e8f5e8;
            font-weight: 700;
            font-size: 14px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
        }
        
        .signature-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
            border: 1px solid #ddd;
            margin: 0 10px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            width: 200px;
            margin: 20px auto 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-left {
            text-align: left;
        }
        
        .font-bold {
            font-weight: 600;
        }
        
        .text-primary {
            color: #1976d2;
        }
        
        .text-success {
            color: #4caf50;
        }
        
        .bg-light {
            background-color: #f8f9fa;
        }
        
        .currency {
            font-weight: 600;
            color: #1976d2;
        }
        
        .sketch-section {
            text-align: center;
            margin: 20px 0;
        }
        
        .sketch-image {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            margin: 10px 0;
        }
        
        @media print {
            body {
                font-size: 11px;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>تقرير التقييم العقاري</h1>
        <h2>{{ $basic_info['valuator_name'] ?? 'غير محدد' }}</h2>
        <div class="subtitle">
            مقيم معتمد - رخصة رقم: {{ $basic_info['valuator_license'] ?? 'غير محدد' }}
        </div>
        <div class="subtitle">
            تاريخ الترخيص: {{ $basic_info['license_date'] ?? 'غير محدد' }}
        </div>
    </div>

    <!-- Basic Information -->
    <div class="section">
        <div class="section-title">المعلومات الأساسية</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">رقم التقرير</div>
                <div class="info-value">{{ $basic_info['reference_number'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">تاريخ التقييم</div>
                <div class="info-value">{{ $basic_info['valuation_date'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">تاريخ المعاينة</div>
                <div class="info-value">{{ $basic_info['inspection_date'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">الغرض من التقييم</div>
                <div class="info-value">{{ $basic_info['valuation_purpose'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">اسم العميل</div>
                <div class="info-value">{{ $basic_info['client_name'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">هاتف العميل</div>
                <div class="info-value">{{ $basic_info['client_phone'] ?? 'غير محدد' }}</div>
            </div>
        </div>
    </div>

    <!-- To Whom Information -->
    @if($to_whom_info['type'])
    <div class="section">
        <div class="section-title">معلومات الجهة المستفيدة</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">نوع الجهة</div>
                <div class="info-value">{{ $to_whom_info['type'] }}</div>
            </div>
            @if($to_whom_info['entity'])
            <div class="info-row">
                <div class="info-label">اسم الجهة</div>
                <div class="info-value">{{ $to_whom_info['entity'] }}</div>
            </div>
            @endif
            @if($to_whom_info['branch_details'])
            <div class="info-row">
                <div class="info-label">تفاصيل الفرع</div>
                <div class="info-value">{{ $to_whom_info['branch_details'] }}</div>
            </div>
            @endif
            @if($to_whom_info['urgency_level'])
            <div class="info-row">
                <div class="info-label">مستوى الأولوية</div>
                <div class="info-value">{{ $to_whom_info['urgency_level'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Property Information -->
    <div class="section">
        <div class="section-title">معلومات العقار</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">نوع العقار</div>
                <div class="info-value">{{ $property_info['type'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">الاستخدام الحالي</div>
                <div class="info-value">{{ $property_info['usage'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">حالة العقار</div>
                <div class="info-value">{{ $property_info['condition'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">الموقع</div>
                <div class="info-value">{{ $property_info['location'] ?? 'غير محدد' }}</div>
            </div>
            @if($property_info['coordinates']['latitude'] && $property_info['coordinates']['longitude'])
            <div class="info-row">
                <div class="info-label">الإحداثيات</div>
                <div class="info-value">
                    خط العرض: {{ $property_info['coordinates']['latitude'] }} - 
                    خط الطول: {{ $property_info['coordinates']['longitude'] }}
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Areas Information -->
    <div class="section">
        <div class="section-title">المساحات</div>
        <div class="info-grid">
            @if($property_info['areas']['land_area'])
            <div class="info-row">
                <div class="info-label">مساحة الأرض</div>
                <div class="info-value">{{ number_format($property_info['areas']['land_area']) }} متر مربع</div>
            </div>
            @endif
            @if($property_info['areas']['building_area'])
            <div class="info-row">
                <div class="info-label">مساحة البناء</div>
                <div class="info-value">{{ number_format($property_info['areas']['building_area']) }} متر مربع</div>
            </div>
            @endif
            @if($property_info['areas']['basement_area'])
            <div class="info-row">
                <div class="info-label">مساحة البدروم</div>
                <div class="info-value">{{ number_format($property_info['areas']['basement_area']) }} متر مربع</div>
            </div>
            @endif
            @if($property_info['areas']['attachments_area'])
            <div class="info-row">
                <div class="info-label">مساحة الملاحق</div>
                <div class="info-value">{{ number_format($property_info['areas']['attachments_area']) }} متر مربع</div>
            </div>
            @endif
            @if($property_info['areas']['total_building_area'])
            <div class="info-row">
                <div class="info-label">إجمالي مساحة البناء</div>
                <div class="info-value font-bold">{{ number_format($property_info['areas']['total_building_area']) }} متر مربع</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Property Components -->
    @if($property_info['components'])
    <div class="section">
        <div class="section-title">مكونات العقار</div>
        <div class="two-column">
            <div class="column">
                <div class="info-grid">
                    @if($property_info['components']['bedrooms'])
                    <div class="info-row">
                        <div class="info-label">غرف النوم</div>
                        <div class="info-value">{{ $property_info['components']['bedrooms'] }}</div>
                    </div>
                    @endif
                    @if($property_info['components']['living_rooms'])
                    <div class="info-row">
                        <div class="info-label">غرف المعيشة</div>
                        <div class="info-value">{{ $property_info['components']['living_rooms'] }}</div>
                    </div>
                    @endif
                    @if($property_info['components']['bathrooms'])
                    <div class="info-row">
                        <div class="info-label">دورات المياه</div>
                        <div class="info-value">{{ $property_info['components']['bathrooms'] }}</div>
                    </div>
                    @endif
                    @if($property_info['components']['kitchens'])
                    <div class="info-row">
                        <div class="info-label">المطابخ</div>
                        <div class="info-value">{{ $property_info['components']['kitchens'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="column">
                <div class="info-grid">
                    @if($property_info['components']['maid_rooms'])
                    <div class="info-row">
                        <div class="info-label">غرف الخادمة</div>
                        <div class="info-value">{{ $property_info['components']['maid_rooms'] }}</div>
                    </div>
                    @endif
                    @if($property_info['components']['driver_rooms'])
                    <div class="info-row">
                        <div class="info-label">غرف السائق</div>
                        <div class="info-value">{{ $property_info['components']['driver_rooms'] }}</div>
                    </div>
                    @endif
                    @if($property_info['components']['parking_spaces'])
                    <div class="info-row">
                        <div class="info-label">مواقف السيارات</div>
                        <div class="info-value">{{ $property_info['components']['parking_spaces'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Additional Features -->
        <div class="info-grid" style="margin-top: 15px;">
            @if($property_info['components']['has_garden'])
            <div class="info-row">
                <div class="info-label">حديقة</div>
                <div class="info-value">{{ $property_info['components']['has_garden'] ? 'نعم' : 'لا' }}</div>
            </div>
            @endif
            @if($property_info['components']['has_pool'])
            <div class="info-row">
                <div class="info-label">مسبح</div>
                <div class="info-value">{{ $property_info['components']['has_pool'] ? 'نعم' : 'لا' }}</div>
            </div>
            @endif
            @if($property_info['components']['has_elevator'])
            <div class="info-row">
                <div class="info-label">مصعد</div>
                <div class="info-value">{{ $property_info['components']['has_elevator'] ? 'نعم' : 'لا' }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Sketch Section -->
    @if($sketch && $sketch['image_url'])
    <div class="section page-break">
        <div class="section-title">كروكي الموقع</div>
        <div class="sketch-section">
            <img src="{{ $sketch['image_url'] }}" alt="كروكي الموقع" class="sketch-image">
        </div>
    </div>
    @endif

    <!-- Valuation Methods -->
    <div class="section page-break">
        <div class="section-title">طرق التقييم المستخدمة</div>
        
        @if($valuation_methods['market_approach']['used'])
        <div style="margin-bottom: 20px;">
            <h4 class="font-bold text-primary">أسلوب السوق (المقارنة)</h4>
            <p>تم استخدام أسلوب السوق لتقدير قيمة العقار من خلال مقارنته بعقارات مماثلة تم بيعها مؤخراً في المنطقة.</p>
            @if($valuation_methods['market_approach']['value'])
            <p class="font-bold">القيمة المقدرة: <span class="currency">{{ number_format($valuation_methods['market_approach']['value']) }} ريال سعودي</span></p>
            @endif
        </div>
        @endif
        
        @if($valuation_methods['income_approach']['used'])
        <div style="margin-bottom: 20px;">
            <h4 class="font-bold text-primary">أسلوب الدخل</h4>
            <p>تم استخدام أسلوب الدخل لتقدير قيمة العقار بناءً على الدخل المتوقع من تأجيره.</p>
        </div>
        @endif
        
        @if($valuation_methods['cost_approach']['used'])
        <div style="margin-bottom: 20px;">
            <h4 class="font-bold text-primary">أسلوب التكلفة</h4>
            <p>تم استخدام أسلوب التكلفة لتقدير قيمة العقار بناءً على تكلفة إعادة البناء مطروحاً منها الإهلاك.</p>
        </div>
        @endif
    </div>

    <!-- Final Results -->
    <div class="section">
        <div class="section-title">نتائج التقييم النهائية</div>
        
        <table class="value-table">
            <thead>
                <tr>
                    <th>البيان</th>
                    <th>القيمة (ريال سعودي)</th>
                </tr>
            </thead>
            <tbody>
                @if($results['land_value'])
                <tr>
                    <td>قيمة الأرض</td>
                    <td class="currency">{{ number_format($results['land_value']) }}</td>
                </tr>
                @endif
                @if($results['building_value'])
                <tr>
                    <td>قيمة البناء</td>
                    <td class="currency">{{ number_format($results['building_value']) }}</td>
                </tr>
                @endif
                @if($results['market_value'])
                <tr>
                    <td>القيمة السوقية</td>
                    <td class="currency">{{ number_format($results['market_value']) }}</td>
                </tr>
                @endif
                <tr class="final-value">
                    <td class="font-bold">القيمة النهائية للعقار</td>
                    <td class="font-bold currency">{{ number_format($results['final_value']) }}</td>
                </tr>
            </tbody>
        </table>
        
        @if($results['value_in_words'])
        <div style="margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-right: 4px solid #1976d2;">
            <strong>القيمة بالأحرف:</strong> {{ $results['value_in_words'] }}
        </div>
        @endif
    </div>

    <!-- Additional Information -->
    @if($additional_info['inspection_limitations'] || $additional_info['special_assumptions'] || $additional_info['report_restrictions'])
    <div class="section">
        <div class="section-title">معلومات إضافية</div>
        
        @if($additional_info['inspection_limitations'])
        <div style="margin-bottom: 15px;">
            <h4 class="font-bold">قيود المعاينة:</h4>
            <p>{{ $additional_info['inspection_limitations'] }}</p>
        </div>
        @endif
        
        @if($additional_info['special_assumptions'])
        <div style="margin-bottom: 15px;">
            <h4 class="font-bold">افتراضات خاصة:</h4>
            <p>{{ $additional_info['special_assumptions'] }}</p>
        </div>
        @endif
        
        @if($additional_info['report_restrictions'])
        <div style="margin-bottom: 15px;">
            <h4 class="font-bold">قيود التقرير:</h4>
            <p>{{ $additional_info['report_restrictions'] }}</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Footer and Signature -->
    <div class="footer">
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="font-bold">{{ $basic_info['valuator_name'] ?? 'المقيم المعتمد' }}</div>
                <div>رخصة رقم: {{ $basic_info['valuator_license'] ?? 'غير محدد' }}</div>
                <div>التوقيع والختم</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="font-bold">التاريخ</div>
                <div>{{ $report_date ?? now()->format('Y-m-d') }}</div>
            </div>
        </div>
        
        <div style="margin-top: 30px; font-size: 10px; color: #666; text-align: center;">
            تم إنشاء هذا التقرير بواسطة نظام التقييم العقاري المتكامل
            <br>
            تاريخ الإنشاء: {{ $report_date ?? now()->format('Y-m-d') }} - الوقت: {{ $report_time ?? now()->format('H:i:s') }}
        </div>
    </div>
</body>
</html>

