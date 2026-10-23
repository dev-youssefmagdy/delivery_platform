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
        'assigned' => 'تم التعيين',
        'escalated' => 'تم التصعيد',
        'resolved' => 'تم الحل',
        'reopened' => 'أُعيد فتحه',
        'linked' => 'تم الربط',
        'requested' => 'تم الطلب',
        'reprocessed' => 'أُعيدت المعالجة',
        'cash_collected' => 'حُصّل النقد',
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
        'reversed' => 'عكس قيد',
        'reconciled' => 'تسوية الدفتر',
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
        'export_pii' => 'تصدير أعمدة البيانات الشخصية',
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
        'finance' => 'المالية',
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

    'contact_role' => [
        'sender' => 'المرسل',
        'recipient' => 'المستلم',
        'pickup_contact' => 'جهة الاتصال عند التحميل',
        'dropoff_contact' => 'جهة الاتصال عند التسليم',
    ],

    'custody_status' => [
        'picked_up' => 'تم الاستلام',
        'in_transit' => 'في الطريق',
        'arrived_dropoff' => 'وصل إلى وجهة التسليم',
        'attempt_failed' => 'فشلت محاولة التسليم',
        'returning' => 'في طريق العودة إلى المرسل',
        'delivered' => 'تم التسليم',
        'returned' => 'أُعيد إلى المرسل',
    ],

    'delivery_attempt_outcome' => [
        'recipient_unavailable' => 'المستلم غير متاح',
        'recipient_refused' => 'رفض المستلم الطرد',
        'wrong_address' => 'عنوان خاطئ أو يتعذر الوصول إليه',
        'otp_failed' => 'تعذّر على المستلم إعطاء الرمز',
        'restricted_item' => 'تم العثور على بضاعة محظورة',
        'other' => 'أخرى',
    ],

    'delivery_attempt_resolution' => [
        'retry' => 'إعادة المحاولة',
        'return_to_sender' => 'إعادة الطرد إلى المرسل',
        'operations' => 'تحويل إلى الدعم',
    ],

    'proof_stage' => [
        'pickup' => 'الاستلام',
        'delivery' => 'التسليم',
        'return' => 'الإعادة',
    ],

    'recipient_otp_delivery' => [
        'pending' => 'جارٍ الإرسال',
        'sent' => 'تم الإرسال',
        'failed' => 'فشل',
        'skipped' => 'رقم تجريبي (لم يُرسل)',
    ],

    'cargo_type' => [
        'general' => 'بضائع عامة',
        'building' => 'مواد بناء',
        'food' => 'طعام',
        'machinery' => 'معدات وآلات',
        'documents' => 'مستندات',
        'clothes' => 'ملابس',
        'electronics' => 'إلكترونيات',
        'other' => 'أخرى',
    ],

    'parcel_size' => [
        'small' => 'صغير (مظروف)',
        'medium' => 'متوسط (صندوق حذاء)',
        'large' => 'كبير (حقيبة)',
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
        'cancellation_cooldown' => 'تم إيقافك مؤقتاً بعد تكرار الإلغاءات أو عدم الحضور.',
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
        'view_proofs' => 'عرض الإثباتات',
        'resolve_delivery_attempt' => 'القرار بعد فشل التسليم',
        'chat' => 'محادثة',
        'appeal' => 'اعتراض على الرسوم',
        'pay' => 'الدفع إلكترونياً',
        'tip' => 'ترك إكرامية',
        'update_request' => 'تعديل الطلب',
        'select_offer' => 'اختيار سائق',
        'cancel' => 'إلغاء الحجز',
        'share' => 'مشاركة الرحلة',
        'rate' => 'تقييم الرحلة',
        'request_adjustment' => 'تعديل الأجرة',
        'view_receipt' => 'عرض الإيصال',
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

    'payment_gateway' => [
        'sandbox' => 'بيئة تجريبية',
        'paymob' => 'باي موب',
    ],

    'payment_kind' => [
        'trip' => 'الرحلة',
        'tip' => 'إكرامية',
        'debt' => 'دين المحفظة',
    ],

    'payment_event_type' => [
        'intent_created' => 'بدأ الدفع',
        'status_changed' => 'تغيرت الحالة',
        'webhook_applied' => 'طُبّق إشعار البوابة',
        'webhook_ignored' => 'تجاهل إشعار البوابة',
        'query_applied' => 'طُبّق استعلام البوابة',
        'cash_collected' => 'حُصّل النقد',
        'settled' => 'سُجّل في دفتر الأستاذ',
        'timed_out' => 'لا تأكيد في الوقت المناسب',
        'amount_mismatch' => 'اختلاف في المبلغ',
        'refund_requested' => 'طُلب استرداد',
        'refund_approved' => 'تمت الموافقة على الاسترداد',
        'refund_rejected' => 'رُفض الاسترداد',
        'refund_completed' => 'اكتمل الاسترداد',
        'refund_failed' => 'فشل الاسترداد',
    ],

    'webhook_event_kind' => [
        'payment_succeeded' => 'نجحت الدفعة',
        'payment_failed' => 'فشلت الدفعة',
        'payment_requires_action' => 'الدفعة تحتاج إجراء',
        'refund_succeeded' => 'نجح الاسترداد',
        'refund_failed' => 'فشل الاسترداد',
        'method_saved' => 'حُفظت وسيلة الدفع',
        'unknown' => 'أخرى',
    ],

    'webhook_outcome' => [
        'received' => 'تم الاستلام',
        'processed' => 'تمت المعالجة',
        'duplicate' => 'مكرر',
        'ignored' => 'متجاهَل',
        'unmatched' => 'لا دفعة مطابقة',
        'invalid_signature' => 'توقيع غير صالح',
        'replay_window' => 'خارج نافذة الإعادة',
        'amount_mismatch' => 'اختلاف في المبلغ',
        'failed' => 'فشلت المعالجة',
    ],

    'refund_status' => [
        'pending_approval' => 'بانتظار الموافقة',
        'approved' => 'تمت الموافقة',
        'processing' => 'لدى البوابة',
        'completed' => 'مكتمل',
        'failed' => 'فشل',
        'rejected' => 'مرفوض',
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

    'entry_side' => [
        'debit' => 'مدين',
        'credit' => 'دائن',
    ],
    'ledger_account_type' => [
        'customer_wallet' => 'محفظة عميل',
        'provider_wallet' => 'محفظة مقدم خدمة',
        'platform_revenue' => 'إيراد المنصة',
        'promo_expense' => 'مصروف العروض',
        'partner_receivable' => 'مستحقات الشركاء',
        'gateway_clearing' => 'تسوية بوابة الدفع',
        'payout_clearing' => 'تسوية السحوبات',
        'taxes_payable' => 'ضرائب مستحقة',
        'adjustments' => 'التعديلات',
    ],
    'journal_entry_type' => [
        'booking_settlement_cash' => 'تسوية رحلة نقدية',
        'booking_settlement_gateway' => 'تسوية رحلة مدفوعة',
        'tip' => 'إكرامية',
        'refund' => 'استرداد',
        'payout_reserve' => 'حجز سحب',
        'payout_paid' => 'سحب مدفوع',
        'adjustment' => 'تعديل',
        'cash_settlement' => 'تسوية عمولة نقدية',
        'cancellation_fee' => 'رسوم إلغاء',
        'debt_settlement' => 'تسديد دين المحفظة',
        'reversal' => 'عكس قيد',
    ],
    'payout_status' => [
        'requested' => 'مطلوب',
        'under_review' => 'قيد المراجعة',
        'approved' => 'تمت الموافقة',
        'processing' => 'قيد التحويل',
        'paid' => 'مدفوع',
        'failed' => 'فشل',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغى',
        'reversed' => 'معكوس',
    ],
    'payout_method_type' => [
        'bank_account' => 'حساب بنكي',
        'mobile_wallet' => 'محفظة إلكترونية',
    ],
    'payout_batch_status' => [
        'open' => 'مفتوحة',
        'exported' => 'تم التصدير',
        'completed' => 'مكتملة',
    ],
    'booking_adjustment_type' => [
        'extra_stop' => 'محطة إضافية',
        'waiting' => 'وقت الانتظار',
        'toll_parking' => 'رسوم طريق أو انتظار سيارات (بالتكلفة)',
        'scope_change' => 'تغيير نطاق الخدمة',
        'extra_helper' => 'عامل إضافي',
        'stairs' => 'سلالم أكثر من المتفق عليه',
    ],
    'booking_adjustment_status' => [
        'pending' => 'بانتظار الطرف الآخر',
        'approved' => 'تمت الموافقة',
        'rejected' => 'مرفوض',
        'withdrawn' => 'تم السحب',
        'expired' => 'انتهت المهلة',
        'voided' => 'ألغته العمليات',
    ],
    'approval_status' => [
        'pending' => 'بانتظار الموافقة',
        'approved' => 'تمت الموافقة',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغى',
    ],
    'adjustment_direction' => [
        'credit' => 'إضافة (زيادة الرصيد)',
        'debit' => 'خصم (تخفيض الرصيد)',
    ],
    'cash_reconciliation_status' => [
        'open' => 'مفتوحة',
        'settled' => 'تمت التسوية',
        'cancelled' => 'ملغاة',
    ],
    'cash_settlement_method' => [
        'cash_deposit' => 'إيداع نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'mobile_wallet' => 'محفظة إلكترونية',
    ],
    'commission_type' => [
        'percentage' => 'نسبة',
        'fixed' => 'مبلغ ثابت',
        'hybrid' => 'نسبة مع مبلغ ثابت',
    ],
    'commission_rule_state' => [
        'current' => 'سارية',
        'scheduled' => 'مجدولة',
        'expired' => 'منتهية',
        'inactive' => 'معطّلة',
    ],
    'discount_funding' => [
        'platform' => 'المنصة',
        'provider' => 'مقدم الخدمة',
        'partner' => 'شريك',
    ],
    'reconciliation_status' => [
        'ok' => 'متسق',
        'mismatch' => 'فروق',
    ],
    'commission_scope' => [
        'service_category' => 'الخدمة',
        'zone' => 'المنطقة',
        'provider_tier' => 'مستوى مقدم الخدمة',
        'campaign' => 'الحملة',
    ],

    'notification_channel' => [
        'push' => 'إشعار فوري',
        'sms' => 'رسالة نصية',
        'email' => 'بريد إلكتروني',
        'in_app' => 'صندوق التطبيق',
    ],

    'notification_category' => [
        'booking' => 'الرحلات والحجوزات',
        'offers' => 'العروض والطلبات',
        'account' => 'الحساب والتوثيق',
        'payments' => 'المدفوعات والرسوم',
        'payouts' => 'السحوبات',
        'promotions' => 'القسائم والعروض',
        'support' => 'الدعم',
        'safety' => 'السلامة',
        'marketing' => 'الأخبار والعروض الترويجية',
        'system' => 'النظام',
    ],

    'notification_category_hint' => [
        'booking' => 'تقدّم الرحلة والإلغاءات والتغييرات على حجوزاتك.',
        'offers' => 'عروض السائقين على حجوزاتك، والطلبات الجديدة للسائقين.',
        'account' => 'حالة الطلب والمستندات وحالة الحساب.',
        'payments' => 'المدفوعات والمبالغ المستردة وتغيّر المحفظة ورسوم الإلغاء.',
        'payouts' => 'طلبات السحب والتحويلات.',
        'promotions' => 'القسائم المطبّقة على حجوزاتك.',
        'support' => 'رسائل وردود فريق الدعم.',
        'safety' => 'تحديثات بلاغات السلامة.',
        'marketing' => 'عروض وأخبار منا. متوقفة حتى تفعّلها.',
        'system' => 'رسائل لوحة الإدارة.',
    ],

    'delivery_status' => [
        'queued' => 'في الانتظار',
        'sending' => 'جارٍ الإرسال',
        'sent' => 'تم الإرسال',
        'delivered' => 'تم التسليم',
        'failed' => 'فشل',
        'skipped' => 'تم التخطي',
    ],

    'notification_audience' => [
        'customer' => 'العملاء',
        'provider' => 'السائقون',
        'both' => 'العملاء والسائقون',
        'admin' => 'المشرفون',
    ],

    'campaign_status' => [
        'draft' => 'مسودة',
        'scheduled' => 'مجدولة',
        'sending' => 'جارٍ الإرسال',
        'sent' => 'تم الإرسال',
        'cancelled' => 'ملغاة',
    ],

    'campaign_audience' => [
        'customers' => 'العملاء',
        'providers' => 'السائقون',
        'everyone' => 'الجميع',
    ],

    'notification_event' => [
        'booking.message_received' => 'رسالة محادثة جديدة',
        'delivery.picked_up' => 'تم استلام الطرد',
        'delivery.arrived_dropoff' => 'الوصول إلى وجهة التسليم',
        'delivery.delivered' => 'تم تسليم الطرد',
        'delivery.attempt_failed' => 'فشل محاولة التسليم',
        'delivery.attempt_resolved' => 'تمت معالجة محاولة التسليم',
        'delivery.returned' => 'تمت إعادة الطرد',
        'delivery.recipient_otp_locked' => 'تم قفل رمز المستلم',
        'provider.application_submitted' => 'تم إرسال طلب السائق',
        'provider.application_more_info' => 'طلب السائق يحتاج تعديلات',
        'provider.application_approved' => 'تمت الموافقة على طلب السائق',
        'provider.application_rejected' => 'تم رفض طلب السائق',
        'provider.suspended' => 'تم إيقاف حساب السائق',
        'provider.reactivated' => 'تمت إعادة تفعيل حساب السائق',
        'document.expiring' => 'مستند على وشك الانتهاء',
        'document.expired' => 'انتهى مستند',
        'booking.search_expired' => 'لم يتم العثور على سائق',
        'booking.offer_received' => 'عرض جديد',
        'booking.offer_updated' => 'تم تعديل عرض',
        'booking.provider_selected' => 'تم تأكيد السائق',
        'booking.provider_heading' => 'السائق في الطريق',
        'booking.provider_arrived' => 'وصل السائق',
        'booking.trip_started' => 'بدأت الرحلة',
        'booking.completed' => 'اكتملت الرحلة',
        'booking.cancelled' => 'تم إلغاء الحجز',
        'booking.cancellation_fee_applied' => 'تطبيق رسوم إلغاء',
        'booking.provider_cancelled_redispatch' => 'ألغى السائق والبحث مستمر',
        'booking.operations_update' => 'الدعم حدّث حجزًا',
        'booking.adjustment_requested' => 'طلب تعديل أجرة',
        'booking.adjustment_answered' => 'رد على تعديل أجرة',
        'request.new' => 'طلب جديد بالقرب منك',
        'offer.accepted' => 'قبول العرض',
        'offer.rejected' => 'رفض العرض',
        'offer.expired' => 'انتهاء العرض',
        'payment.pending' => 'الدفع قيد المعالجة',
        'payment.succeeded' => 'تم استلام الدفع',
        'payment.failed' => 'فشل الدفع',
        'payment.refunded' => 'تم رد مبلغ',
        'wallet.adjusted' => 'تعديل المحفظة',
        'cash.debt_notice' => 'مبلغ نقدي مستحق',
        'coupon.applied' => 'تطبيق قسيمة',
        'coupon.rejected' => 'تعذّر تطبيق قسيمة',
        'coupon.expiring' => 'قسيمة على وشك الانتهاء',
        'payout.requested' => 'طلب سحب',
        'payout.approved' => 'الموافقة على سحب',
        'payout.rejected' => 'رفض طلب سحب',
        'payout.paid' => 'تم دفع مبلغ السحب',
        'payout.failed' => 'فشل دفع مبلغ السحب',
        'support.help_message' => 'رسالة مساعدة من الدعم',
        'support.reply' => 'رد من الدعم',
        'support.dispute_update' => 'تحديث على نزاع',
        'safety.case_update' => 'تحديث على بلاغ سلامة',
        'marketing.campaign' => 'حملة تسويقية',
        'safety.incident_raised' => 'بلاغ سلامة جديد (للفريق)',
        'admin.export_ready' => 'التصدير جاهز',
        'admin.export_failed' => 'فشل التصدير',
        'review.hidden' => 'تم إخفاء تقييم',
        'review.appeal_decided' => 'تم البت في اعتراض على تقييم',
        'risk.signal_raised' => 'تنبيه مخاطر (للفريق)',
        'lead.received' => 'طلب انضمام جديد (للفريق)',
    ],

    'content_workflow_status' => [
        'draft' => 'مسودة',
        'in_review' => 'قيد المراجعة',
        'scheduled' => 'مجدول',
        'published' => 'منشور',
        'archived' => 'مؤرشف',
    ],

    'content_workflow_action' => [
        'submit' => 'إرسال للمراجعة',
        'reject' => 'إعادة إلى مسودة',
        'publish' => 'نشر',
        'schedule' => 'جدولة',
        'revert' => 'إعادة إلى مسودة',
        'archive' => 'أرشفة',
        'restore' => 'استعادة كمسودة',
    ],

    'content_type' => [
        'page' => 'صفحة',
        'article' => 'مقال',
        'faq' => 'سؤال شائع',
        'banner' => 'لافتة',
        'menu' => 'قائمة',
    ],

    'page_kind' => [
        'home' => 'الصفحة الرئيسية',
        'generic' => 'صفحة عامة',
        'service' => 'صفحة خدمة',
        'city' => 'صفحة مدينة',
    ],

    'article_type' => [
        'help' => 'مركز المساعدة',
        'blog' => 'المدونة',
    ],

    'category_kind' => [
        'faq' => 'الأسئلة الشائعة',
        'article' => 'المقالات',
    ],

    'body_format' => [
        'markdown' => 'Markdown',
        'html' => 'HTML مقيّد',
    ],

    'banner_placement' => [
        'app_home' => 'التطبيق: الشاشة الرئيسية',
        'app_booking' => 'التطبيق: شاشة الحجز',
        'web_home' => 'الموقع: الصفحة الرئيسية',
        'web_top_bar' => 'الموقع: الشريط العلوي',
        'web_services' => 'الموقع: صفحات الخدمات',
    ],

    'banner_kind' => [
        'banner' => 'لافتة (صورة)',
        'announcement' => 'إعلان (شريط نصي)',
    ],

    'banner_audience' => [
        'all' => 'الجميع',
        'customer' => 'العملاء',
        'provider' => 'السائقون ومقدمو الخدمة',
    ],

    'menu_location' => [
        'header' => 'رأس الموقع',
        'footer_explore' => 'التذييل: استكشف',
        'footer_legal' => 'التذييل: قانوني',
    ],

    'app_platform' => [
        'android' => 'Google Play',
        'ios' => 'App Store',
        'huawei' => 'Huawei AppGallery',
    ],

    'app_kind' => [
        'customer' => 'تطبيق العميل',
        'driver' => 'تطبيق السائق',
    ],

    'block_type' => [
        'hero' => 'الواجهة الرئيسية',
        'rich_text' => 'نص',
        'features' => 'المزايا',
        'steps' => 'الخطوات',
        'cta' => 'دعوة لاتخاذ إجراء',
        'faq' => 'الأسئلة الشائعة',
        'cities' => 'المدن',
        'app_download' => 'تحميل التطبيق',
    ],

    'locale_completeness' => [
        'complete' => 'العربية والإنجليزية',
        'arabic_only' => 'العربية فقط',
    ],

    // @admin-module:enums

    'cancellation_stage' => [
        'searching' => 'جارٍ البحث عن سائق',
        'provider_selected' => 'تم اختيار السائق',
        'heading_to_pickup' => 'السائق في الطريق',
        'arrived' => 'وصل السائق',
        'no_show' => 'عدم الحضور',
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
        'driver_no_show' => 'السائق لم يصل',
        'customer_no_show' => 'لم يحضر العميل',
        'customer_unreachable' => 'لا يمكن الوصول إلى العميل',
        'vehicle_problem' => 'مشكلة في المركبة',
        'emergency' => 'حالة طارئة',
        'wrong_request_details' => 'تفاصيل الطلب غير صحيحة',
        'unsafe_pickup' => 'موقع الاستلام غير آمن',
        'other' => 'سبب آخر',
    ],

    'support_flag_reason' => [
        'recipient_otp_attempts_exceeded' => 'محاولات خاطئة كثيرة لرمز المستلم',
        'delivery_attempt_escalated' => 'فشل التسليم يحتاج إلى الدعم',
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
        'upload_proof' => 'إضافة إثبات',
        'mark_in_transit' => 'في الطريق إلى المستلم',
        'arrived_dropoff' => 'وصلت إلى وجهة التسليم',
        'verify_recipient_otp' => 'إدخال رمز المستلم',
        'resend_recipient_otp' => 'إعادة إرسال الرمز',
        'report_delivery_attempt' => 'الإبلاغ عن فشل التسليم',
        'resolve_delivery_attempt' => 'القرار بعد فشل التسليم',
        'chat' => 'محادثة',
        'appeal' => 'اعتراض على الرسوم',
        'confirm_cash_collected' => 'تأكيد تحصيل النقد',
        'request_adjustment' => 'تعديل الأجرة',
        'view_receipt' => 'عرض الإيصال',
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
        'lift_recipient_otp_lock' => 'رفع قفل رمز المستلم',
        'resolve_delivery_attempt' => 'حسم محاولة تسليم فاشلة',
    ],

    'operations_cancellation_fee' => [
        'waive' => 'إسقاط الرسوم',
        'apply_policy' => 'تطبيق سياسة الإلغاء الخاصة بالحجز',
    ],

    'offer_anomaly' => [
        'very_low' => 'منخفض جدًا',
        'very_high' => 'مرتفع جدًا',
    ],

    'coupon_status' => [
        'draft' => 'مسودة',
        'scheduled' => 'مجدول',
        'active' => 'فعّال',
        'paused' => 'موقوف مؤقتًا',
        'exhausted' => 'مستنفد',
        'expired' => 'منتهي',
        'archived' => 'مؤرشف',
    ],

    'discount_type' => [
        'fixed' => 'مبلغ ثابت',
        'percentage' => 'نسبة مئوية',
    ],

    'funding_source' => [
        'platform' => 'المنصة',
        'provider' => 'مقدم الخدمة',
        'partner' => 'الشريك',
        'split' => 'مقسّم',
    ],

    'coupon_reservation_status' => [
        'reserved' => 'محجوز',
        'released' => 'مُفرج عنه',
        'committed' => 'مؤكد',
        'reversed' => 'معكوس',
    ],

    'coupon_redemption_status' => [
        'committed' => 'مؤكد',
        'reversed' => 'معكوس',
    ],

    'coupon_attempt_result' => [
        'eligible' => 'مؤهل',
        'rejected' => 'مرفوض',
        'reserved' => 'محجوز',
        'reservation_failed' => 'فشل الحجز',
    ],

    'coupon_attempt_context' => [
        'validate' => 'تحقق',
        'booking' => 'حجز',
        'automatic' => 'تلقائي',
        'admin_test' => 'اختبار مشرف',
    ],

    'coupon_scope_type' => [
        'service_family' => 'فئة خدمة',
        'service_category' => 'خدمة',
        'vehicle_category' => 'فئة مركبة',
        'city' => 'مدينة',
        'zone' => 'منطقة',
    ],

    'coupon_segment' => [
        'new' => 'عملاء جدد',
        'returning' => 'عملاء حاليون',
    ],

    'coupon_reason' => [
        'eligible' => 'مؤهل',
        'not_found' => 'الكوبون غير موجود',
        'not_active' => 'الكوبون غير مفعّل',
        'not_started' => 'الكوبون لم يبدأ بعد',
        'expired' => 'الكوبون منتهي',
        'exhausted' => 'الكوبون استُنفد',
        'category_not_eligible' => 'الخدمة غير مؤهلة',
        'service_family_not_covered' => 'فئة الخدمة غير مشمولة',
        'service_category_not_covered' => 'الخدمة غير مشمولة',
        'service_excluded' => 'الخدمة مستثناة',
        'vehicle_not_covered' => 'المركبة غير مشمولة',
        'city_not_covered' => 'المدينة غير مشمولة',
        'zone_not_covered' => 'المنطقة غير مشمولة',
        'payment_method_not_covered' => 'طريقة الدفع غير مشمولة',
        'new_customers_only' => 'للعملاء الجدد فقط',
        'returning_customers_only' => 'للعملاء الحاليين فقط',
        'outside_schedule' => 'خارج الجدول الزمني',
        'below_minimum_fare' => 'أقل من الحد الأدنى للأجرة',
        'customer_limit_reached' => 'تم بلوغ حد العميل',
        'global_limit_reached' => 'تم بلوغ حد الاستخدام',
        'budget_exhausted' => 'الميزانية استُنفدت',
        'stacking_not_allowed' => 'لا يمكن دمجه',
        'currency_mismatch' => 'عملة غير متطابقة',
        'no_discount' => 'لا يوجد خصم',
    ],

    'ticket_category' => [
        'booking' => 'الحجز',
        'fare_extra_charge' => 'الأجرة أو رسوم إضافية',
        'payment_refund' => 'الدفع أو الاسترداد',
        'coupon' => 'القسيمة',
        'driver_provider' => 'السائق أو مقدم الخدمة',
        'customer_conduct' => 'سلوك العميل',
        'lost_item' => 'غرض مفقود',
        'damaged_goods' => 'بضاعة تالفة',
        'delivery_failure' => 'تعذر التسليم',
        'safety_harassment' => 'السلامة أو التحرش',
        'account' => 'الحساب',
        'verification' => 'التحقق',
        'payout' => 'السحب',
        'technical' => 'مشكلة تقنية',
        'other' => 'أخرى',
    ],

    'ticket_priority' => ['low' => 'منخفضة', 'normal' => 'عادية', 'high' => 'عالية', 'urgent' => 'عاجلة'],

    'ticket_status' => [
        'open' => 'مفتوحة',
        'in_progress' => 'قيد المعالجة',
        'waiting_user' => 'بانتظار المستخدم',
        'escalated' => 'مصعّدة',
        'resolved' => 'تم الحل',
        'closed' => 'مغلقة',
    ],

    'ticket_group' => ['support' => 'الدعم', 'finance' => 'المالية', 'safety' => 'السلامة', 'operations' => 'العمليات'],

    'ticket_visibility' => ['public' => 'ظاهرة للمستخدم', 'internal' => 'ملاحظة داخلية'],

    'ticket_source' => ['app' => 'التطبيق', 'report' => 'بلاغ على حجز', 'appeal' => 'اعتراض على رسوم', 'admin' => 'لوحة الإدارة'],

    'ticket_event_type' => [
        'created' => 'إنشاء التذكرة',
        'reply' => 'رد',
        'internal_note' => 'ملاحظة داخلية',
        'assigned' => 'تعيين',
        'unassigned' => 'إلغاء التعيين',
        'status_changed' => 'تغيير الحالة',
        'priority_changed' => 'تغيير الأولوية',
        'escalated' => 'تصعيد',
        'resolved' => 'حل',
        'reopened' => 'إعادة فتح',
        'linked' => 'ربط عملية مالية',
        'sla_breached' => 'تجاوز مهلة الرد',
    ],
    'scope_item' => [
        'helpers' => 'العمال',
        'stairs' => 'السلالم',
        'extra_stops' => 'التوقفات الإضافية',
        'waiting_allowance' => 'مدة الانتظار المسموحة',
        'tolls' => 'رسوم الطرق',
        'parking' => 'موقف السيارات',
        'packing' => 'التغليف',
        'assembly' => 'الفك والتركيب',
        'loading_equipment' => 'معدات التحميل',
    ],
    'cost_treatment' => [
        'included' => 'مشمول في السعر',
        'at_cost_with_evidence' => 'بالتكلفة الفعلية مع إيصال',
        'excluded' => 'غير مشمول',
    ],
];
