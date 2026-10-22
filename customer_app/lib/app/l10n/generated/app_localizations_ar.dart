// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appName => 'عميل المنصة';

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
  String get onboarding1Title => 'حدّد سعر رحلتك';

  @override
  String get onboarding1Body => 'اطلب وجهتك واقترح السعر الذي يناسبك.';

  @override
  String get onboarding2Title => 'قارن العروض';

  @override
  String get onboarding2Body =>
      'يصلك أكثر من عرض من سائقين قريبين، واختر الأنسب.';

  @override
  String get onboarding3Title => 'تنقّل بأمان';

  @override
  String get onboarding3Body => 'تابع رحلتك لحظة بلحظة وشاركها مع من تثق به.';

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
  String get profileTitle => 'أكمل ملفك الشخصي';

  @override
  String get profileSubtitle => 'أخبرنا باسمك ليعرف السائقون من سيقابلون.';

  @override
  String get profileNameLabel => 'الاسم الكامل';

  @override
  String get profileNameInvalid => 'أدخل اسمك (حرفان على الأقل)';

  @override
  String get profileEmailLabel => 'البريد الإلكتروني (اختياري)';

  @override
  String get profileEmailInvalid => 'أدخل بريداً إلكترونياً صحيحاً';

  @override
  String get consentTitle => 'الشروط والخصوصية';

  @override
  String get consentSubtitle => 'يرجى القراءة والموافقة للمتابعة.';

  @override
  String get consentRead => 'قراءة';

  @override
  String get consentAccept => 'أوافق على شروط الاستخدام وسياسة الخصوصية';

  @override
  String get consentMarketing => 'أرغب في تلقي العروض والأخبار (اختياري)';

  @override
  String get consentPolicyTerms => 'شروط الاستخدام';

  @override
  String get consentPolicyPrivacy => 'سياسة الخصوصية';

  @override
  String get consentPolicyOther => 'سياسة';

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
  String homeGreeting(String name) {
    return 'مرحباً، $name';
  }

  @override
  String get homeLogout => 'تسجيل الخروج';

  @override
  String get homeDeleteAccount => 'حذف حسابي';

  @override
  String get deleteAccountTitle => 'حذف حسابك؟';

  @override
  String get deleteAccountBody =>
      'سنراجع طلبك. قد يتم الاحتفاظ ببعض البيانات حيث يشترط القانون ذلك. يمكنك الإلغاء ما دام الطلب قيد المعالجة.';

  @override
  String get deleteAccountConfirm => 'طلب الحذف';

  @override
  String get actionCancel => 'إلغاء';

  @override
  String get catalogTitle => 'اختر الخدمة';

  @override
  String get catalogEmptyTitle => 'لا توجد خدمات متاحة';

  @override
  String get catalogEmptyBody => 'ستظهر الخدمات هنا فور توفرها في منطقتك.';

  @override
  String catalogCapacityPassengers(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count راكب',
      many: '$count راكبًا',
      few: '$count ركاب',
      two: 'راكبان',
      one: 'راكب واحد',
    );
    return '$_temp0';
  }

  @override
  String catalogCapacityLuggage(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count حقيبة',
      many: '$count حقيبة',
      few: '$count حقائب',
      two: 'حقيبتان',
      one: 'حقيبة واحدة',
    );
    return '$_temp0';
  }

  @override
  String catalogCapacityWeight(String kg) {
    return 'حتى $kg كجم';
  }

  @override
  String catalogFareFrom(String amount) {
    return 'ابتداءً من $amount';
  }

  @override
  String get catalogNegotiable => 'حدد أجرتك';

  @override
  String get catalogSelectedLabel => 'الخدمة المختارة';

  @override
  String get catalogDetailsButton => 'تفاصيل الخدمة';

  @override
  String get catalogDetailsTitle => 'تفاصيل الخدمة';

  @override
  String get catalogDetailsIntro =>
      'أخبرنا بتفاصيل طلبك ليقدّم السائقون السعر المناسب.';

  @override
  String get catalogDetailsNone => 'هذه الخدمة لا تحتاج إلى تفاصيل إضافية.';

  @override
  String get catalogDetailsSave => 'حفظ التفاصيل';

  @override
  String get catalogDetailsSaved => 'تم حفظ التفاصيل';

  @override
  String get catalogDetailsFixErrors => 'يرجى تصحيح الحقول المظللة.';

  @override
  String catalogVehiclesLabel(String names) {
    return 'المركبات: $names';
  }

  @override
  String formTooShort(num limit) {
    return 'أدخل $limit أحرف على الأقل';
  }

  @override
  String formTooLong(num limit) {
    return 'أدخل $limit حرفًا كحد أقصى';
  }

  @override
  String formTooSmall(num limit) {
    return 'يجب ألا تقل القيمة عن $limit';
  }

  @override
  String formTooLarge(num limit) {
    return 'يجب ألا تزيد القيمة عن $limit';
  }

  @override
  String get formNotANumber => 'أدخل رقمًا صحيحًا';

  @override
  String get formNotWhole => 'أدخل عددًا صحيحًا بدون كسور';

  @override
  String get formInvalidChoice => 'اختر خيارًا صالحًا';

  @override
  String formTooFewItems(num limit) {
    return 'اختر $limit على الأقل';
  }

  @override
  String formTooManyItems(num limit) {
    return 'اختر $limit كحد أقصى';
  }

  @override
  String get formInvalidPhone => 'أدخل رقم هاتف صحيحًا مع رمز الدولة';

  @override
  String get formInvalidDate => 'أدخل تاريخًا أو وقتًا صحيحًا';

  @override
  String get formIncomplete => 'أكمل جميع أجزاء هذا الحقل';

  @override
  String get formYes => 'نعم';

  @override
  String get formNo => 'لا';

  @override
  String get formChoose => 'اختر…';

  @override
  String get formContactName => 'الاسم';

  @override
  String get formContactPhone => 'الهاتف (مع رمز الدولة)';

  @override
  String get formLength => 'الطول (سم)';

  @override
  String get formWidth => 'العرض (سم)';

  @override
  String get formHeight => 'الارتفاع (سم)';

  @override
  String get formKilograms => 'كجم';

  @override
  String get formQuantity => 'الكمية';

  @override
  String get formAddPhoto => 'إضافة صورة';

  @override
  String get formRemovePhoto => 'حذف الصورة';

  @override
  String get formPhotoCamera => 'الكاميرا';

  @override
  String get formPhotoGallery => 'المعرض';

  @override
  String get formPickDate => 'اختر التاريخ';

  @override
  String get formPickTime => 'اختر الوقت';

  @override
  String get mapPickupLabel => 'نقطة الاستلام';

  @override
  String get mapDestinationLabel => 'الوجهة';

  @override
  String get mapPickupHint => 'من أين؟';

  @override
  String get mapDestinationHint => 'إلى أين؟';

  @override
  String get mapSwap => 'تبديل نقطة الاستلام والوجهة';

  @override
  String get mapMyLocation => 'موقعي';

  @override
  String get mapCurrentLocation => 'الموقع الحالي';

  @override
  String get mapPinnedLocation => 'موقع محدد على الخريطة';

  @override
  String get mapApproximate => 'موقع تقريبي. العنوان الدقيق غير مؤكد.';

  @override
  String get mapLocating => 'جارٍ تحديد موقعك…';

  @override
  String get mapRationaleTitle => 'استخدام موقعك؟';

  @override
  String get mapRationaleBody =>
      'نستخدم موقعك لتحديد نقطة الاستلام وعرض الخدمات القريبة منك. يمكنك أيضاً البحث عن عنوان بدلاً من ذلك.';

  @override
  String get mapRationaleAllow => 'السماح بالموقع';

  @override
  String get mapRationaleNotNow => 'ليس الآن';

  @override
  String get mapPermissionDenied =>
      'الموقع متوقف. اسمح به أو اختر نقطة الاستلام يدوياً.';

  @override
  String get mapPermissionBlocked =>
      'الموقع محظور لهذا التطبيق. افتح الإعدادات للسماح به أو اختر نقطة الاستلام يدوياً.';

  @override
  String get mapServiceDisabled =>
      'موقع الجهاز متوقف. شغّله أو اختر نقطة الاستلام يدوياً.';

  @override
  String get mapLocateFailed => 'تعذر تحديد موقعك. اختر نقطة الاستلام يدوياً.';

  @override
  String get mapOpenSettings => 'فتح الإعدادات';

  @override
  String get mapChoosePickup => 'اختيار نقطة الاستلام يدوياً';

  @override
  String get mapOutsideTitle => 'خارج نطاق خدمتنا';

  @override
  String get mapOutsideBody =>
      'لا نخدم موقع الاستلام هذا بعد. اختر نقطة استلام أخرى.';

  @override
  String get mapAreaCheckFailed => 'تعذر التحقق من توفر الخدمة هنا.';

  @override
  String get mapRestrictedPickup =>
      'قد يكون الاستلام هنا مقيّداً. اتبع نقاط الاستلام المحددة.';

  @override
  String mapServing(String city) {
    return 'نخدم $city';
  }

  @override
  String get mapsUnavailable =>
      'الخرائط غير متاحة مؤقتاً. حاول مرة أخرى بعد قليل.';

  @override
  String get searchPickupTitle => 'اختر نقطة الاستلام';

  @override
  String get searchDestinationTitle => 'اختر الوجهة';

  @override
  String get searchPlaceTitle => 'البحث عن مكان';

  @override
  String get searchFieldHint => 'ابحث عن مكان أو عنوان';

  @override
  String get searchMinChars => 'اكتب حرفين على الأقل للبحث.';

  @override
  String get searchEmptyTitle => 'لا توجد أماكن مطابقة';

  @override
  String get searchEmptyBody => 'جرّب اسماً آخر أو اختر النقطة على الخريطة.';

  @override
  String get searchOnMap => 'اختيار من الخريطة';

  @override
  String get searchUseCurrent => 'استخدام الموقع الحالي';

  @override
  String get searchSavedHeader => 'الأماكن المحفوظة';

  @override
  String get searchResultsHeader => 'النتائج';

  @override
  String get searchUnavailable =>
      'البحث غير متاح مؤقتاً. ما زال بإمكانك اختيار نقطة من الخريطة.';

  @override
  String get pinInstruction => 'حرّك الخريطة لوضع الدبوس';

  @override
  String get pinConfirm => 'تأكيد الموقع';

  @override
  String get pinResolving => 'جارٍ البحث عن العنوان…';

  @override
  String get pinNoAddress => 'لا يوجد عنوان لهذه النقطة.';

  @override
  String get pinLookupFailed =>
      'البحث عن العنوان غير متاح. يمكنك استخدام هذه النقطة مع ذلك.';

  @override
  String get pinOutside => 'هذه النقطة خارج نطاق خدمتنا.';

  @override
  String get savedPlacesTitle => 'الأماكن المحفوظة';

  @override
  String get savedPlacesAdd => 'إضافة مكان';

  @override
  String get savedPlacesEmptyTitle => 'لا توجد أماكن محفوظة بعد';

  @override
  String get savedPlacesEmptyBody => 'احفظ المنزل والعمل للحجز بشكل أسرع.';

  @override
  String get savedPlaceTypeHome => 'المنزل';

  @override
  String get savedPlaceTypeWork => 'العمل';

  @override
  String get savedPlaceTypeOther => 'أخرى';

  @override
  String get savedPlaceNameField => 'الاسم';

  @override
  String get savedPlaceTypeField => 'النوع';

  @override
  String get savedPlaceNameRequired => 'أدخل اسماً';

  @override
  String get savedPlaceSave => 'حفظ';

  @override
  String get savedPlaceDeleteTitle => 'حذف هذا المكان؟';

  @override
  String get savedPlaceDeleteBody => 'ستتم إزالته من أماكنك المحفوظة.';

  @override
  String get savedPlaceDelete => 'حذف';

  @override
  String get savedPlaceTypeTaken =>
      'لديك مكان محفوظ من هذا النوع. احذفه أولاً.';

  @override
  String get savedPlaceLimit => 'وصلت إلى الحد الأقصى للأماكن المحفوظة.';

  @override
  String get homeSavedPlaces => 'الأماكن المحفوظة';

  @override
  String get navHome => 'الرئيسية';

  @override
  String get navTrips => 'رحلاتي';

  @override
  String get navAccount => 'حسابي';

  @override
  String get homeRequest => 'اطلب الآن';

  @override
  String get homeRequestHintPickup => 'حدد نقطة الانطلاق أولاً.';

  @override
  String get homeRequestHintDestination => 'حدد وجهتك للمتابعة.';

  @override
  String get homeRequestHintService => 'اختر الخدمة المناسبة للمتابعة.';

  @override
  String get homeRequestHintActive =>
      'لديك طلب جارٍ بالفعل. أكمله أو ألغِه قبل طلب جديد.';

  @override
  String get homeActiveBooking => 'طلبك الحالي';

  @override
  String get homeActiveBookingOpen => 'عرض';

  @override
  String get bkFormTitle => 'تأكيد الطلب';

  @override
  String get bkFormIncompleteTitle => 'بيانات الرحلة غير مكتملة';

  @override
  String get bkFormIncompleteBody =>
      'حدد نقطة الانطلاق والوجهة والخدمة من الشاشة الرئيسية.';

  @override
  String get bkFormBackHome => 'العودة';

  @override
  String get bkFormRouteLabel => 'خريطة المسار';

  @override
  String get bkEstimateFailed => 'تعذر حساب المسار حالياً.';

  @override
  String bkRouteFacts(String km, int minutes) {
    return 'المسافة $km كم · حوالي $minutes دقيقة';
  }

  @override
  String get bkPassengers => 'عدد الركاب';

  @override
  String get bkGuidanceTitle => 'السعر المرجعي (للاسترشاد فقط)';

  @override
  String bkGuidanceRange(String low, String high) {
    return 'من $low إلى $high';
  }

  @override
  String bkGuidanceRecommended(String fare) {
    return 'السعر المقترح: $fare';
  }

  @override
  String get bkGuidanceFactors => 'كيف حُسب';

  @override
  String get bkGuidanceNotice =>
      'هذا تقدير مرجعي فقط، وأنت من يحدد السعر الذي تعرضه.';

  @override
  String get bkGuidanceUnavailable =>
      'لا يتوفر سعر مرجعي لهذه الخدمة. حدد السعر الذي يناسبك.';

  @override
  String get bkFactorBase => 'رسم البدء';

  @override
  String get bkFactorDistance => 'المسافة';

  @override
  String get bkFactorDuration => 'المدة';

  @override
  String get bkFactorStops => 'التوقفات';

  @override
  String get bkFactorMinimum => 'الحد الأدنى للأجرة';

  @override
  String get bkFareTitle => 'السعر الذي تعرضه';

  @override
  String bkFareLabel(String currency) {
    return 'السعر ($currency)';
  }

  @override
  String get bkFareRequired => 'أدخل السعر الذي تعرضه.';

  @override
  String get bkFareInvalid => 'السعر غير صالح.';

  @override
  String bkFareBelowMin(String min) {
    return 'السعر أقل من الحد الأدنى ($min).';
  }

  @override
  String bkFareAboveMax(String max) {
    return 'السعر أعلى من الحد الأقصى ($max).';
  }

  @override
  String bkFareBoundsBoth(String min, String max) {
    return 'المسموح من $min إلى $max';
  }

  @override
  String bkFareBoundsMin(String min) {
    return 'الحد الأدنى $min';
  }

  @override
  String bkFareBoundsMax(String max) {
    return 'الحد الأقصى $max';
  }

  @override
  String get bkPaymentTitle => 'طريقة الدفع';

  @override
  String get bkPaymentCash => 'نقداً';

  @override
  String get bkPaymentOther => 'وسيلة أخرى';

  @override
  String get bkPaymentCashUnavailable => 'الدفع النقدي غير متاح لهذه الخدمة.';

  @override
  String get bkSummaryTitle => 'ملخص الطلب';

  @override
  String bkSummaryFare(String fare) {
    return 'السعر: $fare';
  }

  @override
  String get bkSummaryFareNone => 'لم يُحدد بعد';

  @override
  String bkSummaryPayment(String method) {
    return 'الدفع: $method';
  }

  @override
  String get bkSubmit => 'اطلب الآن';

  @override
  String get bkStatusDraft => 'مسودة';

  @override
  String get bkStatusSearching => 'جارٍ البحث عن عروض';

  @override
  String get bkStatusOffersReceived => 'وصلتك عروض';

  @override
  String get bkStatusExpired => 'لم نجد مقدم خدمة';

  @override
  String get bkStatusProviderSelected => 'تم اختيار مقدم الخدمة';

  @override
  String get bkStatusHeading => 'مقدم الخدمة في الطريق إليك';

  @override
  String get bkStatusArrived => 'وصل مقدم الخدمة';

  @override
  String get bkStatusInProgress => 'الرحلة جارية';

  @override
  String get bkStatusDeliveryFailed => 'تعذر التسليم';

  @override
  String get bkStatusCompletedPending => 'مكتملة، بانتظار الدفع';

  @override
  String get bkStatusCompleted => 'مكتملة';

  @override
  String get bkStatusDisputed => 'محل نزاع';

  @override
  String get bkStatusCancelledCustomer => 'ألغيتها أنت';

  @override
  String get bkStatusCancelledProvider => 'ألغاها مقدم الخدمة';

  @override
  String get bkStatusCancelledOps => 'ألغتها الإدارة';

  @override
  String get bkStatusUnknown => 'حالة غير معروفة';

  @override
  String get bkScreenTitle => 'الطلب';

  @override
  String get bkYourFare => 'سعرك المعروض';

  @override
  String get bkAgreedFare => 'السعر المتفق عليه';

  @override
  String get bkSearching => 'جارٍ البحث عن مقدمي خدمة قريبين…';

  @override
  String bkSearchingLeft(String time) {
    return 'جارٍ البحث… المتبقي $time';
  }

  @override
  String get bkSearchEnding => 'ينتهي البحث الآن…';

  @override
  String get bkOffersTitle => 'العروض';

  @override
  String get bkSortPrice => 'الأقل سعراً';

  @override
  String get bkSortEta => 'الأسرع وصولاً';

  @override
  String get bkSortRating => 'الأعلى تقييماً';

  @override
  String get bkEditFare => 'تعديل السعر';

  @override
  String get bkCancelBooking => 'إلغاء الطلب';

  @override
  String get bkCancelTrip => 'إلغاء الرحلة';

  @override
  String get bkWaitingOffersTitle => 'بانتظار العروض';

  @override
  String get bkWaitingOffersBody =>
      'أرسلنا طلبك إلى مقدمي الخدمة القريبين. ستظهر العروض هنا فور وصولها.';

  @override
  String get bkNoOffersTitle => 'لم تصلك عروض بعد';

  @override
  String get bkNoOffersBody =>
      'انتهت مدة البحث دون عروض. يمكنك تعديل السعر أو إلغاء الطلب.';

  @override
  String get bkNoOffersNoGuarantee => 'رفع السعر لا يضمن وصول عروض.';

  @override
  String get bkOfferProviderFallback => 'مقدم الخدمة';

  @override
  String get bkOfferCounter => 'عرض سعر مختلف';

  @override
  String get bkOfferAcceptsFare => 'يقبل سعرك';

  @override
  String get bkOfferAccept => 'قبول';

  @override
  String get bkOfferReject => 'رفض';

  @override
  String bkOfferEta(int minutes) {
    return 'يصل خلال $minutes د';
  }

  @override
  String bkOfferDistance(String km) {
    return 'يبعد $km كم';
  }

  @override
  String bkOfferTrips(int count) {
    return '$count رحلة';
  }

  @override
  String bkOfferRating(String avg, int count) {
    return 'التقييم $avg ($count)';
  }

  @override
  String bkOfferRatingNew(String avg, int count) {
    return '$avg ($count) تقييم أولي';
  }

  @override
  String bkOfferExpiresIn(String time) {
    return 'ينتهي العرض بعد $time';
  }

  @override
  String get bkOfferExpired => 'انتهى هذا العرض';

  @override
  String get bkAcceptTitle => 'قبول العرض؟';

  @override
  String get bkAcceptBody => 'سيتم اختيار مقدم الخدمة هذا لرحلتك.';

  @override
  String bkAcceptBodyPrice(String price, String name) {
    return 'قبول عرض $name بسعر $price؟';
  }

  @override
  String get bkEditFareTitle => 'تعديل السعر';

  @override
  String get bkEditFareWarn => 'سيُعاد البحث عن مقدمي خدمة بالسعر الجديد.';

  @override
  String get bkEditFareWarnOffers =>
      'تنبيه: تعديل السعر يلغي العروض الحالية ويعيد البحث من جديد.';

  @override
  String get bkEditFareNoGuarantee => 'رفع السعر لا يضمن وصول عروض.';

  @override
  String get bkEditFareSave => 'حفظ السعر';

  @override
  String get bkFareUpdated => 'تم تحديث السعر وإعادة البحث.';

  @override
  String get bkLiveMapLabel => 'خريطة الرحلة المباشرة';

  @override
  String get bkLocationWaiting => 'بانتظار موقع مقدم الخدمة…';

  @override
  String bkLocationUpdated(int seconds) {
    return 'آخر تحديث للموقع منذ $seconds ث';
  }

  @override
  String bkLocationStale(int minutes) {
    return 'موقع مقدم الخدمة قديم (منذ $minutes د أو أكثر). قد يكون الاتصال ضعيفاً.';
  }

  @override
  String get bkSupportFlagged =>
      'لاحظنا أمراً يحتاج مراجعة وفريق الدعم على علم به.';

  @override
  String get bkPinTitle => 'رمز بدء الرحلة';

  @override
  String get bkPinHint =>
      'أخبر مقدم الخدمة بهذا الرمز عند الركوب. لا تشاركه مع أحد غيره.';

  @override
  String get bkPinLocked =>
      'تم إيقاف بدء الرحلة بعد محاولات رمز خاطئة. تواصل مع الدعم.';

  @override
  String bkFreeWaitLeft(String time) {
    return 'وقت الانتظار المجاني المتبقي $time';
  }

  @override
  String get bkFreeWaitOver => 'انتهى وقت الانتظار المجاني';

  @override
  String get bkStepSelected => 'تم اختيار مقدم الخدمة';

  @override
  String get bkStepHeading => 'في الطريق إليك';

  @override
  String get bkStepArrived => 'وصل إلى نقطة الانطلاق';

  @override
  String get bkStepStarted => 'بدأت الرحلة';

  @override
  String get bkTimelineTitle => 'مراحل الرحلة';

  @override
  String get bkShareTrip => 'مشاركة الرحلة';

  @override
  String get bkShareStop => 'إيقاف المشاركة';

  @override
  String get bkShareStopped => 'تم إيقاف مشاركة الرحلة.';

  @override
  String bkShareMessage(String url) {
    return 'تابع رحلتي مباشرة: $url';
  }

  @override
  String get bkSafety => 'الأمان';

  @override
  String get bkSafetyTitle => 'الأمان والمساعدة';

  @override
  String get bkSafetyNotice =>
      'خاصية الطوارئ (SOS) الكاملة قادمة قريباً. في الحالات الخطرة اتصل بخدمات الطوارئ المحلية مباشرة. هذه جهات الاتصال المتاحة حالياً.';

  @override
  String get bkSafetyYourContact => 'جهة اتصال الطوارئ الخاصة بك';

  @override
  String get bkSafetySupport => 'دعم المنصة';

  @override
  String get bkSafetyWhatsapp => 'واتساب الدعم';

  @override
  String get bkSafetyNothingConfigured =>
      'لم تتم إضافة جهات اتصال بعد. يمكنك إضافة جهة اتصال طوارئ من ملفك الشخصي.';

  @override
  String get bkCancelTitle => 'إلغاء الطلب';

  @override
  String get bkCancelReasonTitle => 'سبب الإلغاء';

  @override
  String get bkCancelNote => 'ملاحظة (اختياري)';

  @override
  String get bkCancelPickReason => 'اختر سبب الإلغاء.';

  @override
  String get bkCancelContinue => 'متابعة';

  @override
  String get bkCancelKeep => 'الإبقاء على الطلب';

  @override
  String get bkCancelFree => 'الإلغاء الآن مجاني.';

  @override
  String bkCancelFeeDue(String fee) {
    return 'رسوم الإلغاء: $fee';
  }

  @override
  String get bkCancelConfirm => 'تأكيد الإلغاء';

  @override
  String bkCancelConfirmFee(String fee) {
    return 'تأكيد الإلغاء ودفع $fee';
  }

  @override
  String get bkCancelledTitle => 'تم إلغاء الطلب';

  @override
  String bkCancelledFee(String fee) {
    return 'رسوم الإلغاء: $fee';
  }

  @override
  String get bkCancelledNoFee => 'دون رسوم.';

  @override
  String get bkReasonChangedMind => 'غيرت رأيي';

  @override
  String get bkReasonWrongPickup => 'نقطة الانطلاق خاطئة';

  @override
  String get bkReasonFoundAnother => 'وجدت وسيلة أخرى';

  @override
  String get bkReasonDriverTooFar => 'مقدم الخدمة بعيد';

  @override
  String get bkReasonDriverAsked => 'طلب مني مقدم الخدمة الإلغاء';

  @override
  String get bkReasonWaitedTooLong => 'انتظرت طويلاً';

  @override
  String get bkReasonOther => 'سبب آخر';

  @override
  String get bkNoProviderTitle => 'لم نجد مقدم خدمة';

  @override
  String get bkNoProviderBody =>
      'انتهى البحث دون عروض. يمكنك المحاولة مرة أخرى أو تعديل السعر.';

  @override
  String get bkTryAgain => 'حاول مرة أخرى';

  @override
  String get bkEditPriceAgain => 'تعديل السعر والمحاولة';

  @override
  String get bkRebook => 'اطلب مرة أخرى';

  @override
  String get bkReceiptTitle => 'الإيصال';

  @override
  String get bkReceiptCode => 'رقم الطلب';

  @override
  String get bkReceiptTotal => 'الإجمالي';

  @override
  String get bkReceiptPayment => 'طريقة الدفع';

  @override
  String get bkReceiptPaymentStatus => 'حالة الدفع';

  @override
  String get bkReceiptDuration => 'مدة الرحلة';

  @override
  String get bkReceiptDistance => 'المسافة المخططة';

  @override
  String bkMinutes(int count) {
    return '$count دقيقة';
  }

  @override
  String bkKilometres(String km) {
    return '$km كم';
  }

  @override
  String get bkPaymentStatusCashDue => 'نقداً عند الوصول';

  @override
  String get bkPaymentStatusPaid => 'مدفوعة';

  @override
  String get bkPaymentStatusPending => 'قيد الانتظار';

  @override
  String get bkPaymentStatusOther => 'غير محدد';

  @override
  String get bkRatingTitle => 'قيّم مقدم الخدمة';

  @override
  String get bkRatingPickStars => 'اختر عدد النجوم.';

  @override
  String get bkRatingComment => 'تعليق خاص (اختياري)';

  @override
  String get bkRatingSubmit => 'إرسال التقييم';

  @override
  String get bkRatingThanks => 'شكراً لتقييمك.';

  @override
  String get bkRating1 => 'سيئة';

  @override
  String get bkRating2 => 'مقبولة';

  @override
  String get bkRating3 => 'جيدة';

  @override
  String get bkRating4 => 'جيدة جداً';

  @override
  String get bkRating5 => 'ممتازة';

  @override
  String get bkTagProfessional => 'محترف';

  @override
  String get bkTagCleanVehicle => 'مركبة نظيفة';

  @override
  String get bkTagSafeDriving => 'قيادة آمنة';

  @override
  String get bkTagGoodCommunication => 'تواصل جيد';

  @override
  String get bkTagOnTime => 'في الموعد';

  @override
  String get bkTagCarefulWithItems => 'حرص على الأغراض';

  @override
  String get bkTagLate => 'تأخر';

  @override
  String get bkTagUnsafeDriving => 'قيادة غير آمنة';

  @override
  String get bkTagRude => 'أسلوب غير لائق';

  @override
  String get bkTagDirtyVehicle => 'مركبة غير نظيفة';

  @override
  String get bkReportIssue => 'الإبلاغ عن مشكلة';

  @override
  String get bkReportTitle => 'الإبلاغ عن مشكلة';

  @override
  String get bkReportDescription => 'صف ما حدث';

  @override
  String get bkReportDescriptionShort => 'اكتب وصفاً من 10 أحرف على الأقل.';

  @override
  String get bkReportPickCategory => 'اختر نوع المشكلة.';

  @override
  String get bkReportSubmit => 'إرسال البلاغ';

  @override
  String get bkReportSent => 'تم إرسال بلاغك وسنراجعه.';

  @override
  String get bkReportSafety => 'السلامة';

  @override
  String get bkReportBehaviour => 'السلوك';

  @override
  String get bkReportFareDispute => 'خلاف على السعر';

  @override
  String get bkReportRoute => 'المسار أو التأخير';

  @override
  String get bkReportLostItem => 'شيء مفقود';

  @override
  String get bkReportOther => 'أخرى';

  @override
  String get bkErrOutsideArea => 'هذا الموقع خارج منطقة الخدمة.';

  @override
  String get bkErrCategoryUnavailable =>
      'هذه الخدمة غير متاحة حالياً في موقعك.';

  @override
  String get bkErrDropoffRequired => 'حدد الوجهة لهذه الخدمة.';

  @override
  String get bkErrFareBelow => 'السعر أقل من الحد الأدنى المسموح.';

  @override
  String get bkErrFareAbove => 'السعر أعلى من الحد الأقصى المسموح.';

  @override
  String get bkErrCapacity =>
      'تفاصيل الحمولة أو عدد الركاب تتجاوز المسموح لهذه الخدمة.';

  @override
  String get bkErrPayment => 'طريقة الدفع غير متاحة لهذه الخدمة.';

  @override
  String get bkErrActiveBooking => 'لديك طلب جارٍ بالفعل.';

  @override
  String get bkErrAccount =>
      'لا يمكن تنفيذ الطلب بسبب حالة حسابك. تواصل مع الدعم.';

  @override
  String get bkErrMaps => 'خدمة الخرائط غير متاحة مؤقتاً. حاول بعد قليل.';

  @override
  String get bkErrNotEditable =>
      'لا يمكن تعديل الطلب بعد اختيار مقدم الخدمة أو انتهاء البحث.';

  @override
  String get bkErrChangedMeanwhile =>
      'تغيّر الطلب في هذه الأثناء. حدّثنا الشاشة، حاول مرة أخرى.';

  @override
  String get bkErrAlreadyAssigned =>
      'اختيار مقدم خدمة آخر تم بالفعل لهذا الطلب. حدّثنا الشاشة.';

  @override
  String get bkErrOfferExpired => 'انتهى هذا العرض. حدّثنا قائمة العروض.';

  @override
  String get bkErrOfferGone => 'لم يعد هذا العرض متاحاً. حدّثنا قائمة العروض.';

  @override
  String get bkErrSearchEnded => 'انتهى البحث عن عروض لهذا الطلب.';

  @override
  String get bkErrNotCancellable => 'لا يمكن إلغاء هذا الطلب الآن.';

  @override
  String get bkErrFeeChanged =>
      'تغيّرت رسوم الإلغاء. راجع الرسوم الجديدة ثم أكّد.';

  @override
  String get bkErrCancelNote => 'اكتب ملاحظة توضح سبب الإلغاء.';

  @override
  String get bkErrShare => 'لا يمكن مشاركة هذه الرحلة الآن.';

  @override
  String get bkErrRatingDone => 'قيّمت هذه الرحلة بالفعل.';

  @override
  String get bkErrRatingClosed => 'لا يمكن تقييم هذه الرحلة الآن.';

  @override
  String get bkErrReportClosed => 'لا يمكن الإبلاغ عن هذه الرحلة الآن.';

  @override
  String get bkErrReportLimit =>
      'وصلت إلى الحد الأقصى من البلاغات لهذه الرحلة.';

  @override
  String get bkErrInProgress =>
      'العملية السابقة ما زالت قيد التنفيذ. انتظر لحظات.';

  @override
  String get tripsTitle => 'رحلاتي';

  @override
  String get tripsEmptyTitle => 'لا توجد رحلات بعد';

  @override
  String get tripsEmptyBody => 'ستظهر هنا طلباتك ورحلاتك السابقة.';

  @override
  String get tripsEmptyFilteredTitle => 'لا نتائج بهذه التصفية';

  @override
  String get tripsEmptyFilteredBody => 'جرّب تغيير التصفية أو مسحها.';

  @override
  String get tripsFilter => 'تصفية';

  @override
  String get tripsFilterStatus => 'الحالة';

  @override
  String get tripsFilterService => 'الخدمة';

  @override
  String get tripsFilterAllServices => 'كل الخدمات';

  @override
  String get tripsFilterFrom => 'من تاريخ';

  @override
  String get tripsFilterTo => 'إلى تاريخ';

  @override
  String get tripsStatusCompleted => 'مكتملة';

  @override
  String get tripsStatusCancelled => 'ملغاة';

  @override
  String get tripsStatusActive => 'جارية';

  @override
  String get tripsApply => 'تطبيق';

  @override
  String get tripsClearFilters => 'مسح التصفية';

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
}
