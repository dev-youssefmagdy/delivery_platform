<?php

namespace App\Services\Catalog;

use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryVersion;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceCategoryVersionRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFormSchemaRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Catalog\Forms\FormSchemaValidator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Publishing (Plan P1.4 step 3): freezes the draft category configuration, resolved policies, vehicle links and
 * form schema into a new immutable ServiceCategoryVersion. Bookings reference these version ids (P2.3).
 */
final class ServiceCategoryPublisher
{
    public function __construct(
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly ServiceFormSchemaRepositoryInterface $schemas,
        private readonly ServiceCategoryVersionRepositoryInterface $versions,
        private readonly ServiceCategorySnapshotBuilder $snapshots,
        private readonly FormSchemaValidator $validator,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Whether the draft differs from what is published (or nothing is published yet).
     */
    public function hasUnpublishedChanges(ServiceCategory $category): bool
    {
        $published = $category->publishedVersion;

        if ($published === null) {
            return true;
        }

        $draft = $this->schemas->draftFor($category);
        $snapshot = $this->snapshots->build($category, $draft->fields ?? []);

        return $this->snapshots->fingerprint($snapshot) !== $this->snapshots->fingerprint($published->snapshot);
    }

    public function publish(ServiceCategory $category, string $reason, ?Admin $actor): ServiceCategoryVersion
    {
        return $this->db->transaction(function () use ($category, $reason, $actor): ServiceCategoryVersion {
            $locked = $this->categories->lockById($category->getKey());

            if (! $locked instanceof ServiceCategory) {
                throw (new ModelNotFoundException)->setModel(ServiceCategory::class);
            }

            $this->categories->loadDetails($locked);
            $this->assertPublishable($locked);

            $draft = $this->schemas->lockDraftFor($locked) ?? $this->schemas->createDraft($locked, []);
            $fields = array_map(
                static fn ($definition): array => $definition->toArray(),
                $this->validator->assertValid($draft->fields),
            );

            $snapshot = $this->snapshots->build($locked, $fields);
            $current = $locked->publishedVersion;

            if ($current !== null && $this->snapshots->fingerprint($snapshot) === $this->snapshots->fingerprint($current->snapshot)) {
                throw BusinessRuleViolation::make('category_no_changes');
            }

            $schema = $this->schemas->createPublished($locked, $fields);
            $this->schemas->archivePublished($locked, $schema->id);

            /** @var ServiceCategoryVersion $version */
            $version = $this->versions->create([
                'service_category_id' => $locked->id,
                'version' => $this->versions->nextVersionNumber($locked),
                'form_schema_id' => $schema->id,
                'snapshot' => $snapshot,
                'reason' => $reason,
                'published_by_admin_id' => $actor?->getKey(),
            ]);

            $this->categories->update($locked, ['published_version_id' => $version->id]);

            $this->audit->record(
                AuditAction::Published,
                $locked,
                $actor,
                ['version' => $current?->version],
                ['version' => $version->version],
                $reason,
                ['version_id' => $version->id, 'schema_version' => $schema->version],
            );

            return $version;
        });
    }

    private function assertPublishable(ServiceCategory $category): void
    {
        if (trim((string) $category->getTranslation('name', 'ar', false)) === '') {
            throw BusinessRuleViolation::make('category_arabic_name_required');
        }

        if ($category->allowed_booking_types === []) {
            throw BusinessRuleViolation::make('category_requires_booking_type');
        }

        if ($category->vehicleCategories->isEmpty()) {
            throw BusinessRuleViolation::make('category_requires_vehicle_category');
        }
    }
}
