<?php

namespace Database\Seeders;

use App\Enums\Identity\UserStatus;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\FileScanStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ProviderType;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehiclePhotoKind;
use App\Enums\Provider\VehicleStatus;
use App\Models\City;
use App\Models\DocumentType;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\ProviderServiceCategory;
use App\Models\ProviderStatusEvent;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleDocument;
use App\Models\VehiclePhoto;
use App\Support\Files\InspectedUpload;
use App\Support\Files\PrivateFileStorage;
use App\Support\Vehicles\PlateNumber;
use Illuminate\Database\Seeder;

/**
 * DEMO providers for development and demos, each clearly named "(demo)": an approved provider with valid documents and
 * an approved vehicle, one waiting in the verification queue, a rejected one, and an approved one whose driving
 * licence has expired. Files are generated placeholder images on the private disk. Skipped when PROVIDER_SEED_DEMO is
 * false (default false in production). Idempotent: a demo provider that already exists is left untouched.
 */
class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('provider.seed_demo')) {
            return;
        }

        $city = City::query()->where('name->en', 'Cairo')->first();
        $category = ServiceCategory::query()->where('code', 'ride_economy')->first();
        $vehicleCategory = VehicleCategory::query()->where('code', 'economy_car')->first();

        if ($city === null || $category === null || $vehicleCategory === null) {
            return;
        }

        foreach ($this->scenarios() as $index => $scenario) {
            $phone = '+20100000010'.($index + 1);

            if (User::query()->where('phone_e164', $phone)->exists()) {
                continue;
            }

            $this->createProvider($scenario, $phone, $city, $category, $vehicleCategory, $index + 1);
        }
    }

    /**
     * @return list<array{name: string, status: ProviderStatus, vehicle: VehicleStatus, documents: DocumentStatus, licence_expired: bool, reason: string|null}>
     */
    private function scenarios(): array
    {
        return [
            ['name' => 'Demo approved driver (demo)', 'status' => ProviderStatus::Approved, 'vehicle' => VehicleStatus::Approved, 'documents' => DocumentStatus::Approved, 'licence_expired' => false, 'reason' => null],
            ['name' => 'Demo pending driver (demo)', 'status' => ProviderStatus::UnderReview, 'vehicle' => VehicleStatus::UnderReview, 'documents' => DocumentStatus::Pending, 'licence_expired' => false, 'reason' => null],
            ['name' => 'Demo rejected driver (demo)', 'status' => ProviderStatus::Rejected, 'vehicle' => VehicleStatus::Rejected, 'documents' => DocumentStatus::Rejected, 'licence_expired' => false, 'reason' => 'The driving licence photo is not readable. Upload a clear photo of both sides.'],
            ['name' => 'Demo expired licence driver (demo)', 'status' => ProviderStatus::Approved, 'vehicle' => VehicleStatus::Approved, 'documents' => DocumentStatus::Approved, 'licence_expired' => true, 'reason' => null],
        ];
    }

    /**
     * @param array{name: string, status: ProviderStatus, vehicle: VehicleStatus, documents: DocumentStatus, licence_expired: bool, reason: string|null} $scenario
     */
    private function createProvider(array $scenario, string $phone, City $city, ServiceCategory $category, VehicleCategory $vehicleCategory, int $number): void
    {
        $user = new User(['name' => $scenario['name'], 'preferred_locale' => 'ar']);
        $user->forceFill(['phone_e164' => $phone, 'phone_verified_at' => now(), 'status' => UserStatus::Active])->save();

        $status = $scenario['status'];
        $profile = new ProviderProfile(['user_id' => $user->id, 'provider_type' => ProviderType::IndividualDriver, 'city_id' => $city->id, 'date_of_birth' => '1990-01-01']);
        $profile->forceFill([
            'status' => $status,
            'status_reason' => $scenario['reason'],
            'status_changed_at' => now()->subDay(),
            'submitted_at' => now()->subDays(2),
            'approved_at' => $status === ProviderStatus::Approved ? now()->subDay() : null,
        ])->save();

        foreach ([ProviderStatus::PhoneVerified, ProviderStatus::ProfileSubmitted, ProviderStatus::DocumentsPending, ProviderStatus::UnderReview, $status] as $step) {
            ProviderStatusEvent::query()->create(['provider_profile_id' => $profile->id, 'to_status' => $step, 'actor_type' => 'system', 'reason' => $step === $status ? $scenario['reason'] : null]);
        }

        ProviderServiceCategory::query()->create([
            'provider_profile_id' => $profile->id,
            'service_category_id' => $category->id,
            'status' => $status === ProviderStatus::Approved ? ServiceApprovalStatus::Approved : ServiceApprovalStatus::Requested,
            'decided_at' => $status === ProviderStatus::Approved ? now()->subDay() : null,
        ]);

        $plate = 'ق ص ع '.(1000 + $number);
        $vehicle = new Vehicle(['provider_profile_id' => $profile->id, 'vehicle_category_id' => $vehicleCategory->id, 'make' => 'Toyota', 'model' => 'Corolla', 'year' => 2021, 'color' => 'White', 'plate_display' => $plate, 'plate_normalized' => PlateNumber::normalize($plate), 'passenger_capacity' => 4, 'capabilities' => ['air_conditioning']]);
        $vehicle->forceFill([
            'status' => $scenario['vehicle'],
            'status_changed_at' => now()->subDay(),
            'submitted_at' => now()->subDays(2),
            'rejection_reason' => $scenario['vehicle'] === VehicleStatus::Rejected ? 'The plate is not visible in the photos.' : null,
        ])->save();

        if ($scenario['vehicle'] === VehicleStatus::Approved) {
            $profile->forceFill(['current_vehicle_id' => $vehicle->id])->save();
        }

        $storage = app(PrivateFileStorage::class);

        foreach ([VehiclePhotoKind::Front, VehiclePhotoKind::Back] as $kind) {
            $upload = $this->placeholder($scenario['name'].' · '.$kind->value);
            VehiclePhoto::query()->create(['vehicle_id' => $vehicle->id, 'kind' => $kind, 'path' => $storage->putVehiclePhoto($profile->uuid, $vehicle->uuid, $upload), 'mime' => $upload->mime, 'size_bytes' => $upload->size(), 'file_hash' => $upload->hash()]);
        }

        foreach (['national_id' => false, 'driving_licence' => true] as $code => $isLicence) {
            $expired = $isLicence && $scenario['licence_expired'];
            $this->document(ProviderDocument::class, 'provider_profile_id', $profile->id, $profile->uuid, $code, $scenario, $expired);
        }

        foreach (['vehicle_registration', 'vehicle_insurance'] as $code) {
            $this->document(VehicleDocument::class, 'vehicle_id', $vehicle->id, $profile->uuid, $code, $scenario, false);
        }
    }

    /**
     * @param class-string<ProviderDocument|VehicleDocument> $model
     * @param array{name: string, status: ProviderStatus, vehicle: VehicleStatus, documents: DocumentStatus, licence_expired: bool, reason: string|null} $scenario
     */
    private function document(string $model, string $ownerColumn, int $ownerId, string $providerUuid, string $code, array $scenario, bool $expired): void
    {
        $type = DocumentType::query()->where('code', $code)->first();

        if ($type === null) {
            return;
        }

        $upload = $this->placeholder($scenario['name'].' · '.$code);
        $status = $expired ? DocumentStatus::Approved : $scenario['documents'];

        $model::query()->create([
            $ownerColumn => $ownerId,
            'document_type_id' => $type->id,
            'path' => app(PrivateFileStorage::class)->putDocument($providerUuid, $upload),
            'original_name' => 'demo',
            'mime' => $upload->mime,
            'size_bytes' => $upload->size(),
            'file_hash' => $upload->hash(),
            'scan_status' => FileScanStatus::Skipped,
            'number' => $type->requires_number ? 'DEMO-'.strtoupper(substr(md5($code.$ownerId), 0, 8)) : null,
            'issued_at' => now()->subYears(2)->toDateString(),
            'expires_at' => $type->requires_expiry ? ($expired ? now()->subDays(10)->toDateString() : now()->addYear()->toDateString()) : null,
            'status' => $status,
            'reviewed_at' => $status === DocumentStatus::Pending ? null : now()->subDay(),
            'rejection_reason' => $status === DocumentStatus::Rejected ? $scenario['reason'] : null,
        ]);
    }

    private function placeholder(string $text): InspectedUpload
    {
        $image = imagecreatetruecolor(800, 500);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 226, 232, 240));
        imagestring($image, 5, 40, 230, 'DEMO DOCUMENT - NOT REAL', (int) imagecolorallocate($image, 30, 41, 59));
        imagestring($image, 3, 40, 260, preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '', (int) imagecolorallocate($image, 71, 85, 105));
        ob_start();
        imagejpeg($image, null, 80);
        $bytes = (string) ob_get_clean();

        return new InspectedUpload($bytes, 'image/jpeg', 'jpg', 'demo');
    }
}
