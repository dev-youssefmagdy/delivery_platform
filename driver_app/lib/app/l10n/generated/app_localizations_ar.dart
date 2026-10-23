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

  @override
  String get ernTab => 'الأرباح';

  @override
  String get ernDay => 'يوم';

  @override
  String get ernWeek => 'أسبوع';

  @override
  String get ernMonth => 'شهر';

  @override
  String get ernEarned => 'ما ربحته';

  @override
  String ernJobs(int count) {
    return '$count رحلة';
  }

  @override
  String get ernGross => 'الأجرة';

  @override
  String get ernCommission => 'عمولة المنصة';

  @override
  String get ernProviderDiscount => 'خصم تحملته';

  @override
  String get ernPlatformDiscount => 'خصم تحملته المنصة';

  @override
  String get ernTips => 'الإكراميات';

  @override
  String get ernNet => 'الصافي';

  @override
  String get ernCash => 'النقد المحصّل';

  @override
  String get ernDaily => 'الأرباح اليومية';

  @override
  String get ernByCategory => 'حسب الخدمة';

  @override
  String get ernNoJobs => 'لا توجد رحلات مكتملة في هذه الفترة.';

  @override
  String get ernWalletBalance => 'رصيد المحفظة';

  @override
  String get ernWalletReserved => 'محجوز للسحب';

  @override
  String get ernWalletAvailable => 'المتاح للسحب';

  @override
  String get ernBreakdownLink => 'الأرباح لكل رحلة';

  @override
  String get ernOpenJob => 'فتح الرحلة';

  @override
  String get walLedgerTitle => 'سجل المحفظة';

  @override
  String get walLedgerEmpty => 'لا توجد حركات في المحفظة بعد.';

  @override
  String get walLoadMore => 'عرض المزيد';

  @override
  String walBalanceAfter(String amount) {
    return 'الرصيد: $amount';
  }

  @override
  String cashDebtTitle(String amount) {
    return 'عليك $amount عمولة نقدية';
  }

  @override
  String cashDebtWarning(String limit) {
    return 'حدّك $limit. عند تجاوزه تتوقف عن استلام الطلبات حتى تسدد.';
  }

  @override
  String cashDebtBlocked(String limit) {
    return 'تجاوزت الحد $limit، لذلك لن تصلك طلبات جديدة حتى تسدد.';
  }

  @override
  String get cashDebtHowTo => 'سدد العمولة النقدية مع فريق المالية.';

  @override
  String get cashDebtContact => 'تواصل مع الدعم';

  @override
  String get cashCollectTitle => 'أكّد استلام النقد';

  @override
  String cashCollectBody(String amount) {
    return 'أكّد أنك استلمت $amount نقداً من العميل.';
  }

  @override
  String get cashCollectBodyNoAmount => 'أكّد أنك استلمت النقد من العميل.';

  @override
  String get cashConfirm => 'استلمت النقد';

  @override
  String get cashConfirmedTitle => 'تم تأكيد النقد';

  @override
  String get cashConfirmedBody => 'شكراً لك. تمت تسوية الرحلة.';

  @override
  String get cashErrNotCollectable => 'لا يمكن تأكيد نقد هذه الرحلة الآن.';

  @override
  String get poTitle => 'السحوبات';

  @override
  String get poAvailable => 'المتاح للسحب';

  @override
  String poReservedNote(String amount) {
    return '$amount محجوز لسحوبات قيد التنفيذ';
  }

  @override
  String poLimits(String min, String max) {
    return 'الحد الأدنى $min والأقصى $max لكل سحب';
  }

  @override
  String get poRequest => 'طلب سحب';

  @override
  String get poHistory => 'السجل';

  @override
  String get poEmptyTitle => 'لا توجد سحوبات بعد';

  @override
  String get poEmptyBody => 'ستظهر هنا طلبات السحب وحالتها.';

  @override
  String poAmountLabel(String currency) {
    return 'المبلغ ($currency)';
  }

  @override
  String get poAll => 'الكل';

  @override
  String get poMethod => 'وسيلة السحب';

  @override
  String get poNoMethods => 'أضف وسيلة سحب أولاً.';

  @override
  String get poNoVerifiedMethods =>
      'وسائل السحب لديك بانتظار تحقق فريق المالية.';

  @override
  String get poFeeNote =>
      'يُحجز المبلغ في محفظتك الآن. الرسوم والموافقة يحددها فريق المالية.';

  @override
  String get poSubmit => 'إرسال الطلب';

  @override
  String get poNoBalance => 'رصيدك غير متاح. حاول مجدداً.';

  @override
  String get poErrAmountInvalid => 'أدخل مبلغاً صحيحاً.';

  @override
  String poErrBelowMinAmount(String min) {
    return 'الحد الأدنى للسحب $min.';
  }

  @override
  String poErrAboveMaxAmount(String max) {
    return 'الحد الأقصى للسحب $max.';
  }

  @override
  String get poErrChooseMethod => 'اختر وسيلة سحب.';

  @override
  String get poErrBelowMin => 'المبلغ أقل من الحد الأدنى للسحب.';

  @override
  String get poErrAboveMax => 'المبلغ أكبر من الحد الأقصى للسحب.';

  @override
  String get poErrInsufficient => 'المبلغ أكبر من رصيدك المتاح.';

  @override
  String get poErrNotVerified => 'وسيلة السحب هذه لم يتم التحقق منها بعد.';

  @override
  String get poErrNotApproved => 'لا يمكن لحسابك طلب سحب الآن.';

  @override
  String get poErrMethodInUse => 'هذه الوسيلة مستخدمة في سحب قيد التنفيذ.';

  @override
  String get poErrNotCancellable => 'لم يعد بالإمكان إلغاء هذا السحب.';

  @override
  String get poErrKeyReused => 'تغيّر شيء في هذا الطلب. ابدأه من جديد.';

  @override
  String get poDetailTitle => 'السحب';

  @override
  String get poAmount => 'المبلغ';

  @override
  String get poFee => 'الرسوم';

  @override
  String get poNet => 'ما ستستلمه';

  @override
  String get poRequestedAt => 'تاريخ الطلب';

  @override
  String get poPaidAt => 'تاريخ الدفع';

  @override
  String get poReference => 'مرجع التحويل';

  @override
  String get poReason => 'السبب';

  @override
  String get poMoneyBack => 'عاد المبلغ إلى محفظتك.';

  @override
  String get poCancel => 'إلغاء الطلب';

  @override
  String get poCancelTitle => 'إلغاء هذا السحب؟';

  @override
  String get poCancelBody => 'يعود المبلغ إلى محفظتك.';

  @override
  String get poCancelConfirm => 'إلغاء السحب';

  @override
  String get poCancelled => 'تم إلغاء السحب.';

  @override
  String get poAskSupport => 'اسأل الدعم عن هذا السحب';

  @override
  String get poStatusRequested => 'تم الطلب';

  @override
  String get poStatusReview => 'قيد المراجعة';

  @override
  String get poStatusApproved => 'تمت الموافقة';

  @override
  String get poStatusProcessing => 'قيد التحويل';

  @override
  String get poStatusPaid => 'تم الدفع';

  @override
  String get poStatusFailed => 'فشل';

  @override
  String get poStatusRejected => 'مرفوض';

  @override
  String get poStatusCancelled => 'ملغي';

  @override
  String get poStatusReversed => 'مُرتجع';

  @override
  String get poStatusUnknown => 'أخرى';

  @override
  String get poExplainReview => 'يراجع فريق المالية طلبك.';

  @override
  String get poExplainApproved => 'تمت الموافقة. سيبدأ التحويل قريباً.';

  @override
  String get poExplainProcessing => 'التحويل في الطريق.';

  @override
  String get poExplainPaid => 'تم إرسال المال إلى وسيلة السحب.';

  @override
  String get poExplainRejected => 'تم رفض الطلب.';

  @override
  String get poExplainFailed => 'فشل التحويل.';

  @override
  String get poExplainCancelled => 'ألغيت هذا الطلب.';

  @override
  String get poExplainReversed => 'أعاد البنك التحويل.';

  @override
  String get pomTitle => 'وسائل السحب';

  @override
  String get pomManage => 'إدارة وسائل السحب';

  @override
  String get pomAdd => 'إضافة وسيلة';

  @override
  String get pomEdit => 'تعديل';

  @override
  String get pomEditNote =>
      'حفاظاً على أمنك لا تُعرض التفاصيل المحفوظة. أدخلها مجدداً لتعديل الوسيلة، وستحتاج إلى تحقق جديد.';

  @override
  String get pomEmptyTitle => 'لا توجد وسائل سحب';

  @override
  String get pomEmptyBody =>
      'أضف حساباً بنكياً أو محفظة هاتف لاستلام السحوبات.';

  @override
  String get pomVerified => 'تم التحقق';

  @override
  String get pomUnverified => 'غير موثّقة';

  @override
  String get pomUnverifiedNote =>
      'يتحقق فريق المالية من الوسيلة قبل استخدامها.';

  @override
  String get pomDefault => 'الوسيلة الافتراضية';

  @override
  String get pomMakeDefault => 'اجعلها افتراضية';

  @override
  String get pomDefaultSet => 'تم تحديث الوسيلة الافتراضية.';

  @override
  String get pomDelete => 'حذف';

  @override
  String get pomDeleteTitle => 'حذف هذه الوسيلة؟';

  @override
  String get pomDeleteBody => 'يمكنك إضافتها مجدداً لاحقاً.';

  @override
  String get pomDeleted => 'تم حذف الوسيلة.';

  @override
  String get pomBank => 'حساب بنكي';

  @override
  String get pomWallet => 'محفظة هاتف';

  @override
  String get pomLabel => 'اسم لهذه الوسيلة';

  @override
  String get pomHolder => 'اسم صاحب الحساب';

  @override
  String get pomBankName => 'اسم البنك';

  @override
  String get pomAccount => 'رقم الحساب أو الآيبان';

  @override
  String get pomAccountInvalid => 'أدخل رقم حساب صحيحاً.';

  @override
  String get pomWalletNumber => 'رقم هاتف المحفظة';

  @override
  String get pomWalletInvalid => 'أدخل رقم هاتف صحيحاً.';

  @override
  String get pomVerifyNote =>
      'تُخزَّن التفاصيل مشفّرة ويتحقق منها فريق المالية.';

  @override
  String get pomSave => 'حفظ';

  @override
  String get pomSaved => 'تم حفظ وسيلة السحب.';

  @override
  String get fieldRequired => 'هذا الحقل مطلوب.';

  @override
  String get rcpTitle => 'الإيصال';

  @override
  String get rcpViewFull => 'الإيصال الكامل';

  @override
  String rcpNumber(String number) {
    return 'إيصال $number';
  }

  @override
  String get rcpCustomer => 'العميل';

  @override
  String get jbReceiptCode => 'رمز الحجز';

  @override
  String get rcpAgreed => 'الأجرة المتفق عليها';

  @override
  String rcpAdjustment(String label) {
    return 'إضافة: $label';
  }

  @override
  String get rcpSubtotal => 'إجمالي الأجرة';

  @override
  String get rcpTax => 'الضريبة';

  @override
  String get rcpOpenPrintable => 'فتح الإيصال للطباعة';

  @override
  String get rcpErrNotAvailable => 'يتوفر الإيصال بعد اكتمال الرحلة.';

  @override
  String get adjApprove => 'موافقة';

  @override
  String get adjReject => 'رفض';

  @override
  String get adjWithdraw => 'سحب الطلب';

  @override
  String adjExpires(String time) {
    return 'أجب قبل $time';
  }

  @override
  String adjNewTotal(String amount) {
    return 'الإجمالي الجديد: $amount';
  }

  @override
  String get adjNote => 'ملاحظة';

  @override
  String get dadjTitle => 'تغييرات الأجرة';

  @override
  String get dadjRequest => 'طلب تغيير الأجرة';

  @override
  String get dadjRequestedByYou => 'طلبته أنت';

  @override
  String get dadjRequestedByCustomer => 'طلبه العميل';

  @override
  String get dadjTypeExtraStop => 'توقف إضافي';

  @override
  String get dadjTypeWaiting => 'وقت انتظار';

  @override
  String get dadjTypeToll => 'رسوم طريق أو انتظار';

  @override
  String get dadjTypeScope => 'تغيير في نطاق الخدمة';

  @override
  String get dadjAmount => 'المبلغ';

  @override
  String get dadjWaitingHelp => 'اتركه فارغاً لاستخدام سعر الانتظار.';

  @override
  String get dadjReason => 'السبب';

  @override
  String get dadjCustomerApproves => 'لا تتغير الأجرة إلا إذا وافق العميل.';

  @override
  String get dadjSend => 'إرسال الطلب';

  @override
  String get dadjSent => 'تم إرسال الطلب إلى العميل.';

  @override
  String get dadjErrNotNow => 'لا يمكن طلب تغيير الأجرة الآن.';

  @override
  String get dadjErrOpenLimit => 'لديك الحد الأقصى من الطلبات المفتوحة.';

  @override
  String get dadjErrAmount => 'أدخل مبلغاً صحيحاً.';

  @override
  String get dadjErrReason => 'اكتب السبب.';

  @override
  String get dadjErrEvidence =>
      'هذا النوع يحتاج إثباتاً. أضف التفاصيل في السبب.';

  @override
  String get dadjErrType => 'هذا النوع من التغيير غير مسموح لهذه الخدمة.';

  @override
  String get dadjErrNotPending => 'تمت الإجابة عن هذا الطلب أو انتهت مدته.';

  @override
  String get dadjErrCounterpart => 'يمكن للطرف الآخر فقط الإجابة عن هذا الطلب.';

  @override
  String get scPackingNone => 'بدون تغليف';

  @override
  String get scPackingWrap => 'تغطية فقط';

  @override
  String get scPackingFull => 'تغليف كامل';

  @override
  String get scStairsNotNeeded => 'لا توجد سلالم';

  @override
  String get scStairsIncluded => 'السلالم مشمولة';

  @override
  String get scStairsExtra => 'سلالم بسعر إضافي للدور';

  @override
  String get scAssemblyNone => 'بدون فك وتركيب';

  @override
  String get scAssemblyDisassembly => 'فك فقط';

  @override
  String get scAssemblyAssembly => 'تركيب فقط';

  @override
  String get scAssemblyBoth => 'فك وتركيب';

  @override
  String get scEqHandTrolley => 'عربة يد';

  @override
  String get scEqForklift => 'رافعة شوكية';

  @override
  String get scEqCrane => 'ونش';

  @override
  String get scEqTailLift => 'رافعة خلفية';

  @override
  String get scEqStraps => 'أحزمة وأغطية';

  @override
  String get scEqPalletJack => 'عربة بالتات';

  @override
  String get scTreatIncluded => 'مشمولة في السعر';

  @override
  String get scTreatAtCost => 'بالتكلفة مع إيصال';

  @override
  String get scTreatExcluded => 'غير مشمولة';

  @override
  String get scIssueRequired => 'هذا الحقل مطلوب.';

  @override
  String get scIssueRange => 'القيمة خارج الحد المسموح.';

  @override
  String get scIssueChoice => 'اختر قيمة من القائمة.';

  @override
  String get scIssueFloors => 'حدد عدد الأدوار المشمولة.';

  @override
  String get scIssueTooLong => 'النص أطول من المسموح.';

  @override
  String get scIssueDuplicate => 'تم اختيار العنصر أكثر من مرة.';

  @override
  String get scHelpers => 'عدد العمال';

  @override
  String get scPacking => 'التغليف';

  @override
  String get scStairs => 'السلالم';

  @override
  String get scIncludedFloors => 'عدد الأدوار المشمولة';

  @override
  String get scAssembly => 'فك وتركيب الأثاث';

  @override
  String get scEquipment => 'معدات التحميل';

  @override
  String get scWaiting => 'مدة الانتظار المجانية';

  @override
  String get scMinutesShort => 'د';

  @override
  String get scExtraStops => 'محطات إضافية مشمولة';

  @override
  String get scTolls => 'رسوم الطرق';

  @override
  String get scParking => 'الانتظار والركن';

  @override
  String get scExclusions => 'ما لا يشمله العرض';

  @override
  String get scExclusionsHelp => 'اختياري، يظهر للعميل بجانب العرض.';

  @override
  String get scNoVehicle => 'اختر مركبتك الحالية وكن متصلاً قبل إرسال عرض نقل.';

  @override
  String get scTitle => 'ماذا يشمل عرضك؟';

  @override
  String get scIntro =>
      'هذه الشروط تظهر للعميل وتصبح اتفاقاً ملزماً عند اختيار عرضك.';

  @override
  String get scRequestedTitle => 'ما طلبه العميل';

  @override
  String get scContinue => 'متابعة إلى السعر';

  @override
  String get scEditScope => 'تعديل ما يشمله العرض';

  @override
  String get scLegendIncluded => 'مشمول';

  @override
  String get scLegendExcluded => 'غير مشمول';

  @override
  String get scLegendAtCost => 'بالتكلفة';

  @override
  String get scSummaryTitle => 'ما يشمله العرض';

  @override
  String get jbErrScopeRequired => 'أضف ما يشمله عرضك قبل الإرسال.';

  @override
  String get jbErrScopeInvalid =>
      'بعض شروط العرض غير صحيحة. راجع الحقول المعلّمة.';

  @override
  String get jbErrScopeVehicle =>
      'فئة المركبة في العرض لا تطابق مركبتك الحالية.';

  @override
  String get jbErrCapacityBoth => 'مركبتك لا تتسع لوزن وحجم هذه الحمولة.';

  @override
  String get jbErrCapacityVolume => 'حجم الحمولة أكبر من حجم مركبتك.';

  @override
  String get jbErrCapacityPayload => 'وزن الحمولة أكبر من حمولة مركبتك.';

  @override
  String get jbErrCapacity => 'مركبتك لا تناسب هذه الحمولة.';

  @override
  String get dlErrProofNow => 'لا يمكن تسجيل هذا الإثبات في هذه المرحلة.';

  @override
  String get dlErrProofDone => 'هذا الإثبات مسجل بالفعل.';

  @override
  String get dlErrProofLimit =>
      'وصلت إلى الحد الأقصى من الإثباتات لهذه المرحلة.';

  @override
  String get dlErrProofType => 'هذا النوع من الإثبات غير مطلوب لهذه الخدمة.';

  @override
  String get dlErrChecklist => 'قائمة الفحص غير صحيحة.';

  @override
  String get dlErrWeight => 'أدخل وزناً صحيحاً أكبر من الصفر.';

  @override
  String get dlErrInfected => 'رفض الملف لأسباب أمنية. التقط صورة أخرى.';

  @override
  String get dlErrScanner =>
      'فحص الملفات غير متاح الآن. سنعيد المحاولة تلقائياً.';

  @override
  String get dlErrCustody => 'لا يمكن تسجيل هذه الخطوة الآن.';

  @override
  String get dlErrOtpInvalid => 'الرمز غير صحيح.';

  @override
  String dlErrOtpInvalidLeft(int left) {
    return 'الرمز غير صحيح. المحاولات المتبقية: $left';
  }

  @override
  String get dlErrOtpLocked =>
      'تم إيقاف الرمز بعد محاولات خاطئة. تواصل مع الدعم.';

  @override
  String get dlErrOtpNotReady => 'رمز المستلم غير جاهز بعد.';

  @override
  String get dlErrOtpResendSoon => 'انتظر قليلاً قبل إعادة إرسال الرمز.';

  @override
  String get dlErrOtpResendLimit => 'لا يمكن إعادة إرسال الرمز أكثر من ذلك.';

  @override
  String get dlErrAttemptNotAllowed =>
      'لا يمكن تنفيذ هذا الإجراء على محاولة التسليم الآن.';

  @override
  String get dlErrAttemptsExhausted =>
      'استُنفدت محاولات التسليم. اختر الإرجاع أو العمليات.';

  @override
  String get dlErrOtpRequired => 'أدخل رمز المستلم قبل إنهاء التسليم.';

  @override
  String get dadjErrScopeItem => 'اختر البند المعني من اتفاق النقل.';

  @override
  String get dadjErrWithinScope =>
      'هذا البند مشمول في الاتفاق ولا يمكن تحميل مبلغ إضافي عنه.';

  @override
  String get dadjErrFloors => 'أدخل عدد الأدوار الإضافية.';

  @override
  String get dadjErrHelpers => 'عدد العمال الإضافيين غير مسموح.';

  @override
  String jbVolumeM3(String volume) {
    return '$volume م³';
  }

  @override
  String jbPickupWindow(String start, String end) {
    return 'التحميل من $start إلى $end';
  }

  @override
  String get jbRequestedScope => 'ما يطلبه العميل';

  @override
  String get jbAcceptScopeNote =>
      'بقبول سعر العميل تلتزم بما طلبه العميل كما هو موضح.';

  @override
  String get dlCustodyTitle => 'مسار الشحنة';

  @override
  String get dlCustodyPickedUp => 'تم الاستلام من المرسل';

  @override
  String get dlCustodyInTransit => 'في الطريق';

  @override
  String get dlCustodyArrived => 'وصلت إلى نقطة التسليم';

  @override
  String get dlCustodyFailed => 'تعذر التسليم';

  @override
  String get dlCustodyReturning => 'في طريق العودة للمرسل';

  @override
  String get dlCustodyDelivered => 'تم التسليم';

  @override
  String get dlCustodyReturned => 'تم الإرجاع للمرسل';

  @override
  String get dlCustodyUnknown => 'حالة غير معروفة';

  @override
  String get dlOtpTitle => 'رمز المستلم';

  @override
  String get dlOtpWaiting => 'لم يُرسل الرمز بعد';

  @override
  String get dlOtpSent => 'أُرسل الرمز للمستلم';

  @override
  String get dlOtpSmsFailed => 'تعذر إرسال الرسالة';

  @override
  String get dlOtpVerified => 'تم التحقق';

  @override
  String get dlOtpLocked => 'الرمز موقوف';

  @override
  String dlOtpAttemptsLeft(int left) {
    return 'المحاولات المتبقية: $left';
  }

  @override
  String get dlOtpAskRecipient =>
      'اطلب من المستلم الرمز الذي وصله برسالة نصية.';

  @override
  String get dlOtpEnter => 'إدخال رمز المستلم';

  @override
  String get dlOtpLockedButton => 'الرمز موقوف';

  @override
  String get dlOtpSheetTitle => 'رمز التسليم';

  @override
  String get dlOtpField => 'الرمز المكون من أرقام';

  @override
  String get dlOtpVerify => 'تأكيد الرمز';

  @override
  String get dlOtpResend => 'إعادة إرسال الرمز';

  @override
  String dlOtpResendIn(String time) {
    return 'إعادة الإرسال بعد $time';
  }

  @override
  String get dlProofsTitle => 'إثباتات الاستلام والتسليم';

  @override
  String get dlNoProofs => 'لا توجد إثباتات بعد.';

  @override
  String get dlProofLoadError => 'تعذر تحميل الصورة. اضغط للمحاولة.';

  @override
  String get dlProofPhoto => 'صورة';

  @override
  String get dlProofSignature => 'توقيع';

  @override
  String get dlProofOtp => 'رمز المستلم';

  @override
  String get dlProofPin => 'رمز البدء';

  @override
  String get dlProofChecklist => 'قائمة فحص';

  @override
  String get dlProofWeight => 'الوزن';

  @override
  String get dlProofOther => 'إثبات';

  @override
  String get dlStagePickup => 'عند الاستلام';

  @override
  String get dlStageDelivery => 'عند التسليم';

  @override
  String get dlStageReturn => 'عند الإرجاع';

  @override
  String get dlChkSealed => 'الطرد مغلق';

  @override
  String get dlChkUndamaged => 'الطرد سليم بلا أضرار';

  @override
  String get dlChkLabel => 'البيانات مطابقة للملصق';

  @override
  String get dlChkRecipient => 'تم التأكد من هوية المستلم';

  @override
  String dlWeightValue(String kg) {
    return 'الوزن المقاس: $kg كجم';
  }

  @override
  String get dlAttemptsTitle => 'محاولات التسليم';

  @override
  String dlAttemptNumber(int number) {
    return 'المحاولة $number';
  }

  @override
  String get dlAttemptEvidence => 'مرفق صورة';

  @override
  String get dlPending => 'بانتظار القرار';

  @override
  String get dlOutUnavailable => 'المستلم غير متاح';

  @override
  String get dlOutRefused => 'المستلم رفض الاستلام';

  @override
  String get dlOutWrongAddress => 'العنوان خاطئ';

  @override
  String get dlOutOtpFailed => 'تعذر التحقق من الرمز';

  @override
  String get dlOutRestricted => 'الشحنة تحتوي على مواد ممنوعة';

  @override
  String get dlOutOther => 'سبب آخر';

  @override
  String get dlResRetry => 'إعادة المحاولة';

  @override
  String get dlResReturn => 'إرجاع للمرسل';

  @override
  String get dlResOperations => 'تحويل للعمليات';

  @override
  String get dlResUnknown => 'قرار غير معروف';

  @override
  String dlCaptureTitle(String type) {
    return 'تسجيل: $type';
  }

  @override
  String get dlTakePhoto => 'التقاط صورة';

  @override
  String get dlRetakePhoto => 'إعادة الالتقاط';

  @override
  String get dlProofNote => 'ملاحظة (اختيارية)';

  @override
  String get dlSignHint => 'اطلب من المستلم التوقيع داخل الإطار.';

  @override
  String get dlSignClear => 'مسح التوقيع';

  @override
  String get dlWeightLabel => 'الوزن المقاس';

  @override
  String get dlKg => 'كجم';

  @override
  String get dlCm => 'سم';

  @override
  String get dlSaveProof => 'حفظ وإرسال';

  @override
  String get dlQueueHint =>
      'يُحفظ الإثبات على هاتفك ويُرسل تلقائياً عند توفر الاتصال.';

  @override
  String get dlProofQueued => 'تم حفظ الإثبات وسيُرسل الآن.';

  @override
  String get dlErrCamera => 'تعذر فتح الكاميرا.';

  @override
  String get dlErrPhotoFirst => 'التقط صورة أولاً.';

  @override
  String get dlErrSignFirst => 'ارسم التوقيع أولاً.';

  @override
  String get dlErrOutcomeFirst => 'اختر سبب تعذر التسليم.';

  @override
  String get dlErrReasonNeeded => 'اكتب السبب (3 أحرف على الأقل).';

  @override
  String dlAddProof(String type) {
    return 'إضافة: $type';
  }

  @override
  String dlProofWaiting(String type) {
    return 'بانتظار الإرسال: $type';
  }

  @override
  String dlProofFailed(String type, String reason) {
    return 'لم يُقبل $type: $reason';
  }

  @override
  String get dlDiscard => 'حذف';

  @override
  String get dlSendNow => 'إرسال الآن';

  @override
  String get dlShowProofs => 'عرض الإثباتات المسجلة';

  @override
  String get dlHideProofs => 'إخفاء الإثباتات';

  @override
  String get dlMarkInTransit => 'الشحنة في الطريق';

  @override
  String get dlArrivedDropoff => 'وصلت إلى نقطة التسليم';

  @override
  String get dlReportAttempt => 'تعذر التسليم';

  @override
  String get dlAttemptTitle => 'لماذا تعذر التسليم؟';

  @override
  String get dlAttemptBody =>
      'سيُبلَّغ العميل ويقرر الخطوة التالية. أرفق صورة إن أمكن.';

  @override
  String get dlReasonRequired => 'اكتب السبب (مطلوب)';

  @override
  String get dlReasonOptional => 'ملاحظات (اختيارية)';

  @override
  String get dlAddEvidence => 'إضافة صورة كدليل';

  @override
  String get dlAttemptSend => 'إرسال البلاغ';

  @override
  String get dlResolveButton => 'قرار ما بعد تعذر التسليم';

  @override
  String get dlResolveTitle => 'ماذا تفعل بالشحنة؟';

  @override
  String get dlResolveBody =>
      'اختر إعادة المحاولة أو إرجاعها للمرسل أو تحويل الحالة للعمليات.';

  @override
  String get dlResolveNothing => 'لا توجد محاولة تنتظر القرار.';

  @override
  String get dlContactsTitle => 'جهات الاتصال';

  @override
  String get dlRoleSender => 'المرسل';

  @override
  String get dlRoleRecipient => 'المستلم';

  @override
  String get dlRolePickup => 'جهة الاتصال عند التحميل';

  @override
  String get dlRoleDropoff => 'جهة الاتصال عند التسليم';

  @override
  String get dlStopsTitle => 'محطات الرحلة';

  @override
  String dlStopNumber(int number) {
    return 'المحطة $number';
  }

  @override
  String get dlNextStop => 'التالية';

  @override
  String get dlLoadTitle => 'الحمولة';

  @override
  String dlLoadWeight(String kg) {
    return 'الوزن: $kg كجم';
  }

  @override
  String dlLoadVolume(String volume) {
    return 'الحجم: $volume م³';
  }

  @override
  String get dlFileVideo => 'فيديو';

  @override
  String get dlFileDocument => 'مستند';

  @override
  String dadjScopeItemLine(String item) {
    return 'بند الاتفاق: $item';
  }

  @override
  String dadjHelpersLine(int count) {
    return '$count عمال';
  }

  @override
  String dadjFloorsLine(int count) {
    return '$count أدوار';
  }

  @override
  String get dadjTypeHelper => 'عامل إضافي';

  @override
  String get dadjTypeStairs => 'سلالم إضافية';

  @override
  String get dadjHelpersCount => 'عدد العمال الإضافيين';

  @override
  String get dadjFloorsCount => 'عدد الأدوار الإضافية';

  @override
  String get dadjReceiptRef => 'رقم الإيصال';

  @override
  String get sfTitle => 'السلامة والمساعدة';

  @override
  String get sfHoldLabel => 'طوارئ';

  @override
  String sfHoldHint(int seconds) {
    return 'اضغط مطولاً لمدة $seconds ثانية لإرسال تنبيه';
  }

  @override
  String get sfHolding => 'استمر بالضغط';

  @override
  String get sfSending => 'جارٍ إرسال تنبيهك…';

  @override
  String get sfSentTitle => 'تم إرسال التنبيه';

  @override
  String get sfFailedTitle => 'تعذّر إرسال التنبيه';

  @override
  String get sfFailedBody =>
      'تحقق من اتصالك. إذا كنت في خطر فاتصل برقم الطوارئ أدناه الآن.';

  @override
  String get sfRetry => 'حاول مرة أخرى';

  @override
  String get sfNoJob =>
      'يعمل زر الأمان أثناء المهمة. يمكنك الاتصال بأرقام الطوارئ أدناه في أي وقت.';

  @override
  String get sfNumbersTitle => 'أرقام الطوارئ المحلية';

  @override
  String sfCall(String name, String number) {
    return 'اتصل بـ $name ($number)';
  }

  @override
  String get sfNoDispatch =>
      'هذا التطبيق لا يتصل بخدمات الطوارئ نيابةً عنك ولا يتم إرسال أحد تلقائيًا. إذا كنت في خطر فاتصل بالرقم بنفسك.';

  @override
  String get sfWhatWeDid => 'ما الذي تم';

  @override
  String get sfReport => 'الإبلاغ عن حادث';

  @override
  String get sfShareTrip => 'مشاركة الرحلة مع جهات الاتصال الموثوقة';

  @override
  String get sfContacts => 'جهات الاتصال الموثوقة';

  @override
  String get sfCallSupport => 'الاتصال بدعم المنصة';

  @override
  String get sfDone => 'تم';

  @override
  String get sfCancel => 'إلغاء';

  @override
  String get sfNumPolice => 'الشرطة';

  @override
  String get sfNumAmbulance => 'الإسعاف';

  @override
  String get sfNumFire => 'الإطفاء';

  @override
  String get sfNumTourist => 'شرطة السياحة';

  @override
  String get sfNumOther => 'الطوارئ';

  @override
  String get sfCatEmergency => 'طارئ';

  @override
  String get sfCatAccident => 'حادث';

  @override
  String get sfCatMedical => 'مشكلة صحية';

  @override
  String get sfCatThreat => 'تهديد أو عنف';

  @override
  String get sfCatHarassment => 'تحرش';

  @override
  String get sfCatUnsafeDriving => 'قيادة غير آمنة';

  @override
  String get sfCatTheft => 'سرقة أو فقدان أغراض';

  @override
  String get sfCatIdentityMismatch => 'السائق أو المركبة غير مطابقين';

  @override
  String get sfCatOther => 'مخاوف أخرى تتعلق بالسلامة';

  @override
  String get sfReportTitle => 'الإبلاغ عن حادث';

  @override
  String get sfReportCategory => 'ماذا حدث؟';

  @override
  String get sfReportNarrative => 'اشرح ما حدث';

  @override
  String get sfReportNarrativeHint =>
      'المكان والأشخاص وأي تفاصيل تساعد فريق السلامة';

  @override
  String get sfReportTooShort => 'اكتب بضع كلمات على الأقل.';

  @override
  String get sfReportCallback => 'هاتف للاتصال بك (اختياري)';

  @override
  String get sfReportSubmit => 'إرسال البلاغ';

  @override
  String get sfReportSent => 'تم استلام البلاغ';

  @override
  String get sfReportUrgent =>
      'إذا كنت في خطر وشيك فارجع واستخدم زر الطوارئ أو اتصل برقم الطوارئ.';

  @override
  String get sfIncidentsTitle => 'بلاغات السلامة';

  @override
  String get sfIncidentsEmpty => 'لم ترفع أي بلاغ سلامة.';

  @override
  String get sfStatusNew => 'تم الاستلام';

  @override
  String get sfStatusAcknowledged => 'قيد المعالجة';

  @override
  String get sfStatusInvestigating => 'قيد المراجعة';

  @override
  String get sfStatusEscalated => 'تم تصعيده إلى مسؤول أعلى';

  @override
  String get sfStatusResolved => 'تم الحل';

  @override
  String sfOutcome(String outcome) {
    return 'النتيجة: $outcome';
  }

  @override
  String get sfTimeline => 'ما جرى حتى الآن';

  @override
  String get sfIncidentContacts => 'جهات اتصالك الموثوقة';

  @override
  String get sfFiles => 'الملفات التي أضفتها';

  @override
  String get sfAddUpdate => 'إضافة تحديث';

  @override
  String get sfUpdateHint => 'أخبر فريق السلامة بالمزيد';

  @override
  String get sfSendUpdate => 'إرسال التحديث';

  @override
  String get sfAddFile => 'إضافة صورة';

  @override
  String get sfResolvedNote =>
      'تم إغلاق هذا البلاغ. إذا تغيّر شيء فارفع بلاغاً جديداً.';

  @override
  String sfCaseNumber(String number) {
    return 'البلاغ $number';
  }

  @override
  String get sfActorYou => 'أنت';

  @override
  String get sfActorStaff => 'فريق السلامة';

  @override
  String get sfActorSystem => 'النظام';

  @override
  String get sfContactsTitle => 'جهات الاتصال الموثوقة';

  @override
  String get sfContactsIntro =>
      'أشخاص قد نراسلهم برسالة نصية عند ضغطك على الطوارئ أو مشاركة رحلة. نراسلهم فقط وفق ما تسمح به أدناه وبعد تأكيدك أنهم وافقوا.';

  @override
  String get sfContactsEmpty => 'لا توجد جهات اتصال موثوقة بعد.';

  @override
  String get sfContactsAdd => 'إضافة جهة اتصال';

  @override
  String sfContactsLimit(int max) {
    return 'يمكنك الاحتفاظ بحتى $max جهات اتصال.';
  }

  @override
  String get sfContactName => 'الاسم';

  @override
  String get sfContactPhone => 'رقم الهاتف';

  @override
  String get sfContactPhoneHint => '+201001234567';

  @override
  String get sfContactRelationship => 'صلة القرابة (اختياري)';

  @override
  String get sfRelFamily => 'العائلة';

  @override
  String get sfRelPartner => 'الشريك';

  @override
  String get sfRelFriend => 'صديق';

  @override
  String get sfRelColleague => 'زميل';

  @override
  String get sfRelOther => 'آخر';

  @override
  String get sfConsentTitle => 'ماذا يمكننا أن نرسل لهذا الشخص؟';

  @override
  String get sfConsentSos => 'رسالة نصية عندما أضغط على الطوارئ';

  @override
  String get sfConsentTrip => 'رابط الرحلة المباشر عندما أشارك رحلة';

  @override
  String get sfConsentLocation => 'آخر موقع لي في رسالة الطوارئ';

  @override
  String get sfConsentConfirm =>
      'أؤكد أن هذا الشخص وافق على استلام هذه الرسائل.';

  @override
  String get sfContactSave => 'حفظ';

  @override
  String get sfContactDelete => 'حذف جهة الاتصال';

  @override
  String get sfContactDeleteConfirm => 'حذف جهة الاتصال الموثوقة هذه؟';

  @override
  String get sfContactRequired => 'هذا الحقل مطلوب.';

  @override
  String get sfContactConsentRequired => 'أكّد أن هذا الشخص وافق.';

  @override
  String get sfShareTitle => 'مشاركة هذه الرحلة';

  @override
  String get sfShareBody =>
      'نرسل رابط الرحلة المباشر برسالة نصية إلى جهات الاتصال الموثوقة التي وافقت على استلامه. يعرض الرابط الاسم الأول للسائق والمركبة والموقع المباشر، ويتوقف عند انتهاء الرحلة.';

  @override
  String get sfShareNoContacts =>
      'أضف أولاً جهة اتصال موثوقة وافقت على استلام روابط الرحلة.';

  @override
  String get sfShareSend => 'إرسال إلى جهات الاتصال الموثوقة';

  @override
  String sfShareResult(int sent, int total) {
    return 'أُرسل إلى $sent من $total جهات اتصال';
  }

  @override
  String get sfShareCopy => 'نسخ الرابط';

  @override
  String get sfShareCopied => 'تم نسخ الرابط';

  @override
  String get sfShareStop => 'إيقاف المشاركة';

  @override
  String get sfShareStopped => 'تم إيقاف المشاركة. لم يعد الرابط يعمل.';

  @override
  String sfShareExpires(String time) {
    return 'يعمل الرابط حتى $time';
  }

  @override
  String get sfSettingsTile => 'جهات الاتصال الموثوقة والسلامة';

  @override
  String get actionUpdateNow => 'حدّث الآن';

  @override
  String get refTitle => 'ادعُ أصدقاءك';

  @override
  String get refYourCode => 'رمز الإحالة الخاص بك';

  @override
  String get refCopy => 'نسخ الرمز';

  @override
  String get refCopied => 'تم نسخ الرمز';

  @override
  String get refShare => 'مشاركة';

  @override
  String get refNoOffer =>
      'لا يوجد عرض إحالة حاليا. يمكنك مشاركة رمزك على أي حال.';

  @override
  String get refOfferTitle => 'العرض';

  @override
  String refOfferLine(String referrer, String referee) {
    return 'تحصل على $referrer، ويحصل صديقك على $referee.';
  }

  @override
  String refMilestone(int jobs) {
    return 'تُدفع بعد أن يُتم صديقك $jobs طلب(ات).';
  }

  @override
  String refValidity(int days) {
    return 'الرصيد صالح لمدة $days يوما.';
  }

  @override
  String get refHaveCode => 'لديك رمز من صديق؟';

  @override
  String get refCodeHint => 'أدخل الرمز';

  @override
  String get refApply => 'تطبيق الرمز';

  @override
  String get refApplied => 'تم تطبيق الرمز. تصلك المكافأة بعد أول طلب مكتمل.';

  @override
  String get refStatReferred => 'تمت دعوتهم';

  @override
  String get refStatRewarded => 'تمت مكافأتهم';

  @override
  String get refStatPending => 'قيد التقدم';

  @override
  String get refListTitle => 'دعواتك';

  @override
  String get refListEmpty => 'لم يستخدم أحد رمزك بعد.';

  @override
  String get refStatusPending => 'قيد التقدم';

  @override
  String get refStatusRewarded => 'تمت المكافأة';

  @override
  String get refStatusClosed => 'مغلقة';

  @override
  String get refErrInvalid => 'هذا الرمز غير صالح.';

  @override
  String get refErrSelf => 'لا يمكنك استخدام رمزك الخاص.';

  @override
  String get refErrNotNew => 'الرموز مخصصة لمن لم يُتم أي طلب بعد.';

  @override
  String get refErrUnavailable => 'لا يوجد عرض إحالة حاليا.';

  @override
  String get refErrCap => 'بلغ هذا الرمز حده الأقصى.';

  @override
  String get refErrApplied => 'تم تطبيق رمز على حسابك مسبقا.';

  @override
  String get refErrGeneric => 'تعذّر تطبيق الرمز. حاول مرة أخرى.';

  @override
  String get refTile => 'ادعُ سائقا';

  @override
  String get incTitle => 'الحوافز';

  @override
  String get incTile => 'الحوافز والمهام';

  @override
  String get incNone => 'لا توجد حملات حوافز مفتوحة حاليا.';

  @override
  String incQuestLine(int jobs, String bonus) {
    return 'أتمّ $jobs طلبات واكسب $bonus';
  }

  @override
  String incProgress(int done, int target) {
    return '$done من $target طلبات';
  }

  @override
  String incEndsOn(String date) {
    return 'تنتهي $date';
  }

  @override
  String incAwards(int earned, int max) {
    return 'المكافآت المكتسبة: $earned من $max';
  }

  @override
  String get incStateInProgress => 'قيد التقدم';

  @override
  String get incStateCompleted => 'مكتملة';

  @override
  String get incStatePending => 'قيد التحقق';

  @override
  String get incStateClosed => 'مغلقة';

  @override
  String incMinRating(String rating) {
    return 'الحد الأدنى للتقييم $rating';
  }

  @override
  String get growUnavailable => 'هذه الميزة غير متاحة.';
}
