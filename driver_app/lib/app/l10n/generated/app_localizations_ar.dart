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
  String get providerOnboardingBadge => 'قريباً';

  @override
  String get providerOnboardingHeadline => 'تم التحقق من رقم هاتفك';

  @override
  String get providerOnboardingBody =>
      'خطوات طلب الانضمام كسائق (المستندات والمركبة والمراجعة) ستتوفر في تحديث قادم. لا يلزمك أي إجراء الآن.';

  @override
  String providerOnboardingVerifiedPhone(String phone) {
    return 'الرقم الموثّق: $phone';
  }
}
