<?php

/*
| Labels for every App\Enums case: enums.{snake_enum}.{value}.
*/
return [
    'badge_color' => [
        'neutral' => 'محايد',
        'info' => 'معلومة',
        'success' => 'نجاح',
        'warning' => 'تحذير',
        'danger' => 'خطر',
        'primary' => 'أساسي',
    ],

    'locale' => [
        'ar' => 'العربية',
        'en' => 'English',
    ],

    'actor_type' => [
        'customer' => 'عميل',
        'provider' => 'مقدم خدمة',
        'admin' => 'مشرف',
        'system' => 'النظام',
    ],

    'platform' => [
        'android' => 'أندرويد',
        'ios' => 'iOS',
        'web' => 'الويب',
    ],

    'audit_action' => [
        'created' => 'إنشاء',
        'updated' => 'تعديل',
        'deleted' => 'حذف',
        'restored' => 'استعادة',
        'status_changed' => 'تغيير الحالة',
        'approved' => 'اعتماد',
        'rejected' => 'رفض',
        'suspended' => 'إيقاف',
        'reactivated' => 'إعادة تفعيل',
        'published' => 'نشر',
        'exported' => 'تصدير',
        'viewed' => 'عرض',
        'logged_in' => 'تسجيل دخول',
        'logged_out' => 'تسجيل خروج',
        'login_failed' => 'فشل تسجيل الدخول',
        'overridden' => 'تجاوز يدوي',
        'mfa_enabled' => 'تفعيل التحقق بخطوتين',
        'mfa_disabled' => 'إيقاف التحقق بخطوتين',
        'mfa_reset' => 'إعادة تعيين التحقق بخطوتين',
        'password_changed' => 'تغيير كلمة المرور',
        'session_revoked' => 'إنهاء جلسة',
        'restricted' => 'تقييد',
        'message_sent' => 'إرسال رسالة',
        'phone_revealed' => 'كشف رقم الهاتف',
        'anonymised' => 'إخفاء الهوية',
        'downloaded' => 'تم التنزيل',
        'more_info_requested' => 'طُلبت معلومات إضافية',
    ],

    'health_status' => [
        'ok' => 'يعمل',
        'degraded' => 'أداء منخفض',
        'failing' => 'متوقف',
        'not_configured' => 'غير مهيأ',
    ],

    'user_status' => [
        'active' => 'نشط',
        'restricted' => 'مقيّد',
        'suspended' => 'موقوف',
        'verification_required' => 'يتطلب التحقق',
        'deletion_pending' => 'الحذف قيد المعالجة',
        'deleted' => 'محذوف',
    ],

    'client_app' => ['customer' => 'تطبيق العميل', 'provider' => 'تطبيق السائق'],

    'otp_purpose' => ['login' => 'تسجيل الدخول'],

    'otp_delivery_status' => ['sent' => 'تم الإرسال', 'failed' => 'فشل', 'skipped' => 'رقم تجريبي (لم يُرسل)'],

    'otp_challenge_state' => [
        'pending' => 'قيد الانتظار',
        'verified' => 'تم التحقق',
        'expired' => 'منتهي',
        'locked' => 'مقفل',
        'superseded' => 'تم استبداله',
    ],

    'deletion_request_status' => ['pending' => 'قيد الانتظار', 'cancelled' => 'ملغى', 'rejected' => 'مرفوض', 'completed' => 'مكتمل'],

    'admin_permission' => [
        'label' => ':group: :action',
    ],

    'admin_permission_group' => [
        'dashboard' => 'لوحة المعلومات',
        'admins' => 'المشرفون',
        'roles' => 'الأدوار',
        'audit_logs' => 'سجل التدقيق',
        'login_activity' => 'نشاط تسجيل الدخول',
        'otp_challenges' => 'طلبات رمز التحقق',
        'settings' => 'الإعدادات',
        'markets' => 'الأسواق',
        'policies' => 'السياسات',
        'customers' => 'العملاء',
        'drivers' => 'السائقون',
        'documents' => 'المستندات',
        'vehicles' => 'المركبات',
        'services' => 'الخدمات',
        'vehicle_categories' => 'فئات المركبات',
        'service_areas' => 'مناطق الخدمة',
        'fare_policies' => 'سياسات الأجرة',
        'commission_rules' => 'قواعد العمولة',
        'cancellation_policies' => 'سياسات الإلغاء',
        'bookings' => 'الحجوزات',
        'offers' => 'العروض',
        'coupons' => 'القسائم',
        'campaigns' => 'الحملات',
        'finance' => 'المالية',
        'payments' => 'المدفوعات',
        'refunds' => 'المبالغ المستردة',
        'payouts' => 'المستحقات',
        'wallets' => 'المحافظ',
        'ledger' => 'دفتر الأستاذ',
        'incidents' => 'البلاغات',
        'support' => 'الدعم',
        'macros' => 'الردود الجاهزة',
        'reviews' => 'التقييمات',
        'risk' => 'المخاطر',
        'content' => 'المحتوى',
        'translations' => 'الترجمات',
        'leads' => 'العملاء المحتملون',
        'notifications' => 'الإشعارات',
        'push_campaigns' => 'حملات الإشعارات',
        'reports' => 'التقارير',
        'system' => 'النظام',
        'demo_items' => 'عناصر تجريبية',
        // @admin-module:permission-groups
    ],

    'admin_permission_action' => [
        'view' => 'عرض',
        'manage' => 'إدارة',
        'publish' => 'نشر',
        'view_pii' => 'عرض البيانات الشخصية',
        'update' => 'تعديل',
        'suspend' => 'إيقاف',
        'export' => 'تصدير',
        'process_deletion' => 'معالجة طلبات الحذف',
        'verify' => 'توثيق',
        'reject' => 'رفض',
        'manage_types' => 'إدارة الأنواع',
        'approve' => 'اعتماد',
        'cancel' => 'إلغاء',
        'assign_override' => 'تجاوز الإسناد',
        'pin_override' => 'تجاوز رمز التحقق',
        'moderate' => 'إشراف',
        'reconcile' => 'تسوية',
        'request' => 'طلب',
        'review' => 'مراجعة',
        'adjust' => 'تعديل الرصيد',
        'approve_adjustment' => 'اعتماد التعديلات',
        'view_evidence' => 'عرض الأدلة',
        'export_restricted' => 'تصدير البيانات المقيدة',
        'view_all' => 'عرض الكل',
        'health' => 'حالة النظام',
        'feature_flags' => 'مفاتيح الميزات',
        'app_versions' => 'إصدارات التطبيقات',
        'queues' => 'قوائم الانتظار',
    ],

    'admin_export_status' => [
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد التجهيز',
        'completed' => 'جاهز',
        'failed' => 'فشل',
    ],

    'demo_item_status' => [
        'draft' => 'مسودة',
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'archived' => 'مؤرشف',
    ],

    'demo_item_type' => [
        'standard' => 'عادي',
        'premium' => 'مميز',
        'bundle' => 'باقة',
    ],

    'demo_item_tag' => [
        'new' => 'جديد',
        'popular' => 'شائع',
        'seasonal' => 'موسمي',
        'limited' => 'محدود',
    ],

    'admin_status' => [
        'active' => 'نشط',
        'suspended' => 'موقوف',
    ],

    'admin_login_outcome' => [
        'success' => 'تم تسجيل الدخول',
        'invalid_credentials' => 'بيانات اعتماد غير صحيحة',
        'account_suspended' => 'حساب موقوف',
        'mfa_failed' => 'رمز تحقق خاطئ',
        'throttled' => 'محظور (محاولات كثيرة)',
        'logout' => 'تم تسجيل الخروج',
    ],

    'admin_role' => [
        'super_admin' => 'المشرف الأعلى',
        'operations_admin' => 'مدير العمليات',
        'dispatch_agent' => 'موظف التوزيع والتشغيل المباشر',
        'driver_verification_officer' => 'مسؤول التحقق من السائقين',
        'customer_support_agent' => 'موظف دعم العملاء',
        'safety_incident_officer' => 'مسؤول السلامة والحوادث',
        'finance_manager' => 'مدير المالية',
        'finance_payout_officer' => 'مسؤول مدفوعات المالية',
        'marketing_coupon_manager' => 'مدير التسويق والكوبونات',
        'content_cms_editor' => 'محرر المحتوى',
        'read_only_auditor' => 'محلل / مدقق للقراءة فقط',
        'system_integration_admin' => 'مسؤول النظام والتكامل',
    ],

    'digits_preference' => [
        'auto' => 'اتباع اللغة',
        'western' => 'أرقام غربية (0-9)',
        'arabic_indic' => 'أرقام هندية عربية (٠-٩)',
    ],

    'setting_group' => [
        'localization' => 'اللغة والسوق',
        'support' => 'جهات الدعم',
        'otp' => 'رموز التحقق',
        'booking' => 'الحجوزات والعروض',
        'dispatch' => 'الإرسال',
        'providers' => 'مقدمو الخدمة',
        'safety' => 'الأمان والخصوصية',
        'apps' => 'تطبيقات الجوال',
        'security' => 'أمان المشرفين',
        'rate_limits' => 'حدود الطلبات',
    ],

    'content_status' => [
        'draft' => 'مسودة',
        'published' => 'منشور',
        'archived' => 'مؤرشف',
    ],

    'policy_type' => [
        'terms' => 'الشروط والأحكام',
        'privacy' => 'سياسة الخصوصية',
        'driver_terms' => 'شروط السائقين',
        'coupon_rules' => 'قواعد القسائم',
        'restricted_goods' => 'البضائع المحظورة',
        'cancellation' => 'سياسة الإلغاء',
    ],

    'zone_status' => [
        'draft' => 'مسودة',
        'active' => 'نشطة',
        'suspended' => 'معلّقة',
    ],

    'saved_place_type' => [
        'home' => 'المنزل',
        'work' => 'العمل',
        'other' => 'أخرى',
    ],

    'location_precision' => [
        'exact' => 'عنوان دقيق',
        'street' => 'شارع',
        'approximate' => 'تقريبي',
    ],

    'availability_reason' => [
        'available' => 'متاحة',
        'not_in_zone' => 'غير متوفرة في هذه المنطقة',
        'zone_closed' => 'المنطقة مغلقة في هذا الوقت',
        'category_closed' => 'الخدمة مغلقة في هذا الوقت',
        'override_closed' => 'مغلقة في هذا الوقت داخل هذه المنطقة',
    ],

    'maps_provider_status' => [
        'ok' => 'تعمل',
        'not_configured' => 'غير مهيأة',
        'unavailable' => 'غير متاحة',
    ],

    'service_family_kind' => [
        'passenger_city' => 'رحلات الركاب داخل المدينة',
        'intercity' => 'رحلات بين المدن',
        'courier' => 'التوصيل والطرود',
        'moving' => 'نقل الأثاث',
        'freight' => 'الشحن والبضائع',
        'hourly' => 'التأجير بالساعة',
        'other' => 'أخرى',
    ],

    'booking_type' => [
        'on_demand' => 'فوري',
        'scheduled' => 'مجدول',
        'intercity' => 'بين المدن',
        'parcel' => 'طرد',
        'freight' => 'شحن',
        'hourly' => 'بالساعة',
    ],

    'proof_type' => [
        'photo' => 'صورة',
        'signature' => 'توقيع',
        'recipient_otp' => 'رمز المستلم',
        'pickup_pin' => 'رمز الاستلام',
        'checklist' => 'قائمة تحقق',
        'weight' => 'الوزن',
    ],

    'payment_method_type' => [
        'cash' => 'نقدًا',
        'card' => 'بطاقة',
        'mobile_wallet' => 'محفظة الهاتف',
        'platform_wallet' => 'محفظة المنصة',
    ],

    'visibility_operator' => [
        'eq' => 'يساوي',
        'neq' => 'لا يساوي',
        'in' => 'من بين',
        'not_in' => 'ليس من بين',
        'gt' => 'أكبر من',
        'gte' => 'لا يقل عن',
        'lt' => 'أقل من',
        'lte' => 'لا يزيد عن',
        'filled' => 'تمت الإجابة عنه',
        'empty' => 'فارغ',
    ],

    'form_field_type' => [
        'text' => 'نص قصير',
        'textarea' => 'نص طويل',
        'integer' => 'عدد صحيح',
        'decimal' => 'عدد عشري',
        'boolean' => 'نعم / لا',
        'select' => 'اختيار واحد',
        'multiselect' => 'اختيار متعدد',
        'date' => 'تاريخ',
        'time' => 'وقت',
        'datetime' => 'تاريخ ووقت',
        'contact' => 'جهة اتصال (الاسم والهاتف)',
        'photo' => 'صور',
        'item_checklist' => 'قائمة عناصر',
        'dimensions' => 'الأبعاد (الطول × العرض × الارتفاع)',
        'weight' => 'الوزن (كجم)',
    ],

    'weekday' => [
        '1' => 'الاثنين',
        '2' => 'الثلاثاء',
        '3' => 'الأربعاء',
        '4' => 'الخميس',
        '5' => 'الجمعة',
        '6' => 'السبت',
        '7' => 'الأحد',
    ],

    'provider_type' => [
        'individual_driver' => 'سائق فردي',
        'courier' => 'مندوب توصيل',
        'moving_crew' => 'فريق / سائق نقل أثاث',
        'commercial_transport' => 'مزوّد نقل تجاري',
        'company_fleet' => 'شركة / أسطول',
    ],

    'provider_status' => [
        'draft' => 'مسودة',
        'phone_verified' => 'تم التحقق من الهاتف',
        'profile_submitted' => 'تم إرسال الملف الشخصي',
        'documents_pending' => 'المستندات قيد الاستكمال',
        'under_review' => 'قيد المراجعة',
        'more_information_required' => 'مطلوب معلومات إضافية',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'temporarily_suspended' => 'موقوف مؤقتاً',
        'permanently_suspended' => 'موقوف نهائياً',
        'deletion_pending' => 'الحذف قيد المعالجة',
    ],

    'document_status' => [
        'pending' => 'بانتظار المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'expired' => 'منتهي الصلاحية',
    ],

    'vehicle_status' => [
        'draft' => 'مسودة',
        'under_review' => 'قيد المراجعة',
        'approved' => 'معتمدة',
        'rejected' => 'مرفوضة',
        'suspended' => 'موقوفة',
        'inactive' => 'غير نشطة',
    ],

    'eligibility_reason' => [
        'not_approved' => 'لم يتم اعتماد الطلب بعد.',
        'provider_suspended' => 'حساب المزوّد موقوف.',
        'account_blocked' => 'الحساب محظور أو مقيّد.',
        'document_missing' => 'هناك مستند مطلوب غير مرفوع.',
        'document_not_approved' => 'هناك مستند مطلوب لم يتم اعتماده بعد.',
        'document_expired' => 'انتهت صلاحية مستند مطلوب.',
        'no_approved_vehicle' => 'لا توجد مركبة معتمدة.',
        'vehicle_not_approved' => 'المركبة غير معتمدة.',
        'service_not_approved' => 'لا توجد خدمة معتمدة لهذه الفئة.',
        'vehicle_category_mismatch' => 'فئة المركبة لا تناسب هذه الخدمة.',
        'location_stale' => 'موقعك غير متاح أو قديم.',
        'active_job_exists' => 'لديك مهمة نشطة بالفعل.',
        'cash_debt_limit_exceeded' => 'العمولة غير المسددة تجاوزت الحد المسموح.',
    ],

    'tracking_state' => [
        'off' => 'بدون تتبع',
        'idle' => 'في انتظار الطلبات',
        'to_pickup' => 'في الطريق إلى نقطة الاستلام',
        'active' => 'مهمة نشطة',
    ],

    'location_source' => [
        'gps' => 'GPS',
        'fused' => 'مدمج',
        'network' => 'الشبكة',
    ],

    'location_anomaly' => [
        'impossible_speed' => 'سرعة مستحيلة بين الموقعين',
        'future_timestamp' => 'وقت في المستقبل',
    ],

    'offline_reason' => [
        'provider_choice' => 'اختار مقدم الخدمة إيقاف الاتصال',
        'stale_location' => 'أصبح الموقع قديماً',
        'no_longer_eligible' => 'لم يعد مقدم الخدمة مؤهلاً',
        'account_changed' => 'تغيرت حالة الحساب',
    ],

    'dispatch_response' => [
        'pending' => 'في الانتظار',
        'offered' => 'قدّم عرضاً',
        'declined' => 'رفض',
        'expired' => 'انتهى',
    ],

    'document_subject' => [
        'provider' => 'المزوّد',
        'vehicle' => 'المركبة',
    ],

    'vehicle_capability' => [
        'air_conditioning' => 'تكييف',
        'child_seat' => 'مقعد أطفال',
        'wheelchair_accessible' => 'مهيأة للكراسي المتحركة',
        'pets_allowed' => 'يُسمح بالحيوانات الأليفة',
        'large_luggage' => 'حقائب كبيرة',
        'covered_cargo' => 'صندوق شحن مغطى',
        'tail_lift' => 'رافعة خلفية',
        'refrigerated' => 'مبرّدة',
        'moving_equipment' => 'معدات نقل أثاث',
    ],

    'vehicle_photo_kind' => [
        'front' => 'أمامية',
        'back' => 'خلفية',
        'side' => 'جانبية',
        'interior' => 'داخلية',
        'other' => 'أخرى',
    ],

    'service_approval_status' => [
        'requested' => 'مطلوبة',
        'approved' => 'معتمدة',
        'rejected' => 'مرفوضة',
    ],

    'file_scan_status' => [
        'clean' => 'تم الفحص، سليم',
        'infected' => 'مصاب',
        'skipped' => 'لم يُفحص (الفاحص معطل)',
        'not_scanned' => 'لم يُفحص (لا يوجد فاحص مهيأ)',
    ],

    'onboarding_task' => [
        'complete_profile' => 'أكمل ملفك الشخصي',
        'select_services' => 'اختر الخدمات التي تقدمها',
        'add_vehicle' => 'أضف مركبة',
        'add_vehicle_photos' => 'أضف صور المركبة',
        'submit_vehicle' => 'أرسل المركبة للمراجعة',
        'upload_document' => 'ارفع مستنداً مطلوباً',
        'replace_rejected_document' => 'استبدل مستنداً مرفوضاً',
        'renew_expired_document' => 'جدّد مستنداً منتهي الصلاحية',
        'fix_vehicle' => 'صحّح بيانات المركبة وأرسلها مرة أخرى',
        'accept_policies' => 'وافق على شروط السائقين',
        'submit_application' => 'أرسل طلبك',
    ],

    'verification_decision' => [
        'approve' => 'اعتماد',
        'request_more_information' => 'طلب معلومات إضافية',
        'reject' => 'رفض',
    ],

    'booking_status' => [
        'draft' => 'مسودة',
        'searching_offers' => 'جارٍ البحث عن عروض',
        'offers_received' => 'وصلت عروض',
        'provider_selected' => 'تم اختيار مقدّم الخدمة',
        'provider_heading_to_pickup' => 'مقدّم الخدمة في الطريق إليك',
        'provider_arrived' => 'وصل مقدّم الخدمة',
        'in_progress' => 'قيد التنفيذ',
        'delivery_attempt_failed' => 'فشلت محاولة التسليم',
        'completed_pending_payment' => 'اكتملت وبانتظار الدفع',
        'completed' => 'مكتملة',
        'disputed' => 'محل نزاع',
        'cancelled_by_customer' => 'ألغاها العميل',
        'cancelled_by_provider' => 'ألغاها مقدّم الخدمة',
        'cancelled_by_operations' => 'ألغتها العمليات',
        'expired_no_provider' => 'انتهى البحث دون عروض',
    ],

    'booking_action' => [
        'update_request' => 'تعديل الطلب',
        'select_offer' => 'اختيار سائق',
        'cancel' => 'إلغاء الحجز',
        'share' => 'مشاركة الرحلة',
        'rate' => 'تقييم الرحلة',
        'report' => 'الإبلاغ عن مشكلة',
    ],

    'offer_status' => [
        'submitted' => 'عرض بسعرك',
        'counter_offered' => 'عرض بسعر مختلف',
        'accepted_by_customer' => 'تم اختياره',
        'rejected_by_customer' => 'تم رفضه',
        'withdrawn_by_provider' => 'تم سحبه',
        'expired' => 'انتهت مدته',
        'invalidated_request_changed' => 'تغيّر الطلب',
        'invalidated_provider_unavailable' => 'السائق غير متاح',
        'lost_booking_assigned' => 'تم اختيار سائق آخر',
    ],

    'offer_kind' => [
        'accept_customer_fare' => 'يقبل سعرك',
        'counter_offer' => 'عرض بسعر مختلف',
    ],

    'fare_hint' => [
        'below_reference_estimate' => 'أقل من السعر الإرشادي المعتاد',
        'above_reference_estimate' => 'أعلى من السعر الإرشادي المعتاد',
        'below_recent_offers' => 'أقل من العروض الأخرى',
        'above_recent_offers' => 'أعلى من العروض الأخرى',
    ],

    'offer_invalidation_reason' => [
        'request_changed' => 'تم تعديل الطلب',
        'search_expired' => 'انتهت مدة البحث',
        'booking_cancelled' => 'أُلغي الحجز',
        'provider_unavailable' => 'أصبح السائق غير متاح',
    ],

    'payment_status' => [
        'not_required' => 'لا يلزم دفع',
        'pending' => 'بانتظار الدفع',
        'requires_action' => 'يتطلب إجراء',
        'authorized' => 'تم التفويض',
        'paid' => 'مدفوع',
        'failed' => 'فشل الدفع',
        'partially_refunded' => 'مسترد جزئيًا',
        'refunded' => 'مسترد',
        'cash_due' => 'نقدًا عند التسليم',
        'cash_collected' => 'تم استلام النقد',
        'reconciliation_required' => 'تحتاج إلى تسوية',
    ],

    'fare_formula' => [
        '1' => 'الإصدار 1: أساسي + كيلومتر + دقيقة + توقفات + إضافات',
    ],

    'surcharge_mode' => [
        'fixed' => 'مبلغ ثابت',
        'per_unit' => 'لكل وحدة',
    ],

    'price_factor' => [
        'base' => 'الأجرة الأساسية',
        'distance' => 'المسافة',
        'duration' => 'المدة',
        'stops' => 'التوقفات',
        'surcharge' => 'إضافة',
        'minimum_fare' => 'الحد الأدنى للأجرة',
    ],

    'fare_policy_state' => [
        'current' => 'ساري',
        'scheduled' => 'مجدول',
        'expired' => 'منتهٍ',
        'inactive' => 'معطّل',
    ],

    // @admin-module:enums

    'cancellation_stage' => [
        'searching' => 'جارٍ البحث عن سائق',
        'provider_selected' => 'تم اختيار السائق',
        'heading_to_pickup' => 'السائق في الطريق',
        'arrived' => 'وصل السائق',
    ],

    'cancellation_fee_type' => [
        'none' => 'بدون رسوم',
        'fixed' => 'مبلغ ثابت',
        'percent' => 'نسبة من الأجرة',
    ],

    'cancellation_fee_recipient' => [
        'provider' => 'مقدّم الخدمة',
        'platform' => 'المنصة',
    ],

    'cancellation_reason' => [
        'changed_mind' => 'غيّرت رأيي',
        'wrong_pickup' => 'موقع الاستلام خاطئ',
        'found_another_ride' => 'وجدت وسيلة أخرى',
        'driver_too_far' => 'السائق بعيد جداً',
        'driver_asked_to_cancel' => 'طلب مني السائق الإلغاء',
        'waited_too_long' => 'انتظرت طويلاً',
        'customer_no_show' => 'لم يحضر العميل',
        'customer_unreachable' => 'لا يمكن الوصول إلى العميل',
        'vehicle_problem' => 'مشكلة في المركبة',
        'emergency' => 'حالة طارئة',
        'wrong_request_details' => 'تفاصيل الطلب غير صحيحة',
        'unsafe_pickup' => 'موقع الاستلام غير آمن',
        'other' => 'سبب آخر',
    ],

    'support_flag_reason' => [
        'pin_attempts_exceeded' => 'محاولات خاطئة كثيرة لرمز PIN',
    ],

    'report_category' => [
        'safety' => 'السلامة',
        'behaviour' => 'السلوك',
        'fare_dispute' => 'خلاف على الأجرة',
        'route_or_delay' => 'المسار أو التأخير',
        'lost_item' => 'غرض مفقود',
        'other' => 'أخرى',
    ],

    'report_status' => [
        'open' => 'مفتوح',
        'closed' => 'مغلق',
    ],

    'moderation_status' => [
        'visible' => 'ظاهر',
        'hidden' => 'مخفي',
        'under_review' => 'قيد المراجعة',
    ],

    'rating_tag' => [
        'professional' => 'احترافي',
        'clean_vehicle' => 'مركبة نظيفة',
        'safe_driving' => 'قيادة آمنة',
        'good_communication' => 'تواصل جيد',
        'on_time' => 'في الموعد',
        'careful_with_items' => 'حريص على الأغراض',
        'late' => 'متأخر',
        'unsafe_driving' => 'قيادة غير آمنة',
        'rude' => 'أسلوب فظ',
        'dirty_vehicle' => 'مركبة غير نظيفة',
        'respectful' => 'محترم',
        'clear_instructions' => 'تعليمات واضحة',
        'ready_at_pickup' => 'جاهز عند الاستلام',
        'disrespectful' => 'قليل الاحترام',
        'not_ready_at_pickup' => 'غير جاهز عند الاستلام',
    ],

    'provider_booking_action' => [
        'acknowledge' => 'تأكيد المهمة',
        'arrived' => 'لقد وصلت',
        'verify_start_pin' => 'إدخال رمز الرحلة',
        'start' => 'بدء الرحلة',
        'complete' => 'إنهاء الرحلة',
        'cancel' => 'إلغاء المهمة',
        'rate_customer' => 'تقييم العميل',
        'report' => 'الإبلاغ عن مشكلة',
    ],

    'cancellation_policy_state' => [
        'current' => 'سارية',
        'scheduled' => 'مجدولة',
        'expired' => 'منتهية',
        'inactive' => 'معطّلة',
    ],

    'booking_override_action' => [
        'cancel_by_operations' => 'إلغاء من العمليات',
        'extend_search' => 'تمديد البحث',
        'redispatch_now' => 'إعادة الإرسال الآن',
        'force_transition' => 'فرض الحالة',
        'lift_pin_lock' => 'رفع قفل الرمز',
    ],

    'operations_cancellation_fee' => [
        'waive' => 'إسقاط الرسوم',
        'apply_policy' => 'تطبيق سياسة الإلغاء الخاصة بالحجز',
    ],

    'offer_anomaly' => [
        'very_low' => 'منخفض جدًا',
        'very_high' => 'مرتفع جدًا',
    ],
];
