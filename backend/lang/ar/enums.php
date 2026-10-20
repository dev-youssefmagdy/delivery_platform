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

    // @admin-module:enums
];
