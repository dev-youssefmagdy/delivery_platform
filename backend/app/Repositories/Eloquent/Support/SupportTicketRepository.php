<?php

namespace App\Repositories\Eloquent\Support;

use App\DTOs\Support\TicketFilterData;
use App\DTOs\Support\TicketVisibilityScope;
use App\Enums\Identity\ClientApp;
use App\Enums\Support\TicketGroup;
use App\Enums\Support\TicketStatus;
use App\Models\SupportTicket;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseRepository<SupportTicket>
 */
final class SupportTicketRepository extends BaseRepository implements SupportTicketRepositoryInterface
{
    public function __construct(SupportTicket $model)
    {
        parent::__construct($model);
    }

    public function createNumbered(array $attributes): SupportTicket
    {
        /** @var SupportTicket $ticket */
        $ticket = $this->query()->create($attributes);
        $ticket->forceFill(['number' => 'TK-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();

        return $ticket;
    }

    public function findOfUser(string $uuid, int $userId, ClientApp $app): ?SupportTicket
    {
        return $this->query()->where('uuid', $uuid)->where('user_id', $userId)->where('app', $app->value)->first();
    }

    public function userQuery(int $userId, ClientApp $app, ?string $status): Builder
    {
        return $this->query()
            ->with('booking:id,uuid,code')
            ->where('user_id', $userId)
            ->where('app', $app->value)
            ->when($status === 'active', static fn (Builder $query) => $query->whereIn('status', array_map(static fn (TicketStatus $case): string => $case->value, TicketStatus::activeCases())))
            ->when($status === 'finished', static fn (Builder $query) => $query->whereIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value]))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');
    }

    public function countActiveOfUser(int $userId): int
    {
        return $this->query()->where('user_id', $userId)->whereIn('status', array_map(static fn (TicketStatus $case): string => $case->value, TicketStatus::activeCases()))->count();
    }

    public function countCreatedSince(int $userId, CarbonInterface $since): int
    {
        return $this->query()->where('user_id', $userId)->where('created_at', '>=', $since)->count();
    }

    public function findForBookingAndSource(int $bookingId, int $userId, string $source): ?SupportTicket
    {
        return $this->query()->where('booking_id', $bookingId)->where('user_id', $userId)->where('source', $source)->first();
    }

    public function datatableQuery(TicketFilterData $filters, TicketVisibilityScope $scope): Builder
    {
        $query = $this->query()
            ->select('support_tickets.*')
            ->with(['user:id,uuid,name', 'assignedAdmin:id,name', 'booking:id,uuid,code']);

        $this->restrict($query, $scope);

        return $query
            ->when($filters->userId !== null, static fn (Builder $q) => $q->where('support_tickets.user_id', $filters->userId))
            ->when($filters->number !== null, static fn (Builder $q) => $q->where('support_tickets.number', 'like', '%'.$filters->number.'%'))
            ->when($filters->statuses !== [], static fn (Builder $q) => $q->whereIn('support_tickets.status', array_map(static fn ($case): string => $case->value, $filters->statuses)))
            ->when($filters->priorities !== [], static fn (Builder $q) => $q->whereIn('support_tickets.priority', array_map(static fn ($case): string => $case->value, $filters->priorities)))
            ->when($filters->categories !== [], static fn (Builder $q) => $q->whereIn('support_tickets.category', array_map(static fn ($case): string => $case->value, $filters->categories)))
            ->when($filters->groups !== [], static fn (Builder $q) => $q->whereIn('support_tickets.group', array_map(static fn ($case): string => $case->value, $filters->groups)))
            ->when($filters->assignedAdminIds !== [], static fn (Builder $q) => $q->whereIn('support_tickets.assigned_admin_id', $filters->assignedAdminIds))
            ->when($filters->scope === TicketFilterData::SCOPE_MINE, static fn (Builder $q) => $q->where('support_tickets.assigned_admin_id', $scope->adminId))
            ->when($filters->scope === TicketFilterData::SCOPE_UNASSIGNED, static fn (Builder $q) => $q->whereNull('support_tickets.assigned_admin_id')->whereIn('support_tickets.status', array_map(static fn (TicketStatus $case): string => $case->value, TicketStatus::activeCases())))
            ->when($filters->slaBreached === true, static fn (Builder $q) => $q->whereNotNull('support_tickets.sla_breached_at'))
            ->when($filters->slaBreached === false, static fn (Builder $q) => $q->whereNull('support_tickets.sla_breached_at'))
            ->when($filters->bookingCode !== null, static fn (Builder $q) => $q->whereIn('support_tickets.booking_id', DB::table('bookings')->select('id')->where('code', 'like', '%'.$filters->bookingCode.'%')))
            ->when($filters->bookingId !== null, static fn (Builder $q) => $q->where('support_tickets.booking_id', $filters->bookingId))
            ->when($filters->from !== null, static fn (Builder $q) => $q->where('support_tickets.created_at', '>=', $filters->from?->startOfDay()))
            ->when($filters->to !== null, static fn (Builder $q) => $q->where('support_tickets.created_at', '<=', $filters->to?->endOfDay()));
    }

    public function forBooking(int $bookingId): Collection
    {
        return $this->query()->where('booking_id', $bookingId)->orderByDesc('id')->get()->toBase();
    }

    public function allOfUser(int $userId): Collection
    {
        return $this->query()->with(['messages' => static fn ($query) => $query->where('visibility', 'public')->orderBy('id'), 'booking:id,uuid,code'])->where('user_id', $userId)->orderBy('id')->get()->toBase();
    }

    public function activeCountsByAdmin(array $adminIds): array
    {
        if ($adminIds === []) {
            return [];
        }

        return $this->query()
            ->selectRaw('assigned_admin_id, COUNT(*) as total')
            ->whereIn('assigned_admin_id', $adminIds)
            ->whereIn('status', array_map(static fn (TicketStatus $case): string => $case->value, TicketStatus::activeCases()))
            ->groupBy('assigned_admin_id')
            ->pluck('total', 'assigned_admin_id')
            ->map(static fn (mixed $count): int => (int) $count)
            ->all();
    }

    public function slaOverdue(CarbonInterface $now, int $limit): Collection
    {
        return $this->query()
            ->whereIn('status', array_map(static fn (TicketStatus $case): string => $case->value, TicketStatus::activeCases()))
            ->whereNull('first_response_at')
            ->whereNull('sla_breached_at')
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', $now)
            ->orderBy('sla_due_at')
            ->limit($limit)
            ->get()
            ->toBase();
    }

    public function resolvedBefore(CarbonInterface $cutoff, int $limit): Collection
    {
        return $this->query()->where('status', TicketStatus::Resolved->value)->where('resolved_at', '<', $cutoff)->orderBy('resolved_at')->limit($limit)->get()->toBase();
    }

    public function scrubOfUser(int $userId): array
    {
        $ids = $this->query()->where('user_id', $userId)->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        $paths = DB::table('ticket_attachments')->whereIn('ticket_id', $ids)->pluck('path')->map(static fn (mixed $path): string => (string) $path)->all();
        DB::table('ticket_attachments')->whereIn('ticket_id', $ids)->delete();
        DB::table('ticket_messages')->whereIn('ticket_id', $ids)->where('author_user_id', $userId)->update(['body' => '[removed]', 'updated_at' => now()]);
        DB::table('support_tickets')->whereIn('id', $ids)->update(['subject' => '[removed]', 'context' => null, 'updated_at' => now()]);

        return array_values($paths);
    }

    public function resolveOwnedPaymentId(int $userId, string $paymentUuid): ?int
    {
        $id = DB::table('payments')->where('uuid', $paymentUuid)->where('user_id', $userId)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function resolveOwnedPayoutId(int $providerProfileId, string $payoutUuid): ?int
    {
        $id = DB::table('payout_requests')->where('uuid', $payoutUuid)->where('provider_profile_id', $providerProfileId)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function countReportsSince(int $bookingId, int $userId, CarbonInterface $since): int
    {
        return $this->query()->where('booking_id', $bookingId)->where('user_id', $userId)->where('source', 'report')->where('created_at', '>=', $since)->count();
    }

    public function providerProfileIdOfUser(int $userId): ?int
    {
        $id = DB::table('provider_profiles')->where('user_id', $userId)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function bookingHasActiveTicket(int $bookingId): bool
    {
        return $this->query()->where('booking_id', $bookingId)->whereIn('status', array_map(static fn (TicketStatus $case): string => $case->value, TicketStatus::activeCases()))->exists();
    }

    /**
     * @param Builder<SupportTicket> $query
     */
    private function restrict(Builder $query, TicketVisibilityScope $scope): void
    {
        if ($scope->viewAll) {
            return;
        }

        if ($scope->seesNothing()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $teamGroups = array_map(static fn (TicketGroup $group): string => $group->value, $scope->groups);

        $query->where(function (Builder $inner) use ($scope, $teamGroups): void {
            if ($scope->canViewAssignedAndQueue) {
                $inner->where('support_tickets.assigned_admin_id', $scope->adminId)
                    ->orWhere(static fn (Builder $queue) => $queue->whereNull('support_tickets.assigned_admin_id')->where('support_tickets.group', TicketGroup::Support->value));
            } elseif ($teamGroups !== []) {
                $inner->where('support_tickets.assigned_admin_id', $scope->adminId)->whereIn('support_tickets.group', $teamGroups);
            }

            if ($teamGroups !== []) {
                $inner->orWhereIn('support_tickets.group', $teamGroups);
            }
        });
    }
}
