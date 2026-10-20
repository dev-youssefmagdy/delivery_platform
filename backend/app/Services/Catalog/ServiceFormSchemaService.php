<?php

namespace App\Services\Catalog;

use App\DTOs\Catalog\FormFieldDefinition;
use App\DTOs\Catalog\ServiceFormSchemaData;
use App\Enums\Common\AuditAction;
use App\Exceptions\ConflictException;
use App\Models\ServiceCategory;
use App\Models\ServiceFormSchema;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFormSchemaRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\Forms\FormSchemaValidator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Edits a category's draft form schema. The schema is validated by FormSchemaValidator regardless of the
 * caller; published schemas are never touched (publishing copies the draft).
 */
final class ServiceFormSchemaService
{
    public function __construct(
        private readonly ServiceFormSchemaRepositoryInterface $schemas,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly FormSchemaValidator $validator,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function draftFor(ServiceCategory $category): ServiceFormSchema
    {
        return $this->schemas->draftFor($category) ?? $this->schemas->createDraft($category, []);
    }

    public function saveDraft(ServiceCategory $category, ServiceFormSchemaData $data, ?Authenticatable $actor): ServiceFormSchema
    {
        $definitions = $this->validator->assertValid($data->fields);
        $fields = array_map(static fn (FormFieldDefinition $field): array => $field->toArray(), $definitions);

        return $this->db->transaction(function () use ($category, $data, $fields, $actor): ServiceFormSchema {
            $this->categories->lockById($category->getKey()) ?? throw (new ModelNotFoundException)->setModel(ServiceCategory::class);

            $draft = $this->schemas->lockDraftFor($category) ?? $this->schemas->createDraft($category, []);

            if ($data->lockVersion !== null && $data->lockVersion !== $draft->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = ['fields' => $draft->fields];
            $this->schemas->update($draft, ['fields' => $fields, 'lock_version' => $draft->lock_version + 1]);

            if ($before['fields'] !== $draft->fields) {
                $this->audit->record(AuditAction::Updated, $category, $actor, $before, ['fields' => $draft->fields], meta: ['section' => 'form_schema']);
            }

            return $draft;
        });
    }
}
