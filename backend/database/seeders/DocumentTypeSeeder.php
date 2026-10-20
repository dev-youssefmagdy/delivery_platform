<?php

namespace Database\Seeders;

use App\Enums\Provider\DocumentSubject;
use App\Enums\Provider\ProviderType;
use App\Models\Country;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * Default document types and Egypt requirements for provider onboarding. These are STARTING POINTS, not legal advice:
 * requirements are configurable per region and service in the admin panel (Document types) and must be reviewed for
 * local compliance before launch (SRS §7.1). Idempotent and non-destructive: an existing type keeps what an admin
 * edited, and requirements are only added when missing.
 */
class DocumentTypeSeeder extends Seeder
{
    private const IMAGES_AND_PDF = ['image/jpeg', 'image/png', 'application/pdf'];

    public function run(): void
    {
        $egypt = Country::query()->where('iso2', 'EG')->first();

        foreach ($this->definitions() as $order => $definition) {
            $type = DocumentType::query()->firstOrCreate(['code' => $definition['code']], [
                'name' => $definition['name'],
                'description' => $definition['description'],
                'applies_to' => $definition['applies_to'],
                'requires_expiry' => $definition['expiry'],
                'requires_number' => $definition['number'],
                'allowed_mimes' => self::IMAGES_AND_PDF,
                'max_size_kb' => 5120,
                'sort_order' => ($order + 1) * 10,
                'active' => true,
            ]);

            if ($egypt === null) {
                continue;
            }

            foreach ($definition['requirements'] as $requirement) {
                DocumentRequirement::query()->firstOrCreate([
                    'document_type_id' => $type->id,
                    'country_id' => $egypt->id,
                    'city_id' => null,
                    'service_category_id' => null,
                    'vehicle_category_id' => null,
                    'provider_type' => $requirement['provider_type'],
                ], ['mandatory' => $requirement['mandatory']]);
            }
        }
    }

    /**
     * @return list<array{code: string, name: array<string, string>, description: array<string, string>, applies_to: DocumentSubject, expiry: bool, number: bool, requirements: list<array{provider_type: ProviderType|null, mandatory: bool}>}>
     */
    private function definitions(): array
    {
        return [
            [
                'code' => 'national_id',
                'name' => ['ar' => 'بطاقة الرقم القومي', 'en' => 'National ID card'],
                'description' => ['ar' => 'صورة واضحة للوجهين.', 'en' => 'A clear photo of both sides.'],
                'applies_to' => DocumentSubject::Provider, 'expiry' => true, 'number' => true,
                'requirements' => [['provider_type' => null, 'mandatory' => true]],
            ],
            [
                'code' => 'driving_licence',
                'name' => ['ar' => 'رخصة القيادة', 'en' => 'Driving licence'],
                'description' => ['ar' => 'رخصة قيادة سارية للمركبة التي تستخدمها.', 'en' => 'A valid licence for the vehicle you use.'],
                'applies_to' => DocumentSubject::Provider, 'expiry' => true, 'number' => true,
                'requirements' => [['provider_type' => null, 'mandatory' => true]],
            ],
            [
                'code' => 'criminal_record',
                'name' => ['ar' => 'صحيفة الحالة الجنائية', 'en' => 'Criminal record certificate'],
                'description' => ['ar' => 'اختياري حسب سياسة التشغيل.', 'en' => 'Optional, depending on the operating policy.'],
                'applies_to' => DocumentSubject::Provider, 'expiry' => false, 'number' => false,
                'requirements' => [['provider_type' => null, 'mandatory' => false]],
            ],
            [
                'code' => 'commercial_transport_permit',
                'name' => ['ar' => 'ترخيص النقل التجاري', 'en' => 'Commercial transport permit'],
                'description' => ['ar' => 'مطلوب لمزوّدي النقل التجاري.', 'en' => 'Required for commercial transport providers.'],
                'applies_to' => DocumentSubject::Provider, 'expiry' => true, 'number' => true,
                'requirements' => [['provider_type' => ProviderType::CommercialTransport, 'mandatory' => true]],
            ],
            [
                'code' => 'company_registration',
                'name' => ['ar' => 'السجل التجاري', 'en' => 'Company registration'],
                'description' => ['ar' => 'مطلوب للشركات والأساطيل.', 'en' => 'Required for companies and fleets.'],
                'applies_to' => DocumentSubject::Provider, 'expiry' => true, 'number' => true,
                'requirements' => [['provider_type' => ProviderType::CompanyFleet, 'mandatory' => true]],
            ],
            [
                'code' => 'vehicle_registration',
                'name' => ['ar' => 'رخصة المركبة', 'en' => 'Vehicle registration'],
                'description' => ['ar' => 'رخصة تسيير سارية للمركبة.', 'en' => 'A valid registration of the vehicle.'],
                'applies_to' => DocumentSubject::Vehicle, 'expiry' => true, 'number' => true,
                'requirements' => [['provider_type' => null, 'mandatory' => true]],
            ],
            [
                'code' => 'vehicle_insurance',
                'name' => ['ar' => 'تأمين المركبة', 'en' => 'Vehicle insurance'],
                'description' => ['ar' => 'وثيقة تأمين سارية.', 'en' => 'A valid insurance policy.'],
                'applies_to' => DocumentSubject::Vehicle, 'expiry' => true, 'number' => false,
                'requirements' => [['provider_type' => null, 'mandatory' => true]],
            ],
            [
                'code' => 'vehicle_inspection',
                'name' => ['ar' => 'شهادة فحص المركبة', 'en' => 'Vehicle inspection certificate'],
                'description' => ['ar' => 'اختياري حسب الخدمة.', 'en' => 'Optional, depending on the service.'],
                'applies_to' => DocumentSubject::Vehicle, 'expiry' => true, 'number' => false,
                'requirements' => [['provider_type' => null, 'mandatory' => false]],
            ],
        ];
    }
}
