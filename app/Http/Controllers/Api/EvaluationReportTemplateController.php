<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EvaluationReportTemplate;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;

class EvaluationReportTemplateController extends Controller
{
    use HelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = EvaluationReportTemplate::with('toWhomType')->get();
        return $this->returnDataArray($templates);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'to_whom_type_id' => 'required|exists:to_whom_types,id',
                'template_json' => 'required|array',
            ]);

            $template = EvaluationReportTemplate::create([
                'to_whom_type_id' => $request->to_whom_type_id,
                'template_json' => $request->template_json,
            ]);

            return $this->returnDataArray($template, 'تم إنشاء القالب بنجاح');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getMessage(), 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $template = EvaluationReportTemplate::findOrFail($id);

            $request->validate([
                'template_json' => 'required|array',
            ]);

            $template->update([
                'template_json' => $request->template_json,
            ]);

            return $this->returnDataArray($template, 'تم التحديث بنجاح');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getMessage(), 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $template = EvaluationReportTemplate::findOrFail($id);
        $template->delete();

        return $this->returnSuccess('تم الحذف بنجاح');
    }

    public function showByToWhomType($toWhomTypeId)
    {
        $template = EvaluationReportTemplate::where('to_whom_type_id', $toWhomTypeId)->first();

        if (!$template) {
            return $this->returnError('القالب غير موجود', 404);
        }

        return $this->returnDataArray($template);
    }
}
