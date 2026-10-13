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
    ],
];
