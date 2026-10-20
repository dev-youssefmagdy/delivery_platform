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
}
