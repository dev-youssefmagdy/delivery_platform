import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_en.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'generated/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ar'),
    Locale('en'),
  ];

  /// Customer app display name
  ///
  /// In ar, this message translates to:
  /// **'سائق المنصة'**
  String get appName;

  /// No description provided for @splashLoading.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ التحميل…'**
  String get splashLoading;

  /// No description provided for @languageTitle.
  ///
  /// In ar, this message translates to:
  /// **'اختر لغتك'**
  String get languageTitle;

  /// No description provided for @languageSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'يمكنك تغيير اللغة لاحقًا من الإعدادات.'**
  String get languageSubtitle;

  /// No description provided for @languageArabicName.
  ///
  /// In ar, this message translates to:
  /// **'العربية'**
  String get languageArabicName;

  /// No description provided for @languageEnglishName.
  ///
  /// In ar, this message translates to:
  /// **'English'**
  String get languageEnglishName;

  /// No description provided for @actionContinue.
  ///
  /// In ar, this message translates to:
  /// **'متابعة'**
  String get actionContinue;

  /// No description provided for @actionSkip.
  ///
  /// In ar, this message translates to:
  /// **'تخطي'**
  String get actionSkip;

  /// No description provided for @actionNext.
  ///
  /// In ar, this message translates to:
  /// **'التالي'**
  String get actionNext;

  /// No description provided for @actionGetStarted.
  ///
  /// In ar, this message translates to:
  /// **'ابدأ الآن'**
  String get actionGetStarted;

  /// No description provided for @actionRetry.
  ///
  /// In ar, this message translates to:
  /// **'إعادة المحاولة'**
  String get actionRetry;

  /// No description provided for @onboarding1Title.
  ///
  /// In ar, this message translates to:
  /// **'استقبل الطلبات'**
  String get onboarding1Title;

  /// No description provided for @onboarding1Body.
  ///
  /// In ar, this message translates to:
  /// **'اعمل متى شئت واستقبل طلبات قريبة منك.'**
  String get onboarding1Body;

  /// No description provided for @onboarding2Title.
  ///
  /// In ar, this message translates to:
  /// **'قدّم عرضك'**
  String get onboarding2Title;

  /// No description provided for @onboarding2Body.
  ///
  /// In ar, this message translates to:
  /// **'اقبل سعر العميل أو اقترح سعرًا مناسبًا لك.'**
  String get onboarding2Body;

  /// No description provided for @onboarding3Title.
  ///
  /// In ar, this message translates to:
  /// **'اربح بشفافية'**
  String get onboarding3Title;

  /// No description provided for @onboarding3Body.
  ///
  /// In ar, this message translates to:
  /// **'تابع أرباحك وعمولتك بوضوح في كل رحلة.'**
  String get onboarding3Body;

  /// No description provided for @authTitle.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول'**
  String get authTitle;

  /// No description provided for @authUnavailableTitle.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول غير متاح بعد'**
  String get authUnavailableTitle;

  /// No description provided for @authUnavailableBody.
  ///
  /// In ar, this message translates to:
  /// **'سيتوفر الدخول برقم الهاتف وتسجيل السائقين في تحديث قادم.'**
  String get authUnavailableBody;

  /// No description provided for @homeTitle.
  ///
  /// In ar, this message translates to:
  /// **'الرئيسية'**
  String get homeTitle;

  /// No description provided for @homeChangeLanguage.
  ///
  /// In ar, this message translates to:
  /// **'تغيير اللغة'**
  String get homeChangeLanguage;

  /// No description provided for @offlineBanner.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد اتصال بالإنترنت'**
  String get offlineBanner;

  /// No description provided for @errorGenericTitle.
  ///
  /// In ar, this message translates to:
  /// **'حدث خطأ ما'**
  String get errorGenericTitle;

  /// No description provided for @errorGenericBody.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر إكمال الطلب. حاول مرة أخرى.'**
  String get errorGenericBody;

  /// No description provided for @errorOfflineTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد اتصال'**
  String get errorOfflineTitle;

  /// No description provided for @errorOfflineBody.
  ///
  /// In ar, this message translates to:
  /// **'تحقق من اتصالك بالإنترنت ثم أعد المحاولة.'**
  String get errorOfflineBody;

  /// No description provided for @errorSessionExpiredTitle.
  ///
  /// In ar, this message translates to:
  /// **'انتهت الجلسة'**
  String get errorSessionExpiredTitle;

  /// No description provided for @errorSessionExpiredBody.
  ///
  /// In ar, this message translates to:
  /// **'سجّل الدخول مرة أخرى للمتابعة.'**
  String get errorSessionExpiredBody;

  /// No description provided for @errorRequestId.
  ///
  /// In ar, this message translates to:
  /// **'رقم المرجع: {id}'**
  String errorRequestId(String id);

  /// No description provided for @emptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد بيانات'**
  String get emptyTitle;

  /// No description provided for @loadingLabel.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ التحميل'**
  String get loadingLabel;

  /// No description provided for @maintenanceTitle.
  ///
  /// In ar, this message translates to:
  /// **'التطبيق تحت الصيانة'**
  String get maintenanceTitle;

  /// No description provided for @maintenanceBody.
  ///
  /// In ar, this message translates to:
  /// **'سنعود قريبًا. نعتذر عن الإزعاج.'**
  String get maintenanceBody;

  /// No description provided for @forceUpdateTitle.
  ///
  /// In ar, this message translates to:
  /// **'تحديث مطلوب'**
  String get forceUpdateTitle;

  /// No description provided for @forceUpdateBody.
  ///
  /// In ar, this message translates to:
  /// **'يرجى تحديث التطبيق إلى أحدث إصدار للمتابعة.'**
  String get forceUpdateBody;

  /// No description provided for @validationRequired.
  ///
  /// In ar, this message translates to:
  /// **'هذا الحقل مطلوب'**
  String get validationRequired;

  /// No description provided for @actionCheckAgain.
  ///
  /// In ar, this message translates to:
  /// **'تحقق مرة أخرى'**
  String get actionCheckAgain;

  /// No description provided for @supportContactLabel.
  ///
  /// In ar, this message translates to:
  /// **'الدعم'**
  String get supportContactLabel;

  /// No description provided for @forceUpdateRequiredVersion.
  ///
  /// In ar, this message translates to:
  /// **'الإصدار المطلوب: {version}'**
  String forceUpdateRequiredVersion(String version);
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar', 'en'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
    case 'en':
      return AppLocalizationsEn();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
