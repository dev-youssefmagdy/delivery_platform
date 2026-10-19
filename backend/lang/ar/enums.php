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
    ],

    'health_status' => [
        'ok' => 'يعمل',
        'degraded' => 'أداء منخفض',
        'failing' => 'متوقف',
        'not_configured' => 'غير مهيأ',
    ],

    'admin_permission' => [
        'label' => ':group: :action',
    ],

    'admin_permission_group' => [
        'dashboard' => 'لوحة المعلومات',
        'admins' => 'المشرفون',
        'roles' => 'الأدوار',
        'audit_logs' => 'سجل التدقيق',
        'login_activity' => 'نشاط تسجيل الدخول',
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

    // @admin-module:enums
];
