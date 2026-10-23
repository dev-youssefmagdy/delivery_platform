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
  String get actionRefresh => 'تحديث';

  @override
  String get actionBack => 'رجوع';

  @override
  String get navWallet => 'المحفظة';

  @override
  String get navOffers => 'العروض';

  @override
  String get walTitle => 'المحفظة';

  @override
  String get walBalance => 'رصيد المحفظة';

  @override
  String get walNoWallet => 'تُفتح محفظتك عند أول استرداد أو رصيد.';

  @override
  String get walTransactions => 'العمليات';

  @override
  String get walEmptyTitle => 'لا توجد عمليات بعد';

  @override
  String get walEmptyBody => 'ستظهر هنا المبالغ المستردة والأرصدة والرسوم.';

  @override
  String get walLoadMore => 'عرض المزيد';

  @override
  String walBalanceAfter(String amount) {
    return 'الرصيد: $amount';
  }

  @override
  String get walDebtTitle => 'عليك رسوم إلغاء مستحقة';

  @override
  String walDebtBody(String amount) {
    return 'رسوم إلغاء غير مدفوعة: $amount. سدّدها لتتمكن من الحجز دون قيود.';
  }

  @override
  String get walSettleDebt => 'سدّد المبلغ المستحق';

  @override
  String walSettlePay(String amount) {
    return 'ادفع $amount';
  }

  @override
  String get walErrDebtBlocksBooking =>
      'لديك رسوم إلغاء غير مدفوعة. سدّدها من تبويب المحفظة لتتمكن من الحجز مجدداً.';

  @override
  String get walFeeDebtNote =>
      'أُضيفت الرسوم إلى محفظتك كمبلغ مستحق. يمكنك سداده من تبويب المحفظة.';

  @override
  String get walOpenWallet => 'فتح المحفظة';

  @override
  String get bkErrCooldown =>
      'الحجز متوقف لفترة قصيرة بعد تكرار الإلغاء. حاول لاحقاً.';

  @override
  String get pmTitle => 'وسائل الدفع';

  @override
  String get pmIntro =>
      'تُدخل بيانات بطاقتك في صفحة مزود الدفع فقط. نحتفظ بنوع البطاقة وآخر أربعة أرقام فقط.';

  @override
  String get pmEmptyTitle => 'لا توجد وسائل دفع محفوظة';

  @override
  String get pmEmptyBody => 'أضف بطاقة أو محفظة هاتف للدفع بسرعة أكبر.';

  @override
  String get pmCard => 'بطاقة';

  @override
  String get pmWallet => 'محفظة هاتف';

  @override
  String get pmPending => 'بانتظار التأكيد';

  @override
  String get pmDefault => 'الافتراضية';

  @override
  String get pmAddCard => 'إضافة بطاقة';

  @override
  String get pmAddWallet => 'إضافة محفظة هاتف';

  @override
  String get pmDelete => 'إزالة';

  @override
  String get pmDeleteTitle => 'إزالة وسيلة الدفع هذه؟';

  @override
  String get pmDeleteBody => 'يمكنك إضافتها مجدداً لاحقاً.';

  @override
  String get pmDeleted => 'تمت إزالة وسيلة الدفع.';

  @override
  String get pmSetupPending =>
      'ستظهر وسيلة الدفع هنا بعد أن يؤكدها مزود الدفع.';

  @override
  String get pmErrSetupUnsupported =>
      'تُحفظ البطاقة أثناء دفع أجرة رحلة مع هذا المزود.';

  @override
  String get payNow => 'ادفع الآن';

  @override
  String get payDueTitle => 'مبلغ مستحق للدفع';

  @override
  String payCouponSaved(String amount) {
    return 'وفّرت $amount بالكوبون.';
  }

  @override
  String get payNewCard => 'بطاقة أخرى';

  @override
  String get payNewWallet => 'محفظة أخرى';

  @override
  String get payWalletPhone => 'رقم هاتف المحفظة';

  @override
  String get payWalletPhoneHelp => 'اتركه فارغاً لاستخدام هاتف حسابك.';

  @override
  String get paySecureNote => 'ستدفع في صفحة مزود الدفع الآمنة.';

  @override
  String get payAwaitingTitle => 'بانتظار تأكيد الدفع';

  @override
  String get payAwaitingBody =>
      'أتمّ الدفع في صفحة المزود. نتأكد من الدفع لدى المزود قبل اعتباره مدفوعاً، وقد يستغرق ذلك لحظات.';

  @override
  String get payCheckStatus => 'تحقق من حالة الدفع';

  @override
  String get payOpenAgain => 'فتح صفحة الدفع مجدداً';

  @override
  String get payLaunchFailed => 'تعذّر فتح صفحة الدفع. حاول مجدداً.';

  @override
  String get payPaidTitle => 'تم استلام الدفع';

  @override
  String get payPaidBody => 'شكراً لك. تم تأكيد دفعتك.';

  @override
  String get payFailedTitle => 'لم تتم عملية الدفع';

  @override
  String get payTryAgain => 'حاول مجدداً';

  @override
  String get payFailInsufficient =>
      'الرصيد غير كافٍ. استخدم وسيلة أخرى أو حاول مجدداً.';

  @override
  String get payFailExpired => 'انتهت صلاحية البطاقة. استخدم بطاقة أخرى.';

  @override
  String get payFailAuth =>
      'تعذّر على البنك التحقق من الدفع. حاول مجدداً أو استخدم وسيلة أخرى.';

  @override
  String get payFailCancelled => 'تم إلغاء الدفع.';

  @override
  String get payFailTimeout => 'انتهت مهلة صفحة الدفع. ابدأ من جديد.';

  @override
  String get payFailDeclined =>
      'تم رفض الدفع. حاول مجدداً أو استخدم وسيلة أخرى.';

  @override
  String get payReviewTitle => 'نراجع دفعتك';

  @override
  String get payReviewBody =>
      'تعذّر تأكيد هذه الدفعة تلقائياً. لا تدفع مجدداً؛ فريقنا يراجعها.';

  @override
  String get payContactSupport => 'اسأل الدعم عن هذه الدفعة';

  @override
  String get payErrUnavailable =>
      'الدفع الإلكتروني غير متاح حالياً. حاول لاحقاً.';

  @override
  String get payErrMethod =>
      'لا يمكن استخدام وسيلة الدفع هذه. اختر وسيلة أخرى.';

  @override
  String get payErrNotPayable => 'لا يمكن دفع هذه الرحلة الآن.';

  @override
  String get payErrNothingToPay => 'لا يوجد مبلغ للدفع.';

  @override
  String get payErrNeedsReview =>
      'هناك دفعة سابقة قيد المراجعة. يرجى انتظار فريقنا.';

  @override
  String get payErrWalletPhone => 'أدخل رقم هاتف محفظتك.';

  @override
  String get payStatusFailed => 'فشل';

  @override
  String get payStatusRefunded => 'تم الاسترداد';

  @override
  String get payStatusPartRefunded => 'تم استرداد جزء';

  @override
  String get payStatusNotRequired => 'لا يوجد مبلغ للدفع';

  @override
  String get payStatusReview => 'قيد المراجعة';

  @override
  String get bkPaymentOnlineNote => 'يُدفع إلكترونياً بعد الرحلة.';

  @override
  String get tipTitle => 'اترك إكرامية';

  @override
  String get tipBody => 'اختياري. الإكرامية كاملة لسائقك.';

  @override
  String get tipOther => 'مبلغ آخر';

  @override
  String tipAmountLabel(String currency) {
    return 'مبلغ الإكرامية ($currency)';
  }

  @override
  String tipPay(String amount) {
    return 'ادفع إكرامية $amount';
  }

  @override
  String get tipErrNotAvailable => 'الإكرامية غير متاحة لهذه الرحلة.';

  @override
  String get tipErrInvalid => 'أدخل مبلغ إكرامية صحيحاً.';

  @override
  String get tipErrTooLarge => 'الإكرامية أكبر من الحد الأقصى المسموح.';

  @override
  String get tipErrAlreadyGiven => 'لقد تركت إكرامية لهذه الرحلة بالفعل.';

  @override
  String get cpnTitle => 'العروض';

  @override
  String get cpnAvailable => 'عروض لك';

  @override
  String get cpnNoneAvailable =>
      'لا توجد عروض الآن. لديك كود؟ أدخله عند الحجز.';

  @override
  String get cpnAutoNote =>
      'يُطبَّق هذا العرض تلقائياً عند الحجز إذا كنت مستحقاً له.';

  @override
  String get cpnTerms => 'الشروط';

  @override
  String cpnValidUntil(String date) {
    return 'صالح حتى $date';
  }

  @override
  String get cpnHistory => 'استخدام الكوبونات مؤخراً';

  @override
  String get cpnNoHistory => 'لم تستخدم كوبوناً بعد.';

  @override
  String get cpnUsedStatus => 'مستخدم';

  @override
  String get cpnReservedStatus => 'محجوز';

  @override
  String get cpnReleasedStatus => 'أُعيد';

  @override
  String get cpnReversedStatus => 'أُلغي';

  @override
  String get cpnUnknownStatus => 'أخرى';

  @override
  String get cpnFormTitle => 'كوبون';

  @override
  String get cpnFormLabel => 'كود الكوبون';

  @override
  String get cpnApply => 'تطبيق';

  @override
  String get cpnNeedFare => 'حدد أجرتك أولاً للتحقق من الكوبون.';

  @override
  String get cpnNotChecked => 'اضغط تطبيق لمعرفة خصمك. يؤكده الخادم عند الحجز.';

  @override
  String cpnQuoteDiscount(String amount, String title) {
    return 'ستوفر $amount مع $title';
  }

  @override
  String get cpnReasonEligible => 'الكوبون ينطبق.';

  @override
  String get cpnReasonNotFound => 'كود الكوبون غير موجود.';

  @override
  String get cpnReasonNotActive => 'هذا الكوبون غير مفعّل.';

  @override
  String get cpnReasonNotStarted => 'لم يبدأ هذا الكوبون بعد.';

  @override
  String get cpnReasonExpired => 'انتهت صلاحية هذا الكوبون.';

  @override
  String get cpnReasonExhausted => 'هذا الكوبون لم يعد متاحاً.';

  @override
  String get cpnReasonService => 'هذا الكوبون لا يشمل هذه الخدمة.';

  @override
  String get cpnReasonArea => 'هذا الكوبون لا يشمل منطقتك.';

  @override
  String get cpnReasonPayment => 'هذا الكوبون لا يعمل مع وسيلة الدفع المختارة.';

  @override
  String get cpnReasonNewOnly => 'هذا الكوبون للعملاء الجدد فقط.';

  @override
  String get cpnReasonReturningOnly => 'هذا الكوبون للعملاء السابقين فقط.';

  @override
  String get cpnReasonSchedule => 'هذا الكوبون غير صالح في هذا الوقت.';

  @override
  String get cpnReasonMinimum => 'الأجرة أقل من الحد الأدنى لهذا الكوبون.';

  @override
  String get cpnReasonCustomerLimit =>
      'استخدمت هذا الكوبون بالحد الأقصى المسموح.';

  @override
  String get cpnReasonStacking => 'لا يمكن دمج هذا الكوبون مع عرض آخر.';

  @override
  String get cpnReasonCurrency => 'هذا الكوبون لا يطابق عملتك.';

  @override
  String get cpnReasonNoDiscount => 'لا يمنح هذا الكوبون خصماً على هذه الأجرة.';

  @override
  String get cpnReasonUnknown => 'لا يمكن استخدام هذا الكوبون.';

  @override
  String get rcpTitle => 'الإيصال';

  @override
  String get rcpViewFull => 'الإيصال الكامل';

  @override
  String rcpNumber(String number) {
    return 'إيصال $number';
  }

  @override
  String get rcpDriver => 'السائق';

  @override
  String get rcpFrom => 'من';

  @override
  String get rcpTo => 'إلى';

  @override
  String get rcpBreakdown => 'التفاصيل';

  @override
  String rcpAdjustment(String label) {
    return 'إضافة: $label';
  }

  @override
  String get rcpSubtotal => 'المجموع الفرعي';

  @override
  String get rcpDiscount => 'خصم الكوبون';

  @override
  String get rcpServiceFee => 'رسوم الخدمة';

  @override
  String get rcpTax => 'الضريبة';

  @override
  String rcpTaxRate(String rate) {
    return 'الضريبة ($rate%)';
  }

  @override
  String get rcpTip => 'الإكرامية';

  @override
  String get rcpTotal => 'الإجمالي المطلوب';

  @override
  String get rcpOpenPrintable => 'فتح الإيصال للطباعة';

  @override
  String get adjTitle => 'تغييرات الأجرة';

  @override
  String get adjApprove => 'موافقة';

  @override
  String get adjReject => 'رفض';

  @override
  String get adjWithdraw => 'سحب الطلب';

  @override
  String get adjApproved => 'وافقت على التغيير. تم تحديث الأجرة.';

  @override
  String get adjRejected => 'رفضت التغيير.';

  @override
  String get adjRequestedByYou => 'طلبته أنت';

  @override
  String get adjRequestedByDriver => 'طلبه السائق';

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
  String get adjSheetTitle => 'يطلب سائقك تغيير الأجرة';

  @override
  String adjSheetBody(String type) {
    return 'الطلب: $type. لا تتغير الأجرة إلا إذا وافقت.';
  }

  @override
  String get adjOldTotal => 'الإجمالي الحالي';

  @override
  String get adjNewTotalLabel => 'الإجمالي عند الموافقة';

  @override
  String get adjNoteOptional => 'ملاحظة (اختيارية)';

  @override
  String get adjDecideLater => 'قرر لاحقاً';

  @override
  String get adjErrNotPending => 'تمت الإجابة عن هذا الطلب أو انتهت مدته.';

  @override
  String get adjErrCounterpart => 'يمكن للطرف الآخر فقط الإجابة عن هذا الطلب.';

  @override
  String get scHelpers => 'عدد العمال';

  @override
  String get scPacking => 'التغليف';

  @override
  String get scStairs => 'السلالم';

  @override
  String get scAssembly => 'فك وتركيب الأثاث';

  @override
  String get scEquipment => 'معدات التحميل';

  @override
  String get scWaiting => 'مدة الانتظار المجانية';

  @override
  String get scExtraStops => 'محطات إضافية مشمولة';

  @override
  String get scTolls => 'رسوم الطرق';

  @override
  String get scParking => 'الانتظار والركن';

  @override
  String get scLegendIncluded => 'مشمول';

  @override
  String get scLegendExcluded => 'غير مشمول';

  @override
  String get scLegendAtCost => 'بالتكلفة';

  @override
  String get scSummaryTitle => 'ما يشمله العرض';

  @override
  String get scAgreedTitle => 'ما تم الاتفاق عليه';

  @override
  String get scRequestedTitle => 'ما طلبته';

  @override
  String get adjAmountLabel => 'المبلغ المقترح';

  @override
  String get adjErrAmount => 'أدخل مبلغاً صحيحاً أكبر من الصفر.';

  @override
  String get adjErrFloors => 'أدخل عدد الأدوار الإضافية.';

  @override
  String get adjErrHelpers => 'عدد العمال الإضافيين غير مسموح.';

  @override
  String get adjErrNotNow => 'لا يمكن طلب تغيير الآن.';

  @override
  String get adjErrReason => 'اكتب السبب (3 أحرف على الأقل).';

  @override
  String get adjErrScopeItem => 'اختر البند المعني من الاتفاق.';

  @override
  String get adjErrWithinScope =>
      'هذا البند مشمول في الاتفاق ولا يمكن تحميل مبلغ إضافي عنه.';

  @override
  String adjScopeItemLine(String item) {
    return 'بند الاتفاق: $item';
  }

  @override
  String adjHelpersLine(int count) {
    return '$count عمال';
  }

  @override
  String adjFloorsLine(int count) {
    return '$count أدوار';
  }

  @override
  String get adjHelpersCount => 'عدد العمال';

  @override
  String get adjPickItem => 'البند المعني من الاتفاق';

  @override
  String get adjReasonLabel => 'السبب';

  @override
  String get adjRequestButton => 'طلب تغيير في الاتفاق';

  @override
  String get adjRequestSent => 'تم إرسال طلبك للسائق.';

  @override
  String get adjSend => 'إرسال الطلب';

  @override
  String get adjTypeHelper => 'عامل إضافي';

  @override
  String get adjTypeScope => 'تغيير في الاتفاق';

  @override
  String get dcCustodyTitle => 'مسار الشحنة';

  @override
  String get dcCustodyNotStarted => 'لم يتم استلام الشحنة بعد.';

  @override
  String get dcCustodyPickedUp => 'تم الاستلام من المرسل';

  @override
  String get dcCustodyInTransit => 'في الطريق';

  @override
  String get dcCustodyArrived => 'وصلت إلى نقطة التسليم';

  @override
  String get dcCustodyFailed => 'تعذر التسليم';

  @override
  String get dcCustodyReturning => 'في طريق العودة للمرسل';

  @override
  String get dcCustodyDelivered => 'تم التسليم';

  @override
  String get dcCustodyReturned => 'تم الإرجاع للمرسل';

  @override
  String get dcCustodyUnknown => 'حالة غير معروفة';

  @override
  String get dcOtpTitle => 'رمز المستلم';

  @override
  String get dcOtpWaiting => 'لم يُرسل الرمز بعد';

  @override
  String get dcOtpSent => 'أُرسل الرمز للمستلم';

  @override
  String get dcOtpSmsFailed => 'تعذر إرسال الرسالة';

  @override
  String get dcOtpVerified => 'تم التحقق';

  @override
  String get dcOtpLocked => 'الرمز موقوف';

  @override
  String dcOtpAttemptsLeft(int left) {
    return 'المحاولات المتبقية: $left';
  }

  @override
  String get dcOtpShareHint =>
      'نرسل رمزاً برسالة نصية إلى المستلم، ويقوله للمندوب عند استلام الشحنة. لا ترسله لأي شخص آخر.';

  @override
  String get dcProofsTitle => 'إثباتات الاستلام والتسليم';

  @override
  String get dcNoProofs => 'لا توجد إثباتات بعد.';

  @override
  String get dcProofLoadError => 'تعذر تحميل الصورة. اضغط للمحاولة.';

  @override
  String get dcProofPhoto => 'صورة';

  @override
  String get dcProofSignature => 'توقيع';

  @override
  String get dcProofOtp => 'رمز المستلم';

  @override
  String get dcProofPin => 'رمز البدء';

  @override
  String get dcProofChecklist => 'قائمة فحص';

  @override
  String get dcProofWeight => 'الوزن';

  @override
  String get dcProofOther => 'إثبات';

  @override
  String get dcStagePickup => 'عند الاستلام';

  @override
  String get dcStageDelivery => 'عند التسليم';

  @override
  String get dcStageReturn => 'عند الإرجاع';

  @override
  String get dcChkSealed => 'الطرد مغلق';

  @override
  String get dcChkUndamaged => 'الطرد سليم بلا أضرار';

  @override
  String get dcChkLabel => 'البيانات مطابقة للملصق';

  @override
  String get dcChkRecipient => 'تم التأكد من هوية المستلم';

  @override
  String dcWeightValue(String kg) {
    return 'الوزن المقاس: $kg كجم';
  }

  @override
  String get dcAttemptsTitle => 'محاولات التسليم';

  @override
  String dcAttemptNumber(int number) {
    return 'المحاولة $number';
  }

  @override
  String get dcAttemptEvidence => 'مرفق صورة';

  @override
  String get dcPending => 'بانتظار قرارك';

  @override
  String get dcOutUnavailable => 'المستلم غير متاح';

  @override
  String get dcOutRefused => 'المستلم رفض الاستلام';

  @override
  String get dcOutWrongAddress => 'العنوان خاطئ';

  @override
  String get dcOutOtpFailed => 'تعذر التحقق من الرمز';

  @override
  String get dcOutRestricted => 'الشحنة تحتوي على مواد ممنوعة';

  @override
  String get dcOutOther => 'سبب آخر';

  @override
  String get dcResRetry => 'إعادة محاولة التسليم';

  @override
  String get dcResReturn => 'إرجاع للمرسل';

  @override
  String get dcResOperations => 'تحويل لفريق العمليات';

  @override
  String get dcResUnknown => 'قرار غير معروف';

  @override
  String get dcRoleSender => 'المرسل';

  @override
  String get dcRoleRecipient => 'المستلم';

  @override
  String get dcRolePickup => 'جهة الاتصال عند التحميل';

  @override
  String get dcRoleDropoff => 'جهة الاتصال عند التسليم';

  @override
  String get dcContactsTitle => 'جهات الاتصال';

  @override
  String get dcFailedTitle => 'تعذر تسليم شحنتك';

  @override
  String get dcFailedChoose => 'اختر ما يحدث للشحنة الآن.';

  @override
  String get dcShowProofs => 'عرض صور الإثبات';

  @override
  String get dcHideProofs => 'إخفاء صور الإثبات';

  @override
  String get dcErrAttemptDone => 'تم اتخاذ قرار بشأن هذه المحاولة بالفعل.';

  @override
  String get dcErrAttemptsExhausted =>
      'استُنفدت محاولات التسليم. اختر الإرجاع أو العمليات.';

  @override
  String get dcErrScopeMissing => 'لا يوجد اتفاق نقل لهذا الطلب بعد.';

  @override
  String get rgTitle => 'إقرار بالمواد الممنوعة';

  @override
  String get rgIntro => 'اقرأ سياسة المواد الممنوعة قبل إرسال الطرد.';

  @override
  String get rgReadPolicy => 'قراءة السياسة';

  @override
  String rgAcknowledge(int version) {
    return 'أقر بأن طردي لا يحتوي على مواد ممنوعة (النسخة $version)';
  }

  @override
  String get rgErrRequired => 'يجب الإقرار بسياسة المواد الممنوعة.';

  @override
  String get rgErrStale => 'تغيرت السياسة. اقرأها وأقر بها من جديد.';

  @override
  String get rgErrUnavailable => 'تعذر تحميل سياسة المواد الممنوعة الآن.';

  @override
  String get lfLoadTitle => 'الحمولة';

  @override
  String lfLoadWeight(String kg) {
    return 'الوزن: $kg كجم';
  }

  @override
  String lfLoadVolume(String volume) {
    return 'الحجم: $volume م³';
  }

  @override
  String lfLoadValue(String value) {
    return 'قيمة البضاعة: $value';
  }

  @override
  String lfLoadWindow(String start, String end) {
    return 'التحميل من $start إلى $end';
  }

  @override
  String get lfFileVideo => 'فيديو';

  @override
  String get lfFileDocument => 'مستند';

  @override
  String get lfItemNote => 'ملاحظة عن هذا الغرض';

  @override
  String get lfItemWeight => 'الوزن';

  @override
  String get lfItemDimensionsUnit => 'سم';

  @override
  String get lfPickVideo => 'فيديو من المعرض';

  @override
  String get lfUploading => 'جارٍ رفع الملف…';

  @override
  String get lfErrVolume => 'حجم الحمولة أكبر من الحد المسموح لهذه الخدمة.';

  @override
  String get lfErrWindowIncomplete => 'حدد بداية ونهاية موعد التحميل.';

  @override
  String get lfErrWindowPast => 'موعد التحميل في الماضي.';

  @override
  String get lfErrWindowInvalid => 'نهاية موعد التحميل يجب أن تكون بعد بدايته.';

  @override
  String get lfErrWindowLong => 'موعد التحميل طويل جداً.';

  @override
  String get lfErrAttachmentInvalid =>
      'أحد الملفات المرفقة غير صالح. أعد رفعه.';

  @override
  String get lfErrAttachmentLimit =>
      'تجاوزت الحد المسموح من الصور أو الفيديوهات أو المستندات.';

  @override
  String get lfErrPendingLimit =>
      'لديك ملفات مرفوعة كثيرة لم تُستخدم. احذف بعضها أو أكمل الحجز.';

  @override
  String get lfErrInUse => 'هذا الملف مرتبط بحجز ولا يمكن حذفه.';

  @override
  String get lfErrInfected => 'رُفض الملف لأسباب أمنية.';

  @override
  String get lfErrTooLarge => 'حجم الملف أكبر من المسموح.';

  @override
  String get lfErrTooSmall => 'الصورة صغيرة جداً.';

  @override
  String get lfErrFileType => 'نوع الملف غير مدعوم.';

  @override
  String get lfErrScanner => 'فحص الملفات غير متاح الآن. حاول بعد قليل.';

  @override
  String get lfErrUploadFailed => 'تعذر رفع الملف. حاول مرة أخرى.';

  @override
  String get stpTitle => 'محطات إضافية';

  @override
  String get stpIntro =>
      'أضف محطات في الطريق مع اسم وهاتف من سيقابل السائق في كل محطة.';

  @override
  String get stpAdd => 'إضافة محطة';

  @override
  String stpMax(int max) {
    return 'وصلت إلى الحد الأقصى للمحطات ($max).';
  }

  @override
  String stpNumbered(int number, String place) {
    return 'المحطة $number: $place';
  }

  @override
  String get stpMoveUp => 'تحريك لأعلى';

  @override
  String get stpMoveDown => 'تحريك لأسفل';

  @override
  String get stpRemove => 'حذف المحطة';

  @override
  String get stpContactName => 'اسم جهة الاتصال';

  @override
  String get stpContactPhone => 'هاتف جهة الاتصال';

  @override
  String get stpNote => 'ملاحظة للسائق';

  @override
  String get stpErrName => 'اكتب اسم جهة الاتصال.';

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
  String get refTile => 'ادعُ أصدقاءك';

  @override
  String get credTitle => 'رصيدي الترويجي';

  @override
  String get credAvailable => 'الرصيد المتاح';

  @override
  String get credNone => 'لا يوجد رصيد بعد.';

  @override
  String get credSourceReferral => 'مكافأة إحالة';

  @override
  String get credSourceLoyalty => 'مكافأة ولاء';

  @override
  String get credStateActive => 'نشط';

  @override
  String get credStateExpired => 'منتهي';

  @override
  String get credStateReversed => 'معكوس';

  @override
  String credExpiresOn(String date) {
    return 'ينتهي $date';
  }

  @override
  String credRemaining(String amount) {
    return 'المتبقي: $amount';
  }

  @override
  String get credHistory => 'الأرصدة';

  @override
  String get loyTitle => 'الولاء';

  @override
  String loyCompletedJobs(int jobs) {
    return '$jobs طلب مكتمل';
  }

  @override
  String get loyCurrent => 'مستواك';

  @override
  String get loyNone => 'لا يوجد مستوى بعد';

  @override
  String loyToNext(int jobs, String level) {
    return '$jobs طلب إضافي للوصول إلى $level';
  }

  @override
  String get loyTop => 'وصلت إلى أعلى مستوى';

  @override
  String get loyLevels => 'المستويات';

  @override
  String loyLevelJobs(int jobs) {
    return '$jobs طلب';
  }

  @override
  String loyLevelReward(String amount) {
    return 'رصيد: $amount';
  }

  @override
  String get loyReached => 'تم بلوغه';
}
