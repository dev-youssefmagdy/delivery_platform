<?php

namespace App\Services\Provider;

use App\DTOs\Provider\OnboardingTaskItem;
use App\DTOs\Provider\VehicleInput;
use App\DTOs\Provider\VehiclePhotoData;
use App\Enums\Common\AuditAction;
use App\Enums\Provider\OnboardingTask;
use App\Enums\Provider\VehicleCapability;
use App\Enums\Provider\VehicleStatus;
use App\Exceptions\ApplicationIncompleteException;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use App\Repositories\Contracts\Catalog\VehicleCategoryRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\VehiclePhotoRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Files\PrivateFileStorage;
use App\Support\Files\UploadInspector;
use App\Support\Vehicles\PlateNumber;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

/**
 * A provider's vehicles: create / edit while still a draft or rejected, photos, submission for review, and the
 * admin decision (approve / reject with a reason, audited). Approval needs every required vehicle document approved.
 */
final class VehicleService
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly VehiclePhotoRepositoryInterface $photos,
        private readonly VehicleCategoryRepositoryInterface $vehicleCategories,
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly DocumentChecklistService $checklists,
        private readonly UploadInspector $inspector,
        private readonly PrivateFileStorage $storage,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Vehicle>
     */
    public function listFor(ProviderProfile $profile): \Illuminate\Database\Eloquent\Collection
    {
        return $this->vehicles->forProvider($profile);
    }

    public function findPhoto(string $uuid): ?VehiclePhoto
    {
        return $this->photos->findByUuid($uuid);
    }

    /**
     * @throws BusinessRuleViolation
     */
    public function create(ProviderProfile $profile, VehicleInput $input): Vehicle
    {
        try {
            return $this->db->transaction(function () use ($profile, $input): Vehicle {
                $locked = $this->lockProfile($profile);
                $this->assertProviderMayChange($locked);

                if ($this->vehicles->countForProvider($locked) >= (int) config('provider.vehicles.max_per_provider', 10)) {
                    throw BusinessRuleViolation::make('vehicle_limit_reached');
                }

                $attributes = $this->attributes($input, null);
                $attributes['provider_profile_id'] = $locked->id;

                /** @var Vehicle $vehicle */
                $vehicle = $this->vehicles->create($attributes);

                return $vehicle->load('vehicleCategory', 'photos');
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('vehicle_plate_taken', 'plate');
        }
    }

    /**
     * @throws BusinessRuleViolation `vehicle_not_editable`
     */
    public function update(Vehicle $vehicle, VehicleInput $input): Vehicle
    {
        try {
            return $this->db->transaction(function () use ($vehicle, $input): Vehicle {
                $locked = $this->lockVehicle($vehicle);
                $this->assertProviderMayChange($this->lockProfile($locked->providerProfile));

                if (! $locked->status->isEditableByProvider()) {
                    throw BusinessRuleViolation::make('vehicle_not_editable');
                }

                $this->vehicles->update($locked, $this->attributes($input, $locked) + ['lock_version' => $locked->lock_version + 1]);

                return $locked->load('vehicleCategory', 'photos');
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('vehicle_plate_taken', 'plate');
        }
    }

    /**
     * Only a draft that never entered review can be removed; its files go with it.
     */
    public function delete(Vehicle $vehicle): void
    {
        $paths = $this->db->transaction(function () use ($vehicle): array {
            $locked = $this->lockVehicle($vehicle);

            if ($locked->status !== VehicleStatus::Draft || $locked->submitted_at !== null) {
                throw BusinessRuleViolation::make('vehicle_not_deletable');
            }

            $paths = [
                ...$this->photos->forVehicle($locked)->pluck('path')->all(),
                ...$locked->documents()->pluck('path')->all(),
            ];
            $this->vehicles->delete($locked);

            return $paths;
        });

        $this->storage->deleteMany($paths);
    }

    public function addPhoto(Vehicle $vehicle, VehiclePhotoData $data): VehiclePhoto
    {
        $inspected = $this->inspector->inspectPhoto(
            $data->file,
            (int) config('provider.vehicles.photo_max_kilobytes', 8192),
            (int) config('provider.vehicles.photo_max_edge', 1600),
        );
        $path = $this->storage->putVehiclePhoto($vehicle->providerProfile->uuid, $vehicle->uuid, $inspected);

        try {
            return $this->db->transaction(function () use ($vehicle, $data, $inspected, $path): VehiclePhoto {
                $locked = $this->lockVehicle($vehicle);

                if (! $locked->status->isEditableByProvider()) {
                    throw BusinessRuleViolation::make('vehicle_not_editable');
                }

                if ($this->photos->countFor($locked) >= (int) config('provider.vehicles.max_photos', 8)) {
                    throw BusinessRuleViolation::make('vehicle_photo_limit_reached', 'file');
                }

                return $this->photos->create([
                    'vehicle_id' => $locked->id,
                    'kind' => $data->kind,
                    'path' => $path,
                    'mime' => $inspected->mime,
                    'size_bytes' => $inspected->size(),
                    'file_hash' => $inspected->hash(),
                ]);
            });
        } catch (Throwable $exception) {
            $this->storage->delete($path);

            throw $exception;
        }
    }

    public function removePhoto(Vehicle $vehicle, string $photoUuid): void
    {
        $path = $this->db->transaction(function () use ($vehicle, $photoUuid): string {
            $locked = $this->lockVehicle($vehicle);

            if (! $locked->status->isEditableByProvider()) {
                throw BusinessRuleViolation::make('vehicle_not_editable');
            }

            $photo = $this->photos->findByUuid($photoUuid);

            if ($photo === null || $photo->vehicle_id !== $locked->id) {
                throw (new ModelNotFoundException)->setModel(VehiclePhoto::class);
            }

            $this->photos->delete($photo);

            return $photo->path;
        });

        $this->storage->delete($path);
    }

    /**
     * @throws ApplicationIncompleteException `vehicle_incomplete` with the tasks still open
     */
    public function submitForReview(Vehicle $vehicle): Vehicle
    {
        return $this->db->transaction(function () use ($vehicle): Vehicle {
            $locked = $this->lockVehicle($vehicle);
            $profile = $this->lockProfile($locked->providerProfile);
            $this->assertProviderMayChange($profile);

            if (! $locked->status->isEditableByProvider()) {
                throw BusinessRuleViolation::make('vehicle_not_editable');
            }

            $tasks = [];

            if ($this->photos->countFor($locked) < (int) config('provider.vehicles.min_photos', 2)) {
                $tasks[] = new OnboardingTaskItem(OnboardingTask::AddVehiclePhotos, null, $locked->uuid);
            }

            $locked->setRelation('providerProfile', $profile);

            foreach (DocumentChecklistService::unsatisfiedMandatory($this->checklists->forVehicle($locked)) as $item) {
                if (! $item->isUploaded()) {
                    $tasks[] = new OnboardingTaskItem(OnboardingTask::UploadDocument, $item->type->code, $locked->uuid);
                }
            }

            if ($tasks !== []) {
                throw ApplicationIncompleteException::forTasks('vehicle_incomplete', $tasks);
            }

            $this->vehicles->applyStatus($locked, VehicleStatus::UnderReview, null, null);

            return $locked->load('vehicleCategory', 'photos');
        });
    }

    /**
     * @throws BusinessRuleViolation `vehicle_status_transition_invalid`, `vehicle_documents_incomplete`
     */
    public function approve(Vehicle $vehicle, Admin $actor): Vehicle
    {
        return $this->db->transaction(function () use ($vehicle, $actor): Vehicle {
            $locked = $this->lockVehicle($vehicle);

            if ($locked->status !== VehicleStatus::UnderReview) {
                throw BusinessRuleViolation::make('vehicle_status_transition_invalid');
            }

            $locked->setRelation('providerProfile', $this->lockProfile($locked->providerProfile));

            if (DocumentChecklistService::unsatisfiedMandatory($this->checklists->forVehicle($locked)) !== []) {
                throw BusinessRuleViolation::make('vehicle_documents_incomplete');
            }

            $before = ['status' => $locked->status->value];
            $this->vehicles->applyStatus($locked, VehicleStatus::Approved, $actor->id, null);
            $this->audit->record(AuditAction::Approved, $locked, $actor, $before, ['status' => VehicleStatus::Approved->value]);

            if ($locked->providerProfile->current_vehicle_id === null) {
                $this->profiles->setCurrentVehicle($locked->providerProfile, $locked->id);
            }

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation `vehicle_status_transition_invalid`
     */
    public function reject(Vehicle $vehicle, string $reason, Admin $actor): Vehicle
    {
        return $this->db->transaction(function () use ($vehicle, $reason, $actor): Vehicle {
            $locked = $this->lockVehicle($vehicle);

            if ($locked->status !== VehicleStatus::UnderReview) {
                throw BusinessRuleViolation::make('vehicle_status_transition_invalid');
            }

            $before = ['status' => $locked->status->value];
            $this->vehicles->applyStatus($locked, VehicleStatus::Rejected, $actor->id, $reason);
            $this->audit->record(AuditAction::Rejected, $locked, $actor, $before, ['status' => VehicleStatus::Rejected->value], $reason);

            return $locked;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(VehicleInput $input, ?Vehicle $current): array
    {
        $attributes = [];

        if ($input->vehicleCategoryId !== null) {
            $category = $this->vehicleCategories->find($input->vehicleCategoryId);

            if ($category === null || ! $category->active) {
                throw BusinessRuleViolation::make('vehicle_category_unavailable', 'vehicle_category_id');
            }

            $attributes['vehicle_category_id'] = $category->id;
        }

        foreach (['make' => $input->make, 'model' => $input->model, 'color' => $input->color] as $key => $value) {
            if ($value !== null) {
                $attributes[$key] = mb_substr(trim($value), 0, 60);
            }
        }

        if ($input->year !== null) {
            if ($input->year < (int) config('provider.vehicles.min_year', 1980) || $input->year > CarbonImmutable::today()->year + 1) {
                throw BusinessRuleViolation::make('vehicle_year_invalid', 'year');
            }

            $attributes['year'] = $input->year;
        }

        if ($input->plate !== null) {
            $normalized = PlateNumber::normalize($input->plate);

            if (! PlateNumber::isPlausible($normalized)) {
                throw BusinessRuleViolation::make('vehicle_plate_invalid', 'plate');
            }

            if ($this->vehicles->plateExists($normalized, $current?->id)) {
                throw BusinessRuleViolation::make('vehicle_plate_taken', 'plate');
            }

            $attributes['plate_display'] = PlateNumber::display($input->plate);
            $attributes['plate_normalized'] = $normalized;
        }

        if ($input->passengerCapacity !== null) {
            $attributes['passenger_capacity'] = $input->passengerCapacity;
        }

        if ($input->payloadKg !== null) {
            $attributes['payload_kg'] = $input->payloadKg;
        }

        if ($input->volumeM3 !== null) {
            $attributes['volume_m3'] = $input->volumeM3;
        }

        if ($input->capabilities !== null) {
            $attributes['capabilities'] = array_values(array_unique(array_filter(
                $input->capabilities,
                static fn (string $capability): bool => VehicleCapability::tryFrom($capability) !== null,
            )));
        }

        return $attributes;
    }

    private function assertProviderMayChange(ProviderProfile $profile): void
    {
        if (! $profile->status->allowsDocumentsAndVehicles()) {
            throw BusinessRuleViolation::make('application_locked');
        }
    }

    private function lockProfile(ProviderProfile $profile): ProviderProfile
    {
        return $this->profiles->lockForUpdate($profile->id) ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);
    }

    private function lockVehicle(Vehicle $vehicle): Vehicle
    {
        $locked = $this->vehicles->lockForUpdate($vehicle->id) ?? throw (new ModelNotFoundException)->setModel(Vehicle::class);
        $locked->setRelation('providerProfile', $vehicle->relationLoaded('providerProfile') ? $vehicle->providerProfile : $locked->providerProfile);

        return $locked;
    }
}
