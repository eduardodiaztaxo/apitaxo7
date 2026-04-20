<?php

namespace App\Http\Requests\Api\V2;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppLogRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $eventType = $this->filled('event_type') ? $this->input('event_type') : $this->input('tag');
        $clientAt = $this->filled('client_at') ? $this->input('client_at') : $this->input('timestamp');
        $metadata = $this->input('metadata');
        $severity = $this->filled('severity') ? $this->input('severity') : 'info';

        if ($metadata === '') {
            $metadata = null;
        } elseif (is_string($metadata)) {
            $decodedMetadata = json_decode($metadata, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $metadata = $decodedMetadata;
            }
        }

        if (is_numeric($clientAt)) {
            $clientAt = (int) $clientAt > 9999999999
                ? Carbon::createFromTimestampMs((int) $clientAt)->toDateTimeString()
                : Carbon::createFromTimestamp((int) $clientAt)->toDateTimeString();
        }

        $this->merge([
            'event_type' => $eventType,
            'severity' => $severity,
            'client_at' => $clientAt,
            'metadata' => $metadata,
        ]);
    }

    public function rules()
    {
        return [
            'event_type' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:1000'],
            'severity' => ['required', Rule::in(['debug', 'info', 'warning', 'error', 'critical'])],
            'metadata' => ['nullable', 'array'],
            'client_at' => ['nullable', 'date'],
            'platform' => ['nullable', 'string', 'max:50'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}