<?php

namespace App\Repositories\Eloquent\Safety;

use App\DTOs\Safety\IncidentFilterData;
use App\Enums\Safety\IncidentStatus;
use App\Enums\Safety\IncidentTrigger;
use App\Models\SafetyIncident;
use App\Models\SafetyIncidentAttachment;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<SafetyIncident>
 */
final class SafetyIncidentRepository extends BaseRepository implements SafetyIncidentRepositoryInterface
{
    public function __construct(SafetyIncident $model)
    {
        parent::__construct($model);
    }

    public function createNumbered(array $attributes): SafetyIncident
    {
        /** @var SafetyIncident $incident */
        $incident = $this->query()->create($attributes);
        $incident->forceFill(['number' => 'INC-'.str_pad((string) $incident->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();

        return $incident;
    }

    public function findOfReporter(string $uuid, int $userId): ?SafetyIncident
    {
        return $this->query()->where('uuid', $uuid)->where('reporter_user_id', $userId)->first();
    }

    public function userQuery(int $userId): Builder
    {
        return $this->query()->with('booking:id,uuid,code')->where('reporter_user_id', $userId)->orderByDesc('id');
    }

    public function activeSosFor(int $bookingId, int $userId): ?SafetyIncident
    {
        return $this->query()
            ->where('booking_id', $bookingId)
            ->where('reporter_user_id', $userId)
            ->where('trigger', IncidentTrigger::Sos->value)
            ->whereIn('status', IncidentStatus::activeValues())
            ->orderByDesc('id')
            ->first();
    }

    public function countReportsSince(int $userId, CarbonInterface $since): int
    {
        return $this->query()->where('reporter_user_id', $userId)->where('trigger', IncidentTrigger::Report->value)->where('created_at', '>=', $since)->count();
    }

    public function datatableQuery(IncidentFilterData $filters): Builder
    {
        $active = IncidentStatus::activeValues();

        return $this->query()
            ->select('safety_incidents.*')
            ->with(['reporter:id,uuid,name', 'assignedAdmin:id,name', 'booking:id,uuid,code'])
            ->when($filters->number !== null, static fn (Builder $q) => $q->where('safety_incidents.number', 'like', '%'.$filters->number.'%'))
            ->when($filters->severities !== [], static fn (Builder $q) => $q->whereIn('safety_incidents.severity', array_map(static fn ($case): string => $case->value, $filters->severities)))
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('safety_incidents.status', array_map(static fn ($case): string => $case->value, $filters->statuses)))
            ->when($filters->categories !== [], static fn (Builder $q) => $q->whereIn('safety_incidents.category', array_map(static fn ($case): string => $case->value, $filters->categories)))
            ->when($filters->priorities !== [], static fn (Builder $q) => $q->whereIn('safety_incidents.priority', array_map(static fn ($case): string => $case->value, $filters->priorities)))
            ->when($filters->assignedAdminIds !== [], static fn (Builder $q) => $q->whereIn('safety_incidents.assigned_admin_id', $filters->assignedAdminIds))
            ->when($filters->trigger !== null, static fn (Builder $q) => $q->where('safety_incidents.trigger', $filters->trigger?->value))
            ->when($filters->scope === IncidentFilterData::SCOPE_URGENT, static fn (Builder $q) => $q->whereIn('safety_incidents.status', $active)->where('safety_incidents.priority', 'urgent'))
            ->when($filters->scope === IncidentFilterData::SCOPE_UNASSIGNED, static fn (Builder $q) => $q->whereIn('safety_incidents.status', $active)->whereNull('safety_incidents.assigned_admin_id'))
            ->when($filters->scope === IncidentFilterData::SCOPE_MINE, static fn (Builder $q) => $q->where('safety_incidents.assigned_admin_id', (int) $filters->adminId))
            ->when($filters->slaBreached === true, static fn (Builder $q) => $q->whereNotNull('safety_incidents.sla_breached_at'))
            ->when($filters->slaBreached === false, static fn (Builder $q) => $q->whereNull('safety_incidents.sla_breached_at'))
            ->when($filters->legalHold !== null, static fn (Builder $q) => $q->where('safety_incidents.legal_hold', $filters->legalHold))
            ->when($filters->bookingCode !== null, static fn (Builder $q) => $q->whereIn('safety_incidents.booking_id', DB::table('bookings')->select('id')->where('code', 'like', '%'.$filters->bookingCode.'%')))
            ->when($filters->bookingId !== null, static fn (Builder $q) => $q->where('safety_incidents.booking_id', $filters->bookingId))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('safety_incidents.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('safety_incidents.created_at', '<=', $filters->to?->endOfDay()));
    }

    public function forBooking(int $bookingId): Collection
    {
        return $this->query()->where('booking_id', $bookingId)->orderByDesc('id')->get()->toBase();
    }

    public function allOfUser(int $userId): Collection
    {
        return $this->query()->with('booking:id,uuid,code')->where('reporter_user_id', $userId)->orderBy('id')->get()->toBase();
    }

    public function slaOverdue(CarbonInterface $now, int $limit): Collection
    {
        return $this->query()
            ->where('status', IncidentStatus::New->value)
            ->whereNull('sla_breached_at')
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', $now)
            ->orderBy('sla_due_at')
            ->limit($limit)
            ->get()
            ->toBase();
    }

    public function expired(CarbonInterface $resolvedBefore, int $limit): Collection
    {
        return $this->query()
            ->where('status', IncidentStatus::Resolved->value)
            ->where('legal_hold', false)
            ->where('resolved_at', '<', $resolvedBefore)
            ->whereNotNull('narrative')
            ->orderBy('resolved_at')
            ->limit($limit)
            ->get()
            ->toBase();
    }

    public function countActive(): int
    {
        return $this->query()->whereIn('status', IncidentStatus::activeValues())->count();
    }

    public function countUrgentNew(): int
    {
        return $this->query()->where('status', IncidentStatus::New->value)->where('priority', 'urgent')->count();
    }

    public function scrubOfUser(int $userId): array
    {
        $held = $this->query()->where('reporter_user_id', $userId)->where('legal_hold', true)->count();
        $files = [];
        $scrubbed = 0;

        $this->query()->where('reporter_user_id', $userId)->where('legal_hold', false)->each(static function (SafetyIncident $incident) use (&$files, &$scrubbed): void {
            foreach ($incident->attachments()->whereNotNull('path')->get() as $attachment) {
                /** @var SafetyIncidentAttachment $attachment */
                $files[] = (string) $attachment->path;
                $attachment->forceFill(['path' => null, 'purged_at' => now()])->save();
            }

            // The person's own words go (events are append-only for staff actions, so bodies are removed through the query builder).
            DB::table('safety_incident_events')->where('incident_id', $incident->id)->whereIn('actor_type', ['customer', 'provider'])->update(['body' => null]);
            $incident->forceFill(['narrative' => null, 'callback_phone_encrypted' => null, 'location_snapshot' => null, 'context' => null])->saveQuietly();
            $scrubbed++;
        });

        return ['scrubbed' => $scrubbed, 'held' => $held, 'files' => $files];
    }
}
