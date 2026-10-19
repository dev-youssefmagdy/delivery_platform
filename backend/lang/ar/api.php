<?php

/*
| API and AJAX envelope messages. `errors.{code}` keys match the stable machine codes in `errors._code`
| (docs/API.md). Never put internal details in these messages.
*/
return [
    'ok' => 'تمت العملية بنجاح.',
    'created' => 'تم الإنشاء بنجاح.',

    'health' => [
        'ok' => 'جميع الخدمات تعمل.',
        'failing' => 'بعض الخدمات لا تعمل حاليًا.',
    ],

    'http' => [
        '400' => 'تعذر تنفيذ الطلب.',
        '401' => 'يجب تسجيل الدخول أولًا.',
        '403' => 'غير مسموح لك بتنفيذ هذا الإجراء.',
        '404' => 'العنصر المطلوب غير موجود.',
        '409' => 'تعارض الطلب مع الحالة الحالية. يرجى التحديث والمحاولة مرة أخرى.',
        '422' => 'البيانات المرسلة غير صالحة.',
        '429' => 'طلبات كثيرة جدًا. يرجى المحاولة لاحقًا.',
        '500' => 'حدث خطأ غير متوقع. يرجى المحاولة لاحقًا.',
    ],

    'errors' => [
        'validation_failed' => 'البيانات المرسلة غير صالحة.',
        'unauthenticated' => 'يجب تسجيل الدخول أولًا.',
        'forbidden' => 'غير مسموح لك بتنفيذ هذا الإجراء.',
        'not_found' => 'العنصر المطلوب غير موجود.',
        'method_not_allowed' => 'طريقة الطلب غير مدعومة.',
        'session_expired' => 'انتهت صلاحية الجلسة. يرجى إعادة تحميل الصفحة.',
        'too_many_requests' => 'طلبات كثيرة جدًا. يرجى المحاولة بعد :seconds ثانية.',
        'service_unavailable' => 'الخدمة غير متاحة مؤقتًا.',
        'server_error' => 'حدث خطأ غير متوقع. يرجى المحاولة لاحقًا.',
        'idempotency_key_required' => 'ترويسة Idempotency-Key مطلوبة لهذا الطلب.',
        'idempotency_key_invalid' => 'يجب أن تكون ترويسة Idempotency-Key معرف UUID صحيحًا.',
        'idempotency_key_reused' => 'تم استخدام مفتاح منع التكرار هذا مع بيانات مختلفة.',
        'idempotency_request_in_progress' => 'طلب مماثل قيد التنفيذ حاليًا. يرجى الانتظار.',
        'stale_version' => 'عدّل شخص آخر هذا السجل. أعد تحميل الصفحة وحاول مرة أخرى.',
        'export_not_ready' => 'ملف التصدير غير متاح للتنزيل.',
        'export_failed' => 'تعذّر إنشاء ملف التصدير.',
        'code_taken' => 'هذا الرمز مستخدم بالفعل.',
        'demo_item_archived' => 'لا يمكن تعديل العناصر المؤرشفة.',
        'invalid_credentials' => 'بيانات الدخول هذه لا تطابق سجلاتنا.',
        'mfa_invalid_code' => 'رمز التحقق غير صحيح.',
        'mfa_session_expired' => 'انتهت خطوة التحقق. يرجى تسجيل الدخول من جديد.',
        'mfa_setup_expired' => 'انتهت جلسة الإعداد. أعد تحميل الصفحة للحصول على رمز QR جديد.',
        'mfa_already_enabled' => 'التحقق بخطوتين مفعّل بالفعل.',
        'mfa_not_enabled' => 'التحقق بخطوتين غير مفعّل.',
        'mfa_required_for_role' => 'التحقق بخطوتين مطلوب لدورك ولا يمكن إيقافه.',
        'mfa_enrolment_required' => 'أعدّ التحقق بخطوتين قبل استخدام لوحة الإدارة.',
        'password_incorrect' => 'كلمة المرور غير صحيحة.',
        'password_reset_invalid' => 'رابط إعادة تعيين كلمة المرور غير صالح أو منتهي.',
        'email_taken' => 'هذا البريد الإلكتروني مستخدم لدى مشرف آخر.',
        'cannot_modify_self' => 'لا يمكنك تنفيذ هذا الإجراء على حسابك.',
        'last_super_admin' => 'لا يمكن إيقاف آخر مشرف أعلى نشط أو حذفه أو تخفيض دوره.',
        'cannot_remove_own_super_admin' => 'لا يمكنك إزالة دور المشرف الأعلى من نفسك.',
        'super_admin_only' => 'المشرف الأعلى وحده يمكنه منح دور المشرف الأعلى أو إزالته.',
        'role_exceeds_own_permissions' => 'لا يمكنك منح صلاحيات لا تملكها بنفسك.',
        'role_name_taken' => 'معرّف الدور مستخدم بالفعل.',
        'role_system_protected' => 'لا يمكن حذف أدوار النظام.',
        'role_in_use' => 'ما زال هذا الدور معيّناً لمشرفين.',
        'role_super_admin_protected' => 'لا يمكن تعديل دور المشرف الأعلى.',
        'admin_already_suspended' => 'هذا المشرف موقوف بالفعل.',
        'admin_already_active' => 'هذا المشرف نشط بالفعل.',
    ],
];
