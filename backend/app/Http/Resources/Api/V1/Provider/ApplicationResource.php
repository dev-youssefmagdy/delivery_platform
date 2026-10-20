<?php

namespace App\Http\Resources\Api\V1\Provider;

use App\DTOs\Provider\ChecklistItem;
use App\DTOs\Provider\OnboardingProgress;
use App\DTOs\Provider\OnboardingTaskItem;
use App\Enums\Provider\ProviderStatus;
use App\Models\ProviderServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The driver's application, verification progress and pending tasks (docs/API.md 8.9).
 *
 * @property OnboardingProgress $resource
 */
class ApplicationResource extends JsonResource
{
    /**
     * @param list<ProviderServiceCategory>|null $services
     */
    public function __construct(OnboardingProgress $resource, private readonly ?array $services = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $progress = $this->resource;
        $profile = $progress->profile;
        $showReason = in_array($profile->status, [ProviderStatus::Rejected, ProviderStatus::MoreInformationRequired], true);

        return [
            'started' => true,
            'id' => $profile->uuid,
            'status' => $profile->status->value,
            'status_reason' => $showReason ? $profile->status_reason : null,
            'status_changed_at' => $profile->status_changed_at?->toIso8601ZuluString(),
            'provider_type' => $profile->provider_type?->value,
            'city_id' => $profile->city_id,
            'name' => $profile->user->name,
            'date_of_birth' => $profile->date_of_birth?->toDateString(),
            'services' => array_map(static fn (ProviderServiceCategory $service): array => [
                'service_category_id' => $service->service_category_id,
                'status' => $service->status->value,
            ], $this->services ?? []),
            'editable' => $progress->editable,
            'submitted_at' => $profile->submitted_at?->toIso8601ZuluString(),
            'steps' => $progress->steps,
            'resume_step' => $progress->resumeStep,
            'can_submit' => $progress->canSubmit,
            'tasks' => array_map(static fn (OnboardingTaskItem $item): array => [
                'task' => $item->task->value,
                'reference' => $item->reference,
                'vehicle_id' => $item->vehicleUuid,
            ], $progress->tasks),
            'required_documents' => array_map(static fn (ChecklistItem $item): ChecklistItemResource => new ChecklistItemResource($item), $this->allItems()),
            'expiring_documents' => array_map(static fn (ChecklistItem $item): ChecklistItemResource => new ChecklistItemResource($item), $progress->expiring),
            'vehicles' => array_map(static fn (array $entry): VehicleResource => new VehicleResource($entry['vehicle']), $progress->vehicleChecklists),
            'pending_policy_ids' => $progress->pendingPolicyIds,
            'support' => $progress->support,
        ];
    }

    /**
     * The provider's own checklist followed by the checklist of each vehicle.
     *
     * @return list<ChecklistItem>
     */
    private function allItems(): array
    {
        $items = $this->resource->providerChecklist;

        foreach ($this->resource->vehicleChecklists as $entry) {
            $items = [...$items, ...$entry['items']];
        }

        return $items;
    }

    /**
     * What the app shows before the driver started an application.
     *
     * @return array<string, mixed>
     */
    public static function notStarted(): array
    {
        return ['started' => false];
    }
}
