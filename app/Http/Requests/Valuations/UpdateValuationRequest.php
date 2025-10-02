<?php

namespace App\Http\Requests\Valuations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateValuationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         return [
            'valuation_number'      => 'required|string|unique:valuations,valuation_number',
            'client_id'             => 'required|exists:clients,id',
            'prepared_by'           => 'required|exists:users,id',
            'inspected_by'          => 'nullable|exists:users,id',
            'to_whom_type_id'       => 'nullable|exists:to_whom_types,id',
            'property_type_id'      => 'required|exists:property_types,id',
            'property_usage'        => 'nullable|in:residential,commercial,residential_commercial,industrial,agricultural,touristic,leased',
            'property_description'  => 'nullable|string',
            'location_name'         => 'nullable|string',
            'coordinates'           => 'nullable|string',
            'krooki_path'           => 'nullable|string',
            'address_details'       => 'nullable|string',
            'latitude'              => 'nullable|numeric',
            'longitude'             => 'nullable|numeric',
            'site_visit_date'       => 'nullable|date',
            'report_submitted_at'   => 'nullable|date',
            'report_approved_at'    => 'nullable|date',
            'status'                => 'nullable|in:draft,pending,in_progress,submitted,approved,rejected',
            'request_source'        => 'nullable|in:phone_call,whatsapp,social_media,old_client,friend,office_visit,bank',
            'source_details'        => 'nullable|string',
        ];
    }
}
