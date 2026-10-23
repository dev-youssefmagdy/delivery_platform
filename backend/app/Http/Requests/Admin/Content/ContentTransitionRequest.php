<?php

namespace App\Http\Requests\Admin\Content;

use App\Enums\Content\ContentType;
use App\Enums\Content\WorkflowAction;
use App\Http\Requests\Admin\Concerns\HasReason;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * One workflow step (submit, reject, publish, schedule, revert, archive, restore) on any content entity.
 * Authorized per action: content.manage submits, content.publish does the rest; reason mandatory except for submit.
 */
class ContentTransitionRequest extends FormRequest
{
    use HasReason;

    public function authorize(): bool
    {
        return Gate::allows('transition', [$this->content(), $this->action()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reasonRules($this->action()->reasonRequired()) + [
            'scheduled_at' => [
                $this->action() === WorkflowAction::Schedule ? 'required' : 'prohibited',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $at = CarbonImmutable::parse((string) $value, (string) config('admin.display_timezone'));

                    if ($at->lt(now()->addSeconds((int) config('content.schedule_min_lead_seconds', 60)))) {
                        $fail((string) __('content.schedule_in_future'));
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return ['reason' => (string) __('admin.common.reason'), 'scheduled_at' => (string) __('content.fields.scheduled_at')];
    }

    public function action(): WorkflowAction
    {
        return WorkflowAction::from((string) $this->route('action'));
    }

    public function type(): ContentType
    {
        return ContentType::from((string) $this->route('contentType'));
    }

    public function content(): Model
    {
        $content = $this->route('content');

        return $content instanceof Model ? $content : $this->type()->modelClass()::query()->findOrFail((int) $this->route('content'));
    }

    public function scheduledAt(): ?CarbonImmutable
    {
        $value = $this->validated('scheduled_at');

        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value, (string) config('admin.display_timezone'))->utc() : null;
    }
}
