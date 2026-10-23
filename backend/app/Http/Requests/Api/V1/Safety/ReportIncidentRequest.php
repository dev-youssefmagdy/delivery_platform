<?php

namespace App\Http\Requests\Api\V1\Safety;

use App\DTOs\Safety\LocationFix;
use App\DTOs\Safety\ReportIncidentData;
use App\Enums\Safety\IncidentCategory;
use App\Enums\Safety\IncidentTrigger;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /safety/incidents. An SOS needs only the booking and (when available) the GPS fix: everything else has a safe default so
 * a person in danger is never stopped by a form. A report needs a category and a narrative.
 */
class ReportIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sos = $this->input('trigger') === IncidentTrigger::Sos->value;
        $max = (int) config('safety.reports.narrative_max_chars', 4000);

        return [
            'booking_id' => ['required', 'uuid'],
            'trigger' => ['required', 'string', Rule::enum(IncidentTrigger::class)],
            'category' => [$sos ? 'nullable' : 'required', 'string', Rule::enum(IncidentCategory::class)],
            'narrative' => [$sos ? 'nullable' : 'required', 'string', $sos ? 'min:0' : 'min:5', 'max:'.$max],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'captured_at' => ['nullable', 'date'],
            'callback_phone' => ['nullable', 'string', 'max:24'],
            'notify_trusted_contacts' => ['nullable', 'boolean'],
        ];
    }

    public function toDto(): ReportIncidentData
    {
        $trigger = IncidentTrigger::from((string) $this->input('trigger'));
        $location = $this->filled('lat') && $this->filled('lng')
            ? new LocationFix((float) $this->input('lat'), (float) $this->input('lng'), $this->filled('accuracy_m') ? (float) $this->input('accuracy_m') : null, $this->filled('captured_at') ? CarbonImmutable::parse((string) $this->input('captured_at')) : null)
            : null;

        return new ReportIncidentData(
            (string) $this->input('booking_id'),
            $trigger,
            $this->filled('category') ? IncidentCategory::from((string) $this->input('category')) : IncidentCategory::Emergency,
            $this->filled('narrative') ? (string) $this->input('narrative') : null,
            $location,
            $this->filled('callback_phone') ? (string) $this->input('callback_phone') : null,
            $this->boolean('notify_trusted_contacts', true),
        );
    }
}
