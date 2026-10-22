<?php

namespace App\Services\Support;

use App\DTOs\Support\SupportMacroData;
use App\Enums\Common\AuditAction;
use App\Enums\Support\TicketCategory;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\SupportMacro;
use App\Repositories\Contracts\Support\SupportMacroRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Macros: canned replies in Arabic and English that agents insert into an answer (SRS §16.11). Every change is audited; agents
 * only read the active ones for the ticket's category.
 */
final class SupportMacroService
{
    public function __construct(
        private readonly SupportMacroRepositoryInterface $macros,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(SupportMacroData $data, Admin $actor): SupportMacro
    {
        return $this->db->transaction(function () use ($data, $actor): SupportMacro {
            /** @var SupportMacro $macro */
            $macro = $this->macros->create($this->attributes($data) + ['created_by_admin_id' => $actor->id]);
            $this->audit->record(AuditAction::Created, $macro, $actor, after: $macro->auditSnapshot());

            return $macro;
        });
    }

    /**
     * @throws ConflictException `stale_version`
     */
    public function update(SupportMacro $macro, SupportMacroData $data, Admin $actor): SupportMacro
    {
        return $this->db->transaction(function () use ($macro, $data, $actor): SupportMacro {
            $locked = $this->lock($macro);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = $locked->auditSnapshot();
            $this->macros->update($locked, $this->attributes($data) + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    public function setActive(SupportMacro $macro, bool $active, Admin $actor, ?string $reason = null): SupportMacro
    {
        return $this->db->transaction(function () use ($macro, $active, $actor, $reason): SupportMacro {
            $locked = $this->lock($macro);
            $this->macros->update($locked, ['active' => $active, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record($active ? AuditAction::Reactivated : AuditAction::Suspended, $locked, $actor, ['active' => ! $active], ['active' => $active], $reason);

            return $locked;
        });
    }

    /**
     * Macros an agent can insert into a ticket of this category.
     *
     * @return Collection<int, SupportMacro>
     */
    public function forTicket(TicketCategory $category): Collection
    {
        return $this->macros->activeFor($category);
    }

    public function findActive(string $uuid): ?SupportMacro
    {
        return $this->macros->findActiveByUuid($uuid);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(SupportMacroData $data): array
    {
        return [
            'title_ar' => $data->titleAr,
            'title_en' => $data->titleEn,
            'body_ar' => $data->bodyAr,
            'body_en' => $data->bodyEn,
            'category' => $data->category,
            'active' => $data->active,
        ];
    }

    private function lock(SupportMacro $macro): SupportMacro
    {
        $locked = $this->macros->lockById($macro->id);

        return $locked instanceof SupportMacro ? $locked : throw (new ModelNotFoundException)->setModel(SupportMacro::class);
    }
}
