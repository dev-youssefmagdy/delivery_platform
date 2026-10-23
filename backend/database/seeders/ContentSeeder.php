<?php

namespace Database\Seeders;

use App\Enums\Content\AppKind;
use App\Enums\Content\ArticleType;
use App\Enums\Content\BodyFormat;
use App\Enums\Content\CategoryKind;
use App\Enums\Content\ContentType;
use App\Enums\Content\ContentWorkflowStatus;
use App\Enums\Content\MenuLocation;
use App\Enums\Content\PageKind;
use App\Models\City;
use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\ContentFaq;
use App\Models\ContentMenu;
use App\Models\ContentPage;
use App\Models\ServiceCategory;
use App\Services\Content\ContentPayloadNormalizer;
use App\Services\Content\ContentRevisionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Arabic-first DEMO / PLACEHOLDER content so the website (P5.5) and the apps have something to render from day one:
 * homepage sections, how it works, safety, about, driver recruitment, a page per seeded service category and per
 * seeded city, FAQ, help articles and menus. Every text ends with a visible "demo placeholder" note and is meant to be
 * replaced by the client's own copy before launch (edit it in Content > Pages / FAQs / Articles).
 *
 * The safety text only describes safeguards the platform implements today (verified phone number, document-checked
 * drivers, trip PIN, trip sharing, trusted contacts and SOS, reports, ratings moderation, support). No insurance,
 * background-check or monitoring claim is made.
 *
 * Idempotent and non-destructive: an item whose slug already exists (possibly edited by an operator) is never touched.
 * Store links are NOT seeded (they would be invented): add the real ones under Content > App links.
 */
class ContentSeeder extends Seeder
{
    private const MARKER_AR = '_نص تجريبي مؤقت: سيستبدله العميل قبل الإطلاق._';

    private const MARKER_EN = '_Demo placeholder text: to be replaced by the client before launch._';

    public function __construct(
        private readonly ContentPayloadNormalizer $normalizer,
        private readonly ContentRevisionService $revisions,
    ) {}

    public function run(): void
    {
        $categories = $this->categories();
        $this->faqs($categories);
        $this->pages();
        $this->servicePages();
        $this->cityPages();
        $this->articles($categories);
        $this->menus();
    }

    /**
     * @return array<string, ContentCategory>
     */
    private function categories(): array
    {
        $defs = [
            [CategoryKind::Faq, 'general', 'عن المنصة', 'About the platform', 10],
            [CategoryKind::Faq, 'booking-and-fares', 'الحجز والأسعار', 'Booking and fares', 20],
            [CategoryKind::Faq, 'safety', 'الأمان', 'Safety', 30],
            [CategoryKind::Faq, 'payments', 'الدفع', 'Payments', 40],
            [CategoryKind::Faq, 'drivers', 'للسائقين', 'For drivers', 50],
            [CategoryKind::Article, 'getting-started', 'البدء', 'Getting started', 10],
            [CategoryKind::Article, 'account-and-payments', 'الحساب والدفع', 'Account and payments', 20],
        ];
        $result = [];

        foreach ($defs as [$kind, $slug, $ar, $en, $sort]) {
            $result[$kind->value.':'.$slug] = ContentCategory::query()->firstOrCreate(
                ['kind' => $kind->value, 'slug' => $slug],
                ['name' => ['ar' => $ar, 'en' => $en], 'sort_order' => $sort, 'active' => true],
            );
        }

        return $result;
    }

    /**
     * @param array<string, ContentCategory> $categories
     */
    private function faqs(array $categories): void
    {
        $faqs = [
            ['general', 10, 'ما هي المنصة؟', 'What is the platform?', 'منصة تربطك بسائقين ومقدمي خدمات قريبين منك: تحدد وجهتك واقتراحك للأجرة، فتصلك عروض من السائقين وتختار من تناسبك.', 'A platform that connects you with nearby drivers and providers: you set your trip and propose a fare, drivers send offers, and you choose the one you prefer.'],
            ['booking-and-fares', 20, 'كيف أحجز رحلة؟', 'How do I book a ride?', "1. حدد نقطة الانطلاق والوجهة.\n2. اقترح أجرتك أو اقبل السعر المرجعي.\n3. اختر أحد عروض السائقين.\n4. تابع السائق على الخريطة حتى يصل.", "1. Choose the pickup and the destination.\n2. Propose your fare or accept the reference price.\n3. Choose one of the drivers' offers.\n4. Follow the driver on the map until arrival."],
            ['booking-and-fares', 30, 'ماذا يعني اقتراح الأجرة؟', 'What does proposing a fare mean?', 'بدلًا من سعر ثابت، تقترح أنت المبلغ الذي تراه مناسبًا. يستطيع السائق قبوله أو إرسال عرض مختلف، وأنت تقرر من تختار. السعر المتفق عليه يظهر لك قبل بدء الرحلة.', 'Instead of a fixed price, you propose the amount you find fair. A driver can accept it or send a different offer, and you decide whom to choose. The agreed fare is shown before the trip starts.'],
            ['booking-and-fares', 40, 'هل يمكنني إلغاء الحجز؟', 'Can I cancel a booking?', 'نعم، يمكنك الإلغاء من التطبيق. قد تُطبَّق رسوم إلغاء بحسب مرحلة الحجز؛ يعرض لك التطبيق الرسوم قبل تأكيد الإلغاء.', 'Yes, you can cancel from the app. A cancellation fee may apply depending on the stage of the booking; the app shows the fee before you confirm.'],
            ['safety', 50, 'ما إجراءات الأمان المتاحة؟', 'What safety features are available?', "- رقم هاتفك يُتحقق منه برمز تأكيد.\n- يُراجَع سائقو المنصة ومركباتهم ومستنداتهم قبل اعتمادهم.\n- رمز تأكيد (PIN) لبدء الرحلة.\n- مشاركة الرحلة مع من تثق بهم برابط مباشر.\n- جهات اتصال موثوقة وزر استغاثة (SOS) داخل التطبيق.\n- الإبلاغ عن أي مشكلة، ومراجعة التقييمات والبلاغات من فريقنا.", "- Your phone number is verified with a code.\n- Drivers, vehicles and documents are reviewed before approval.\n- A PIN to start the trip.\n- Share the trip with people you trust through a live link.\n- Trusted contacts and an SOS button in the app.\n- Report any problem; our team reviews ratings and reports."],
            ['payments', 60, 'ما طرق الدفع المتاحة؟', 'Which payment methods are available?', 'تظهر لك طرق الدفع المتاحة في مدينتك أثناء الحجز، وقد تشمل الدفع النقدي للسائق والمحفظة. تعرض الشاشة الطريقة المتاحة قبل التأكيد.', 'The payment methods available in your city are shown while booking and may include cash to the driver and the wallet. The screen shows what is available before you confirm.'],
            ['drivers', 70, 'كيف أنضم كسائق؟', 'How do I join as a driver?', 'حمّل تطبيق السائق، وسجّل برقم هاتفك، وارفع المستندات المطلوبة لك ولمركبتك. يراجعها فريقنا، وبعد الاعتماد تستقبل الطلبات.', 'Download the driver app, register with your phone number and upload the documents required for you and your vehicle. Our team reviews them, and once approved you receive requests.'],
            ['drivers', 80, 'كيف أحصل على أرباحي؟', 'How do I get my earnings?', 'تظهر أرباحك في التطبيق، ويمكنك طلب السحب إلى طريقة الدفع المسجلة بعد مراجعة الطلب من الفريق المالي.', 'Your earnings appear in the app, and you can request a payout to your registered payout method after the finance team reviews the request.'],
        ];

        foreach ($faqs as [$category, $sort, $questionAr, $questionEn, $answerAr, $answerEn]) {
            $exists = ContentFaq::query()->where('question->ar', $questionAr)->exists();

            if (! $exists) {
                $this->save(ContentType::Faq, new ContentFaq, [
                    'category_id' => $categories[CategoryKind::Faq->value.':'.$category]->id,
                    'question' => ['ar' => $questionAr, 'en' => $questionEn],
                    'answer' => ['ar' => $answerAr."\n\n".self::MARKER_AR, 'en' => $answerEn."\n\n".self::MARKER_EN],
                    'sort_order' => $sort,
                ]);
            }
        }
    }

    private function pages(): void
    {
        $this->page('home', PageKind::Home, 'الصفحة الرئيسية', 'Home', 'اقترح أجرتك، واختر سائقك.', 'Propose your fare, choose your driver.', null, null, [
            $this->block('hero', 'اقترح أجرتك واختر سائقك', 'Propose your fare and choose your driver', "تحرّك داخل مدينتك بأجرة تناسبك: اقترح السعر، وستصلك عروض من سائقين قريبين.\n\n".self::MARKER_AR, "Get around your city at a fare that suits you: propose a price and receive offers from nearby drivers.\n\n".self::MARKER_EN, [], ['حمّل التطبيق', 'Get the app', '/apps']),
            $this->block('features', 'لماذا نحن؟', 'Why us?', null, null, [
                ['أنت تحدد السعر', 'You set the price', 'اقترح الأجرة التي تراها مناسبة واختر من العروض.', 'Propose the fare you find fair and choose from offers.'],
                ['تختار سائقك', 'You choose your driver', 'قارن العروض والتقييمات ثم اختر.', 'Compare offers and ratings, then choose.'],
                ['خدمات متعددة', 'More than rides', 'رحلات داخل المدينة وتوصيل طرود ونقل أثاث وشحن بحسب توفرها في مدينتك.', 'City rides, parcel delivery, furniture moving and freight, depending on availability in your city.'],
                ['أمان أولًا', 'Safety first', 'مشاركة الرحلة وجهات اتصال موثوقة وزر استغاثة.', 'Trip sharing, trusted contacts and an SOS button.'],
            ]),
            $this->block('steps', 'كيف تعمل المنصة؟', 'How it works', null, null, [
                ['حدد وجهتك', 'Set your trip', 'اختر نقطة الانطلاق والوجهة.', 'Choose pickup and destination.'],
                ['اقترح أجرتك', 'Propose your fare', 'اقترح السعر أو اقبل السعر المرجعي.', 'Propose a price or accept the reference price.'],
                ['اختر عرضًا', 'Pick an offer', 'اختر السائق الذي يناسبك.', 'Choose the driver you prefer.'],
                ['تابع رحلتك', 'Follow your trip', 'تابع السائق على الخريطة حتى الوصول.', 'Follow the driver on the map until arrival.'],
            ], ['اعرف المزيد', 'Learn more', '/how-it-works']),
            $this->block('faq', 'أسئلة شائعة', 'Frequently asked questions', null, null, []),
            $this->block('cities', 'المدن المتاحة', 'Available cities', null, null, []),
            $this->block('app_download', 'حمّل التطبيق', 'Get the app', 'متاح لتطبيق العميل وتطبيق السائق.', 'Available as a customer app and a driver app.', [], null),
        ]);

        $this->page('how-it-works', PageKind::Generic, 'كيف تعمل المنصة', 'How it works', 'من الحجز حتى الوصول، للعميل وللسائق.', 'From booking to arrival, for customers and drivers.',
            "## للعميل\n1. حدد نقطة الانطلاق والوجهة.\n2. اقترح أجرتك أو اقبل السعر المرجعي.\n3. استقبل عروض السائقين واختر أحدها.\n4. تابع السائق، واستخدم رمز التأكيد لبدء الرحلة.\n5. ادفع بالطريقة المتاحة وقيّم الرحلة.\n\n## للسائق\n1. سجّل وارفع المستندات المطلوبة وانتظر الاعتماد.\n2. اعمل متصلًا لتستقبل الطلبات القريبة.\n3. أرسل عرضك أو اقبل سعر العميل.\n4. أنهِ الرحلة وتابع أرباحك من التطبيق.\n\n".self::MARKER_AR,
            "## For customers\n1. Choose the pickup and the destination.\n2. Propose your fare or accept the reference price.\n3. Receive driver offers and choose one.\n4. Follow the driver and use the PIN to start the trip.\n5. Pay with an available method and rate the trip.\n\n## For drivers\n1. Register, upload the required documents and wait for approval.\n2. Go online to receive nearby requests.\n3. Send your offer or accept the customer's price.\n4. Complete the trip and follow your earnings in the app.\n\n".self::MARKER_EN);

        $this->page('safety', PageKind::Generic, 'الأمان والثقة', 'Safety and trust', 'ما نوفره اليوم لحمايتك أثناء الرحلة.', 'What we provide today to protect you during a trip.',
            "نصف هنا الإجراءات المتوفرة فعلًا في المنصة:\n\n- **التحقق من رقم الهاتف** برمز تأكيد عند التسجيل.\n- **مراجعة السائقين**: تُراجَع هوية السائق ومستنداته ومركبته من فريقنا قبل أن يستقبل الطلبات.\n- **رمز تأكيد (PIN)** تعطيه للسائق لبدء الرحلة.\n- **مشاركة الرحلة** برابط مباشر مع من تثق بهم.\n- **جهات اتصال موثوقة وزر استغاثة (SOS)** داخل التطبيق، وفريق متابعة يراجع الحالات.\n- **الإبلاغ والتقييم**: يمكنك الإبلاغ عن مشكلة وتقييم الرحلة، ويراجع فريقنا البلاغات والتقييمات.\n- **الدعم**: افتح طلب دعم من التطبيق أو تحدث إلى السائق عبر المحادثة داخل الحجز.\n\nلا تغني هذه الإجراءات عن الحذر الشخصي؛ وفي الحالات الطارئة تواصل مع جهات الطوارئ المحلية.\n\n".self::MARKER_AR,
            "Here are the safeguards that actually exist in the platform:\n\n- **Phone number verification** with a code at sign-up.\n- **Driver review**: our team checks the driver's identity, documents and vehicle before they can receive requests.\n- **A PIN** you give the driver to start the trip.\n- **Trip sharing** through a live link with people you trust.\n- **Trusted contacts and an SOS button** in the app, with a team that reviews cases.\n- **Reports and ratings**: you can report a problem and rate the trip, and our team reviews reports and ratings.\n- **Support**: open a support request from the app or talk to the driver through the in-booking chat.\n\nThese measures do not replace personal caution; in an emergency contact the local emergency services.\n\n".self::MARKER_EN);

        $this->page('about', PageKind::Generic, 'من نحن', 'About us', 'تعرّف على فريقنا ورسالتنا.', 'Meet our team and mission.',
            "نص تعريفي بالشركة ورسالتها وقيمها. يضيف العميل هنا قصته الحقيقية.\n\n".self::MARKER_AR,
            "A company introduction, mission and values. The client adds the real story here.\n\n".self::MARKER_EN);

        $this->page('become-a-driver', PageKind::Generic, 'انضم كسائق أو مقدم خدمة', 'Become a driver or provider', 'اربح بجدول عملك أنت.', 'Earn on your own schedule.',
            "## ما تحتاجه\n- هاتف ذكي ورقم هاتف.\n- مستنداتك الشخصية ومستندات مركبتك سارية.\n- موافقتك على شروط السائقين.\n\n## كيف تبدأ\n1. حمّل تطبيق السائق وسجّل.\n2. ارفع المستندات المطلوبة.\n3. يراجعها فريقنا، وبعد الاعتماد تستقبل الطلبات.\n\n".self::MARKER_AR,
            "## What you need\n- A smartphone and a phone number.\n- Valid personal and vehicle documents.\n- Your agreement to the driver terms.\n\n## How to start\n1. Download the driver app and register.\n2. Upload the required documents.\n3. Our team reviews them, and once approved you receive requests.\n\n".self::MARKER_EN,
            [$this->block('app_download', 'حمّل تطبيق السائق', 'Get the driver app', null, null, [])]);

        $this->page('contact', PageKind::Generic, 'تواصل معنا', 'Contact us', 'نحن هنا للمساعدة.', 'We are here to help.',
            "بيانات التواصل الرسمية (البريد والهاتف والعنوان) يضيفها العميل هنا قبل الإطلاق.\n\n".self::MARKER_AR,
            "Official contact details (email, phone, address) are added here by the client before launch.\n\n".self::MARKER_EN);
    }

    private function servicePages(): void
    {
        $copy = [
            'ride_economy' => ['رحلة اقتصادية بأجرة تقترحها أنت داخل مدينتك.', 'An economy ride at a fare you propose within your city.'],
            'ride_standard' => ['رحلة بسيارة أكثر راحة، اقترح أجرتك واختر عرضك.', 'A more comfortable car ride: propose your fare and choose your offer.'],
            'ride_taxi' => ['اطلب سيارة أجرة (تاكسي) بأجرة متفق عليها قبل الرحلة.', 'Request a taxi at a fare agreed before the trip.'],
            'ride_tuk_tuk' => ['رحلات قصيرة بالتوك توك في المناطق التي تتوفر فيها الخدمة.', 'Short tuk-tuk trips in the areas where the service is available.'],
            'courier_motorcycle' => ['توصيل طرود صغيرة بدراجة نارية مع تتبع الحالة.', 'Small parcel delivery by motorcycle with status tracking.'],
            'moving_pickup_truck' => ['نقل أغراض متوسطة بسيارة نصف نقل.', 'Moving medium-sized items with a pickup truck.'],
            'moving_furniture_truck' => ['نقل أثاث المنزل بسيارة مخصصة، اطلب عروضًا واختر.', 'Home furniture moving with a dedicated truck: request offers and choose.'],
            'freight_truck' => ['شحن بضائع بالشاحنات بعروض مخصصة لحمولتك.', 'Freight by truck with offers tailored to your load.'],
        ];

        foreach (ServiceCategory::query()->orderBy('sort_order')->orderBy('id')->get() as $category) {
            [$ar, $en] = $copy[$category->code] ?? ['وصف الخدمة.', 'Service description.'];
            $slug = 'service-'.str_replace('_', '-', $category->code);
            $nameAr = (string) $category->getTranslation('name', 'ar', false);
            $nameEn = (string) $category->getTranslation('name', 'en', false);

            $this->page(
                $slug,
                PageKind::Service,
                $nameAr,
                $nameEn !== '' ? $nameEn : null,
                $ar,
                $en,
                "## عن الخدمة\n{$ar}\n\n## كيف تطلبها\nحدد التفاصيل في التطبيق، واقترح سعرك، ثم اختر من العروض التي تصلك.\n\n".self::MARKER_AR,
                "## About the service\n{$en}\n\n## How to request it\nEnter the details in the app, propose your price, then choose from the offers you receive.\n\n".self::MARKER_EN,
                [],
                ['service_category_id' => $category->id],
            );
        }
    }

    private function cityPages(): void
    {
        $slugs = ['القاهرة' => ['cairo', 'Cairo'], 'الجيزة' => ['giza', 'Giza']];

        foreach (City::query()->get() as $city) {
            $nameAr = (string) $city->getTranslation('name', 'ar', false);

            if (! isset($slugs[$nameAr])) {
                continue;
            }

            [$slug, $nameEn] = $slugs[$nameAr];
            $this->page(
                "city-{$slug}",
                PageKind::City,
                $nameAr,
                $nameEn,
                "الخدمة في {$nameAr}.",
                "Service in {$nameEn}.",
                "## الخدمة في {$nameAr}\nتعرّف على الخدمات المتاحة في {$nameAr} ومناطق التغطية. تفاصيل الأحياء والأسعار تضيفها الإدارة.\n\n".self::MARKER_AR,
                "## Service in {$nameEn}\nSee the services available in {$nameEn} and the coverage areas. Neighbourhood and pricing details are added by the operations team.\n\n".self::MARKER_EN,
                [],
                ['city_id' => $city->id],
            );
        }
    }

    /**
     * @param array<string, ContentCategory> $categories
     */
    private function articles(array $categories): void
    {
        $articles = [
            ['how-to-request-a-ride', 'getting-started', 'كيف أطلب رحلتي الأولى؟', 'How do I request my first ride?', "1. افتح التطبيق وسجّل برقم هاتفك.\n2. اختر الوجهة واقترح أجرتك.\n3. اختر أحد العروض وتابع سائقك.\n\n".self::MARKER_AR, "1. Open the app and sign in with your phone number.\n2. Choose the destination and propose your fare.\n3. Choose an offer and follow your driver.\n\n".self::MARKER_EN],
            ['payment-methods', 'account-and-payments', 'طرق الدفع والمحفظة', 'Payment methods and the wallet', "تعرض لك شاشة الحجز طرق الدفع المتاحة في مدينتك. يظهر رصيد المحفظة وسجل العمليات في قسم المحفظة داخل التطبيق.\n\n".self::MARKER_AR, "The booking screen shows the payment methods available in your city. The wallet balance and history are in the wallet section of the app.\n\n".self::MARKER_EN],
        ];

        foreach ($articles as $order => [$slug, $category, $titleAr, $titleEn, $bodyAr, $bodyEn]) {
            if (ContentArticle::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $this->save(ContentType::Article, new ContentArticle, [
                'type' => ArticleType::Help,
                'slug' => $slug,
                'category_id' => $categories[CategoryKind::Article->value.':'.$category]->id,
                'title' => ['ar' => $titleAr, 'en' => $titleEn],
                'body_format' => BodyFormat::Markdown,
                'body' => ['ar' => $bodyAr, 'en' => $bodyEn],
                'sort_order' => ($order + 1) * 10,
            ]);
        }
    }

    private function menus(): void
    {
        $defs = [
            [MenuLocation::Header, 'القائمة الرئيسية', 'Main menu', [
                ['كيف تعمل المنصة', 'How it works', '/how-it-works'],
                ['الخدمات', 'Services', '/services'],
                ['المدن', 'Cities', '/cities'],
                ['الأمان', 'Safety', '/safety'],
                ['الأسئلة الشائعة', 'FAQ', '/faq'],
            ]],
            [MenuLocation::FooterExplore, 'استكشف', 'Explore', [
                ['من نحن', 'About us', '/about'],
                ['انضم كسائق', 'Become a driver', '/become-a-driver'],
                ['تحميل التطبيق', 'Get the app', '/apps'],
                ['تواصل معنا', 'Contact us', '/contact'],
            ]],
            [MenuLocation::FooterLegal, 'قانوني', 'Legal', [
                ['الشروط والأحكام', 'Terms of service', '/legal/terms'],
                ['سياسة الخصوصية', 'Privacy policy', '/legal/privacy'],
                ['سياسة الإلغاء', 'Cancellation policy', '/legal/cancellation'],
                ['البضائع المحظورة', 'Restricted goods', '/legal/restricted_goods'],
                ['شروط السائقين', 'Driver terms', '/legal/driver_terms'],
                ['قواعد الكوبونات', 'Coupon rules', '/legal/coupon_rules'],
            ]],
        ];

        foreach ($defs as [$location, $titleAr, $titleEn, $items]) {
            if (ContentMenu::query()->where('location', $location->value)->exists()) {
                continue;
            }

            $this->save(ContentType::Menu, new ContentMenu, [
                'location' => $location,
                'title' => ['ar' => $titleAr, 'en' => $titleEn],
                'items' => array_map(static fn (array $item): array => ['label' => ['ar' => $item[0], 'en' => $item[1]], 'url' => $item[2], 'new_tab' => false], $items),
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $blocks
     * @param array<string, mixed> $extra
     */
    private function page(string $slug, PageKind $kind, string $titleAr, ?string $titleEn, string $summaryAr, ?string $summaryEn, ?string $bodyAr, ?string $bodyEn = null, array $blocks = [], array $extra = []): void
    {
        if (ContentPage::query()->where('slug', $slug)->exists()) {
            return;
        }

        $this->save(ContentType::Page, new ContentPage, [
            'kind' => $kind,
            'slug' => $slug,
            'title' => array_filter(['ar' => $titleAr, 'en' => $titleEn]),
            'summary' => array_filter(['ar' => $summaryAr, 'en' => $summaryEn]),
            'body_format' => BodyFormat::Markdown,
            'body' => $bodyAr === null ? [] : array_filter(['ar' => $bodyAr, 'en' => $bodyEn]),
            'blocks' => $blocks,
        ] + $extra);
    }

    /**
     * @param list<array{0: string, 1: string, 2: string, 3: string}> $items
     * @param array{0: string, 1: string, 2: string}|null $cta
     * @return array<string, mixed>
     */
    private function block(string $type, string $titleAr, string $titleEn, ?string $bodyAr, ?string $bodyEn, array $items, ?array $cta = null): array
    {
        return [
            'type' => $type,
            'title' => ['ar' => $titleAr, 'en' => $titleEn],
            'body' => array_filter(['ar' => $bodyAr, 'en' => $bodyEn]),
            'items' => array_map(static fn (array $item): array => ['title' => ['ar' => $item[0], 'en' => $item[1]], 'text' => ['ar' => $item[2], 'en' => $item[3]]], $items),
            'cta' => ['label' => $cta === null ? [] : ['ar' => $cta[0], 'en' => $cta[1]], 'url' => $cta[2] ?? null],
            'ref' => null,
        ];
    }

    /**
     * Writes a published row through the same normalizer the admin uses (rich text is sanitized) and records a
     * system revision.
     *
     * @param array<string, mixed> $attributes
     */
    private function save(ContentType $type, Model $model, array $attributes): void
    {
        $attributes = $this->normalizer->derive($type, $attributes);
        $attributes += [
            'status' => ContentWorkflowStatus::Published,
            'published_at' => now(),
            'lock_version' => 0,
        ];

        $model->forceFill($attributes)->save();
        $this->revisions->capture($model, null, 'created', 'demo_seed');
    }
}
