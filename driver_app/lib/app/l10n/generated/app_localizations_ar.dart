// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appName => 'سائق المنصة';

  @override
  String get splashLoading => 'جارٍ التحميل…';

  @override
  String get languageTitle => 'اختر لغتك';

  @override
  String get languageSubtitle => 'يمكنك تغيير اللغة لاحقًا من الإعدادات.';

  @override
  String get languageArabicName => 'العربية';

  @override
  String get languageEnglishName => 'English';

  @override
  String get actionContinue => 'متابعة';

  @override
  String get actionSkip => 'تخطي';

  @override
  String get actionNext => 'التالي';

  @override
  String get actionGetStarted => 'ابدأ الآن';

  @override
  String get actionRetry => 'إعادة المحاولة';

  @override
  String get onboarding1Title => 'استقبل الطلبات';

  @override
  String get onboarding1Body => 'اعمل متى شئت واستقبل طلبات قريبة منك.';

  @override
  String get onboarding2Title => 'قدّم عرضك';

  @override
  String get onboarding2Body => 'اقبل سعر العميل أو اقترح سعرًا مناسبًا لك.';

  @override
  String get onboarding3Title => 'اربح بشفافية';

  @override
  String get onboarding3Body => 'تابع أرباحك وعمولتك بوضوح في كل رحلة.';

  @override
  String get authTitle => 'تسجيل الدخول';

  @override
  String get homeTitle => 'الرئيسية';

  @override
  String get homeChangeLanguage => 'تغيير اللغة';

  @override
  String get offlineBanner => 'لا يوجد اتصال بالإنترنت';

  @override
  String get errorGenericTitle => 'حدث خطأ ما';

  @override
  String get errorGenericBody => 'تعذّر إكمال الطلب. حاول مرة أخرى.';

  @override
  String get errorOfflineTitle => 'لا يوجد اتصال';

  @override
  String get errorOfflineBody => 'تحقق من اتصالك بالإنترنت ثم أعد المحاولة.';

  @override
  String get errorSessionExpiredTitle => 'انتهت الجلسة';

  @override
  String get errorSessionExpiredBody => 'سجّل الدخول مرة أخرى للمتابعة.';

  @override
  String errorRequestId(String id) {
    return 'رقم المرجع: $id';
  }

  @override
  String get emptyTitle => 'لا توجد بيانات';

  @override
  String get loadingLabel => 'جارٍ التحميل';

  @override
  String get maintenanceTitle => 'التطبيق تحت الصيانة';

  @override
  String get maintenanceBody => 'سنعود قريبًا. نعتذر عن الإزعاج.';

  @override
  String get forceUpdateTitle => 'تحديث مطلوب';

  @override
  String get forceUpdateBody => 'يرجى تحديث التطبيق إلى أحدث إصدار للمتابعة.';

  @override
  String get validationRequired => 'هذا الحقل مطلوب';

  @override
  String get actionCheckAgain => 'تحقق مرة أخرى';

  @override
  String get supportContactLabel => 'الدعم';

  @override
  String forceUpdateRequiredVersion(String version) {
    return 'الإصدار المطلوب: $version';
  }

  @override
  String get authPhoneTitle => 'أدخل رقم هاتفك';

  @override
  String get authPhoneSubtitle => 'سنرسل لك رمز تحقق عبر رسالة نصية.';

  @override
  String get authPhoneLabel => 'رقم الهاتف';

  @override
  String get authPhoneInvalid => 'أدخل رقم هاتف صحيحاً';

  @override
  String get authSendCode => 'إرسال الرمز';

  @override
  String get otpTitle => 'رمز التحقق';

  @override
  String otpSubtitle(int length, String phone) {
    return 'أدخل الرمز المكوّن من $length أرقام المرسل إلى $phone';
  }

  @override
  String otpResendIn(int seconds) {
    return 'إعادة الإرسال خلال $seconds ث';
  }

  @override
  String get otpResend => 'إعادة إرسال الرمز';

  @override
  String get otpChangeNumber => 'تغيير الرقم';

  @override
  String get otpErrorInvalid => 'الرمز غير صحيح.';

  @override
  String get otpErrorExpired => 'انتهت صلاحية الرمز. اطلب رمزاً جديداً.';

  @override
  String get otpErrorLocked => 'محاولات خاطئة كثيرة. اطلب رمزاً جديداً.';

  @override
  String otpErrorTooManyRequests(int seconds) {
    return 'محاولات كثيرة. حاول بعد $seconds ثانية.';
  }

  @override
  String get otpErrorDeliveryFailed => 'تعذر إرسال الرمز. حاول لاحقاً.';

  @override
  String get statusSuspendedTitle => 'الحساب موقوف';

  @override
  String get statusSuspendedBody => 'حسابك موقوف. يرجى التواصل مع الدعم.';

  @override
  String get statusRestrictedTitle => 'الحساب مقيّد';

  @override
  String get statusRestrictedBody =>
      'بعض الميزات محدودة على حسابك. تواصل مع الدعم لمعرفة التفاصيل.';

  @override
  String get statusDeletionPendingTitle => 'تم طلب الحذف';

  @override
  String get statusDeletionPendingBody =>
      'حسابك بانتظار الحذف. يمكنك إلغاء الطلب ما دام قيد المعالجة.';

  @override
  String get statusVerificationTitle => 'التحقق مطلوب';

  @override
  String get statusVerificationBody =>
      'يجب التحقق من حسابك قبل المتابعة. تواصل مع الدعم.';

  @override
  String get statusDeletedTitle => 'تم حذف الحساب';

  @override
  String get statusDeletedBody => 'تم حذف هذا الحساب.';

  @override
  String get statusCancelDeletion => 'إلغاء طلب الحذف';

  @override
  String get homeLogout => 'تسجيل الخروج';

  @override
  String get providerOnboardingTitle => 'طلب الانضمام كسائق';

  @override
  String get actionBack => 'رجوع';

  @override
  String get actionSave => 'حفظ';

  @override
  String get actionClose => 'إغلاق';

  @override
  String get actionRefresh => 'تحديث';

  @override
  String get actionRemove => 'إزالة';

  @override
  String get actionEdit => 'تعديل';

  @override
  String get actionDelete => 'حذف';

  @override
  String get actionCancel => 'إلغاء';

  @override
  String get photoCamera => 'التقاط صورة';

  @override
  String get photoGallery => 'اختيار من المعرض';

  @override
  String get obStepProfile => 'الملف الشخصي';

  @override
  String get obStepServices => 'الخدمات';

  @override
  String get obStepVehicles => 'المركبات';

  @override
  String get obStepDocuments => 'المستندات';

  @override
  String get obStepDeclarations => 'الإقرارات';

  @override
  String get obStepReview => 'المراجعة';

  @override
  String obStepCounter(int current, int total) {
    return 'الخطوة $current من $total';
  }

  @override
  String get obStepDone => 'مكتملة';

  @override
  String get obProfileTitle => 'عرّفنا بنفسك';

  @override
  String get obProfileIntro =>
      'هذه البيانات مطلوبة لمراجعة طلبك. يمكنك تعديلها قبل الإرسال.';

  @override
  String get obProfileName => 'الاسم الكامل';

  @override
  String get obProfileDob => 'تاريخ الميلاد';

  @override
  String get obProfileDobPick => 'اختر التاريخ';

  @override
  String get obProfileType => 'نوع مقدم الخدمة';

  @override
  String get obProfileCity => 'المدينة';

  @override
  String get obProfileCityPick => 'اختر المدينة';

  @override
  String get obProfileSave => 'حفظ ومتابعة';

  @override
  String get obProfileCitiesEmpty => 'لا توجد مدن متاحة حالياً.';

  @override
  String get obTypeIndividualDriver => 'سائق فردي';

  @override
  String get obTypeCourier => 'مندوب توصيل';

  @override
  String get obTypeMovingCrew => 'فريق / سائق نقل أثاث';

  @override
  String get obTypeCommercialTransport => 'مزوّد نقل تجاري';

  @override
  String get obTypeCompanyFleet => 'شركة / أسطول';

  @override
  String get obServicesTitle => 'ما الخدمات التي تقدمها؟';

  @override
  String get obServicesIntro =>
      'اختر خدمة واحدة على الأقل. يمكنك إضافة المزيد لاحقاً وتُراجع كل خدمة قبل اعتمادها.';

  @override
  String get obServicesEmpty => 'لا توجد خدمات متاحة حالياً.';

  @override
  String get obServicesSave => 'حفظ ومتابعة';

  @override
  String get obServiceStatusApproved => 'معتمدة';

  @override
  String get obServiceStatusRejected => 'مرفوضة';

  @override
  String get obServiceStatusRequested => 'قيد المراجعة';

  @override
  String get obVehiclesTitle => 'مركباتك';

  @override
  String get obVehiclesIntro =>
      'أضف مركبة واحدة على الأقل بصورها ومستنداتها ثم أرسلها للمراجعة.';

  @override
  String get obVehiclesEmpty => 'لم تضف مركبة بعد.';

  @override
  String get obVehicleAdd => 'إضافة مركبة';

  @override
  String get obVehicleFormTitle => 'بيانات المركبة';

  @override
  String get obVehicleCategory => 'فئة المركبة';

  @override
  String get obVehicleCategoryPick => 'اختر الفئة';

  @override
  String get obVehicleMake => 'الماركة';

  @override
  String get obVehicleModel => 'الطراز';

  @override
  String get obVehicleYear => 'سنة الصنع';

  @override
  String get obVehicleColor => 'اللون';

  @override
  String get obVehiclePlate => 'رقم اللوحة';

  @override
  String get obVehiclePassengers => 'عدد الركاب';

  @override
  String get obVehiclePayload => 'الحمولة (كجم)';

  @override
  String get obVehicleVolume => 'حجم الشحن (م³)';

  @override
  String get obVehicleCapabilities => 'القدرات الخاصة';

  @override
  String get obVehicleSave => 'حفظ المركبة';

  @override
  String get obVehiclePhotos => 'الصور';

  @override
  String obVehiclePhotosHint(int count) {
    return 'أضف $count صور على الأقل (أمامية وخلفية).';
  }

  @override
  String get obVehiclePhotoAdd => 'إضافة صورة';

  @override
  String get obVehiclePhotoKindTitle => 'أي جزء من المركبة؟';

  @override
  String get obPhotoKindFront => 'أمامية';

  @override
  String get obPhotoKindBack => 'خلفية';

  @override
  String get obPhotoKindSide => 'جانبية';

  @override
  String get obPhotoKindInterior => 'داخلية';

  @override
  String get obPhotoKindOther => 'أخرى';

  @override
  String get obVehicleDocuments => 'مستندات المركبة';

  @override
  String get obVehicleSubmit => 'إرسال المركبة للمراجعة';

  @override
  String get obVehicleDeleteTitle => 'إزالة هذه المركبة؟';

  @override
  String get obVehicleDeleteBody => 'تُحذف المركبة وصورها ومستنداتها المرفوعة.';

  @override
  String get obVehicleSubmitted => 'تم إرسال المركبة للمراجعة.';

  @override
  String get obVehicleLockedHint => 'لا يمكن تعديل المركبة وهي قيد المراجعة.';

  @override
  String get obVehicleStatusDraft => 'مسودة';

  @override
  String get obVehicleStatusUnderReview => 'قيد المراجعة';

  @override
  String get obVehicleStatusApproved => 'معتمدة';

  @override
  String get obVehicleStatusRejected => 'مرفوضة';

  @override
  String get obVehicleStatusSuspended => 'موقوفة';

  @override
  String get obVehicleStatusInactive => 'غير نشطة';

  @override
  String obVehicleRejectedReason(String reason) {
    return 'سبب الرفض: $reason';
  }

  @override
  String get obCapAirConditioning => 'تكييف';

  @override
  String get obCapChildSeat => 'مقعد أطفال';

  @override
  String get obCapWheelchair => 'مهيأة للكراسي المتحركة';

  @override
  String get obCapPets => 'يُسمح بالحيوانات الأليفة';

  @override
  String get obCapLargeLuggage => 'حقائب كبيرة';

  @override
  String get obCapCoveredCargo => 'صندوق شحن مغطى';

  @override
  String get obCapTailLift => 'رافعة خلفية';

  @override
  String get obCapRefrigerated => 'مبرّدة';

  @override
  String get obCapMovingEquipment => 'معدات نقل أثاث';

  @override
  String get obDocumentsTitle => 'المستندات المطلوبة';

  @override
  String get obDocumentsIntro =>
      'ارفع صورة واضحة لكل مستند. تُراجع المستندات يدوياً وتظهر هنا حالة كل منها.';

  @override
  String get obDocumentsEmpty => 'لا توجد مستندات مطلوبة لك حالياً.';

  @override
  String get obDocOptional => 'اختياري';

  @override
  String get obDocStateMissing => 'لم يُرفع';

  @override
  String get obDocStatePending => 'بانتظار المراجعة';

  @override
  String get obDocStateApproved => 'معتمد';

  @override
  String get obDocStateRejected => 'مرفوض';

  @override
  String get obDocStateExpired => 'منتهي الصلاحية';

  @override
  String obDocExpires(String date) {
    return 'ينتهي في $date';
  }

  @override
  String obDocReason(String reason) {
    return 'السبب: $reason';
  }

  @override
  String get obDocUpload => 'رفع';

  @override
  String get obDocReplace => 'رفع مرة أخرى';

  @override
  String get obDocRenew => 'تجديد';

  @override
  String get obDocFormTitle => 'رفع مستند';

  @override
  String get obDocPhoto => 'صورة المستند';

  @override
  String get obDocChoosePhoto => 'التقط أو اختر صورة';

  @override
  String get obDocChangePhoto => 'تغيير الصورة';

  @override
  String get obDocNumber => 'رقم المستند';

  @override
  String get obDocIssuedAt => 'تاريخ الإصدار';

  @override
  String get obDocExpiresAt => 'تاريخ الانتهاء';

  @override
  String get obDocSend => 'إرسال المستند';

  @override
  String get obDocSent => 'تم رفع المستند وسيُراجع قريباً.';

  @override
  String obDocHint(String types, int size) {
    return 'الأنواع المسموحة: $types. الحد الأقصى $size كيلوبايت.';
  }

  @override
  String get obDocPhotoRequired => 'اختر صورة المستند أولاً.';

  @override
  String get obDeclTitle => 'الإقرارات والشروط';

  @override
  String get obDeclIntro =>
      'اقرأ الشروط ووافق عليها لإكمال طلبك. يُسجَّل إصدار الشروط ووقت الموافقة.';

  @override
  String obDeclAccept(String title) {
    return 'قرأت وأوافق على: $title';
  }

  @override
  String get obDeclAcceptAll => 'موافقة ومتابعة';

  @override
  String get obDeclRead => 'قراءة النص';

  @override
  String get obDeclNone => 'لا توجد شروط تنتظر موافقتك.';

  @override
  String get obDeclAccepted => 'تمت الموافقة على كل الشروط.';

  @override
  String get obPolicyTerms => 'شروط الاستخدام';

  @override
  String get obPolicyPrivacy => 'سياسة الخصوصية';

  @override
  String get obPolicyDriverTerms => 'شروط السائقين';

  @override
  String get obPolicyOther => 'سياسة';

  @override
  String get obReviewTitle => 'المراجعة والإرسال';

  @override
  String get obReviewReady => 'كل شيء جاهز. أرسل طلبك ليراجعه فريقنا.';

  @override
  String get obReviewTodo => 'ما زال أمامك:';

  @override
  String get obReviewSubmit => 'إرسال الطلب';

  @override
  String get obReviewSubmitted => 'تم إرسال طلبك للمراجعة.';

  @override
  String get obGoToStep => 'انتقل';

  @override
  String get obTaskCompleteProfile => 'أكمل ملفك الشخصي';

  @override
  String get obTaskSelectServices => 'اختر الخدمات التي تقدمها';

  @override
  String get obTaskAddVehicle => 'أضف مركبة';

  @override
  String get obTaskAddVehiclePhotos => 'أضف صور المركبة';

  @override
  String get obTaskSubmitVehicle => 'أرسل المركبة للمراجعة';

  @override
  String obTaskUploadDocument(String name) {
    return 'ارفع: $name';
  }

  @override
  String obTaskReplaceDocument(String name) {
    return 'استبدل المستند المرفوض: $name';
  }

  @override
  String obTaskRenewDocument(String name) {
    return 'جدّد المستند المنتهي: $name';
  }

  @override
  String get obTaskFixVehicle => 'صحّح بيانات المركبة وأرسلها مرة أخرى';

  @override
  String get obTaskAcceptPolicies => 'وافق على شروط السائقين';

  @override
  String get obTaskSubmitApplication => 'أرسل طلبك';

  @override
  String get vsTitle => 'حالة التحقق';

  @override
  String get vsUnderReviewTitle => 'نراجع طلبك';

  @override
  String get vsUnderReviewBody =>
      'سيراجع فريقنا مستنداتك ومركبتك. سنخبرك بالنتيجة، ولا يلزمك أي إجراء الآن.';

  @override
  String get vsMoreInfoTitle => 'نحتاج إلى معلومات إضافية';

  @override
  String get vsMoreInfoBody => 'صحّح ما يلي ثم أعد إرسال طلبك للمراجعة.';

  @override
  String get vsRejectedTitle => 'لم يُقبل طلبك';

  @override
  String get vsRejectedBody => 'يمكنك تصحيح الأسباب أدناه وإرسال طلبك مجدداً.';

  @override
  String get vsApprovedTitle => 'تم اعتماد طلبك';

  @override
  String get vsApprovedBody =>
      'تم اعتمادك كمقدم خدمة. تأكد من بقاء مستنداتك صالحة.';

  @override
  String get vsSuspendedTitle => 'حسابك موقوف مؤقتاً';

  @override
  String get vsSuspendedBody =>
      'لا يمكنك العمل حتى يُرفع الإيقاف. تواصل مع الدعم لمعرفة المزيد.';

  @override
  String get vsPermanentTitle => 'حسابك موقوف نهائياً';

  @override
  String get vsPermanentBody =>
      'لا يمكنك العمل بهذا الحساب. تواصل مع الدعم لمعرفة المزيد.';

  @override
  String get vsDeletionTitle => 'طلب حذف الحساب قيد المعالجة';

  @override
  String get vsDeletionBody => 'أُرسل طلب حذف حسابك وهو قيد المعالجة.';

  @override
  String get vsDraftTitle => 'أكمل طلبك';

  @override
  String get vsDraftBody => 'لم تُرسل طلبك بعد. تابع من حيث توقفت.';

  @override
  String get vsReviewerReason => 'رسالة من فريق المراجعة';

  @override
  String get vsTasksTitle => 'ما يجب فعله الآن';

  @override
  String get vsNoTasks => 'لا توجد إجراءات مطلوبة.';

  @override
  String get vsDocumentsTitle => 'المستندات';

  @override
  String get vsVehiclesTitle => 'المركبات';

  @override
  String get vsExpiringTitle => 'مستندات تنتهي قريباً';

  @override
  String get vsEligibilityTitle => 'الجاهزية للعمل';

  @override
  String get vsEligible => 'أنت تستوفي كل شروط العمل.';

  @override
  String get vsNotEligible => 'لم تكتمل جاهزيتك للعمل بعد:';

  @override
  String get vsContinue => 'متابعة الطلب';

  @override
  String get vsUpdate => 'تحديث الطلب';

  @override
  String get vsSupportTitle => 'تحتاج مساعدة؟';

  @override
  String vsSupportPhone(String phone) {
    return 'اتصل بالدعم: $phone';
  }

  @override
  String vsSupportEmail(String email) {
    return 'راسل الدعم: $email';
  }

  @override
  String vsSubmittedOn(String date) {
    return 'أُرسل في $date';
  }

  @override
  String get erNotApproved => 'لم يتم اعتماد طلبك بعد.';

  @override
  String get erProviderSuspended => 'حسابك موقوف.';

  @override
  String get erAccountBlocked => 'الحساب محظور أو مقيّد.';

  @override
  String get erDocumentMissing => 'هناك مستند مطلوب غير مرفوع.';

  @override
  String get erDocumentNotApproved => 'هناك مستند مطلوب لم يُعتمد بعد.';

  @override
  String get erDocumentExpired => 'انتهت صلاحية مستند مطلوب.';

  @override
  String get erNoApprovedVehicle => 'لا توجد مركبة معتمدة.';

  @override
  String get erVehicleNotApproved => 'المركبة غير معتمدة.';

  @override
  String get erServiceNotApproved => 'لا توجد خدمة معتمدة لهذه الفئة.';

  @override
  String get erVehicleCategoryMismatch => 'فئة المركبة لا تناسب هذه الخدمة.';

  @override
  String get erUnknown => 'هناك متطلب غير مكتمل.';

  @override
  String get obErrGeneric => 'تعذر حفظ التغيير. حاول مرة أخرى.';

  @override
  String get obErrValidation => 'راجع البيانات المدخلة وحاول مرة أخرى.';

  @override
  String obErrUnderage(int age) {
    return 'يجب ألا يقل عمرك عن $age سنة.';
  }

  @override
  String get obErrCityUnavailable => 'لا نعمل في هذه المدينة بعد.';

  @override
  String get obErrServiceUnavailable => 'إحدى الخدمات المختارة غير متاحة.';

  @override
  String get obErrApplicationLocked => 'لا يمكن تعديل الطلب في حالته الحالية.';

  @override
  String get obErrApplicationExists => 'لقد بدأت طلباً بالفعل.';

  @override
  String get obErrIncomplete => 'الطلب غير مكتمل بعد. أكمل المهام المتبقية.';

  @override
  String get obErrDocTypeNotAllowed => 'نوع الملف غير مسموح لهذا المستند.';

  @override
  String get obErrDocTooLarge => 'الملف كبير جداً. اختر صورة أصغر.';

  @override
  String get obErrDocUnreadable => 'الملف فارغ أو لا يمكن قراءته.';

  @override
  String get obErrDocTooSmall => 'الصورة صغيرة جداً. التقط صورة أوضح.';

  @override
  String get obErrDocInfected => 'تم رفض الملف بواسطة الفحص الأمني.';

  @override
  String get obErrScannerDown => 'تعذر فحص الملفات حالياً. حاول بعد قليل.';

  @override
  String get obErrNumberRequired => 'رقم المستند مطلوب.';

  @override
  String get obErrExpiryRequired => 'تاريخ الانتهاء مطلوب لهذا المستند.';

  @override
  String get obErrAlreadyExpired => 'انتهت صلاحية هذا المستند بالفعل.';

  @override
  String get obErrDatesInconsistent =>
      'يجب أن يكون تاريخ الانتهاء بعد تاريخ الإصدار.';

  @override
  String get obErrIssueFuture => 'لا يمكن أن يكون تاريخ الإصدار في المستقبل.';

  @override
  String get obErrPlateTaken => 'توجد مركبة مسجلة بهذه اللوحة بالفعل.';

  @override
  String get obErrPlateInvalid => 'رقم اللوحة غير صالح.';

  @override
  String get obErrYearInvalid => 'سنة الصنع غير صالحة.';

  @override
  String get obErrVehicleNotEditable =>
      'لا يمكن تعديل المركبة في حالتها الحالية.';

  @override
  String get obErrVehicleIncomplete =>
      'المركبة غير مكتملة: أضف الصور والمستندات الناقصة.';

  @override
  String get obErrPhotoLimit => 'وصلت إلى الحد الأقصى لعدد الصور.';

  @override
  String get obErrVehicleLimit => 'وصلت إلى الحد الأقصى لعدد المركبات.';

  @override
  String get obErrPolicyStale => 'تغيّرت الشروط. حدّث الصفحة وحاول مرة أخرى.';

  @override
  String get erLocationStale =>
      'موقعك غير متاح أو قديم. فعّل الموقع وحاول مرة أخرى.';

  @override
  String get erActiveJobExists => 'لديك مهمة نشطة بالفعل.';

  @override
  String get erCashDebtLimitExceeded =>
      'العمولة غير المسددة تجاوزت الحد المسموح. سدّدها لتتمكن من الاتصال.';

  @override
  String get avTitle => 'التواجد';

  @override
  String get avOnlineTitle => 'أنت متصل';

  @override
  String get avOfflineTitle => 'أنت غير متصل';

  @override
  String get avOnlineBody => 'ستظهر هنا الطلبات الجديدة القريبة منك.';

  @override
  String get avOfflineBody => 'فعّل التواجد لاستقبال الطلبات.';

  @override
  String get avSwitchLabel => 'استقبال الطلبات';

  @override
  String get avReasonsTitle => 'لا يمكنك الاتصال بعد:';

  @override
  String get avVehicleLabel => 'المركبة';

  @override
  String get avNoLocationNote =>
      'تتم مشاركة موقعك فقط أثناء الاتصال أو تنفيذ مهمة.';

  @override
  String get avTrackingOff => 'مشاركة الموقع متوقفة';

  @override
  String get avTrackingIdle => 'نشارك موقعك أثناء انتظار الطلبات';

  @override
  String get avTrackingToPickup => 'نشارك موقعك في الطريق إلى نقطة الاستلام';

  @override
  String get avTrackingActive => 'نشارك موقعك أثناء المهمة';

  @override
  String avTrackingEvery(int seconds) {
    return 'يتم التحديث كل $seconds ثانية';
  }

  @override
  String get avUploadFailed => 'تعذر إرسال موقعك. نعيد المحاولة.';

  @override
  String get avRealtimeConnected => 'مباشر';

  @override
  String get avRealtimeConnecting => 'جارٍ الاتصال';

  @override
  String get avRealtimeDisconnected => 'لا يوجد اتصال مباشر، نعيد المحاولة';

  @override
  String get avPermissionDenied => 'اسمح بالوصول إلى الموقع لتتمكن من الاتصال.';

  @override
  String get avPermissionForever =>
      'الموقع محظور لهذا التطبيق. افتح الإعدادات للسماح به.';

  @override
  String get avServiceDisabled => 'خدمات الموقع متوقفة على هذا الجهاز.';

  @override
  String get avOpenSettings => 'فتح الإعدادات';

  @override
  String get avPositionUnavailable =>
      'تعذر تحديد موقعك. حاول في مكان إشارته أفضل.';

  @override
  String get avOutsideArea => 'أنت خارج مناطق الخدمة.';

  @override
  String get avVehicleNotUsable => 'لا يمكن استخدام هذه المركبة الآن.';

  @override
  String get avOfflineStale => 'تم إيقاف اتصالك لأن موقعك توقف عن التحديث.';

  @override
  String get avOfflineIneligible =>
      'تم إيقاف اتصالك لأنك لم تعد تستوفي الشروط.';

  @override
  String get avOfflineAccount => 'تم إيقاف اتصالك لأن حالة حسابك تغيرت.';

  @override
  String get avRationaleTitle => 'لماذا نحتاج موقعك';

  @override
  String get avRationaleBody =>
      'نستخدم موقعك فقط أثناء الاتصال وأثناء المهام: لإظهارك للعملاء القريبين، وإرشادك إلى نقطة الاستلام، وتتبع الرحلة والحفاظ على أمانها. نتوقف فور إيقاف اتصالك. أثناء الاتصال يبقى إشعار ظاهراً على الشاشة وقد يستمر تحديث الموقع عندما يكون التطبيق في الخلفية.';

  @override
  String get avRationaleContinue => 'متابعة';

  @override
  String get avRationaleCancel => 'ليس الآن';

  @override
  String get avOfflineConfirmTitle => 'لديك مهمة جارية';

  @override
  String get avOfflineConfirmBody =>
      'إيقاف الاتصال يوقف الطلبات الجديدة. تستمر مهمتك الحالية ويستمر إرسال موقعك حتى تنتهي.';

  @override
  String get avOfflineConfirmAction => 'إيقاف الاتصال';

  @override
  String get avStayOnline => 'البقاء متصلاً';

  @override
  String get avActiveJobBanner => 'مهمة جارية';

  @override
  String get avNotificationTitle => 'أنت متصل';

  @override
  String get avNotificationText => 'نشارك موقعك لاستقبال الطلبات';

  @override
  String get actionDone => 'تم';

  @override
  String get jbTabHome => 'الرئيسية';

  @override
  String get jbTabRequests => 'الطلبات';

  @override
  String get jbTabOffers => 'عروضي';

  @override
  String get jbTabHistory => 'السجل';

  @override
  String get jbApplicationEntry => 'طلب الانضمام والمستندات';

  @override
  String get jbApplicationEntryBody =>
      'تابع حالة المراجعة والمستندات والمركبات.';

  @override
  String get jbAlertSound => 'صوت عند وصول طلب جديد';

  @override
  String get jbAlertVibration => 'اهتزاز عند وصول طلب جديد';

  @override
  String get jbGoOnlineTitle => 'أنت غير متصل';

  @override
  String get jbGoOnlineBody =>
      'فعّل الاتصال من الرئيسية لتستقبل الطلبات القريبة منك.';

  @override
  String get jbGoOnlineAction => 'الذهاب إلى الرئيسية';

  @override
  String get jbNotEligibleTitle => 'لا يمكنك استقبال الطلبات الآن';

  @override
  String get jbNoRequestsTitle => 'لا توجد طلبات حاليًا';

  @override
  String get jbNoRequestsBody =>
      'سنخبرك فور وصول طلب قريب. اترك التطبيق مفتوحًا.';

  @override
  String get jbRequestExpired => 'انتهت المهلة';

  @override
  String jbExpiresIn(String time) {
    return 'ينتهي خلال $time';
  }

  @override
  String get jbCustomerFare => 'سعر العميل';

  @override
  String jbPickupArea(String area) {
    return 'منطقة الالتقاء: $area';
  }

  @override
  String jbDropoffArea(String area) {
    return 'منطقة الوجهة: $area';
  }

  @override
  String get jbAreaUnknown => 'غير محددة';

  @override
  String jbAwayKm(String distance) {
    return 'يبعد $distance';
  }

  @override
  String jbKm(String value) {
    return '$value كم';
  }

  @override
  String jbMinutes(int count) {
    return '$count دقيقة';
  }

  @override
  String jbPassengers(int count) {
    return '$count ركاب';
  }

  @override
  String jbStops(int count) {
    return '$count محطات';
  }

  @override
  String jbLuggage(int count) {
    return '$count حقائب';
  }

  @override
  String jbWeightKg(String kg) {
    return '$kg كجم';
  }

  @override
  String jbScheduledAt(String time) {
    return 'مجدول: $time';
  }

  @override
  String get jbRestrictedPickup => 'منطقة التقاء مقيّدة';

  @override
  String get jbAcceptFare => 'أقبل سعر العميل';

  @override
  String get jbCounterOffer => 'اقترح سعرًا';

  @override
  String get jbSkip => 'تخطي';

  @override
  String get jbReviseOffer => 'عدّل عرضي';

  @override
  String get jbWithdraw => 'سحب العرض';

  @override
  String get jbWithdrawn => 'تم سحب العرض.';

  @override
  String get jbOfferSent => 'تم إرسال عرضك. بانتظار قرار العميل.';

  @override
  String jbMyOfferAmount(String amount) {
    return 'عرضك: $amount';
  }

  @override
  String get jbCounterTitle => 'اقترح سعرك';

  @override
  String jbCustomerFareIs(String fare) {
    return 'سعر العميل: $fare';
  }

  @override
  String jbAllowedRange(String min, String max) {
    return 'المسموح من $min إلى $max';
  }

  @override
  String jbAmountBelow(String min) {
    return 'أقل من الحد الأدنى ($min)';
  }

  @override
  String jbAmountAbove(String max) {
    return 'أعلى من الحد الأقصى ($max)';
  }

  @override
  String get jbAmountSame => 'هذا هو سعر العميل نفسه. استخدم زر قبول السعر.';

  @override
  String get jbSendOffer => 'إرسال العرض';

  @override
  String get jbKeypadBackspace => 'حذف';

  @override
  String get jbKeypadDecimal => 'فاصلة عشرية';

  @override
  String get jbPreviewGross => 'المبلغ المقترح';

  @override
  String get jbPreviewCommission => 'عمولة المنصة';

  @override
  String get jbPreviewNet => 'صافي ربحك';

  @override
  String get jbNoOffersTitle => 'لا توجد عروض';

  @override
  String get jbNoOffersBody => 'عروضك على الطلبات ستظهر هنا.';

  @override
  String get jbOfferPending => 'بانتظار العميل';

  @override
  String get jbOfferSelected => 'تم اختيارك';

  @override
  String get jbOfferRejected => 'لم يُختر';

  @override
  String get jbOfferExpired => 'انتهت المهلة';

  @override
  String get jbOfferInvalidated => 'لم يعد صالحًا';

  @override
  String get jbOfferWithdrawn => 'تم سحبه';

  @override
  String get jbOfferUnknown => 'غير معروف';

  @override
  String get jbReasonRejected => 'رفض العميل هذا العرض.';

  @override
  String get jbReasonLost => 'اختار العميل سائقًا آخر.';

  @override
  String get jbReasonExpired => 'انتهت مهلة العرض دون رد.';

  @override
  String get jbReasonRequestChanged => 'غيّر العميل تفاصيل الطلب فأُلغي العرض.';

  @override
  String get jbReasonUnavailable => 'أُلغي العرض لأنك لم تعد متاحًا.';

  @override
  String get jbReasonWithdrawn => 'سحبت هذا العرض.';

  @override
  String get jbKindFare => 'قبول سعر العميل';

  @override
  String get jbKindCounter => 'سعر مقترح منك';

  @override
  String get jbOpenJob => 'فتح الرحلة';

  @override
  String get jbNoHistoryTitle => 'لا توجد رحلات بعد';

  @override
  String get jbNoHistoryBody => 'رحلاتك المنتهية ستظهر هنا.';

  @override
  String get jbHistoryJob => 'رحلة';

  @override
  String get jbJobTitle => 'الرحلة';

  @override
  String get jbActiveJob => 'لديك رحلة جارية';

  @override
  String get jbJobLostTitle => 'لم تعد هذه الرحلة معك';

  @override
  String get jbJobLostBody => 'أُعيد توزيع الطلب أو أُلغي أو أُسند لسائق آخر.';

  @override
  String get jbBackHome => 'العودة للرئيسية';

  @override
  String get jbStatusSelected => 'تم اختيارك';

  @override
  String get jbStatusHeading => 'في الطريق إلى العميل';

  @override
  String get jbStatusArrived => 'وصلت إلى العميل';

  @override
  String get jbStatusInProgress => 'الرحلة جارية';

  @override
  String get jbStatusCompleted => 'اكتملت الرحلة';

  @override
  String get jbStatusCancelledCustomer => 'ألغاها العميل';

  @override
  String get jbStatusCancelledMe => 'ألغيتها أنت';

  @override
  String get jbStatusCancelledOps => 'ألغتها الإدارة';

  @override
  String get jbStatusDisputed => 'قيد النزاع';

  @override
  String get jbStatusFailed => 'فشلت محاولة التسليم';

  @override
  String get jbStatusSearching => 'قيد البحث عن سائق';

  @override
  String get jbStatusOther => 'حالة أخرى';

  @override
  String get jbCollectCash => 'حصّل المبلغ نقدًا من العميل';

  @override
  String get jbPayCash => 'الدفع نقدًا';

  @override
  String get jbPayOther => 'الدفع إلكترونيًا';

  @override
  String get jbSupportFlagged => 'فريق الدعم يتابع هذه الرحلة.';

  @override
  String jbFreeWaitLeft(String time) {
    return 'وقت الانتظار المجاني المتبقي: $time';
  }

  @override
  String get jbFreeWaitOver => 'انتهى وقت الانتظار المجاني.';

  @override
  String get jbPinNeeded => 'اطلب من العميل رمز بدء الرحلة وأدخله.';

  @override
  String get jbPinVerified => 'تم التحقق من الرمز.';

  @override
  String get jbRouteTitle => 'المسار';

  @override
  String get jbPickup => 'نقطة الالتقاء';

  @override
  String get jbDropoff => 'الوجهة';

  @override
  String get jbStopLabel => 'محطة';

  @override
  String get jbCustomerTitle => 'العميل';

  @override
  String get jbCustomerUnknown => 'عميل';

  @override
  String jbCustomerRating(String avg, int count) {
    return 'التقييم $avg ($count)';
  }

  @override
  String get jbCallCustomer => 'اتصل بالعميل';

  @override
  String get jbCallFailed => 'تعذّر بدء الاتصال.';

  @override
  String get jbContactMasked => 'رقم العميل مخفي. تواصل عبر الدعم عند الحاجة.';

  @override
  String get jbInstructionsTitle => 'تفاصيل وتعليمات العميل';

  @override
  String get jbActionAcknowledge => 'أنا في الطريق إلى العميل';

  @override
  String get jbActionArrived => 'وصلت';

  @override
  String get jbActionPin => 'إدخال رمز الرحلة';

  @override
  String get jbPinLockedButton => 'الرمز مقفل';

  @override
  String get jbActionStart => 'بدء الرحلة';

  @override
  String get jbComplete => 'إنهاء الرحلة';

  @override
  String get jbCompleteConfirmTitle => 'تأكيد إنهاء الرحلة';

  @override
  String get jbCompleteConfirmBody => 'تأكد أنك وصلت إلى الوجهة.';

  @override
  String jbCompleteConfirmCash(String amount) {
    return 'حصّل $amount نقدًا من العميل ثم أنهِ الرحلة.';
  }

  @override
  String get jbNavigatePickup => 'التنقل إلى العميل';

  @override
  String get jbNavigateDropoff => 'التنقل إلى الوجهة';

  @override
  String get jbNavFailed => 'تعذّر فتح تطبيق الخرائط.';

  @override
  String get jbCancelJob => 'إلغاء الرحلة';

  @override
  String get jbReportIssue => 'الإبلاغ عن مشكلة';

  @override
  String get jbPinTitle => 'رمز بدء الرحلة';

  @override
  String get jbPinHint => 'اطلب الرمز من العميل وأدخله هنا.';

  @override
  String get jbPinConfirm => 'تأكيد الرمز';

  @override
  String jbPinAttemptsLeft(int count) {
    return 'المحاولات المتبقية: $count';
  }

  @override
  String get jbSummaryDone => 'اكتملت الرحلة';

  @override
  String get jbSummaryEnded => 'انتهت الرحلة';

  @override
  String get jbSummaryAmount => 'قيمة الرحلة';

  @override
  String jbSummaryCollectCash(String amount) {
    return 'حصّل $amount نقدًا من العميل.';
  }

  @override
  String get jbRateTitle => 'قيّم العميل';

  @override
  String jbStarsLabel(int count) {
    return '$count نجوم';
  }

  @override
  String get jbRateComment => 'تعليق (اختياري)';

  @override
  String get jbRateSubmit => 'إرسال التقييم';

  @override
  String get jbRatingThanks => 'شكرًا، تم إرسال تقييمك.';

  @override
  String get jbTagRespectful => 'محترم';

  @override
  String get jbTagClear => 'تعليمات واضحة';

  @override
  String get jbTagReady => 'جاهز عند الالتقاء';

  @override
  String get jbTagDisrespectful => 'غير محترم';

  @override
  String get jbTagNotReady => 'لم يكن جاهزًا';

  @override
  String get jbTagLate => 'متأخر';

  @override
  String get jbCancelTitle => 'إلغاء الرحلة';

  @override
  String get jbCancelPrompt => 'لماذا تريد الإلغاء؟';

  @override
  String get jbCancelNoShow => 'العميل لم يحضر';

  @override
  String get jbCancelUnreachable => 'لا يمكن الوصول للعميل';

  @override
  String get jbCancelVehicle => 'مشكلة في المركبة';

  @override
  String get jbCancelEmergency => 'حالة طارئة';

  @override
  String get jbCancelWrongDetails => 'تفاصيل الطلب غير صحيحة';

  @override
  String get jbCancelUnsafe => 'مكان الالتقاء غير آمن';

  @override
  String get jbCancelOther => 'سبب آخر';

  @override
  String get jbCancelNote => 'اكتب السبب';

  @override
  String get jbCancelShowCost => 'عرض تكلفة الإلغاء';

  @override
  String get jbCancelFree => 'الإلغاء الآن بدون رسوم.';

  @override
  String jbCancelFee(String fee) {
    return 'رسوم الإلغاء: $fee';
  }

  @override
  String get jbCancelRedispatchNote => 'سيُعاد الطلب إلى سائقين آخرين.';

  @override
  String get jbCancelConfirmAction => 'تأكيد الإلغاء';

  @override
  String get jbCancelConfirmTitle => 'هل تريد إلغاء الرحلة؟';

  @override
  String get jbCancelConfirmBody => 'لا يمكن التراجع عن الإلغاء.';

  @override
  String get jbCancelConfirmRedispatch =>
      'سيُعاد الطلب إلى سائقين آخرين ولن تعود إليه.';

  @override
  String get jbCancelledDone => 'تم إلغاء الرحلة.';

  @override
  String get jbCancelledRedispatch =>
      'تم الإلغاء وأُعيد الطلب إلى سائقين آخرين.';

  @override
  String get jbReportTitle => 'الإبلاغ عن مشكلة';

  @override
  String get jbReportPrompt => 'ما نوع المشكلة؟';

  @override
  String get jbReportSafety => 'سلامة';

  @override
  String get jbReportBehaviour => 'سلوك العميل';

  @override
  String get jbReportFare => 'خلاف على السعر';

  @override
  String get jbReportRoute => 'المسار أو التأخير';

  @override
  String get jbReportOther => 'أخرى';

  @override
  String get jbReportDescription => 'وصف المشكلة';

  @override
  String get jbReportDescriptionHelp => '10 أحرف على الأقل';

  @override
  String get jbReportSubmit => 'إرسال البلاغ';

  @override
  String get jbReportSentTitle => 'تم إرسال البلاغ';

  @override
  String get jbReportSentBody => 'سيراجعه فريق الدعم.';

  @override
  String get jbErrLost =>
      'لم يعد هذا الطلب متاحًا (انتهت مهلته أو أُسند لسائق آخر). حدّثنا القائمة.';

  @override
  String get jbErrNotEligible =>
      'لا يمكنك إرسال عروض الآن. راجع أسباب عدم الأهلية في الرئيسية.';

  @override
  String get jbErrVehicle => 'اختر مركبة معتمدة من الرئيسية أولًا.';

  @override
  String get jbErrCounterOff => 'هذا الطلب لا يقبل عروضًا مضادة.';

  @override
  String get jbErrAmountInvalid => 'المبلغ غير صالح.';

  @override
  String get jbErrAmountBounds => 'المبلغ خارج الحدود المسموحة.';

  @override
  String get jbErrCurrency => 'عملة المبلغ غير مطابقة.';

  @override
  String get jbErrSameAsFare => 'هذا هو سعر العميل نفسه. استخدم زر قبول السعر.';

  @override
  String get jbErrOfferLimit => 'وصلت إلى الحد الأقصى للعروض المفتوحة.';

  @override
  String get jbErrRevisions => 'استنفدت عدد مرات تعديل هذا العرض.';

  @override
  String get jbErrNotWithdrawable => 'لم يعد بالإمكان سحب هذا العرض.';

  @override
  String get jbErrWithdrawFirst => 'اسحب عرضك أولًا قبل التخطي.';

  @override
  String get jbErrNotYours => 'هذا العرض ليس لك.';

  @override
  String get jbErrTransition => 'تغيّرت حالة الرحلة. تم تحديثها.';

  @override
  String get jbErrPinRequired => 'أدخل رمز الرحلة أولًا.';

  @override
  String get jbErrProofRequired => 'مطلوب إثبات قبل المتابعة.';

  @override
  String get jbErrPinLocked =>
      'الرمز مقفل بعد محاولات خاطئة. تواصل مع الدعم لبدء الرحلة.';

  @override
  String get jbErrPinInvalid => 'الرمز غير صحيح.';

  @override
  String get jbErrPinNotRequired => 'هذه الرحلة لا تحتاج إلى رمز.';

  @override
  String get jbErrNotCancellable => 'لا يمكن إلغاء هذه الرحلة الآن.';

  @override
  String get jbErrReasonNotAllowed => 'سبب الإلغاء غير مسموح به.';

  @override
  String get jbErrNoteRequired => 'اكتب سبب الإلغاء.';

  @override
  String get jbErrNoShowEarly =>
      'لا يمكن اختيار عدم الحضور قبل انتهاء وقت الانتظار المجاني.';

  @override
  String get jbErrReportLimit => 'لا يمكن إرسال بلاغ آخر الآن.';

  @override
  String get jbErrRatingClosed => 'لا يمكن تقييم هذه الرحلة الآن.';

  @override
  String get jbErrRatingDone => 'قيّمت هذه الرحلة من قبل.';

  @override
  String get jbErrRateLimited => 'محاولات كثيرة. انتظر قليلًا ثم أعد المحاولة.';

  @override
  String get jbErrAccount => 'حسابك لا يسمح بهذا الإجراء حاليًا.';

  @override
  String get jbErrInProgress => 'الطلب قيد التنفيذ. انتظر لحظة.';

  @override
  String get jbErrNotFound => 'لم نجد هذه الرحلة.';

  @override
  String jbErrPinInvalidLeft(int count) {
    return 'الرمز غير صحيح. المحاولات المتبقية: $count';
  }

  @override
  String get notificationsTitle => 'الإشعارات';

  @override
  String get notificationsMarkAllRead => 'تعليم الكل كمقروء';

  @override
  String get notificationUnavailable =>
      'لم يعد هذا متاحًا. يمكنك الاطلاع على التفاصيل في إشعاراتك.';

  @override
  String get notificationSettingsTitle => 'إعدادات الإشعارات';

  @override
  String get notificationChannelPush => 'الإشعارات الفورية';

  @override
  String get notificationChannelSms => 'الرسائل النصية';

  @override
  String get notificationChannelEmail => 'البريد الإلكتروني';

  @override
  String get notificationChannelInApp => 'صندوق الوارد';

  @override
  String get notificationEssentialExplain =>
      'الإشعارات الخاصة برحلاتك وحسابك ومدفوعاتك وسلامتك أساسية: تبقى الإشعارات الفورية مفعّلة لها حتى لا يفوتك أمر مهم. يمكنك اختيار القنوات الأخرى والفئات الاختيارية أدناه.';

  @override
  String get notificationEssentialBadge => 'أساسي: الإشعار الفوري مفعّل دائمًا';

  @override
  String get notificationOptInBadge => 'متوقف حتى تفعّله';

  @override
  String get notificationLockedOn => 'مفعّل دائمًا لهذه الفئة';

  @override
  String get pushStatusReady => 'الإشعارات الفورية مفعّلة على هذا الجهاز.';

  @override
  String get pushStatusPermissionNeeded =>
      'فعّل الإشعارات الفورية لتعرف أخبار رحلاتك فورًا.';

  @override
  String get pushStatusDenied =>
      'الإشعارات الفورية محظورة. اسمح بها من إعدادات هاتفك.';

  @override
  String get pushStatusFailed => 'تعذّر إعداد الإشعارات الفورية. حاول لاحقًا.';

  @override
  String get pushStatusNotConfigured =>
      'الإشعارات الفورية غير متاحة في هذا الإصدار. ستجد كل شيء في صندوق الوارد.';

  @override
  String get pushEnable => 'تفعيل';

  @override
  String get pushRationaleTitle => 'ابقَ على اطلاع';

  @override
  String get pushRationaleBody =>
      'سنخبرك بتحديثات الرحلات والمدفوعات والسلامة. تبقى الإشعارات على شاشة القفل قصيرة ولا تعرض بيانات شخصية.';

  @override
  String get pushRationaleLater => 'ليس الآن';

  @override
  String get accountNotifications => 'الإشعارات';

  @override
  String get chatTitle => 'المحادثة';

  @override
  String get chatInputHint => 'اكتب رسالة';

  @override
  String get chatSend => 'إرسال';

  @override
  String get chatAttach => 'إرفاق صورة';

  @override
  String get attachCamera => 'التقاط صورة';

  @override
  String get attachGallery => 'اختيار من المعرض';

  @override
  String get attachRemove => 'إزالة';

  @override
  String get chatEmptyTitle => 'لا توجد رسائل بعد';

  @override
  String get chatEmptyBody =>
      'ابدأ المحادثة. الرسائل ظاهرة لك وللطرف الآخر في هذا الحجز فقط.';

  @override
  String get chatClosedTitle => 'تم إغلاق هذه المحادثة';

  @override
  String get chatClosedBody =>
      'ما زال بإمكانك قراءة الرسائل. لأي أمر آخر تواصل مع الدعم.';

  @override
  String chatClosesAt(String time) {
    return 'يمكنك المحادثة حتى $time';
  }

  @override
  String get chatSending => 'جارٍ الإرسال…';

  @override
  String get chatFailed => 'لم تُرسل';

  @override
  String get chatDiscard => 'حذف';

  @override
  String get chatRead => 'تمت القراءة';

  @override
  String get chatDelivered => 'أُرسلت';

  @override
  String get chatLoadOlder => 'رسائل أقدم';

  @override
  String get chatImageFailed => 'تعذر تحميل الصورة. اضغط لإعادة المحاولة.';

  @override
  String get chatImageLabel => 'صورة في المحادثة';

  @override
  String get chatRemoved => 'تمت إزالتها بعد انتهاء مدة الاحتفاظ';

  @override
  String chatErrTooLongMax(int max) {
    return 'الرسالة طويلة جداً (الحد الأقصى $max حرفاً)';
  }

  @override
  String get chatPhoneNotice =>
      'تُخفى أرقام الهواتف في الرسائل. استخدم زر الاتصال بدلاً من ذلك.';

  @override
  String get chatUnavailableTitle => 'المحادثة غير متاحة';

  @override
  String get chatUnavailableBody => 'يمكنك المحادثة بعد تأكيد سائق لهذا الحجز.';

  @override
  String get chatErrClosed => 'تم إغلاق محادثة هذا الحجز.';

  @override
  String get chatErrForbidden => 'أنت لست طرفاً في هذه المحادثة.';

  @override
  String get chatErrTooLong => 'الرسالة طويلة جداً.';

  @override
  String get supportTitle => 'المساعدة والدعم';

  @override
  String get supportTicketTitle => 'الطلب';

  @override
  String get supportEmptyTitle => 'لا توجد طلبات بعد';

  @override
  String get supportEmptyBody =>
      'افتح طلباً إذا احتجت مساعدة في رحلة أو دفعة أو حسابك.';

  @override
  String get supportNew => 'تواصل مع الدعم';

  @override
  String get supportFilterActive => 'المفتوحة';

  @override
  String get supportFilterFinished => 'المحلولة';

  @override
  String get supportFilterAll => 'الكل';

  @override
  String get supportLoadMore => 'عرض المزيد';

  @override
  String get supportCategory => 'ما موضوع الطلب؟';

  @override
  String get supportCategoryHint => 'اختر تصنيفاً';

  @override
  String get supportSubject => 'الموضوع';

  @override
  String get supportSubjectHint => 'عنوان قصير';

  @override
  String get supportBody => 'صف المشكلة';

  @override
  String get supportBodyHint => 'ماذا حدث؟ أضف تفاصيل تساعدنا على التحقق.';

  @override
  String supportBooking(String code) {
    return 'الحجز $code';
  }

  @override
  String get supportSubmit => 'إرسال الطلب';

  @override
  String get supportRequired => 'هذا الحقل مطلوب';

  @override
  String get supportTooShort => 'يرجى كتابة المزيد';

  @override
  String get supportSent => 'تم إرسال طلبك.';

  @override
  String get supportReplyHint => 'اكتب رداً';

  @override
  String get supportReplySend => 'إرسال الرد';

  @override
  String get supportReplyClosed =>
      'تم إغلاق هذا الطلب. افتح طلباً جديداً إذا كنت ما زلت بحاجة إلى مساعدة.';

  @override
  String get supportResolution => 'الحل';

  @override
  String get supportFromSupport => 'الدعم';

  @override
  String get supportFromMe => 'أنت';

  @override
  String get supportNoInternal => 'تظهر فقط الردود الموجهة إليك.';

  @override
  String get supportAttachment => 'مرفق';

  @override
  String supportTicketNumber(String number) {
    return 'الطلب $number';
  }

  @override
  String supportOpenedOn(String date) {
    return 'فُتح في $date';
  }

  @override
  String get supportErrLimit =>
      'لديك طلبات مفتوحة كثيرة. انتظر الرد أو أضف رداً على طلب قائم.';

  @override
  String get supportErrRate => 'أرسلت طلبات كثيرة اليوم. حاول مرة أخرى غداً.';

  @override
  String get supportErrClosed => 'تم إغلاق هذا الطلب.';

  @override
  String get supportErrAppealOpen => 'لقد قدمت اعتراضاً على هذا الحجز من قبل.';

  @override
  String get supportErrAppealNone =>
      'لا توجد رسوم على هذا الحجز يمكن الاعتراض عليها.';

  @override
  String get supportErrInfected => 'تم رفض الملف بعد الفحص الأمني.';

  @override
  String get supportErrFileType =>
      'نوع الملف غير مسموح. استخدم صورة أو ملف PDF.';

  @override
  String get supportErrFileSize => 'حجم الملف كبير جداً.';

  @override
  String get supportAppealTitle => 'الاعتراض على الرسوم';

  @override
  String get supportAppealIntro =>
      'أخبرنا لماذا يجب مراجعة الرسوم. سيراجع الدعم الرحلة وقرار الرسوم ويرد عليك في هذا الطلب.';

  @override
  String get supportAppealReason => 'لماذا تعترض؟';

  @override
  String get supportAppealShort => 'يرجى الشرح بما لا يقل عن 10 أحرف.';

  @override
  String get supportAppealSubmit => 'إرسال الاعتراض';

  @override
  String get supportAppealSent => 'تم إرسال اعتراضك إلى الدعم.';

  @override
  String get supportOpenChat => 'محادثة';

  @override
  String get supportOpenChatHistory => 'سجل المحادثة';

  @override
  String get supportHelp => 'المساعدة والدعم';

  @override
  String get supportHelpAboutTrip => 'احصل على مساعدة بخصوص هذه الرحلة';

  @override
  String get supportAppealButton => 'الاعتراض على الرسوم';
}
