<?php

namespace Database\Seeders;

use App\Enums\Catalog\BookingType;
use App\Enums\Catalog\ProofType;
use App\Enums\Catalog\ServiceFamilyKind;
use App\Enums\Payments\PaymentMethodType;
use App\Exceptions\BusinessRuleViolation;
use App\Models\ServiceCategory;
use App\Models\ServiceFamily;
use App\Models\VehicleCategory;
use App\Services\Catalog\ServiceCategoryPublisher;
use App\Services\Catalog\ServiceCategoryService;
use App\Services\Catalog\ServiceFormSchemaService;
use Illuminate\Database\Seeder;

/**
 * Starter service catalog (SRS §28): families, vehicle classes and categories with Arabic-first form schemas for
 * tuk-tuk, taxi / economy / standard car, courier motorcycle, pickup truck, furniture moving truck and freight
 * truck. Idempotent: rows are matched by code; a category is re-published only when the seed changed it.
 */
class CatalogSeeder extends Seeder
{
    public function run(
        ServiceCategoryPublisher $publisher,
        ServiceCategoryService $categoryService,
        ServiceFormSchemaService $schemas,
    ): void {
        $families = $this->families();
        $vehicles = $this->vehicles();

        foreach ($this->categories() as $sort => $definition) {
            /** @var ServiceCategory $category */
            $category = ServiceCategory::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'service_family_id' => $families[$definition['family']]->id,
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'icon' => $definition['icon'],
                    'map_marker' => $definition['icon'],
                    'sort_order' => ($sort + 1) * 10,
                    'allowed_booking_types' => array_map(static fn (BookingType $type): string => $type->value, $definition['booking_types']),
                    'negotiation_enabled' => true,
                    'reference_estimate_enabled' => true,
                    'min_customer_fare_minor' => $definition['min_fare'],
                    'max_customer_fare_minor' => $definition['max_fare'],
                    'counter_offer_min_pct' => 70,
                    'counter_offer_max_pct' => 200,
                    'max_passengers' => $definition['max_passengers'] ?? null,
                    'max_luggage' => $definition['max_luggage'] ?? null,
                    'max_weight_kg' => $definition['max_weight_kg'] ?? null,
                    'max_volume_m3' => $definition['max_volume_m3'] ?? null,
                    'supports_stops' => $definition['stops'] ?? false,
                    'max_stops' => $definition['stops'] ?? false ? 3 : 0,
                    'pickup_proof_required' => $definition['pickup_proof'] ?? false,
                    'delivery_proof_types' => array_map(static fn (ProofType $type): string => $type->value, $definition['proofs'] ?? []),
                    'recipient_otp_required' => $definition['recipient_otp'] ?? false,
                    'cash_allowed' => true,
                    'payment_methods' => [PaymentMethodType::Cash->value, PaymentMethodType::Card->value],
                ],
            );

            $category->vehicleCategories()->sync(array_map(static fn (string $code): int => $vehicles[$code]->id, $definition['vehicles']));

            $draft = $schemas->draftFor($category);
            $draft->fields = $definition['fields'];
            $draft->save();

            try {
                $publisher->publish($category, 'Initial catalog seed', null);
            } catch (BusinessRuleViolation $exception) {
                if ($exception->errorCode !== 'category_no_changes') {
                    throw $exception;
                }
            }

            $category->refresh();

            if (! $category->active) {
                $categoryService->activate($category, null);
            }
        }
    }

    /**
     * @return array<string, ServiceFamily>
     */
    private function families(): array
    {
        $definitions = [
            'rides' => [ServiceFamilyKind::PassengerCity, 'car', 'رحلات داخل المدينة', 'City rides', 'تنقّل داخل المدينة بسهولة وبسعر تتفق عليه.', 'Get around the city at a fare you agree on.'],
            'courier' => [ServiceFamilyKind::Courier, 'box', 'توصيل الطرود', 'Courier', 'توصيل المستندات والطرود الصغيرة بسرعة.', 'Fast delivery of documents and small parcels.'],
            'moving' => [ServiceFamilyKind::Moving, 'truck', 'نقل الأثاث', 'Furniture moving', 'نقل الأثاث والعفش مع عمال عند الحاجة.', 'Move furniture with helpers when you need them.'],
            'freight' => [ServiceFamilyKind::Freight, 'truck', 'شحن البضائع', 'Freight', 'نقل البضائع والحمولات التجارية.', 'Move cargo and commercial loads.'],
        ];
        $families = [];
        $sort = 10;

        foreach ($definitions as $code => [$kind, $icon, $nameAr, $nameEn, $descriptionAr, $descriptionEn]) {
            /** @var ServiceFamily $family */
            $family = ServiceFamily::query()->updateOrCreate(['code' => $code], [
                'name' => ['ar' => $nameAr, 'en' => $nameEn],
                'description' => ['ar' => $descriptionAr, 'en' => $descriptionEn],
                'kind' => $kind,
                'icon' => $icon,
                'sort_order' => $sort,
                'active' => true,
            ]);
            $families[$code] = $family;
            $sort += 10;
        }

        return $families;
    }

    /**
     * @return array<string, VehicleCategory>
     */
    private function vehicles(): array
    {
        $definitions = [
            'tuk_tuk' => ['توك توك', 'Tuk-tuk', 'tuk_tuk', ['passenger_capacity' => 3, 'luggage_capacity' => 1]],
            'taxi' => ['تاكسي', 'Taxi', 'car', ['passenger_capacity' => 4, 'luggage_capacity' => 2]],
            'economy_car' => ['سيارة اقتصادية', 'Economy car', 'car', ['passenger_capacity' => 4, 'luggage_capacity' => 2]],
            'standard_car' => ['سيارة عادية', 'Standard car', 'car', ['passenger_capacity' => 4, 'luggage_capacity' => 3]],
            'motorcycle' => ['دراجة نارية', 'Motorcycle', 'motorcycle', ['max_payload_kg' => 20, 'max_volume_m3' => 0.1]],
            'pickup_truck' => ['سيارة نصف نقل', 'Pickup truck', 'pickup', ['max_payload_kg' => 1000, 'max_volume_m3' => 3]],
            'moving_truck' => ['سيارة نقل أثاث', 'Moving truck', 'truck', ['max_payload_kg' => 3000, 'max_volume_m3' => 20]],
            'freight_truck_2t' => ['شاحنة 2 طن', '2-ton truck', 'truck', ['max_payload_kg' => 2000, 'max_volume_m3' => 12]],
        ];
        $vehicles = [];
        $sort = 10;

        foreach ($definitions as $code => [$nameAr, $nameEn, $icon, $capacity]) {
            /** @var VehicleCategory $vehicle */
            $vehicle = VehicleCategory::query()->updateOrCreate(['code' => $code], $capacity + [
                'name' => ['ar' => $nameAr, 'en' => $nameEn],
                'icon' => $icon,
                'map_marker' => $icon,
                'sort_order' => $sort,
                'active' => true,
            ]);
            $vehicles[$code] = $vehicle;
            $sort += 10;
        }

        return $vehicles;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categories(): array
    {
        $rideFields = static fn (int $passengers, int $luggage): array => [
            self::field('passengers', 'integer', 'عدد الركاب', 'Passengers', required: true, min: 1, max: $passengers),
            self::field('luggage', 'integer', 'عدد الحقائب', 'Luggage items', min: 0, max: $luggage),
            self::field('notes', 'textarea', 'ملاحظات للسائق', 'Notes for the driver', helpAr: 'اختياري', helpEn: 'Optional', max: 300),
        ];

        return [
            [
                'code' => 'ride_tuk_tuk', 'family' => 'rides', 'icon' => 'tuk_tuk', 'vehicles' => ['tuk_tuk'],
                'name' => ['ar' => 'توك توك', 'en' => 'Tuk-tuk'],
                'description' => ['ar' => 'للمشاوير القصيرة داخل الأحياء.', 'en' => 'For short trips inside neighbourhoods.'],
                'booking_types' => [BookingType::OnDemand], 'min_fare' => 500, 'max_fare' => 20000,
                'max_passengers' => 3, 'max_luggage' => 1, 'fields' => $rideFields(3, 1),
            ],
            [
                'code' => 'ride_taxi', 'family' => 'rides', 'icon' => 'car', 'vehicles' => ['taxi'],
                'name' => ['ar' => 'تاكسي', 'en' => 'Taxi'],
                'description' => ['ar' => 'تاكسي بسعر تتفق عليه مع السائق.', 'en' => 'A taxi at a fare you agree with the driver.'],
                'booking_types' => [BookingType::OnDemand, BookingType::Scheduled], 'min_fare' => 1500, 'max_fare' => 150000,
                'max_passengers' => 4, 'max_luggage' => 2, 'stops' => true, 'fields' => $rideFields(4, 2),
            ],
            [
                'code' => 'ride_economy', 'family' => 'rides', 'icon' => 'car', 'vehicles' => ['economy_car'],
                'name' => ['ar' => 'سيارة اقتصادية', 'en' => 'Economy car'],
                'description' => ['ar' => 'خيار اقتصادي لمشاويرك اليومية.', 'en' => 'An affordable option for everyday trips.'],
                'booking_types' => [BookingType::OnDemand, BookingType::Scheduled], 'min_fare' => 1500, 'max_fare' => 150000,
                'max_passengers' => 4, 'max_luggage' => 2, 'stops' => true, 'fields' => $rideFields(4, 2),
            ],
            [
                'code' => 'ride_standard', 'family' => 'rides', 'icon' => 'car', 'vehicles' => ['standard_car'],
                'name' => ['ar' => 'سيارة عادية', 'en' => 'Standard car'],
                'description' => ['ar' => 'سيارة مريحة بمساحة أكبر للحقائب.', 'en' => 'A comfortable car with more luggage space.'],
                'booking_types' => [BookingType::OnDemand, BookingType::Scheduled], 'min_fare' => 2000, 'max_fare' => 200000,
                'max_passengers' => 4, 'max_luggage' => 3, 'stops' => true, 'fields' => $rideFields(4, 3),
            ],
            [
                'code' => 'courier_motorcycle', 'family' => 'courier', 'icon' => 'motorcycle', 'vehicles' => ['motorcycle'],
                'name' => ['ar' => 'توصيل بالدراجة النارية', 'en' => 'Motorcycle courier'],
                'description' => ['ar' => 'للمستندات والطرود الصغيرة حتى 20 كجم.', 'en' => 'For documents and small parcels up to 20 kg.'],
                'booking_types' => [BookingType::Parcel], 'min_fare' => 1000, 'max_fare' => 50000,
                'max_weight_kg' => 20, 'max_volume_m3' => 0.1, 'pickup_proof' => true, 'proofs' => [ProofType::Photo, ProofType::RecipientOtp], 'recipient_otp' => true,
                'fields' => [
                    self::field('recipient', 'contact', 'بيانات المستلم', 'Recipient', required: true),
                    // P4.1: optional sender contact (met at pickup). Both contacts are kept encrypted in booking_contacts, not in service_details.
                    self::field('sender', 'contact', 'بيانات المرسل', 'Sender'),
                    self::field('parcel_category', 'select', 'نوع الطرد', 'Parcel type', required: true, options: [
                        ['documents', 'مستندات', 'Documents'], ['clothes', 'ملابس', 'Clothes'], ['food', 'طعام', 'Food'],
                        ['electronics', 'إلكترونيات', 'Electronics'], ['other', 'أخرى', 'Other'],
                    ]),
                    self::field('parcel_description', 'text', 'وصف الطرد', 'Parcel description', required: true, helpAr: 'اكتب ما بداخل الطرد بإيجاز', helpEn: 'Briefly say what is inside', max: 120,
                        visibleIf: ['field' => 'parcel_category', 'op' => 'eq', 'value' => 'other']),
                    self::field('parcel_size', 'select', 'حجم الطرد', 'Parcel size', required: true, options: [
                        ['small', 'صغير (مظروف)', 'Small (envelope)'], ['medium', 'متوسط (صندوق حذاء)', 'Medium (shoe box)'], ['large', 'كبير (حقيبة)', 'Large (bag)'],
                    ]),
                    self::field('parcel_weight', 'weight', 'وزن الطرد (كجم)', 'Parcel weight (kg)', required: true, min: 0.1, max: 20),
                    self::field('fragile', 'boolean', 'قابل للكسر', 'Fragile'),
                    self::field('declared_value', 'decimal', 'القيمة المعلنة', 'Declared value', helpAr: 'اختياري، لطرود القيمة العالية', helpEn: 'Optional, for valuable parcels', min: 0, max: 100000),
                    self::field('notes', 'textarea', 'ملاحظات', 'Notes', max: 300),
                ],
            ],
            [
                'code' => 'moving_pickup_truck', 'family' => 'moving', 'icon' => 'pickup', 'vehicles' => ['pickup_truck'],
                'name' => ['ar' => 'سيارة نصف نقل', 'en' => 'Pickup truck'],
                'description' => ['ar' => 'لنقل الأغراض الصغيرة والمتوسطة.', 'en' => 'For small and medium loads.'],
                'booking_types' => [BookingType::OnDemand, BookingType::Scheduled], 'min_fare' => 5000, 'max_fare' => 500000,
                'max_weight_kg' => 1000, 'max_volume_m3' => 3, 'stops' => true, 'pickup_proof' => true, 'proofs' => [ProofType::Photo],
                'fields' => [
                    self::field('load_description', 'textarea', 'ماذا ستنقل؟', 'What are you moving?', required: true, max: 500),
                    self::field('estimated_weight', 'weight', 'الوزن التقريبي (كجم)', 'Approximate weight (kg)', min: 1, max: 1000),
                    self::field('estimated_volume', 'decimal', 'الحجم التقريبي (م³)', 'Approximate volume (m³)', min: 0.1, max: 3),
                    self::field('helpers', 'integer', 'عدد العمال', 'Helpers', helpAr: 'عمال للتحميل والتنزيل', helpEn: 'Loaders for loading and unloading', min: 0, max: 3),
                    self::field('pickup_window_start', 'datetime', 'بداية موعد التحميل', 'Pickup window starts', helpAr: 'اختياري، يُحدد مع نهاية الموعد', helpEn: 'Optional, set together with the end'),
                    self::field('pickup_window_end', 'datetime', 'نهاية موعد التحميل', 'Pickup window ends', helpAr: 'اختياري', helpEn: 'Optional'),
                    self::field('photos', 'photo', 'صور الحمولة', 'Photos of the load', max: 5),
                ],
            ],
            [
                'code' => 'moving_furniture_truck', 'family' => 'moving', 'icon' => 'truck', 'vehicles' => ['moving_truck'],
                'name' => ['ar' => 'سيارة نقل أثاث', 'en' => 'Furniture moving truck'],
                'description' => ['ar' => 'نقل عفش المنازل والمكاتب مع عمال.', 'en' => 'Home and office moving with helpers.'],
                'booking_types' => [BookingType::Scheduled, BookingType::OnDemand], 'min_fare' => 30000, 'max_fare' => 2000000,
                'max_weight_kg' => 3000, 'max_volume_m3' => 20, 'stops' => true, 'pickup_proof' => true, 'proofs' => [ProofType::Photo, ProofType::Checklist],
                'fields' => [
                    self::field('items', 'item_checklist', 'الأثاث المطلوب نقله', 'Items to move', required: true, min: 1, options: [
                        ['bed', 'سرير', 'Bed'], ['wardrobe', 'دولاب', 'Wardrobe'], ['sofa', 'كنبة', 'Sofa'], ['table', 'ترابيزة', 'Table'],
                        ['fridge', 'ثلاجة', 'Fridge'], ['washer', 'غسالة', 'Washing machine'], ['desk', 'مكتب', 'Desk'], ['boxes', 'كراتين', 'Boxes'],
                    ]),
                    self::field('items_notes', 'textarea', 'أغراض أخرى', 'Other items', helpAr: 'اكتب كل غرض في سطر', helpEn: 'One item per line', max: 500),
                    self::field('estimated_weight', 'weight', 'الوزن التقريبي (كجم)', 'Approximate weight (kg)', min: 1, max: 3000),
                    self::field('estimated_volume', 'decimal', 'الحجم التقريبي (م³)', 'Approximate volume (m³)', min: 0.1, max: 20),
                    self::field('origin_floor', 'integer', 'الدور في مكان التحميل', 'Floor at pickup', required: true, min: 0, max: 40),
                    self::field('origin_elevator', 'boolean', 'يوجد مصعد في مكان التحميل', 'Elevator at pickup'),
                    self::field('destination_floor', 'integer', 'الدور في مكان التنزيل', 'Floor at drop-off', required: true, min: 0, max: 40),
                    self::field('destination_elevator', 'boolean', 'يوجد مصعد في مكان التنزيل', 'Elevator at drop-off'),
                    self::field('carry_distance', 'integer', 'المسافة من السيارة إلى الباب (متر)', 'Distance from truck to door (m)', helpAr: 'تظهر عند عدم وجود مصعد', helpEn: 'Shown when there is no elevator', min: 0, max: 500,
                        visibleIf: ['field' => 'origin_elevator', 'op' => 'eq', 'value' => false]),
                    self::field('destination_carry_distance', 'integer', 'المسافة من السيارة إلى الباب عند التنزيل (متر)', 'Distance from truck to door at drop-off (m)', min: 0, max: 500,
                        visibleIf: ['field' => 'destination_elevator', 'op' => 'eq', 'value' => false]),
                    self::field('parking', 'select', 'مكان ركن السيارة', 'Truck parking', options: [
                        ['at_door', 'أمام المبنى', 'At the door'], ['street_nearby', 'في الشارع بالقرب', 'On a nearby street'], ['far', 'بعيد عن المبنى', 'Far from the building'],
                    ]),
                    self::field('loading_equipment', 'multiselect', 'معدات التحميل', 'Loading equipment', options: [
                        ['hand_trolley', 'عربة يد', 'Hand trolley'], ['tail_lift', 'رافعة خلفية', 'Tail lift'], ['straps_blankets', 'أحزمة وأغطية', 'Straps and blankets'],
                    ]),
                    self::field('helpers', 'integer', 'عدد العمال', 'Helpers', required: true, min: 0, max: 6),
                    self::field('disassembly', 'boolean', 'فك وتركيب الأثاث', 'Disassembly and assembly'),
                    self::field('packing', 'boolean', 'تغليف الأثاث', 'Packing'),
                    self::field('preferred_time', 'datetime', 'الموعد المفضل', 'Preferred time'),
                    // v3 (P4.4): an optional window for the loading, both ends or none (same rule as freight).
                    self::field('pickup_window_start', 'datetime', 'بداية موعد التحميل', 'Pickup window starts', helpAr: 'اختياري، يُحدد مع نهاية الموعد', helpEn: 'Optional, set together with the end'),
                    self::field('pickup_window_end', 'datetime', 'نهاية موعد التحميل', 'Pickup window ends', helpAr: 'اختياري', helpEn: 'Optional'),
                    self::field('access_notes', 'textarea', 'ملاحظات الوصول والركن', 'Access and parking notes', max: 500),
                    self::field('photos', 'photo', 'صور الأثاث', 'Photos of the furniture', max: 8),
                ],
            ],
            [
                'code' => 'freight_truck', 'family' => 'freight', 'icon' => 'truck', 'vehicles' => ['freight_truck_2t'],
                'name' => ['ar' => 'شاحنة بضائع', 'en' => 'Freight truck'],
                'description' => ['ar' => 'لنقل البضائع والحمولات حتى 2 طن.', 'en' => 'For cargo and loads up to 2 tons.'],
                'booking_types' => [BookingType::Freight, BookingType::Scheduled], 'min_fare' => 50000, 'max_fare' => 5000000,
                'max_weight_kg' => 2000, 'max_volume_m3' => 12, 'stops' => true, 'pickup_proof' => true, 'proofs' => [ProofType::Photo, ProofType::Signature],
                'fields' => [
                    self::field('cargo_type', 'select', 'نوع البضاعة', 'Cargo type', required: true, options: [
                        ['general', 'بضائع عامة', 'General goods'], ['building', 'مواد بناء', 'Building materials'], ['food', 'مواد غذائية', 'Food'],
                        ['machinery', 'معدات وآلات', 'Machinery'], ['other', 'أخرى', 'Other'],
                    ]),
                    self::field('cargo_description', 'text', 'وصف البضاعة', 'Cargo description', required: true, max: 200,
                        visibleIf: ['field' => 'cargo_type', 'op' => 'eq', 'value' => 'other']),
                    self::field('cargo_weight', 'weight', 'الوزن التقريبي (كجم)', 'Approximate weight (kg)', required: true, min: 1, max: 2000),
                    self::field('cargo_dimensions', 'dimensions', 'أبعاد الحمولة (سم)', 'Load dimensions (cm)', min: 1, max: 1200),
                    self::field('pieces', 'integer', 'عدد القطع', 'Number of pieces', min: 1, max: 999),
                    self::field('pickup_window_start', 'datetime', 'بداية موعد التحميل', 'Pickup window starts', required: true),
                    self::field('pickup_window_end', 'datetime', 'نهاية موعد التحميل', 'Pickup window ends', required: true),
                    self::field('loading_equipment', 'multiselect', 'معدات التحميل', 'Loading equipment', options: [
                        ['hand_trolley', 'عربة يد', 'Hand trolley'], ['forklift', 'رافعة شوكية', 'Forklift'], ['crane', 'ونش', 'Crane'],
                        ['tail_lift', 'رافعة خلفية', 'Tail lift'], ['pallet_jack', 'عربة بالتات', 'Pallet jack'],
                    ]),
                    self::field('needs_loaders', 'boolean', 'أحتاج عمال تحميل', 'I need loaders'),
                    self::field('pickup_contact', 'contact', 'جهة الاتصال عند التحميل', 'Pickup contact', required: true),
                    self::field('dropoff_contact', 'contact', 'جهة الاتصال عند التسليم', 'Drop-off contact', required: true),
                    self::field('goods_value', 'decimal', 'قيمة البضاعة', 'Goods value', helpAr: 'للتأمين، اختياري', helpEn: 'For insurance, optional', min: 0),
                    self::field('documents', 'photo', 'صور المستندات', 'Document photos', max: 5),
                ],
            ],
        ];
    }

    /**
     * @param list<array{0: string, 1: string, 2: string}> $options value, Arabic label, English label
     * @param array{field: string, op: string, value: mixed}|null $visibleIf
     * @return array<string, mixed>
     */
    private static function field(
        string $key,
        string $type,
        string $labelAr,
        string $labelEn,
        bool $required = false,
        int|float|null $min = null,
        int|float|null $max = null,
        array $options = [],
        ?string $helpAr = null,
        ?string $helpEn = null,
        ?array $visibleIf = null,
    ): array {
        return [
            'key' => $key,
            'type' => $type,
            'label' => ['ar' => $labelAr, 'en' => $labelEn],
            'help' => $helpAr === null ? null : ['ar' => $helpAr, 'en' => $helpEn ?? $helpAr],
            'required' => $required,
            'min' => $min,
            'max' => $max,
            'options' => array_map(static fn (array $option): array => ['value' => $option[0], 'label' => ['ar' => $option[1], 'en' => $option[2]]], $options),
            'visible_if' => $visibleIf,
        ];
    }
}
