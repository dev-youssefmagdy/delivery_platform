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
  String get authUnavailableTitle => 'تسجيل الدخول غير متاح بعد';

  @override
  String get authUnavailableBody => 'سيتوفر الدخول برقم الهاتف في تحديث قادم.';

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
}
