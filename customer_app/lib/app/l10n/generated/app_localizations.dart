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
  /// **'عميل المنصة'**
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
  /// **'حدّد سعر رحلتك'**
  String get onboarding1Title;

  /// No description provided for @onboarding1Body.
  ///
  /// In ar, this message translates to:
  /// **'اطلب وجهتك واقترح السعر الذي يناسبك.'**
  String get onboarding1Body;

  /// No description provided for @onboarding2Title.
  ///
  /// In ar, this message translates to:
  /// **'قارن العروض'**
  String get onboarding2Title;

  /// No description provided for @onboarding2Body.
  ///
  /// In ar, this message translates to:
  /// **'يصلك أكثر من عرض من سائقين قريبين، واختر الأنسب.'**
  String get onboarding2Body;

  /// No description provided for @onboarding3Title.
  ///
  /// In ar, this message translates to:
  /// **'تنقّل بأمان'**
  String get onboarding3Title;

  /// No description provided for @onboarding3Body.
  ///
  /// In ar, this message translates to:
  /// **'تابع رحلتك لحظة بلحظة وشاركها مع من تثق به.'**
  String get onboarding3Body;

  /// No description provided for @authTitle.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الدخول'**
  String get authTitle;

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

  /// No description provided for @authPhoneTitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم هاتفك'**
  String get authPhoneTitle;

  /// No description provided for @authPhoneSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'سنرسل لك رمز تحقق عبر رسالة نصية.'**
  String get authPhoneSubtitle;

  /// No description provided for @authPhoneLabel.
  ///
  /// In ar, this message translates to:
  /// **'رقم الهاتف'**
  String get authPhoneLabel;

  /// No description provided for @authPhoneInvalid.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم هاتف صحيحاً'**
  String get authPhoneInvalid;

  /// No description provided for @authSendCode.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الرمز'**
  String get authSendCode;

  /// No description provided for @otpTitle.
  ///
  /// In ar, this message translates to:
  /// **'رمز التحقق'**
  String get otpTitle;

  /// No description provided for @otpSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أدخل الرمز المكوّن من {length} أرقام المرسل إلى {phone}'**
  String otpSubtitle(int length, String phone);

  /// No description provided for @otpResendIn.
  ///
  /// In ar, this message translates to:
  /// **'إعادة الإرسال خلال {seconds} ث'**
  String otpResendIn(int seconds);

  /// No description provided for @otpResend.
  ///
  /// In ar, this message translates to:
  /// **'إعادة إرسال الرمز'**
  String get otpResend;

  /// No description provided for @otpChangeNumber.
  ///
  /// In ar, this message translates to:
  /// **'تغيير الرقم'**
  String get otpChangeNumber;

  /// No description provided for @otpErrorInvalid.
  ///
  /// In ar, this message translates to:
  /// **'الرمز غير صحيح.'**
  String get otpErrorInvalid;

  /// No description provided for @otpErrorExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت صلاحية الرمز. اطلب رمزاً جديداً.'**
  String get otpErrorExpired;

  /// No description provided for @otpErrorLocked.
  ///
  /// In ar, this message translates to:
  /// **'محاولات خاطئة كثيرة. اطلب رمزاً جديداً.'**
  String get otpErrorLocked;

  /// No description provided for @otpErrorTooManyRequests.
  ///
  /// In ar, this message translates to:
  /// **'محاولات كثيرة. حاول بعد {seconds} ثانية.'**
  String otpErrorTooManyRequests(int seconds);

  /// No description provided for @otpErrorDeliveryFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر إرسال الرمز. حاول لاحقاً.'**
  String get otpErrorDeliveryFailed;

  /// No description provided for @profileTitle.
  ///
  /// In ar, this message translates to:
  /// **'أكمل ملفك الشخصي'**
  String get profileTitle;

  /// No description provided for @profileSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'أخبرنا باسمك ليعرف السائقون من سيقابلون.'**
  String get profileSubtitle;

  /// No description provided for @profileNameLabel.
  ///
  /// In ar, this message translates to:
  /// **'الاسم الكامل'**
  String get profileNameLabel;

  /// No description provided for @profileNameInvalid.
  ///
  /// In ar, this message translates to:
  /// **'أدخل اسمك (حرفان على الأقل)'**
  String get profileNameInvalid;

  /// No description provided for @profileEmailLabel.
  ///
  /// In ar, this message translates to:
  /// **'البريد الإلكتروني (اختياري)'**
  String get profileEmailLabel;

  /// No description provided for @profileEmailInvalid.
  ///
  /// In ar, this message translates to:
  /// **'أدخل بريداً إلكترونياً صحيحاً'**
  String get profileEmailInvalid;

  /// No description provided for @consentTitle.
  ///
  /// In ar, this message translates to:
  /// **'الشروط والخصوصية'**
  String get consentTitle;

  /// No description provided for @consentSubtitle.
  ///
  /// In ar, this message translates to:
  /// **'يرجى القراءة والموافقة للمتابعة.'**
  String get consentSubtitle;

  /// No description provided for @consentRead.
  ///
  /// In ar, this message translates to:
  /// **'قراءة'**
  String get consentRead;

  /// No description provided for @consentAccept.
  ///
  /// In ar, this message translates to:
  /// **'أوافق على شروط الاستخدام وسياسة الخصوصية'**
  String get consentAccept;

  /// No description provided for @consentMarketing.
  ///
  /// In ar, this message translates to:
  /// **'أرغب في تلقي العروض والأخبار (اختياري)'**
  String get consentMarketing;

  /// No description provided for @consentPolicyTerms.
  ///
  /// In ar, this message translates to:
  /// **'شروط الاستخدام'**
  String get consentPolicyTerms;

  /// No description provided for @consentPolicyPrivacy.
  ///
  /// In ar, this message translates to:
  /// **'سياسة الخصوصية'**
  String get consentPolicyPrivacy;

  /// No description provided for @consentPolicyOther.
  ///
  /// In ar, this message translates to:
  /// **'سياسة'**
  String get consentPolicyOther;

  /// No description provided for @statusSuspendedTitle.
  ///
  /// In ar, this message translates to:
  /// **'الحساب موقوف'**
  String get statusSuspendedTitle;

  /// No description provided for @statusSuspendedBody.
  ///
  /// In ar, this message translates to:
  /// **'حسابك موقوف. يرجى التواصل مع الدعم.'**
  String get statusSuspendedBody;

  /// No description provided for @statusRestrictedTitle.
  ///
  /// In ar, this message translates to:
  /// **'الحساب مقيّد'**
  String get statusRestrictedTitle;

  /// No description provided for @statusRestrictedBody.
  ///
  /// In ar, this message translates to:
  /// **'بعض الميزات محدودة على حسابك. تواصل مع الدعم لمعرفة التفاصيل.'**
  String get statusRestrictedBody;

  /// No description provided for @statusDeletionPendingTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم طلب الحذف'**
  String get statusDeletionPendingTitle;

  /// No description provided for @statusDeletionPendingBody.
  ///
  /// In ar, this message translates to:
  /// **'حسابك بانتظار الحذف. يمكنك إلغاء الطلب ما دام قيد المعالجة.'**
  String get statusDeletionPendingBody;

  /// No description provided for @statusVerificationTitle.
  ///
  /// In ar, this message translates to:
  /// **'التحقق مطلوب'**
  String get statusVerificationTitle;

  /// No description provided for @statusVerificationBody.
  ///
  /// In ar, this message translates to:
  /// **'يجب التحقق من حسابك قبل المتابعة. تواصل مع الدعم.'**
  String get statusVerificationBody;

  /// No description provided for @statusDeletedTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم حذف الحساب'**
  String get statusDeletedTitle;

  /// No description provided for @statusDeletedBody.
  ///
  /// In ar, this message translates to:
  /// **'تم حذف هذا الحساب.'**
  String get statusDeletedBody;

  /// No description provided for @statusCancelDeletion.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء طلب الحذف'**
  String get statusCancelDeletion;

  /// No description provided for @homeGreeting.
  ///
  /// In ar, this message translates to:
  /// **'مرحباً، {name}'**
  String homeGreeting(String name);

  /// No description provided for @homeLogout.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الخروج'**
  String get homeLogout;

  /// No description provided for @homeDeleteAccount.
  ///
  /// In ar, this message translates to:
  /// **'حذف حسابي'**
  String get homeDeleteAccount;

  /// No description provided for @deleteAccountTitle.
  ///
  /// In ar, this message translates to:
  /// **'حذف حسابك؟'**
  String get deleteAccountTitle;

  /// No description provided for @deleteAccountBody.
  ///
  /// In ar, this message translates to:
  /// **'سنراجع طلبك. قد يتم الاحتفاظ ببعض البيانات حيث يشترط القانون ذلك. يمكنك الإلغاء ما دام الطلب قيد المعالجة.'**
  String get deleteAccountBody;

  /// No description provided for @deleteAccountConfirm.
  ///
  /// In ar, this message translates to:
  /// **'طلب الحذف'**
  String get deleteAccountConfirm;

  /// No description provided for @actionCancel.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء'**
  String get actionCancel;

  /// No description provided for @catalogTitle.
  ///
  /// In ar, this message translates to:
  /// **'اختر الخدمة'**
  String get catalogTitle;

  /// No description provided for @catalogEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد خدمات متاحة'**
  String get catalogEmptyTitle;

  /// No description provided for @catalogEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'ستظهر الخدمات هنا فور توفرها في منطقتك.'**
  String get catalogEmptyBody;

  /// No description provided for @catalogCapacityPassengers.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =1{راكب واحد} =2{راكبان} few{{count} ركاب} many{{count} راكبًا} other{{count} راكب}}'**
  String catalogCapacityPassengers(int count);

  /// No description provided for @catalogCapacityLuggage.
  ///
  /// In ar, this message translates to:
  /// **'{count, plural, =1{حقيبة واحدة} =2{حقيبتان} few{{count} حقائب} many{{count} حقيبة} other{{count} حقيبة}}'**
  String catalogCapacityLuggage(int count);

  /// No description provided for @catalogCapacityWeight.
  ///
  /// In ar, this message translates to:
  /// **'حتى {kg} كجم'**
  String catalogCapacityWeight(String kg);

  /// No description provided for @catalogFareFrom.
  ///
  /// In ar, this message translates to:
  /// **'ابتداءً من {amount}'**
  String catalogFareFrom(String amount);

  /// No description provided for @catalogNegotiable.
  ///
  /// In ar, this message translates to:
  /// **'حدد أجرتك'**
  String get catalogNegotiable;

  /// No description provided for @catalogSelectedLabel.
  ///
  /// In ar, this message translates to:
  /// **'الخدمة المختارة'**
  String get catalogSelectedLabel;

  /// No description provided for @catalogDetailsButton.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل الخدمة'**
  String get catalogDetailsButton;

  /// No description provided for @catalogDetailsTitle.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل الخدمة'**
  String get catalogDetailsTitle;

  /// No description provided for @catalogDetailsIntro.
  ///
  /// In ar, this message translates to:
  /// **'أخبرنا بتفاصيل طلبك ليقدّم السائقون السعر المناسب.'**
  String get catalogDetailsIntro;

  /// No description provided for @catalogDetailsNone.
  ///
  /// In ar, this message translates to:
  /// **'هذه الخدمة لا تحتاج إلى تفاصيل إضافية.'**
  String get catalogDetailsNone;

  /// No description provided for @catalogDetailsSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ التفاصيل'**
  String get catalogDetailsSave;

  /// No description provided for @catalogDetailsSaved.
  ///
  /// In ar, this message translates to:
  /// **'تم حفظ التفاصيل'**
  String get catalogDetailsSaved;

  /// No description provided for @catalogDetailsFixErrors.
  ///
  /// In ar, this message translates to:
  /// **'يرجى تصحيح الحقول المظللة.'**
  String get catalogDetailsFixErrors;

  /// No description provided for @catalogVehiclesLabel.
  ///
  /// In ar, this message translates to:
  /// **'المركبات: {names}'**
  String catalogVehiclesLabel(String names);

  /// No description provided for @formTooShort.
  ///
  /// In ar, this message translates to:
  /// **'أدخل {limit} أحرف على الأقل'**
  String formTooShort(num limit);

  /// No description provided for @formTooLong.
  ///
  /// In ar, this message translates to:
  /// **'أدخل {limit} حرفًا كحد أقصى'**
  String formTooLong(num limit);

  /// No description provided for @formTooSmall.
  ///
  /// In ar, this message translates to:
  /// **'يجب ألا تقل القيمة عن {limit}'**
  String formTooSmall(num limit);

  /// No description provided for @formTooLarge.
  ///
  /// In ar, this message translates to:
  /// **'يجب ألا تزيد القيمة عن {limit}'**
  String formTooLarge(num limit);

  /// No description provided for @formNotANumber.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقمًا صحيحًا'**
  String get formNotANumber;

  /// No description provided for @formNotWhole.
  ///
  /// In ar, this message translates to:
  /// **'أدخل عددًا صحيحًا بدون كسور'**
  String get formNotWhole;

  /// No description provided for @formInvalidChoice.
  ///
  /// In ar, this message translates to:
  /// **'اختر خيارًا صالحًا'**
  String get formInvalidChoice;

  /// No description provided for @formTooFewItems.
  ///
  /// In ar, this message translates to:
  /// **'اختر {limit} على الأقل'**
  String formTooFewItems(num limit);

  /// No description provided for @formTooManyItems.
  ///
  /// In ar, this message translates to:
  /// **'اختر {limit} كحد أقصى'**
  String formTooManyItems(num limit);

  /// No description provided for @formInvalidPhone.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم هاتف صحيحًا مع رمز الدولة'**
  String get formInvalidPhone;

  /// No description provided for @formInvalidDate.
  ///
  /// In ar, this message translates to:
  /// **'أدخل تاريخًا أو وقتًا صحيحًا'**
  String get formInvalidDate;

  /// No description provided for @formIncomplete.
  ///
  /// In ar, this message translates to:
  /// **'أكمل جميع أجزاء هذا الحقل'**
  String get formIncomplete;

  /// No description provided for @formYes.
  ///
  /// In ar, this message translates to:
  /// **'نعم'**
  String get formYes;

  /// No description provided for @formNo.
  ///
  /// In ar, this message translates to:
  /// **'لا'**
  String get formNo;

  /// No description provided for @formChoose.
  ///
  /// In ar, this message translates to:
  /// **'اختر…'**
  String get formChoose;

  /// No description provided for @formContactName.
  ///
  /// In ar, this message translates to:
  /// **'الاسم'**
  String get formContactName;

  /// No description provided for @formContactPhone.
  ///
  /// In ar, this message translates to:
  /// **'الهاتف (مع رمز الدولة)'**
  String get formContactPhone;

  /// No description provided for @formLength.
  ///
  /// In ar, this message translates to:
  /// **'الطول (سم)'**
  String get formLength;

  /// No description provided for @formWidth.
  ///
  /// In ar, this message translates to:
  /// **'العرض (سم)'**
  String get formWidth;

  /// No description provided for @formHeight.
  ///
  /// In ar, this message translates to:
  /// **'الارتفاع (سم)'**
  String get formHeight;

  /// No description provided for @formKilograms.
  ///
  /// In ar, this message translates to:
  /// **'كجم'**
  String get formKilograms;

  /// No description provided for @formQuantity.
  ///
  /// In ar, this message translates to:
  /// **'الكمية'**
  String get formQuantity;

  /// No description provided for @formAddPhoto.
  ///
  /// In ar, this message translates to:
  /// **'إضافة صورة'**
  String get formAddPhoto;

  /// No description provided for @formRemovePhoto.
  ///
  /// In ar, this message translates to:
  /// **'حذف الصورة'**
  String get formRemovePhoto;

  /// No description provided for @formPhotoCamera.
  ///
  /// In ar, this message translates to:
  /// **'الكاميرا'**
  String get formPhotoCamera;

  /// No description provided for @formPhotoGallery.
  ///
  /// In ar, this message translates to:
  /// **'المعرض'**
  String get formPhotoGallery;

  /// No description provided for @formPickDate.
  ///
  /// In ar, this message translates to:
  /// **'اختر التاريخ'**
  String get formPickDate;

  /// No description provided for @formPickTime.
  ///
  /// In ar, this message translates to:
  /// **'اختر الوقت'**
  String get formPickTime;

  /// No description provided for @mapPickupLabel.
  ///
  /// In ar, this message translates to:
  /// **'نقطة الاستلام'**
  String get mapPickupLabel;

  /// No description provided for @mapDestinationLabel.
  ///
  /// In ar, this message translates to:
  /// **'الوجهة'**
  String get mapDestinationLabel;

  /// No description provided for @mapPickupHint.
  ///
  /// In ar, this message translates to:
  /// **'من أين؟'**
  String get mapPickupHint;

  /// No description provided for @mapDestinationHint.
  ///
  /// In ar, this message translates to:
  /// **'إلى أين؟'**
  String get mapDestinationHint;

  /// No description provided for @mapSwap.
  ///
  /// In ar, this message translates to:
  /// **'تبديل نقطة الاستلام والوجهة'**
  String get mapSwap;

  /// No description provided for @mapMyLocation.
  ///
  /// In ar, this message translates to:
  /// **'موقعي'**
  String get mapMyLocation;

  /// No description provided for @mapCurrentLocation.
  ///
  /// In ar, this message translates to:
  /// **'الموقع الحالي'**
  String get mapCurrentLocation;

  /// No description provided for @mapPinnedLocation.
  ///
  /// In ar, this message translates to:
  /// **'موقع محدد على الخريطة'**
  String get mapPinnedLocation;

  /// No description provided for @mapApproximate.
  ///
  /// In ar, this message translates to:
  /// **'موقع تقريبي. العنوان الدقيق غير مؤكد.'**
  String get mapApproximate;

  /// No description provided for @mapLocating.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ تحديد موقعك…'**
  String get mapLocating;

  /// No description provided for @mapRationaleTitle.
  ///
  /// In ar, this message translates to:
  /// **'استخدام موقعك؟'**
  String get mapRationaleTitle;

  /// No description provided for @mapRationaleBody.
  ///
  /// In ar, this message translates to:
  /// **'نستخدم موقعك لتحديد نقطة الاستلام وعرض الخدمات القريبة منك. يمكنك أيضاً البحث عن عنوان بدلاً من ذلك.'**
  String get mapRationaleBody;

  /// No description provided for @mapRationaleAllow.
  ///
  /// In ar, this message translates to:
  /// **'السماح بالموقع'**
  String get mapRationaleAllow;

  /// No description provided for @mapRationaleNotNow.
  ///
  /// In ar, this message translates to:
  /// **'ليس الآن'**
  String get mapRationaleNotNow;

  /// No description provided for @mapPermissionDenied.
  ///
  /// In ar, this message translates to:
  /// **'الموقع متوقف. اسمح به أو اختر نقطة الاستلام يدوياً.'**
  String get mapPermissionDenied;

  /// No description provided for @mapPermissionBlocked.
  ///
  /// In ar, this message translates to:
  /// **'الموقع محظور لهذا التطبيق. افتح الإعدادات للسماح به أو اختر نقطة الاستلام يدوياً.'**
  String get mapPermissionBlocked;

  /// No description provided for @mapServiceDisabled.
  ///
  /// In ar, this message translates to:
  /// **'موقع الجهاز متوقف. شغّله أو اختر نقطة الاستلام يدوياً.'**
  String get mapServiceDisabled;

  /// No description provided for @mapLocateFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحديد موقعك. اختر نقطة الاستلام يدوياً.'**
  String get mapLocateFailed;

  /// No description provided for @mapOpenSettings.
  ///
  /// In ar, this message translates to:
  /// **'فتح الإعدادات'**
  String get mapOpenSettings;

  /// No description provided for @mapChoosePickup.
  ///
  /// In ar, this message translates to:
  /// **'اختيار نقطة الاستلام يدوياً'**
  String get mapChoosePickup;

  /// No description provided for @mapOutsideTitle.
  ///
  /// In ar, this message translates to:
  /// **'خارج نطاق خدمتنا'**
  String get mapOutsideTitle;

  /// No description provided for @mapOutsideBody.
  ///
  /// In ar, this message translates to:
  /// **'لا نخدم موقع الاستلام هذا بعد. اختر نقطة استلام أخرى.'**
  String get mapOutsideBody;

  /// No description provided for @mapAreaCheckFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر التحقق من توفر الخدمة هنا.'**
  String get mapAreaCheckFailed;

  /// No description provided for @mapRestrictedPickup.
  ///
  /// In ar, this message translates to:
  /// **'قد يكون الاستلام هنا مقيّداً. اتبع نقاط الاستلام المحددة.'**
  String get mapRestrictedPickup;

  /// No description provided for @mapServing.
  ///
  /// In ar, this message translates to:
  /// **'نخدم {city}'**
  String mapServing(String city);

  /// No description provided for @mapsUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'الخرائط غير متاحة مؤقتاً. حاول مرة أخرى بعد قليل.'**
  String get mapsUnavailable;

  /// No description provided for @searchPickupTitle.
  ///
  /// In ar, this message translates to:
  /// **'اختر نقطة الاستلام'**
  String get searchPickupTitle;

  /// No description provided for @searchDestinationTitle.
  ///
  /// In ar, this message translates to:
  /// **'اختر الوجهة'**
  String get searchDestinationTitle;

  /// No description provided for @searchPlaceTitle.
  ///
  /// In ar, this message translates to:
  /// **'البحث عن مكان'**
  String get searchPlaceTitle;

  /// No description provided for @searchFieldHint.
  ///
  /// In ar, this message translates to:
  /// **'ابحث عن مكان أو عنوان'**
  String get searchFieldHint;

  /// No description provided for @searchMinChars.
  ///
  /// In ar, this message translates to:
  /// **'اكتب حرفين على الأقل للبحث.'**
  String get searchMinChars;

  /// No description provided for @searchEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد أماكن مطابقة'**
  String get searchEmptyTitle;

  /// No description provided for @searchEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'جرّب اسماً آخر أو اختر النقطة على الخريطة.'**
  String get searchEmptyBody;

  /// No description provided for @searchOnMap.
  ///
  /// In ar, this message translates to:
  /// **'اختيار من الخريطة'**
  String get searchOnMap;

  /// No description provided for @searchUseCurrent.
  ///
  /// In ar, this message translates to:
  /// **'استخدام الموقع الحالي'**
  String get searchUseCurrent;

  /// No description provided for @searchSavedHeader.
  ///
  /// In ar, this message translates to:
  /// **'الأماكن المحفوظة'**
  String get searchSavedHeader;

  /// No description provided for @searchResultsHeader.
  ///
  /// In ar, this message translates to:
  /// **'النتائج'**
  String get searchResultsHeader;

  /// No description provided for @searchUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'البحث غير متاح مؤقتاً. ما زال بإمكانك اختيار نقطة من الخريطة.'**
  String get searchUnavailable;

  /// No description provided for @pinInstruction.
  ///
  /// In ar, this message translates to:
  /// **'حرّك الخريطة لوضع الدبوس'**
  String get pinInstruction;

  /// No description provided for @pinConfirm.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد الموقع'**
  String get pinConfirm;

  /// No description provided for @pinResolving.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ البحث عن العنوان…'**
  String get pinResolving;

  /// No description provided for @pinNoAddress.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد عنوان لهذه النقطة.'**
  String get pinNoAddress;

  /// No description provided for @pinLookupFailed.
  ///
  /// In ar, this message translates to:
  /// **'البحث عن العنوان غير متاح. يمكنك استخدام هذه النقطة مع ذلك.'**
  String get pinLookupFailed;

  /// No description provided for @pinOutside.
  ///
  /// In ar, this message translates to:
  /// **'هذه النقطة خارج نطاق خدمتنا.'**
  String get pinOutside;

  /// No description provided for @savedPlacesTitle.
  ///
  /// In ar, this message translates to:
  /// **'الأماكن المحفوظة'**
  String get savedPlacesTitle;

  /// No description provided for @savedPlacesAdd.
  ///
  /// In ar, this message translates to:
  /// **'إضافة مكان'**
  String get savedPlacesAdd;

  /// No description provided for @savedPlacesEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد أماكن محفوظة بعد'**
  String get savedPlacesEmptyTitle;

  /// No description provided for @savedPlacesEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'احفظ المنزل والعمل للحجز بشكل أسرع.'**
  String get savedPlacesEmptyBody;

  /// No description provided for @savedPlaceTypeHome.
  ///
  /// In ar, this message translates to:
  /// **'المنزل'**
  String get savedPlaceTypeHome;

  /// No description provided for @savedPlaceTypeWork.
  ///
  /// In ar, this message translates to:
  /// **'العمل'**
  String get savedPlaceTypeWork;

  /// No description provided for @savedPlaceTypeOther.
  ///
  /// In ar, this message translates to:
  /// **'أخرى'**
  String get savedPlaceTypeOther;

  /// No description provided for @savedPlaceNameField.
  ///
  /// In ar, this message translates to:
  /// **'الاسم'**
  String get savedPlaceNameField;

  /// No description provided for @savedPlaceTypeField.
  ///
  /// In ar, this message translates to:
  /// **'النوع'**
  String get savedPlaceTypeField;

  /// No description provided for @savedPlaceNameRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل اسماً'**
  String get savedPlaceNameRequired;

  /// No description provided for @savedPlaceSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ'**
  String get savedPlaceSave;

  /// No description provided for @savedPlaceDeleteTitle.
  ///
  /// In ar, this message translates to:
  /// **'حذف هذا المكان؟'**
  String get savedPlaceDeleteTitle;

  /// No description provided for @savedPlaceDeleteBody.
  ///
  /// In ar, this message translates to:
  /// **'ستتم إزالته من أماكنك المحفوظة.'**
  String get savedPlaceDeleteBody;

  /// No description provided for @savedPlaceDelete.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get savedPlaceDelete;

  /// No description provided for @savedPlaceTypeTaken.
  ///
  /// In ar, this message translates to:
  /// **'لديك مكان محفوظ من هذا النوع. احذفه أولاً.'**
  String get savedPlaceTypeTaken;

  /// No description provided for @savedPlaceLimit.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى الحد الأقصى للأماكن المحفوظة.'**
  String get savedPlaceLimit;

  /// No description provided for @homeSavedPlaces.
  ///
  /// In ar, this message translates to:
  /// **'الأماكن المحفوظة'**
  String get homeSavedPlaces;

  /// No description provided for @navHome.
  ///
  /// In ar, this message translates to:
  /// **'الرئيسية'**
  String get navHome;

  /// No description provided for @navTrips.
  ///
  /// In ar, this message translates to:
  /// **'رحلاتي'**
  String get navTrips;

  /// No description provided for @navAccount.
  ///
  /// In ar, this message translates to:
  /// **'حسابي'**
  String get navAccount;

  /// No description provided for @homeRequest.
  ///
  /// In ar, this message translates to:
  /// **'اطلب الآن'**
  String get homeRequest;

  /// No description provided for @homeRequestHintPickup.
  ///
  /// In ar, this message translates to:
  /// **'حدد نقطة الانطلاق أولاً.'**
  String get homeRequestHintPickup;

  /// No description provided for @homeRequestHintDestination.
  ///
  /// In ar, this message translates to:
  /// **'حدد وجهتك للمتابعة.'**
  String get homeRequestHintDestination;

  /// No description provided for @homeRequestHintService.
  ///
  /// In ar, this message translates to:
  /// **'اختر الخدمة المناسبة للمتابعة.'**
  String get homeRequestHintService;

  /// No description provided for @homeRequestHintActive.
  ///
  /// In ar, this message translates to:
  /// **'لديك طلب جارٍ بالفعل. أكمله أو ألغِه قبل طلب جديد.'**
  String get homeRequestHintActive;

  /// No description provided for @homeActiveBooking.
  ///
  /// In ar, this message translates to:
  /// **'طلبك الحالي'**
  String get homeActiveBooking;

  /// No description provided for @homeActiveBookingOpen.
  ///
  /// In ar, this message translates to:
  /// **'عرض'**
  String get homeActiveBookingOpen;

  /// No description provided for @bkFormTitle.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد الطلب'**
  String get bkFormTitle;

  /// No description provided for @bkFormIncompleteTitle.
  ///
  /// In ar, this message translates to:
  /// **'بيانات الرحلة غير مكتملة'**
  String get bkFormIncompleteTitle;

  /// No description provided for @bkFormIncompleteBody.
  ///
  /// In ar, this message translates to:
  /// **'حدد نقطة الانطلاق والوجهة والخدمة من الشاشة الرئيسية.'**
  String get bkFormIncompleteBody;

  /// No description provided for @bkFormBackHome.
  ///
  /// In ar, this message translates to:
  /// **'العودة'**
  String get bkFormBackHome;

  /// No description provided for @bkFormRouteLabel.
  ///
  /// In ar, this message translates to:
  /// **'خريطة المسار'**
  String get bkFormRouteLabel;

  /// No description provided for @bkEstimateFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر حساب المسار حالياً.'**
  String get bkEstimateFailed;

  /// No description provided for @bkRouteFacts.
  ///
  /// In ar, this message translates to:
  /// **'المسافة {km} كم · حوالي {minutes} دقيقة'**
  String bkRouteFacts(String km, int minutes);

  /// No description provided for @bkPassengers.
  ///
  /// In ar, this message translates to:
  /// **'عدد الركاب'**
  String get bkPassengers;

  /// No description provided for @bkGuidanceTitle.
  ///
  /// In ar, this message translates to:
  /// **'السعر المرجعي (للاسترشاد فقط)'**
  String get bkGuidanceTitle;

  /// No description provided for @bkGuidanceRange.
  ///
  /// In ar, this message translates to:
  /// **'من {low} إلى {high}'**
  String bkGuidanceRange(String low, String high);

  /// No description provided for @bkGuidanceRecommended.
  ///
  /// In ar, this message translates to:
  /// **'السعر المقترح: {fare}'**
  String bkGuidanceRecommended(String fare);

  /// No description provided for @bkGuidanceFactors.
  ///
  /// In ar, this message translates to:
  /// **'كيف حُسب'**
  String get bkGuidanceFactors;

  /// No description provided for @bkGuidanceNotice.
  ///
  /// In ar, this message translates to:
  /// **'هذا تقدير مرجعي فقط، وأنت من يحدد السعر الذي تعرضه.'**
  String get bkGuidanceNotice;

  /// No description provided for @bkGuidanceUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'لا يتوفر سعر مرجعي لهذه الخدمة. حدد السعر الذي يناسبك.'**
  String get bkGuidanceUnavailable;

  /// No description provided for @bkFactorBase.
  ///
  /// In ar, this message translates to:
  /// **'رسم البدء'**
  String get bkFactorBase;

  /// No description provided for @bkFactorDistance.
  ///
  /// In ar, this message translates to:
  /// **'المسافة'**
  String get bkFactorDistance;

  /// No description provided for @bkFactorDuration.
  ///
  /// In ar, this message translates to:
  /// **'المدة'**
  String get bkFactorDuration;

  /// No description provided for @bkFactorStops.
  ///
  /// In ar, this message translates to:
  /// **'التوقفات'**
  String get bkFactorStops;

  /// No description provided for @bkFactorMinimum.
  ///
  /// In ar, this message translates to:
  /// **'الحد الأدنى للأجرة'**
  String get bkFactorMinimum;

  /// No description provided for @bkFareTitle.
  ///
  /// In ar, this message translates to:
  /// **'السعر الذي تعرضه'**
  String get bkFareTitle;

  /// No description provided for @bkFareLabel.
  ///
  /// In ar, this message translates to:
  /// **'السعر ({currency})'**
  String bkFareLabel(String currency);

  /// No description provided for @bkFareRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل السعر الذي تعرضه.'**
  String get bkFareRequired;

  /// No description provided for @bkFareInvalid.
  ///
  /// In ar, this message translates to:
  /// **'السعر غير صالح.'**
  String get bkFareInvalid;

  /// No description provided for @bkFareBelowMin.
  ///
  /// In ar, this message translates to:
  /// **'السعر أقل من الحد الأدنى ({min}).'**
  String bkFareBelowMin(String min);

  /// No description provided for @bkFareAboveMax.
  ///
  /// In ar, this message translates to:
  /// **'السعر أعلى من الحد الأقصى ({max}).'**
  String bkFareAboveMax(String max);

  /// No description provided for @bkFareBoundsBoth.
  ///
  /// In ar, this message translates to:
  /// **'المسموح من {min} إلى {max}'**
  String bkFareBoundsBoth(String min, String max);

  /// No description provided for @bkFareBoundsMin.
  ///
  /// In ar, this message translates to:
  /// **'الحد الأدنى {min}'**
  String bkFareBoundsMin(String min);

  /// No description provided for @bkFareBoundsMax.
  ///
  /// In ar, this message translates to:
  /// **'الحد الأقصى {max}'**
  String bkFareBoundsMax(String max);

  /// No description provided for @bkPaymentTitle.
  ///
  /// In ar, this message translates to:
  /// **'طريقة الدفع'**
  String get bkPaymentTitle;

  /// No description provided for @bkPaymentCash.
  ///
  /// In ar, this message translates to:
  /// **'نقداً'**
  String get bkPaymentCash;

  /// No description provided for @bkPaymentOther.
  ///
  /// In ar, this message translates to:
  /// **'وسيلة أخرى'**
  String get bkPaymentOther;

  /// No description provided for @bkPaymentCashUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'الدفع النقدي غير متاح لهذه الخدمة.'**
  String get bkPaymentCashUnavailable;

  /// No description provided for @bkSummaryTitle.
  ///
  /// In ar, this message translates to:
  /// **'ملخص الطلب'**
  String get bkSummaryTitle;

  /// No description provided for @bkSummaryFare.
  ///
  /// In ar, this message translates to:
  /// **'السعر: {fare}'**
  String bkSummaryFare(String fare);

  /// No description provided for @bkSummaryFareNone.
  ///
  /// In ar, this message translates to:
  /// **'لم يُحدد بعد'**
  String get bkSummaryFareNone;

  /// No description provided for @bkSummaryPayment.
  ///
  /// In ar, this message translates to:
  /// **'الدفع: {method}'**
  String bkSummaryPayment(String method);

  /// No description provided for @bkSubmit.
  ///
  /// In ar, this message translates to:
  /// **'اطلب الآن'**
  String get bkSubmit;

  /// No description provided for @bkStatusDraft.
  ///
  /// In ar, this message translates to:
  /// **'مسودة'**
  String get bkStatusDraft;

  /// No description provided for @bkStatusSearching.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ البحث عن عروض'**
  String get bkStatusSearching;

  /// No description provided for @bkStatusOffersReceived.
  ///
  /// In ar, this message translates to:
  /// **'وصلتك عروض'**
  String get bkStatusOffersReceived;

  /// No description provided for @bkStatusExpired.
  ///
  /// In ar, this message translates to:
  /// **'لم نجد مقدم خدمة'**
  String get bkStatusExpired;

  /// No description provided for @bkStatusProviderSelected.
  ///
  /// In ar, this message translates to:
  /// **'تم اختيار مقدم الخدمة'**
  String get bkStatusProviderSelected;

  /// No description provided for @bkStatusHeading.
  ///
  /// In ar, this message translates to:
  /// **'مقدم الخدمة في الطريق إليك'**
  String get bkStatusHeading;

  /// No description provided for @bkStatusArrived.
  ///
  /// In ar, this message translates to:
  /// **'وصل مقدم الخدمة'**
  String get bkStatusArrived;

  /// No description provided for @bkStatusInProgress.
  ///
  /// In ar, this message translates to:
  /// **'الرحلة جارية'**
  String get bkStatusInProgress;

  /// No description provided for @bkStatusDeliveryFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر التسليم'**
  String get bkStatusDeliveryFailed;

  /// No description provided for @bkStatusCompletedPending.
  ///
  /// In ar, this message translates to:
  /// **'مكتملة، بانتظار الدفع'**
  String get bkStatusCompletedPending;

  /// No description provided for @bkStatusCompleted.
  ///
  /// In ar, this message translates to:
  /// **'مكتملة'**
  String get bkStatusCompleted;

  /// No description provided for @bkStatusDisputed.
  ///
  /// In ar, this message translates to:
  /// **'محل نزاع'**
  String get bkStatusDisputed;

  /// No description provided for @bkStatusCancelledCustomer.
  ///
  /// In ar, this message translates to:
  /// **'ألغيتها أنت'**
  String get bkStatusCancelledCustomer;

  /// No description provided for @bkStatusCancelledProvider.
  ///
  /// In ar, this message translates to:
  /// **'ألغاها مقدم الخدمة'**
  String get bkStatusCancelledProvider;

  /// No description provided for @bkStatusCancelledOps.
  ///
  /// In ar, this message translates to:
  /// **'ألغتها الإدارة'**
  String get bkStatusCancelledOps;

  /// No description provided for @bkStatusUnknown.
  ///
  /// In ar, this message translates to:
  /// **'حالة غير معروفة'**
  String get bkStatusUnknown;

  /// No description provided for @bkScreenTitle.
  ///
  /// In ar, this message translates to:
  /// **'الطلب'**
  String get bkScreenTitle;

  /// No description provided for @bkYourFare.
  ///
  /// In ar, this message translates to:
  /// **'سعرك المعروض'**
  String get bkYourFare;

  /// No description provided for @bkAgreedFare.
  ///
  /// In ar, this message translates to:
  /// **'السعر المتفق عليه'**
  String get bkAgreedFare;

  /// No description provided for @bkSearching.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ البحث عن مقدمي خدمة قريبين…'**
  String get bkSearching;

  /// No description provided for @bkSearchingLeft.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ البحث… المتبقي {time}'**
  String bkSearchingLeft(String time);

  /// No description provided for @bkSearchEnding.
  ///
  /// In ar, this message translates to:
  /// **'ينتهي البحث الآن…'**
  String get bkSearchEnding;

  /// No description provided for @bkOffersTitle.
  ///
  /// In ar, this message translates to:
  /// **'العروض'**
  String get bkOffersTitle;

  /// No description provided for @bkSortPrice.
  ///
  /// In ar, this message translates to:
  /// **'الأقل سعراً'**
  String get bkSortPrice;

  /// No description provided for @bkSortEta.
  ///
  /// In ar, this message translates to:
  /// **'الأسرع وصولاً'**
  String get bkSortEta;

  /// No description provided for @bkSortRating.
  ///
  /// In ar, this message translates to:
  /// **'الأعلى تقييماً'**
  String get bkSortRating;

  /// No description provided for @bkEditFare.
  ///
  /// In ar, this message translates to:
  /// **'تعديل السعر'**
  String get bkEditFare;

  /// No description provided for @bkCancelBooking.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الطلب'**
  String get bkCancelBooking;

  /// No description provided for @bkCancelTrip.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الرحلة'**
  String get bkCancelTrip;

  /// No description provided for @bkWaitingOffersTitle.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار العروض'**
  String get bkWaitingOffersTitle;

  /// No description provided for @bkWaitingOffersBody.
  ///
  /// In ar, this message translates to:
  /// **'أرسلنا طلبك إلى مقدمي الخدمة القريبين. ستظهر العروض هنا فور وصولها.'**
  String get bkWaitingOffersBody;

  /// No description provided for @bkNoOffersTitle.
  ///
  /// In ar, this message translates to:
  /// **'لم تصلك عروض بعد'**
  String get bkNoOffersTitle;

  /// No description provided for @bkNoOffersBody.
  ///
  /// In ar, this message translates to:
  /// **'انتهت مدة البحث دون عروض. يمكنك تعديل السعر أو إلغاء الطلب.'**
  String get bkNoOffersBody;

  /// No description provided for @bkNoOffersNoGuarantee.
  ///
  /// In ar, this message translates to:
  /// **'رفع السعر لا يضمن وصول عروض.'**
  String get bkNoOffersNoGuarantee;

  /// No description provided for @bkOfferProviderFallback.
  ///
  /// In ar, this message translates to:
  /// **'مقدم الخدمة'**
  String get bkOfferProviderFallback;

  /// No description provided for @bkOfferCounter.
  ///
  /// In ar, this message translates to:
  /// **'عرض سعر مختلف'**
  String get bkOfferCounter;

  /// No description provided for @bkOfferAcceptsFare.
  ///
  /// In ar, this message translates to:
  /// **'يقبل سعرك'**
  String get bkOfferAcceptsFare;

  /// No description provided for @bkOfferAccept.
  ///
  /// In ar, this message translates to:
  /// **'قبول'**
  String get bkOfferAccept;

  /// No description provided for @bkOfferReject.
  ///
  /// In ar, this message translates to:
  /// **'رفض'**
  String get bkOfferReject;

  /// No description provided for @bkOfferEta.
  ///
  /// In ar, this message translates to:
  /// **'يصل خلال {minutes} د'**
  String bkOfferEta(int minutes);

  /// No description provided for @bkOfferDistance.
  ///
  /// In ar, this message translates to:
  /// **'يبعد {km} كم'**
  String bkOfferDistance(String km);

  /// No description provided for @bkOfferTrips.
  ///
  /// In ar, this message translates to:
  /// **'{count} رحلة'**
  String bkOfferTrips(int count);

  /// No description provided for @bkOfferRating.
  ///
  /// In ar, this message translates to:
  /// **'التقييم {avg} ({count})'**
  String bkOfferRating(String avg, int count);

  /// No description provided for @bkOfferRatingNew.
  ///
  /// In ar, this message translates to:
  /// **'{avg} ({count}) تقييم أولي'**
  String bkOfferRatingNew(String avg, int count);

  /// No description provided for @bkOfferExpiresIn.
  ///
  /// In ar, this message translates to:
  /// **'ينتهي العرض بعد {time}'**
  String bkOfferExpiresIn(String time);

  /// No description provided for @bkOfferExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهى هذا العرض'**
  String get bkOfferExpired;

  /// No description provided for @bkAcceptTitle.
  ///
  /// In ar, this message translates to:
  /// **'قبول العرض؟'**
  String get bkAcceptTitle;

  /// No description provided for @bkAcceptBody.
  ///
  /// In ar, this message translates to:
  /// **'سيتم اختيار مقدم الخدمة هذا لرحلتك.'**
  String get bkAcceptBody;

  /// No description provided for @bkAcceptBodyPrice.
  ///
  /// In ar, this message translates to:
  /// **'قبول عرض {name} بسعر {price}؟'**
  String bkAcceptBodyPrice(String price, String name);

  /// No description provided for @bkEditFareTitle.
  ///
  /// In ar, this message translates to:
  /// **'تعديل السعر'**
  String get bkEditFareTitle;

  /// No description provided for @bkEditFareWarn.
  ///
  /// In ar, this message translates to:
  /// **'سيُعاد البحث عن مقدمي خدمة بالسعر الجديد.'**
  String get bkEditFareWarn;

  /// No description provided for @bkEditFareWarnOffers.
  ///
  /// In ar, this message translates to:
  /// **'تنبيه: تعديل السعر يلغي العروض الحالية ويعيد البحث من جديد.'**
  String get bkEditFareWarnOffers;

  /// No description provided for @bkEditFareNoGuarantee.
  ///
  /// In ar, this message translates to:
  /// **'رفع السعر لا يضمن وصول عروض.'**
  String get bkEditFareNoGuarantee;

  /// No description provided for @bkEditFareSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ السعر'**
  String get bkEditFareSave;

  /// No description provided for @bkFareUpdated.
  ///
  /// In ar, this message translates to:
  /// **'تم تحديث السعر وإعادة البحث.'**
  String get bkFareUpdated;

  /// No description provided for @bkLiveMapLabel.
  ///
  /// In ar, this message translates to:
  /// **'خريطة الرحلة المباشرة'**
  String get bkLiveMapLabel;

  /// No description provided for @bkLocationWaiting.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار موقع مقدم الخدمة…'**
  String get bkLocationWaiting;

  /// No description provided for @bkLocationUpdated.
  ///
  /// In ar, this message translates to:
  /// **'آخر تحديث للموقع منذ {seconds} ث'**
  String bkLocationUpdated(int seconds);

  /// No description provided for @bkLocationStale.
  ///
  /// In ar, this message translates to:
  /// **'موقع مقدم الخدمة قديم (منذ {minutes} د أو أكثر). قد يكون الاتصال ضعيفاً.'**
  String bkLocationStale(int minutes);

  /// No description provided for @bkSupportFlagged.
  ///
  /// In ar, this message translates to:
  /// **'لاحظنا أمراً يحتاج مراجعة وفريق الدعم على علم به.'**
  String get bkSupportFlagged;

  /// No description provided for @bkPinTitle.
  ///
  /// In ar, this message translates to:
  /// **'رمز بدء الرحلة'**
  String get bkPinTitle;

  /// No description provided for @bkPinHint.
  ///
  /// In ar, this message translates to:
  /// **'أخبر مقدم الخدمة بهذا الرمز عند الركوب. لا تشاركه مع أحد غيره.'**
  String get bkPinHint;

  /// No description provided for @bkPinLocked.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف بدء الرحلة بعد محاولات رمز خاطئة. تواصل مع الدعم.'**
  String get bkPinLocked;

  /// No description provided for @bkFreeWaitLeft.
  ///
  /// In ar, this message translates to:
  /// **'وقت الانتظار المجاني المتبقي {time}'**
  String bkFreeWaitLeft(String time);

  /// No description provided for @bkFreeWaitOver.
  ///
  /// In ar, this message translates to:
  /// **'انتهى وقت الانتظار المجاني'**
  String get bkFreeWaitOver;

  /// No description provided for @bkStepSelected.
  ///
  /// In ar, this message translates to:
  /// **'تم اختيار مقدم الخدمة'**
  String get bkStepSelected;

  /// No description provided for @bkStepHeading.
  ///
  /// In ar, this message translates to:
  /// **'في الطريق إليك'**
  String get bkStepHeading;

  /// No description provided for @bkStepArrived.
  ///
  /// In ar, this message translates to:
  /// **'وصل إلى نقطة الانطلاق'**
  String get bkStepArrived;

  /// No description provided for @bkStepStarted.
  ///
  /// In ar, this message translates to:
  /// **'بدأت الرحلة'**
  String get bkStepStarted;

  /// No description provided for @bkTimelineTitle.
  ///
  /// In ar, this message translates to:
  /// **'مراحل الرحلة'**
  String get bkTimelineTitle;

  /// No description provided for @bkShareTrip.
  ///
  /// In ar, this message translates to:
  /// **'مشاركة الرحلة'**
  String get bkShareTrip;

  /// No description provided for @bkShareStop.
  ///
  /// In ar, this message translates to:
  /// **'إيقاف المشاركة'**
  String get bkShareStop;

  /// No description provided for @bkShareStopped.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف مشاركة الرحلة.'**
  String get bkShareStopped;

  /// No description provided for @bkShareMessage.
  ///
  /// In ar, this message translates to:
  /// **'تابع رحلتي مباشرة: {url}'**
  String bkShareMessage(String url);

  /// No description provided for @bkSafety.
  ///
  /// In ar, this message translates to:
  /// **'الأمان'**
  String get bkSafety;

  /// No description provided for @bkSafetyTitle.
  ///
  /// In ar, this message translates to:
  /// **'الأمان والمساعدة'**
  String get bkSafetyTitle;

  /// No description provided for @bkSafetyNotice.
  ///
  /// In ar, this message translates to:
  /// **'خاصية الطوارئ (SOS) الكاملة قادمة قريباً. في الحالات الخطرة اتصل بخدمات الطوارئ المحلية مباشرة. هذه جهات الاتصال المتاحة حالياً.'**
  String get bkSafetyNotice;

  /// No description provided for @bkSafetyYourContact.
  ///
  /// In ar, this message translates to:
  /// **'جهة اتصال الطوارئ الخاصة بك'**
  String get bkSafetyYourContact;

  /// No description provided for @bkSafetySupport.
  ///
  /// In ar, this message translates to:
  /// **'دعم المنصة'**
  String get bkSafetySupport;

  /// No description provided for @bkSafetyWhatsapp.
  ///
  /// In ar, this message translates to:
  /// **'واتساب الدعم'**
  String get bkSafetyWhatsapp;

  /// No description provided for @bkSafetyNothingConfigured.
  ///
  /// In ar, this message translates to:
  /// **'لم تتم إضافة جهات اتصال بعد. يمكنك إضافة جهة اتصال طوارئ من ملفك الشخصي.'**
  String get bkSafetyNothingConfigured;

  /// No description provided for @bkCancelTitle.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الطلب'**
  String get bkCancelTitle;

  /// No description provided for @bkCancelReasonTitle.
  ///
  /// In ar, this message translates to:
  /// **'سبب الإلغاء'**
  String get bkCancelReasonTitle;

  /// No description provided for @bkCancelNote.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظة (اختياري)'**
  String get bkCancelNote;

  /// No description provided for @bkCancelPickReason.
  ///
  /// In ar, this message translates to:
  /// **'اختر سبب الإلغاء.'**
  String get bkCancelPickReason;

  /// No description provided for @bkCancelContinue.
  ///
  /// In ar, this message translates to:
  /// **'متابعة'**
  String get bkCancelContinue;

  /// No description provided for @bkCancelKeep.
  ///
  /// In ar, this message translates to:
  /// **'الإبقاء على الطلب'**
  String get bkCancelKeep;

  /// No description provided for @bkCancelFree.
  ///
  /// In ar, this message translates to:
  /// **'الإلغاء الآن مجاني.'**
  String get bkCancelFree;

  /// No description provided for @bkCancelFeeDue.
  ///
  /// In ar, this message translates to:
  /// **'رسوم الإلغاء: {fee}'**
  String bkCancelFeeDue(String fee);

  /// No description provided for @bkCancelConfirm.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد الإلغاء'**
  String get bkCancelConfirm;

  /// No description provided for @bkCancelConfirmFee.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد الإلغاء ودفع {fee}'**
  String bkCancelConfirmFee(String fee);

  /// No description provided for @bkCancelledTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم إلغاء الطلب'**
  String get bkCancelledTitle;

  /// No description provided for @bkCancelledFee.
  ///
  /// In ar, this message translates to:
  /// **'رسوم الإلغاء: {fee}'**
  String bkCancelledFee(String fee);

  /// No description provided for @bkCancelledNoFee.
  ///
  /// In ar, this message translates to:
  /// **'دون رسوم.'**
  String get bkCancelledNoFee;

  /// No description provided for @bkReasonChangedMind.
  ///
  /// In ar, this message translates to:
  /// **'غيرت رأيي'**
  String get bkReasonChangedMind;

  /// No description provided for @bkReasonWrongPickup.
  ///
  /// In ar, this message translates to:
  /// **'نقطة الانطلاق خاطئة'**
  String get bkReasonWrongPickup;

  /// No description provided for @bkReasonFoundAnother.
  ///
  /// In ar, this message translates to:
  /// **'وجدت وسيلة أخرى'**
  String get bkReasonFoundAnother;

  /// No description provided for @bkReasonDriverTooFar.
  ///
  /// In ar, this message translates to:
  /// **'مقدم الخدمة بعيد'**
  String get bkReasonDriverTooFar;

  /// No description provided for @bkReasonDriverAsked.
  ///
  /// In ar, this message translates to:
  /// **'طلب مني مقدم الخدمة الإلغاء'**
  String get bkReasonDriverAsked;

  /// No description provided for @bkReasonWaitedTooLong.
  ///
  /// In ar, this message translates to:
  /// **'انتظرت طويلاً'**
  String get bkReasonWaitedTooLong;

  /// No description provided for @bkReasonOther.
  ///
  /// In ar, this message translates to:
  /// **'سبب آخر'**
  String get bkReasonOther;

  /// No description provided for @bkNoProviderTitle.
  ///
  /// In ar, this message translates to:
  /// **'لم نجد مقدم خدمة'**
  String get bkNoProviderTitle;

  /// No description provided for @bkNoProviderBody.
  ///
  /// In ar, this message translates to:
  /// **'انتهى البحث دون عروض. يمكنك المحاولة مرة أخرى أو تعديل السعر.'**
  String get bkNoProviderBody;

  /// No description provided for @bkTryAgain.
  ///
  /// In ar, this message translates to:
  /// **'حاول مرة أخرى'**
  String get bkTryAgain;

  /// No description provided for @bkEditPriceAgain.
  ///
  /// In ar, this message translates to:
  /// **'تعديل السعر والمحاولة'**
  String get bkEditPriceAgain;

  /// No description provided for @bkRebook.
  ///
  /// In ar, this message translates to:
  /// **'اطلب مرة أخرى'**
  String get bkRebook;

  /// No description provided for @bkReceiptTitle.
  ///
  /// In ar, this message translates to:
  /// **'الإيصال'**
  String get bkReceiptTitle;

  /// No description provided for @bkReceiptCode.
  ///
  /// In ar, this message translates to:
  /// **'رقم الطلب'**
  String get bkReceiptCode;

  /// No description provided for @bkReceiptTotal.
  ///
  /// In ar, this message translates to:
  /// **'الإجمالي'**
  String get bkReceiptTotal;

  /// No description provided for @bkReceiptPayment.
  ///
  /// In ar, this message translates to:
  /// **'طريقة الدفع'**
  String get bkReceiptPayment;

  /// No description provided for @bkReceiptPaymentStatus.
  ///
  /// In ar, this message translates to:
  /// **'حالة الدفع'**
  String get bkReceiptPaymentStatus;

  /// No description provided for @bkReceiptDuration.
  ///
  /// In ar, this message translates to:
  /// **'مدة الرحلة'**
  String get bkReceiptDuration;

  /// No description provided for @bkReceiptDistance.
  ///
  /// In ar, this message translates to:
  /// **'المسافة المخططة'**
  String get bkReceiptDistance;

  /// No description provided for @bkMinutes.
  ///
  /// In ar, this message translates to:
  /// **'{count} دقيقة'**
  String bkMinutes(int count);

  /// No description provided for @bkKilometres.
  ///
  /// In ar, this message translates to:
  /// **'{km} كم'**
  String bkKilometres(String km);

  /// No description provided for @bkPaymentStatusCashDue.
  ///
  /// In ar, this message translates to:
  /// **'نقداً عند الوصول'**
  String get bkPaymentStatusCashDue;

  /// No description provided for @bkPaymentStatusPaid.
  ///
  /// In ar, this message translates to:
  /// **'مدفوعة'**
  String get bkPaymentStatusPaid;

  /// No description provided for @bkPaymentStatusPending.
  ///
  /// In ar, this message translates to:
  /// **'قيد الانتظار'**
  String get bkPaymentStatusPending;

  /// No description provided for @bkPaymentStatusOther.
  ///
  /// In ar, this message translates to:
  /// **'غير محدد'**
  String get bkPaymentStatusOther;

  /// No description provided for @bkRatingTitle.
  ///
  /// In ar, this message translates to:
  /// **'قيّم مقدم الخدمة'**
  String get bkRatingTitle;

  /// No description provided for @bkRatingPickStars.
  ///
  /// In ar, this message translates to:
  /// **'اختر عدد النجوم.'**
  String get bkRatingPickStars;

  /// No description provided for @bkRatingComment.
  ///
  /// In ar, this message translates to:
  /// **'تعليق خاص (اختياري)'**
  String get bkRatingComment;

  /// No description provided for @bkRatingSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال التقييم'**
  String get bkRatingSubmit;

  /// No description provided for @bkRatingThanks.
  ///
  /// In ar, this message translates to:
  /// **'شكراً لتقييمك.'**
  String get bkRatingThanks;

  /// No description provided for @bkRating1.
  ///
  /// In ar, this message translates to:
  /// **'سيئة'**
  String get bkRating1;

  /// No description provided for @bkRating2.
  ///
  /// In ar, this message translates to:
  /// **'مقبولة'**
  String get bkRating2;

  /// No description provided for @bkRating3.
  ///
  /// In ar, this message translates to:
  /// **'جيدة'**
  String get bkRating3;

  /// No description provided for @bkRating4.
  ///
  /// In ar, this message translates to:
  /// **'جيدة جداً'**
  String get bkRating4;

  /// No description provided for @bkRating5.
  ///
  /// In ar, this message translates to:
  /// **'ممتازة'**
  String get bkRating5;

  /// No description provided for @bkTagProfessional.
  ///
  /// In ar, this message translates to:
  /// **'محترف'**
  String get bkTagProfessional;

  /// No description provided for @bkTagCleanVehicle.
  ///
  /// In ar, this message translates to:
  /// **'مركبة نظيفة'**
  String get bkTagCleanVehicle;

  /// No description provided for @bkTagSafeDriving.
  ///
  /// In ar, this message translates to:
  /// **'قيادة آمنة'**
  String get bkTagSafeDriving;

  /// No description provided for @bkTagGoodCommunication.
  ///
  /// In ar, this message translates to:
  /// **'تواصل جيد'**
  String get bkTagGoodCommunication;

  /// No description provided for @bkTagOnTime.
  ///
  /// In ar, this message translates to:
  /// **'في الموعد'**
  String get bkTagOnTime;

  /// No description provided for @bkTagCarefulWithItems.
  ///
  /// In ar, this message translates to:
  /// **'حرص على الأغراض'**
  String get bkTagCarefulWithItems;

  /// No description provided for @bkTagLate.
  ///
  /// In ar, this message translates to:
  /// **'تأخر'**
  String get bkTagLate;

  /// No description provided for @bkTagUnsafeDriving.
  ///
  /// In ar, this message translates to:
  /// **'قيادة غير آمنة'**
  String get bkTagUnsafeDriving;

  /// No description provided for @bkTagRude.
  ///
  /// In ar, this message translates to:
  /// **'أسلوب غير لائق'**
  String get bkTagRude;

  /// No description provided for @bkTagDirtyVehicle.
  ///
  /// In ar, this message translates to:
  /// **'مركبة غير نظيفة'**
  String get bkTagDirtyVehicle;

  /// No description provided for @bkReportIssue.
  ///
  /// In ar, this message translates to:
  /// **'الإبلاغ عن مشكلة'**
  String get bkReportIssue;

  /// No description provided for @bkReportTitle.
  ///
  /// In ar, this message translates to:
  /// **'الإبلاغ عن مشكلة'**
  String get bkReportTitle;

  /// No description provided for @bkReportDescription.
  ///
  /// In ar, this message translates to:
  /// **'صف ما حدث'**
  String get bkReportDescription;

  /// No description provided for @bkReportDescriptionShort.
  ///
  /// In ar, this message translates to:
  /// **'اكتب وصفاً من 10 أحرف على الأقل.'**
  String get bkReportDescriptionShort;

  /// No description provided for @bkReportPickCategory.
  ///
  /// In ar, this message translates to:
  /// **'اختر نوع المشكلة.'**
  String get bkReportPickCategory;

  /// No description provided for @bkReportSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال البلاغ'**
  String get bkReportSubmit;

  /// No description provided for @bkReportSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال بلاغك وسنراجعه.'**
  String get bkReportSent;

  /// No description provided for @bkReportSafety.
  ///
  /// In ar, this message translates to:
  /// **'السلامة'**
  String get bkReportSafety;

  /// No description provided for @bkReportBehaviour.
  ///
  /// In ar, this message translates to:
  /// **'السلوك'**
  String get bkReportBehaviour;

  /// No description provided for @bkReportFareDispute.
  ///
  /// In ar, this message translates to:
  /// **'خلاف على السعر'**
  String get bkReportFareDispute;

  /// No description provided for @bkReportRoute.
  ///
  /// In ar, this message translates to:
  /// **'المسار أو التأخير'**
  String get bkReportRoute;

  /// No description provided for @bkReportLostItem.
  ///
  /// In ar, this message translates to:
  /// **'شيء مفقود'**
  String get bkReportLostItem;

  /// No description provided for @bkReportOther.
  ///
  /// In ar, this message translates to:
  /// **'أخرى'**
  String get bkReportOther;

  /// No description provided for @bkErrOutsideArea.
  ///
  /// In ar, this message translates to:
  /// **'هذا الموقع خارج منطقة الخدمة.'**
  String get bkErrOutsideArea;

  /// No description provided for @bkErrCategoryUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'هذه الخدمة غير متاحة حالياً في موقعك.'**
  String get bkErrCategoryUnavailable;

  /// No description provided for @bkErrDropoffRequired.
  ///
  /// In ar, this message translates to:
  /// **'حدد الوجهة لهذه الخدمة.'**
  String get bkErrDropoffRequired;

  /// No description provided for @bkErrFareBelow.
  ///
  /// In ar, this message translates to:
  /// **'السعر أقل من الحد الأدنى المسموح.'**
  String get bkErrFareBelow;

  /// No description provided for @bkErrFareAbove.
  ///
  /// In ar, this message translates to:
  /// **'السعر أعلى من الحد الأقصى المسموح.'**
  String get bkErrFareAbove;

  /// No description provided for @bkErrCapacity.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل الحمولة أو عدد الركاب تتجاوز المسموح لهذه الخدمة.'**
  String get bkErrCapacity;

  /// No description provided for @bkErrPayment.
  ///
  /// In ar, this message translates to:
  /// **'طريقة الدفع غير متاحة لهذه الخدمة.'**
  String get bkErrPayment;

  /// No description provided for @bkErrActiveBooking.
  ///
  /// In ar, this message translates to:
  /// **'لديك طلب جارٍ بالفعل.'**
  String get bkErrActiveBooking;

  /// No description provided for @bkErrAccount.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تنفيذ الطلب بسبب حالة حسابك. تواصل مع الدعم.'**
  String get bkErrAccount;

  /// No description provided for @bkErrMaps.
  ///
  /// In ar, this message translates to:
  /// **'خدمة الخرائط غير متاحة مؤقتاً. حاول بعد قليل.'**
  String get bkErrMaps;

  /// No description provided for @bkErrNotEditable.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تعديل الطلب بعد اختيار مقدم الخدمة أو انتهاء البحث.'**
  String get bkErrNotEditable;

  /// No description provided for @bkErrChangedMeanwhile.
  ///
  /// In ar, this message translates to:
  /// **'تغيّر الطلب في هذه الأثناء. حدّثنا الشاشة، حاول مرة أخرى.'**
  String get bkErrChangedMeanwhile;

  /// No description provided for @bkErrAlreadyAssigned.
  ///
  /// In ar, this message translates to:
  /// **'اختيار مقدم خدمة آخر تم بالفعل لهذا الطلب. حدّثنا الشاشة.'**
  String get bkErrAlreadyAssigned;

  /// No description provided for @bkErrOfferExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهى هذا العرض. حدّثنا قائمة العروض.'**
  String get bkErrOfferExpired;

  /// No description provided for @bkErrOfferGone.
  ///
  /// In ar, this message translates to:
  /// **'لم يعد هذا العرض متاحاً. حدّثنا قائمة العروض.'**
  String get bkErrOfferGone;

  /// No description provided for @bkErrSearchEnded.
  ///
  /// In ar, this message translates to:
  /// **'انتهى البحث عن عروض لهذا الطلب.'**
  String get bkErrSearchEnded;

  /// No description provided for @bkErrNotCancellable.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن إلغاء هذا الطلب الآن.'**
  String get bkErrNotCancellable;

  /// No description provided for @bkErrFeeChanged.
  ///
  /// In ar, this message translates to:
  /// **'تغيّرت رسوم الإلغاء. راجع الرسوم الجديدة ثم أكّد.'**
  String get bkErrFeeChanged;

  /// No description provided for @bkErrCancelNote.
  ///
  /// In ar, this message translates to:
  /// **'اكتب ملاحظة توضح سبب الإلغاء.'**
  String get bkErrCancelNote;

  /// No description provided for @bkErrShare.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن مشاركة هذه الرحلة الآن.'**
  String get bkErrShare;

  /// No description provided for @bkErrRatingDone.
  ///
  /// In ar, this message translates to:
  /// **'قيّمت هذه الرحلة بالفعل.'**
  String get bkErrRatingDone;

  /// No description provided for @bkErrRatingClosed.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تقييم هذه الرحلة الآن.'**
  String get bkErrRatingClosed;

  /// No description provided for @bkErrReportClosed.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن الإبلاغ عن هذه الرحلة الآن.'**
  String get bkErrReportClosed;

  /// No description provided for @bkErrReportLimit.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى الحد الأقصى من البلاغات لهذه الرحلة.'**
  String get bkErrReportLimit;

  /// No description provided for @bkErrInProgress.
  ///
  /// In ar, this message translates to:
  /// **'العملية السابقة ما زالت قيد التنفيذ. انتظر لحظات.'**
  String get bkErrInProgress;

  /// No description provided for @tripsTitle.
  ///
  /// In ar, this message translates to:
  /// **'رحلاتي'**
  String get tripsTitle;

  /// No description provided for @tripsEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد رحلات بعد'**
  String get tripsEmptyTitle;

  /// No description provided for @tripsEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'ستظهر هنا طلباتك ورحلاتك السابقة.'**
  String get tripsEmptyBody;

  /// No description provided for @tripsEmptyFilteredTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا نتائج بهذه التصفية'**
  String get tripsEmptyFilteredTitle;

  /// No description provided for @tripsEmptyFilteredBody.
  ///
  /// In ar, this message translates to:
  /// **'جرّب تغيير التصفية أو مسحها.'**
  String get tripsEmptyFilteredBody;

  /// No description provided for @tripsFilter.
  ///
  /// In ar, this message translates to:
  /// **'تصفية'**
  String get tripsFilter;

  /// No description provided for @tripsFilterStatus.
  ///
  /// In ar, this message translates to:
  /// **'الحالة'**
  String get tripsFilterStatus;

  /// No description provided for @tripsFilterService.
  ///
  /// In ar, this message translates to:
  /// **'الخدمة'**
  String get tripsFilterService;

  /// No description provided for @tripsFilterAllServices.
  ///
  /// In ar, this message translates to:
  /// **'كل الخدمات'**
  String get tripsFilterAllServices;

  /// No description provided for @tripsFilterFrom.
  ///
  /// In ar, this message translates to:
  /// **'من تاريخ'**
  String get tripsFilterFrom;

  /// No description provided for @tripsFilterTo.
  ///
  /// In ar, this message translates to:
  /// **'إلى تاريخ'**
  String get tripsFilterTo;

  /// No description provided for @tripsStatusCompleted.
  ///
  /// In ar, this message translates to:
  /// **'مكتملة'**
  String get tripsStatusCompleted;

  /// No description provided for @tripsStatusCancelled.
  ///
  /// In ar, this message translates to:
  /// **'ملغاة'**
  String get tripsStatusCancelled;

  /// No description provided for @tripsStatusActive.
  ///
  /// In ar, this message translates to:
  /// **'جارية'**
  String get tripsStatusActive;

  /// No description provided for @tripsApply.
  ///
  /// In ar, this message translates to:
  /// **'تطبيق'**
  String get tripsApply;

  /// No description provided for @tripsClearFilters.
  ///
  /// In ar, this message translates to:
  /// **'مسح التصفية'**
  String get tripsClearFilters;

  /// No description provided for @notificationsTitle.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات'**
  String get notificationsTitle;

  /// No description provided for @notificationsMarkAllRead.
  ///
  /// In ar, this message translates to:
  /// **'تعليم الكل كمقروء'**
  String get notificationsMarkAllRead;

  /// No description provided for @notificationUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'لم يعد هذا متاحًا. يمكنك الاطلاع على التفاصيل في إشعاراتك.'**
  String get notificationUnavailable;

  /// No description provided for @notificationSettingsTitle.
  ///
  /// In ar, this message translates to:
  /// **'إعدادات الإشعارات'**
  String get notificationSettingsTitle;

  /// No description provided for @notificationChannelPush.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات الفورية'**
  String get notificationChannelPush;

  /// No description provided for @notificationChannelSms.
  ///
  /// In ar, this message translates to:
  /// **'الرسائل النصية'**
  String get notificationChannelSms;

  /// No description provided for @notificationChannelEmail.
  ///
  /// In ar, this message translates to:
  /// **'البريد الإلكتروني'**
  String get notificationChannelEmail;

  /// No description provided for @notificationChannelInApp.
  ///
  /// In ar, this message translates to:
  /// **'صندوق الوارد'**
  String get notificationChannelInApp;

  /// No description provided for @notificationEssentialExplain.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات الخاصة برحلاتك وحسابك ومدفوعاتك وسلامتك أساسية: تبقى الإشعارات الفورية مفعّلة لها حتى لا يفوتك أمر مهم. يمكنك اختيار القنوات الأخرى والفئات الاختيارية أدناه.'**
  String get notificationEssentialExplain;

  /// No description provided for @notificationEssentialBadge.
  ///
  /// In ar, this message translates to:
  /// **'أساسي: الإشعار الفوري مفعّل دائمًا'**
  String get notificationEssentialBadge;

  /// No description provided for @notificationOptInBadge.
  ///
  /// In ar, this message translates to:
  /// **'متوقف حتى تفعّله'**
  String get notificationOptInBadge;

  /// No description provided for @notificationLockedOn.
  ///
  /// In ar, this message translates to:
  /// **'مفعّل دائمًا لهذه الفئة'**
  String get notificationLockedOn;

  /// No description provided for @pushStatusReady.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات الفورية مفعّلة على هذا الجهاز.'**
  String get pushStatusReady;

  /// No description provided for @pushStatusPermissionNeeded.
  ///
  /// In ar, this message translates to:
  /// **'فعّل الإشعارات الفورية لتعرف أخبار رحلاتك فورًا.'**
  String get pushStatusPermissionNeeded;

  /// No description provided for @pushStatusDenied.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات الفورية محظورة. اسمح بها من إعدادات هاتفك.'**
  String get pushStatusDenied;

  /// No description provided for @pushStatusFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر إعداد الإشعارات الفورية. حاول لاحقًا.'**
  String get pushStatusFailed;

  /// No description provided for @pushStatusNotConfigured.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات الفورية غير متاحة في هذا الإصدار. ستجد كل شيء في صندوق الوارد.'**
  String get pushStatusNotConfigured;

  /// No description provided for @pushEnable.
  ///
  /// In ar, this message translates to:
  /// **'تفعيل'**
  String get pushEnable;

  /// No description provided for @pushRationaleTitle.
  ///
  /// In ar, this message translates to:
  /// **'ابقَ على اطلاع'**
  String get pushRationaleTitle;

  /// No description provided for @pushRationaleBody.
  ///
  /// In ar, this message translates to:
  /// **'سنخبرك بتحديثات الرحلات والمدفوعات والسلامة. تبقى الإشعارات على شاشة القفل قصيرة ولا تعرض بيانات شخصية.'**
  String get pushRationaleBody;

  /// No description provided for @pushRationaleLater.
  ///
  /// In ar, this message translates to:
  /// **'ليس الآن'**
  String get pushRationaleLater;

  /// No description provided for @accountNotifications.
  ///
  /// In ar, this message translates to:
  /// **'الإشعارات'**
  String get accountNotifications;

  /// No description provided for @chatTitle.
  ///
  /// In ar, this message translates to:
  /// **'المحادثة'**
  String get chatTitle;

  /// No description provided for @chatInputHint.
  ///
  /// In ar, this message translates to:
  /// **'اكتب رسالة'**
  String get chatInputHint;

  /// No description provided for @chatSend.
  ///
  /// In ar, this message translates to:
  /// **'إرسال'**
  String get chatSend;

  /// No description provided for @chatAttach.
  ///
  /// In ar, this message translates to:
  /// **'إرفاق صورة'**
  String get chatAttach;

  /// No description provided for @attachCamera.
  ///
  /// In ar, this message translates to:
  /// **'التقاط صورة'**
  String get attachCamera;

  /// No description provided for @attachGallery.
  ///
  /// In ar, this message translates to:
  /// **'اختيار من المعرض'**
  String get attachGallery;

  /// No description provided for @attachRemove.
  ///
  /// In ar, this message translates to:
  /// **'إزالة'**
  String get attachRemove;

  /// No description provided for @chatEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد رسائل بعد'**
  String get chatEmptyTitle;

  /// No description provided for @chatEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'ابدأ المحادثة. الرسائل ظاهرة لك وللطرف الآخر في هذا الحجز فقط.'**
  String get chatEmptyBody;

  /// No description provided for @chatClosedTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم إغلاق هذه المحادثة'**
  String get chatClosedTitle;

  /// No description provided for @chatClosedBody.
  ///
  /// In ar, this message translates to:
  /// **'ما زال بإمكانك قراءة الرسائل. لأي أمر آخر تواصل مع الدعم.'**
  String get chatClosedBody;

  /// No description provided for @chatClosesAt.
  ///
  /// In ar, this message translates to:
  /// **'يمكنك المحادثة حتى {time}'**
  String chatClosesAt(String time);

  /// No description provided for @chatSending.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ الإرسال…'**
  String get chatSending;

  /// No description provided for @chatFailed.
  ///
  /// In ar, this message translates to:
  /// **'لم تُرسل'**
  String get chatFailed;

  /// No description provided for @chatDiscard.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get chatDiscard;

  /// No description provided for @chatRead.
  ///
  /// In ar, this message translates to:
  /// **'تمت القراءة'**
  String get chatRead;

  /// No description provided for @chatDelivered.
  ///
  /// In ar, this message translates to:
  /// **'أُرسلت'**
  String get chatDelivered;

  /// No description provided for @chatLoadOlder.
  ///
  /// In ar, this message translates to:
  /// **'رسائل أقدم'**
  String get chatLoadOlder;

  /// No description provided for @chatImageFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل الصورة. اضغط لإعادة المحاولة.'**
  String get chatImageFailed;

  /// No description provided for @chatImageLabel.
  ///
  /// In ar, this message translates to:
  /// **'صورة في المحادثة'**
  String get chatImageLabel;

  /// No description provided for @chatRemoved.
  ///
  /// In ar, this message translates to:
  /// **'تمت إزالتها بعد انتهاء مدة الاحتفاظ'**
  String get chatRemoved;

  /// No description provided for @chatErrTooLongMax.
  ///
  /// In ar, this message translates to:
  /// **'الرسالة طويلة جداً (الحد الأقصى {max} حرفاً)'**
  String chatErrTooLongMax(int max);

  /// No description provided for @chatPhoneNotice.
  ///
  /// In ar, this message translates to:
  /// **'تُخفى أرقام الهواتف في الرسائل. استخدم زر الاتصال بدلاً من ذلك.'**
  String get chatPhoneNotice;

  /// No description provided for @chatUnavailableTitle.
  ///
  /// In ar, this message translates to:
  /// **'المحادثة غير متاحة'**
  String get chatUnavailableTitle;

  /// No description provided for @chatUnavailableBody.
  ///
  /// In ar, this message translates to:
  /// **'يمكنك المحادثة بعد تأكيد سائق لهذا الحجز.'**
  String get chatUnavailableBody;

  /// No description provided for @chatErrClosed.
  ///
  /// In ar, this message translates to:
  /// **'تم إغلاق محادثة هذا الحجز.'**
  String get chatErrClosed;

  /// No description provided for @chatErrForbidden.
  ///
  /// In ar, this message translates to:
  /// **'أنت لست طرفاً في هذه المحادثة.'**
  String get chatErrForbidden;

  /// No description provided for @chatErrTooLong.
  ///
  /// In ar, this message translates to:
  /// **'الرسالة طويلة جداً.'**
  String get chatErrTooLong;

  /// No description provided for @supportTitle.
  ///
  /// In ar, this message translates to:
  /// **'المساعدة والدعم'**
  String get supportTitle;

  /// No description provided for @supportTicketTitle.
  ///
  /// In ar, this message translates to:
  /// **'الطلب'**
  String get supportTicketTitle;

  /// No description provided for @supportEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد طلبات بعد'**
  String get supportEmptyTitle;

  /// No description provided for @supportEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'افتح طلباً إذا احتجت مساعدة في رحلة أو دفعة أو حسابك.'**
  String get supportEmptyBody;

  /// No description provided for @supportNew.
  ///
  /// In ar, this message translates to:
  /// **'تواصل مع الدعم'**
  String get supportNew;

  /// No description provided for @supportFilterActive.
  ///
  /// In ar, this message translates to:
  /// **'المفتوحة'**
  String get supportFilterActive;

  /// No description provided for @supportFilterFinished.
  ///
  /// In ar, this message translates to:
  /// **'المحلولة'**
  String get supportFilterFinished;

  /// No description provided for @supportFilterAll.
  ///
  /// In ar, this message translates to:
  /// **'الكل'**
  String get supportFilterAll;

  /// No description provided for @supportLoadMore.
  ///
  /// In ar, this message translates to:
  /// **'عرض المزيد'**
  String get supportLoadMore;

  /// No description provided for @supportCategory.
  ///
  /// In ar, this message translates to:
  /// **'ما موضوع الطلب؟'**
  String get supportCategory;

  /// No description provided for @supportCategoryHint.
  ///
  /// In ar, this message translates to:
  /// **'اختر تصنيفاً'**
  String get supportCategoryHint;

  /// No description provided for @supportSubject.
  ///
  /// In ar, this message translates to:
  /// **'الموضوع'**
  String get supportSubject;

  /// No description provided for @supportSubjectHint.
  ///
  /// In ar, this message translates to:
  /// **'عنوان قصير'**
  String get supportSubjectHint;

  /// No description provided for @supportBody.
  ///
  /// In ar, this message translates to:
  /// **'صف المشكلة'**
  String get supportBody;

  /// No description provided for @supportBodyHint.
  ///
  /// In ar, this message translates to:
  /// **'ماذا حدث؟ أضف تفاصيل تساعدنا على التحقق.'**
  String get supportBodyHint;

  /// No description provided for @supportBooking.
  ///
  /// In ar, this message translates to:
  /// **'الحجز {code}'**
  String supportBooking(String code);

  /// No description provided for @supportSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الطلب'**
  String get supportSubmit;

  /// No description provided for @supportRequired.
  ///
  /// In ar, this message translates to:
  /// **'هذا الحقل مطلوب'**
  String get supportRequired;

  /// No description provided for @supportTooShort.
  ///
  /// In ar, this message translates to:
  /// **'يرجى كتابة المزيد'**
  String get supportTooShort;

  /// No description provided for @supportSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال طلبك.'**
  String get supportSent;

  /// No description provided for @supportReplyHint.
  ///
  /// In ar, this message translates to:
  /// **'اكتب رداً'**
  String get supportReplyHint;

  /// No description provided for @supportReplySend.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الرد'**
  String get supportReplySend;

  /// No description provided for @supportReplyClosed.
  ///
  /// In ar, this message translates to:
  /// **'تم إغلاق هذا الطلب. افتح طلباً جديداً إذا كنت ما زلت بحاجة إلى مساعدة.'**
  String get supportReplyClosed;

  /// No description provided for @supportResolution.
  ///
  /// In ar, this message translates to:
  /// **'الحل'**
  String get supportResolution;

  /// No description provided for @supportFromSupport.
  ///
  /// In ar, this message translates to:
  /// **'الدعم'**
  String get supportFromSupport;

  /// No description provided for @supportFromMe.
  ///
  /// In ar, this message translates to:
  /// **'أنت'**
  String get supportFromMe;

  /// No description provided for @supportNoInternal.
  ///
  /// In ar, this message translates to:
  /// **'تظهر فقط الردود الموجهة إليك.'**
  String get supportNoInternal;

  /// No description provided for @supportAttachment.
  ///
  /// In ar, this message translates to:
  /// **'مرفق'**
  String get supportAttachment;

  /// No description provided for @supportTicketNumber.
  ///
  /// In ar, this message translates to:
  /// **'الطلب {number}'**
  String supportTicketNumber(String number);

  /// No description provided for @supportOpenedOn.
  ///
  /// In ar, this message translates to:
  /// **'فُتح في {date}'**
  String supportOpenedOn(String date);

  /// No description provided for @supportErrLimit.
  ///
  /// In ar, this message translates to:
  /// **'لديك طلبات مفتوحة كثيرة. انتظر الرد أو أضف رداً على طلب قائم.'**
  String get supportErrLimit;

  /// No description provided for @supportErrRate.
  ///
  /// In ar, this message translates to:
  /// **'أرسلت طلبات كثيرة اليوم. حاول مرة أخرى غداً.'**
  String get supportErrRate;

  /// No description provided for @supportErrClosed.
  ///
  /// In ar, this message translates to:
  /// **'تم إغلاق هذا الطلب.'**
  String get supportErrClosed;

  /// No description provided for @supportErrAppealOpen.
  ///
  /// In ar, this message translates to:
  /// **'لقد قدمت اعتراضاً على هذا الحجز من قبل.'**
  String get supportErrAppealOpen;

  /// No description provided for @supportErrAppealNone.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد رسوم على هذا الحجز يمكن الاعتراض عليها.'**
  String get supportErrAppealNone;

  /// No description provided for @supportErrInfected.
  ///
  /// In ar, this message translates to:
  /// **'تم رفض الملف بعد الفحص الأمني.'**
  String get supportErrInfected;

  /// No description provided for @supportErrFileType.
  ///
  /// In ar, this message translates to:
  /// **'نوع الملف غير مسموح. استخدم صورة أو ملف PDF.'**
  String get supportErrFileType;

  /// No description provided for @supportErrFileSize.
  ///
  /// In ar, this message translates to:
  /// **'حجم الملف كبير جداً.'**
  String get supportErrFileSize;

  /// No description provided for @supportAppealTitle.
  ///
  /// In ar, this message translates to:
  /// **'الاعتراض على الرسوم'**
  String get supportAppealTitle;

  /// No description provided for @supportAppealIntro.
  ///
  /// In ar, this message translates to:
  /// **'أخبرنا لماذا يجب مراجعة الرسوم. سيراجع الدعم الرحلة وقرار الرسوم ويرد عليك في هذا الطلب.'**
  String get supportAppealIntro;

  /// No description provided for @supportAppealReason.
  ///
  /// In ar, this message translates to:
  /// **'لماذا تعترض؟'**
  String get supportAppealReason;

  /// No description provided for @supportAppealShort.
  ///
  /// In ar, this message translates to:
  /// **'يرجى الشرح بما لا يقل عن 10 أحرف.'**
  String get supportAppealShort;

  /// No description provided for @supportAppealSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الاعتراض'**
  String get supportAppealSubmit;

  /// No description provided for @supportAppealSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال اعتراضك إلى الدعم.'**
  String get supportAppealSent;

  /// No description provided for @supportOpenChat.
  ///
  /// In ar, this message translates to:
  /// **'محادثة'**
  String get supportOpenChat;

  /// No description provided for @supportOpenChatHistory.
  ///
  /// In ar, this message translates to:
  /// **'سجل المحادثة'**
  String get supportOpenChatHistory;

  /// No description provided for @supportHelp.
  ///
  /// In ar, this message translates to:
  /// **'المساعدة والدعم'**
  String get supportHelp;

  /// No description provided for @supportHelpAboutTrip.
  ///
  /// In ar, this message translates to:
  /// **'احصل على مساعدة بخصوص هذه الرحلة'**
  String get supportHelpAboutTrip;

  /// No description provided for @supportAppealButton.
  ///
  /// In ar, this message translates to:
  /// **'الاعتراض على الرسوم'**
  String get supportAppealButton;

  /// No description provided for @actionRefresh.
  ///
  /// In ar, this message translates to:
  /// **'تحديث'**
  String get actionRefresh;

  /// No description provided for @actionBack.
  ///
  /// In ar, this message translates to:
  /// **'رجوع'**
  String get actionBack;

  /// No description provided for @navWallet.
  ///
  /// In ar, this message translates to:
  /// **'المحفظة'**
  String get navWallet;

  /// No description provided for @navOffers.
  ///
  /// In ar, this message translates to:
  /// **'العروض'**
  String get navOffers;

  /// No description provided for @walTitle.
  ///
  /// In ar, this message translates to:
  /// **'المحفظة'**
  String get walTitle;

  /// No description provided for @walBalance.
  ///
  /// In ar, this message translates to:
  /// **'رصيد المحفظة'**
  String get walBalance;

  /// No description provided for @walNoWallet.
  ///
  /// In ar, this message translates to:
  /// **'تُفتح محفظتك عند أول استرداد أو رصيد.'**
  String get walNoWallet;

  /// No description provided for @walTransactions.
  ///
  /// In ar, this message translates to:
  /// **'العمليات'**
  String get walTransactions;

  /// No description provided for @walEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد عمليات بعد'**
  String get walEmptyTitle;

  /// No description provided for @walEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'ستظهر هنا المبالغ المستردة والأرصدة والرسوم.'**
  String get walEmptyBody;

  /// No description provided for @walLoadMore.
  ///
  /// In ar, this message translates to:
  /// **'عرض المزيد'**
  String get walLoadMore;

  /// No description provided for @walBalanceAfter.
  ///
  /// In ar, this message translates to:
  /// **'الرصيد: {amount}'**
  String walBalanceAfter(String amount);

  /// No description provided for @walDebtTitle.
  ///
  /// In ar, this message translates to:
  /// **'عليك رسوم إلغاء مستحقة'**
  String get walDebtTitle;

  /// No description provided for @walDebtBody.
  ///
  /// In ar, this message translates to:
  /// **'رسوم إلغاء غير مدفوعة: {amount}. سدّدها لتتمكن من الحجز دون قيود.'**
  String walDebtBody(String amount);

  /// No description provided for @walSettleDebt.
  ///
  /// In ar, this message translates to:
  /// **'سدّد المبلغ المستحق'**
  String get walSettleDebt;

  /// No description provided for @walSettlePay.
  ///
  /// In ar, this message translates to:
  /// **'ادفع {amount}'**
  String walSettlePay(String amount);

  /// No description provided for @walErrDebtBlocksBooking.
  ///
  /// In ar, this message translates to:
  /// **'لديك رسوم إلغاء غير مدفوعة. سدّدها من تبويب المحفظة لتتمكن من الحجز مجدداً.'**
  String get walErrDebtBlocksBooking;

  /// No description provided for @walFeeDebtNote.
  ///
  /// In ar, this message translates to:
  /// **'أُضيفت الرسوم إلى محفظتك كمبلغ مستحق. يمكنك سداده من تبويب المحفظة.'**
  String get walFeeDebtNote;

  /// No description provided for @walOpenWallet.
  ///
  /// In ar, this message translates to:
  /// **'فتح المحفظة'**
  String get walOpenWallet;

  /// No description provided for @bkErrCooldown.
  ///
  /// In ar, this message translates to:
  /// **'الحجز متوقف لفترة قصيرة بعد تكرار الإلغاء. حاول لاحقاً.'**
  String get bkErrCooldown;

  /// No description provided for @pmTitle.
  ///
  /// In ar, this message translates to:
  /// **'وسائل الدفع'**
  String get pmTitle;

  /// No description provided for @pmIntro.
  ///
  /// In ar, this message translates to:
  /// **'تُدخل بيانات بطاقتك في صفحة مزود الدفع فقط. نحتفظ بنوع البطاقة وآخر أربعة أرقام فقط.'**
  String get pmIntro;

  /// No description provided for @pmEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد وسائل دفع محفوظة'**
  String get pmEmptyTitle;

  /// No description provided for @pmEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'أضف بطاقة أو محفظة هاتف للدفع بسرعة أكبر.'**
  String get pmEmptyBody;

  /// No description provided for @pmCard.
  ///
  /// In ar, this message translates to:
  /// **'بطاقة'**
  String get pmCard;

  /// No description provided for @pmWallet.
  ///
  /// In ar, this message translates to:
  /// **'محفظة هاتف'**
  String get pmWallet;

  /// No description provided for @pmPending.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار التأكيد'**
  String get pmPending;

  /// No description provided for @pmDefault.
  ///
  /// In ar, this message translates to:
  /// **'الافتراضية'**
  String get pmDefault;

  /// No description provided for @pmAddCard.
  ///
  /// In ar, this message translates to:
  /// **'إضافة بطاقة'**
  String get pmAddCard;

  /// No description provided for @pmAddWallet.
  ///
  /// In ar, this message translates to:
  /// **'إضافة محفظة هاتف'**
  String get pmAddWallet;

  /// No description provided for @pmDelete.
  ///
  /// In ar, this message translates to:
  /// **'إزالة'**
  String get pmDelete;

  /// No description provided for @pmDeleteTitle.
  ///
  /// In ar, this message translates to:
  /// **'إزالة وسيلة الدفع هذه؟'**
  String get pmDeleteTitle;

  /// No description provided for @pmDeleteBody.
  ///
  /// In ar, this message translates to:
  /// **'يمكنك إضافتها مجدداً لاحقاً.'**
  String get pmDeleteBody;

  /// No description provided for @pmDeleted.
  ///
  /// In ar, this message translates to:
  /// **'تمت إزالة وسيلة الدفع.'**
  String get pmDeleted;

  /// No description provided for @pmSetupPending.
  ///
  /// In ar, this message translates to:
  /// **'ستظهر وسيلة الدفع هنا بعد أن يؤكدها مزود الدفع.'**
  String get pmSetupPending;

  /// No description provided for @pmErrSetupUnsupported.
  ///
  /// In ar, this message translates to:
  /// **'تُحفظ البطاقة أثناء دفع أجرة رحلة مع هذا المزود.'**
  String get pmErrSetupUnsupported;

  /// No description provided for @payNow.
  ///
  /// In ar, this message translates to:
  /// **'ادفع الآن'**
  String get payNow;

  /// No description provided for @payDueTitle.
  ///
  /// In ar, this message translates to:
  /// **'مبلغ مستحق للدفع'**
  String get payDueTitle;

  /// No description provided for @payCouponSaved.
  ///
  /// In ar, this message translates to:
  /// **'وفّرت {amount} بالكوبون.'**
  String payCouponSaved(String amount);

  /// No description provided for @payNewCard.
  ///
  /// In ar, this message translates to:
  /// **'بطاقة أخرى'**
  String get payNewCard;

  /// No description provided for @payNewWallet.
  ///
  /// In ar, this message translates to:
  /// **'محفظة أخرى'**
  String get payNewWallet;

  /// No description provided for @payWalletPhone.
  ///
  /// In ar, this message translates to:
  /// **'رقم هاتف المحفظة'**
  String get payWalletPhone;

  /// No description provided for @payWalletPhoneHelp.
  ///
  /// In ar, this message translates to:
  /// **'اتركه فارغاً لاستخدام هاتف حسابك.'**
  String get payWalletPhoneHelp;

  /// No description provided for @paySecureNote.
  ///
  /// In ar, this message translates to:
  /// **'ستدفع في صفحة مزود الدفع الآمنة.'**
  String get paySecureNote;

  /// No description provided for @payAwaitingTitle.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار تأكيد الدفع'**
  String get payAwaitingTitle;

  /// No description provided for @payAwaitingBody.
  ///
  /// In ar, this message translates to:
  /// **'أتمّ الدفع في صفحة المزود. نتأكد من الدفع لدى المزود قبل اعتباره مدفوعاً، وقد يستغرق ذلك لحظات.'**
  String get payAwaitingBody;

  /// No description provided for @payCheckStatus.
  ///
  /// In ar, this message translates to:
  /// **'تحقق من حالة الدفع'**
  String get payCheckStatus;

  /// No description provided for @payOpenAgain.
  ///
  /// In ar, this message translates to:
  /// **'فتح صفحة الدفع مجدداً'**
  String get payOpenAgain;

  /// No description provided for @payLaunchFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر فتح صفحة الدفع. حاول مجدداً.'**
  String get payLaunchFailed;

  /// No description provided for @payPaidTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم استلام الدفع'**
  String get payPaidTitle;

  /// No description provided for @payPaidBody.
  ///
  /// In ar, this message translates to:
  /// **'شكراً لك. تم تأكيد دفعتك.'**
  String get payPaidBody;

  /// No description provided for @payFailedTitle.
  ///
  /// In ar, this message translates to:
  /// **'لم تتم عملية الدفع'**
  String get payFailedTitle;

  /// No description provided for @payTryAgain.
  ///
  /// In ar, this message translates to:
  /// **'حاول مجدداً'**
  String get payTryAgain;

  /// No description provided for @payFailInsufficient.
  ///
  /// In ar, this message translates to:
  /// **'الرصيد غير كافٍ. استخدم وسيلة أخرى أو حاول مجدداً.'**
  String get payFailInsufficient;

  /// No description provided for @payFailExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت صلاحية البطاقة. استخدم بطاقة أخرى.'**
  String get payFailExpired;

  /// No description provided for @payFailAuth.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر على البنك التحقق من الدفع. حاول مجدداً أو استخدم وسيلة أخرى.'**
  String get payFailAuth;

  /// No description provided for @payFailCancelled.
  ///
  /// In ar, this message translates to:
  /// **'تم إلغاء الدفع.'**
  String get payFailCancelled;

  /// No description provided for @payFailTimeout.
  ///
  /// In ar, this message translates to:
  /// **'انتهت مهلة صفحة الدفع. ابدأ من جديد.'**
  String get payFailTimeout;

  /// No description provided for @payFailDeclined.
  ///
  /// In ar, this message translates to:
  /// **'تم رفض الدفع. حاول مجدداً أو استخدم وسيلة أخرى.'**
  String get payFailDeclined;

  /// No description provided for @payReviewTitle.
  ///
  /// In ar, this message translates to:
  /// **'نراجع دفعتك'**
  String get payReviewTitle;

  /// No description provided for @payReviewBody.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر تأكيد هذه الدفعة تلقائياً. لا تدفع مجدداً؛ فريقنا يراجعها.'**
  String get payReviewBody;

  /// No description provided for @payContactSupport.
  ///
  /// In ar, this message translates to:
  /// **'اسأل الدعم عن هذه الدفعة'**
  String get payContactSupport;

  /// No description provided for @payErrUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'الدفع الإلكتروني غير متاح حالياً. حاول لاحقاً.'**
  String get payErrUnavailable;

  /// No description provided for @payErrMethod.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن استخدام وسيلة الدفع هذه. اختر وسيلة أخرى.'**
  String get payErrMethod;

  /// No description provided for @payErrNotPayable.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن دفع هذه الرحلة الآن.'**
  String get payErrNotPayable;

  /// No description provided for @payErrNothingToPay.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد مبلغ للدفع.'**
  String get payErrNothingToPay;

  /// No description provided for @payErrNeedsReview.
  ///
  /// In ar, this message translates to:
  /// **'هناك دفعة سابقة قيد المراجعة. يرجى انتظار فريقنا.'**
  String get payErrNeedsReview;

  /// No description provided for @payErrWalletPhone.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم هاتف محفظتك.'**
  String get payErrWalletPhone;

  /// No description provided for @payStatusFailed.
  ///
  /// In ar, this message translates to:
  /// **'فشل'**
  String get payStatusFailed;

  /// No description provided for @payStatusRefunded.
  ///
  /// In ar, this message translates to:
  /// **'تم الاسترداد'**
  String get payStatusRefunded;

  /// No description provided for @payStatusPartRefunded.
  ///
  /// In ar, this message translates to:
  /// **'تم استرداد جزء'**
  String get payStatusPartRefunded;

  /// No description provided for @payStatusNotRequired.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد مبلغ للدفع'**
  String get payStatusNotRequired;

  /// No description provided for @payStatusReview.
  ///
  /// In ar, this message translates to:
  /// **'قيد المراجعة'**
  String get payStatusReview;

  /// No description provided for @bkPaymentOnlineNote.
  ///
  /// In ar, this message translates to:
  /// **'يُدفع إلكترونياً بعد الرحلة.'**
  String get bkPaymentOnlineNote;

  /// No description provided for @tipTitle.
  ///
  /// In ar, this message translates to:
  /// **'اترك إكرامية'**
  String get tipTitle;

  /// No description provided for @tipBody.
  ///
  /// In ar, this message translates to:
  /// **'اختياري. الإكرامية كاملة لسائقك.'**
  String get tipBody;

  /// No description provided for @tipOther.
  ///
  /// In ar, this message translates to:
  /// **'مبلغ آخر'**
  String get tipOther;

  /// No description provided for @tipAmountLabel.
  ///
  /// In ar, this message translates to:
  /// **'مبلغ الإكرامية ({currency})'**
  String tipAmountLabel(String currency);

  /// No description provided for @tipPay.
  ///
  /// In ar, this message translates to:
  /// **'ادفع إكرامية {amount}'**
  String tipPay(String amount);

  /// No description provided for @tipErrNotAvailable.
  ///
  /// In ar, this message translates to:
  /// **'الإكرامية غير متاحة لهذه الرحلة.'**
  String get tipErrNotAvailable;

  /// No description provided for @tipErrInvalid.
  ///
  /// In ar, this message translates to:
  /// **'أدخل مبلغ إكرامية صحيحاً.'**
  String get tipErrInvalid;

  /// No description provided for @tipErrTooLarge.
  ///
  /// In ar, this message translates to:
  /// **'الإكرامية أكبر من الحد الأقصى المسموح.'**
  String get tipErrTooLarge;

  /// No description provided for @tipErrAlreadyGiven.
  ///
  /// In ar, this message translates to:
  /// **'لقد تركت إكرامية لهذه الرحلة بالفعل.'**
  String get tipErrAlreadyGiven;

  /// No description provided for @cpnTitle.
  ///
  /// In ar, this message translates to:
  /// **'العروض'**
  String get cpnTitle;

  /// No description provided for @cpnAvailable.
  ///
  /// In ar, this message translates to:
  /// **'عروض لك'**
  String get cpnAvailable;

  /// No description provided for @cpnNoneAvailable.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد عروض الآن. لديك كود؟ أدخله عند الحجز.'**
  String get cpnNoneAvailable;

  /// No description provided for @cpnAutoNote.
  ///
  /// In ar, this message translates to:
  /// **'يُطبَّق هذا العرض تلقائياً عند الحجز إذا كنت مستحقاً له.'**
  String get cpnAutoNote;

  /// No description provided for @cpnTerms.
  ///
  /// In ar, this message translates to:
  /// **'الشروط'**
  String get cpnTerms;

  /// No description provided for @cpnValidUntil.
  ///
  /// In ar, this message translates to:
  /// **'صالح حتى {date}'**
  String cpnValidUntil(String date);

  /// No description provided for @cpnHistory.
  ///
  /// In ar, this message translates to:
  /// **'استخدام الكوبونات مؤخراً'**
  String get cpnHistory;

  /// No description provided for @cpnNoHistory.
  ///
  /// In ar, this message translates to:
  /// **'لم تستخدم كوبوناً بعد.'**
  String get cpnNoHistory;

  /// No description provided for @cpnUsedStatus.
  ///
  /// In ar, this message translates to:
  /// **'مستخدم'**
  String get cpnUsedStatus;

  /// No description provided for @cpnReservedStatus.
  ///
  /// In ar, this message translates to:
  /// **'محجوز'**
  String get cpnReservedStatus;

  /// No description provided for @cpnReleasedStatus.
  ///
  /// In ar, this message translates to:
  /// **'أُعيد'**
  String get cpnReleasedStatus;

  /// No description provided for @cpnReversedStatus.
  ///
  /// In ar, this message translates to:
  /// **'أُلغي'**
  String get cpnReversedStatus;

  /// No description provided for @cpnUnknownStatus.
  ///
  /// In ar, this message translates to:
  /// **'أخرى'**
  String get cpnUnknownStatus;

  /// No description provided for @cpnFormTitle.
  ///
  /// In ar, this message translates to:
  /// **'كوبون'**
  String get cpnFormTitle;

  /// No description provided for @cpnFormLabel.
  ///
  /// In ar, this message translates to:
  /// **'كود الكوبون'**
  String get cpnFormLabel;

  /// No description provided for @cpnApply.
  ///
  /// In ar, this message translates to:
  /// **'تطبيق'**
  String get cpnApply;

  /// No description provided for @cpnNeedFare.
  ///
  /// In ar, this message translates to:
  /// **'حدد أجرتك أولاً للتحقق من الكوبون.'**
  String get cpnNeedFare;

  /// No description provided for @cpnNotChecked.
  ///
  /// In ar, this message translates to:
  /// **'اضغط تطبيق لمعرفة خصمك. يؤكده الخادم عند الحجز.'**
  String get cpnNotChecked;

  /// No description provided for @cpnQuoteDiscount.
  ///
  /// In ar, this message translates to:
  /// **'ستوفر {amount} مع {title}'**
  String cpnQuoteDiscount(String amount, String title);

  /// No description provided for @cpnReasonEligible.
  ///
  /// In ar, this message translates to:
  /// **'الكوبون ينطبق.'**
  String get cpnReasonEligible;

  /// No description provided for @cpnReasonNotFound.
  ///
  /// In ar, this message translates to:
  /// **'كود الكوبون غير موجود.'**
  String get cpnReasonNotFound;

  /// No description provided for @cpnReasonNotActive.
  ///
  /// In ar, this message translates to:
  /// **'هذا الكوبون غير مفعّل.'**
  String get cpnReasonNotActive;

  /// No description provided for @cpnReasonNotStarted.
  ///
  /// In ar, this message translates to:
  /// **'لم يبدأ هذا الكوبون بعد.'**
  String get cpnReasonNotStarted;

  /// No description provided for @cpnReasonExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت صلاحية هذا الكوبون.'**
  String get cpnReasonExpired;

  /// No description provided for @cpnReasonExhausted.
  ///
  /// In ar, this message translates to:
  /// **'هذا الكوبون لم يعد متاحاً.'**
  String get cpnReasonExhausted;

  /// No description provided for @cpnReasonService.
  ///
  /// In ar, this message translates to:
  /// **'هذا الكوبون لا يشمل هذه الخدمة.'**
  String get cpnReasonService;

  /// No description provided for @cpnReasonArea.
  ///
  /// In ar, this message translates to:
  /// **'هذا الكوبون لا يشمل منطقتك.'**
  String get cpnReasonArea;

  /// No description provided for @cpnReasonPayment.
  ///
  /// In ar, this message translates to:
  /// **'هذا الكوبون لا يعمل مع وسيلة الدفع المختارة.'**
  String get cpnReasonPayment;

  /// No description provided for @cpnReasonNewOnly.
  ///
  /// In ar, this message translates to:
  /// **'هذا الكوبون للعملاء الجدد فقط.'**
  String get cpnReasonNewOnly;

  /// No description provided for @cpnReasonReturningOnly.
  ///
  /// In ar, this message translates to:
  /// **'هذا الكوبون للعملاء السابقين فقط.'**
  String get cpnReasonReturningOnly;

  /// No description provided for @cpnReasonSchedule.
  ///
  /// In ar, this message translates to:
  /// **'هذا الكوبون غير صالح في هذا الوقت.'**
  String get cpnReasonSchedule;

  /// No description provided for @cpnReasonMinimum.
  ///
  /// In ar, this message translates to:
  /// **'الأجرة أقل من الحد الأدنى لهذا الكوبون.'**
  String get cpnReasonMinimum;

  /// No description provided for @cpnReasonCustomerLimit.
  ///
  /// In ar, this message translates to:
  /// **'استخدمت هذا الكوبون بالحد الأقصى المسموح.'**
  String get cpnReasonCustomerLimit;

  /// No description provided for @cpnReasonStacking.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن دمج هذا الكوبون مع عرض آخر.'**
  String get cpnReasonStacking;

  /// No description provided for @cpnReasonCurrency.
  ///
  /// In ar, this message translates to:
  /// **'هذا الكوبون لا يطابق عملتك.'**
  String get cpnReasonCurrency;

  /// No description provided for @cpnReasonNoDiscount.
  ///
  /// In ar, this message translates to:
  /// **'لا يمنح هذا الكوبون خصماً على هذه الأجرة.'**
  String get cpnReasonNoDiscount;

  /// No description provided for @cpnReasonUnknown.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن استخدام هذا الكوبون.'**
  String get cpnReasonUnknown;

  /// No description provided for @rcpTitle.
  ///
  /// In ar, this message translates to:
  /// **'الإيصال'**
  String get rcpTitle;

  /// No description provided for @rcpViewFull.
  ///
  /// In ar, this message translates to:
  /// **'الإيصال الكامل'**
  String get rcpViewFull;

  /// No description provided for @rcpNumber.
  ///
  /// In ar, this message translates to:
  /// **'إيصال {number}'**
  String rcpNumber(String number);

  /// No description provided for @rcpDriver.
  ///
  /// In ar, this message translates to:
  /// **'السائق'**
  String get rcpDriver;

  /// No description provided for @rcpFrom.
  ///
  /// In ar, this message translates to:
  /// **'من'**
  String get rcpFrom;

  /// No description provided for @rcpTo.
  ///
  /// In ar, this message translates to:
  /// **'إلى'**
  String get rcpTo;

  /// No description provided for @rcpBreakdown.
  ///
  /// In ar, this message translates to:
  /// **'التفاصيل'**
  String get rcpBreakdown;

  /// No description provided for @rcpAdjustment.
  ///
  /// In ar, this message translates to:
  /// **'إضافة: {label}'**
  String rcpAdjustment(String label);

  /// No description provided for @rcpSubtotal.
  ///
  /// In ar, this message translates to:
  /// **'المجموع الفرعي'**
  String get rcpSubtotal;

  /// No description provided for @rcpDiscount.
  ///
  /// In ar, this message translates to:
  /// **'خصم الكوبون'**
  String get rcpDiscount;

  /// No description provided for @rcpServiceFee.
  ///
  /// In ar, this message translates to:
  /// **'رسوم الخدمة'**
  String get rcpServiceFee;

  /// No description provided for @rcpTax.
  ///
  /// In ar, this message translates to:
  /// **'الضريبة'**
  String get rcpTax;

  /// No description provided for @rcpTaxRate.
  ///
  /// In ar, this message translates to:
  /// **'الضريبة ({rate}%)'**
  String rcpTaxRate(String rate);

  /// No description provided for @rcpTip.
  ///
  /// In ar, this message translates to:
  /// **'الإكرامية'**
  String get rcpTip;

  /// No description provided for @rcpTotal.
  ///
  /// In ar, this message translates to:
  /// **'الإجمالي المطلوب'**
  String get rcpTotal;

  /// No description provided for @rcpOpenPrintable.
  ///
  /// In ar, this message translates to:
  /// **'فتح الإيصال للطباعة'**
  String get rcpOpenPrintable;

  /// No description provided for @adjTitle.
  ///
  /// In ar, this message translates to:
  /// **'تغييرات الأجرة'**
  String get adjTitle;

  /// No description provided for @adjApprove.
  ///
  /// In ar, this message translates to:
  /// **'موافقة'**
  String get adjApprove;

  /// No description provided for @adjReject.
  ///
  /// In ar, this message translates to:
  /// **'رفض'**
  String get adjReject;

  /// No description provided for @adjWithdraw.
  ///
  /// In ar, this message translates to:
  /// **'سحب الطلب'**
  String get adjWithdraw;

  /// No description provided for @adjApproved.
  ///
  /// In ar, this message translates to:
  /// **'وافقت على التغيير. تم تحديث الأجرة.'**
  String get adjApproved;

  /// No description provided for @adjRejected.
  ///
  /// In ar, this message translates to:
  /// **'رفضت التغيير.'**
  String get adjRejected;

  /// No description provided for @adjRequestedByYou.
  ///
  /// In ar, this message translates to:
  /// **'طلبته أنت'**
  String get adjRequestedByYou;

  /// No description provided for @adjRequestedByDriver.
  ///
  /// In ar, this message translates to:
  /// **'طلبه السائق'**
  String get adjRequestedByDriver;

  /// No description provided for @adjExpires.
  ///
  /// In ar, this message translates to:
  /// **'أجب قبل {time}'**
  String adjExpires(String time);

  /// No description provided for @adjNewTotal.
  ///
  /// In ar, this message translates to:
  /// **'الإجمالي الجديد: {amount}'**
  String adjNewTotal(String amount);

  /// No description provided for @adjNote.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظة'**
  String get adjNote;

  /// No description provided for @adjSheetTitle.
  ///
  /// In ar, this message translates to:
  /// **'يطلب سائقك تغيير الأجرة'**
  String get adjSheetTitle;

  /// No description provided for @adjSheetBody.
  ///
  /// In ar, this message translates to:
  /// **'الطلب: {type}. لا تتغير الأجرة إلا إذا وافقت.'**
  String adjSheetBody(String type);

  /// No description provided for @adjOldTotal.
  ///
  /// In ar, this message translates to:
  /// **'الإجمالي الحالي'**
  String get adjOldTotal;

  /// No description provided for @adjNewTotalLabel.
  ///
  /// In ar, this message translates to:
  /// **'الإجمالي عند الموافقة'**
  String get adjNewTotalLabel;

  /// No description provided for @adjNoteOptional.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظة (اختيارية)'**
  String get adjNoteOptional;

  /// No description provided for @adjDecideLater.
  ///
  /// In ar, this message translates to:
  /// **'قرر لاحقاً'**
  String get adjDecideLater;

  /// No description provided for @adjErrNotPending.
  ///
  /// In ar, this message translates to:
  /// **'تمت الإجابة عن هذا الطلب أو انتهت مدته.'**
  String get adjErrNotPending;

  /// No description provided for @adjErrCounterpart.
  ///
  /// In ar, this message translates to:
  /// **'يمكن للطرف الآخر فقط الإجابة عن هذا الطلب.'**
  String get adjErrCounterpart;

  /// No description provided for @scHelpers.
  ///
  /// In ar, this message translates to:
  /// **'عدد العمال'**
  String get scHelpers;

  /// No description provided for @scPacking.
  ///
  /// In ar, this message translates to:
  /// **'التغليف'**
  String get scPacking;

  /// No description provided for @scStairs.
  ///
  /// In ar, this message translates to:
  /// **'السلالم'**
  String get scStairs;

  /// No description provided for @scAssembly.
  ///
  /// In ar, this message translates to:
  /// **'فك وتركيب الأثاث'**
  String get scAssembly;

  /// No description provided for @scEquipment.
  ///
  /// In ar, this message translates to:
  /// **'معدات التحميل'**
  String get scEquipment;

  /// No description provided for @scWaiting.
  ///
  /// In ar, this message translates to:
  /// **'مدة الانتظار المجانية'**
  String get scWaiting;

  /// No description provided for @scExtraStops.
  ///
  /// In ar, this message translates to:
  /// **'محطات إضافية مشمولة'**
  String get scExtraStops;

  /// No description provided for @scTolls.
  ///
  /// In ar, this message translates to:
  /// **'رسوم الطرق'**
  String get scTolls;

  /// No description provided for @scParking.
  ///
  /// In ar, this message translates to:
  /// **'الانتظار والركن'**
  String get scParking;

  /// No description provided for @scLegendIncluded.
  ///
  /// In ar, this message translates to:
  /// **'مشمول'**
  String get scLegendIncluded;

  /// No description provided for @scLegendExcluded.
  ///
  /// In ar, this message translates to:
  /// **'غير مشمول'**
  String get scLegendExcluded;

  /// No description provided for @scLegendAtCost.
  ///
  /// In ar, this message translates to:
  /// **'بالتكلفة'**
  String get scLegendAtCost;

  /// No description provided for @scSummaryTitle.
  ///
  /// In ar, this message translates to:
  /// **'ما يشمله العرض'**
  String get scSummaryTitle;

  /// No description provided for @scAgreedTitle.
  ///
  /// In ar, this message translates to:
  /// **'ما تم الاتفاق عليه'**
  String get scAgreedTitle;

  /// No description provided for @scRequestedTitle.
  ///
  /// In ar, this message translates to:
  /// **'ما طلبته'**
  String get scRequestedTitle;

  /// No description provided for @adjAmountLabel.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ المقترح'**
  String get adjAmountLabel;

  /// No description provided for @adjErrAmount.
  ///
  /// In ar, this message translates to:
  /// **'أدخل مبلغاً صحيحاً أكبر من الصفر.'**
  String get adjErrAmount;

  /// No description provided for @adjErrFloors.
  ///
  /// In ar, this message translates to:
  /// **'أدخل عدد الأدوار الإضافية.'**
  String get adjErrFloors;

  /// No description provided for @adjErrHelpers.
  ///
  /// In ar, this message translates to:
  /// **'عدد العمال الإضافيين غير مسموح.'**
  String get adjErrHelpers;

  /// No description provided for @adjErrNotNow.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن طلب تغيير الآن.'**
  String get adjErrNotNow;

  /// No description provided for @adjErrReason.
  ///
  /// In ar, this message translates to:
  /// **'اكتب السبب (3 أحرف على الأقل).'**
  String get adjErrReason;

  /// No description provided for @adjErrScopeItem.
  ///
  /// In ar, this message translates to:
  /// **'اختر البند المعني من الاتفاق.'**
  String get adjErrScopeItem;

  /// No description provided for @adjErrWithinScope.
  ///
  /// In ar, this message translates to:
  /// **'هذا البند مشمول في الاتفاق ولا يمكن تحميل مبلغ إضافي عنه.'**
  String get adjErrWithinScope;

  /// No description provided for @adjScopeItemLine.
  ///
  /// In ar, this message translates to:
  /// **'بند الاتفاق: {item}'**
  String adjScopeItemLine(String item);

  /// No description provided for @adjHelpersLine.
  ///
  /// In ar, this message translates to:
  /// **'{count} عمال'**
  String adjHelpersLine(int count);

  /// No description provided for @adjFloorsLine.
  ///
  /// In ar, this message translates to:
  /// **'{count} أدوار'**
  String adjFloorsLine(int count);

  /// No description provided for @adjHelpersCount.
  ///
  /// In ar, this message translates to:
  /// **'عدد العمال'**
  String get adjHelpersCount;

  /// No description provided for @adjPickItem.
  ///
  /// In ar, this message translates to:
  /// **'البند المعني من الاتفاق'**
  String get adjPickItem;

  /// No description provided for @adjReasonLabel.
  ///
  /// In ar, this message translates to:
  /// **'السبب'**
  String get adjReasonLabel;

  /// No description provided for @adjRequestButton.
  ///
  /// In ar, this message translates to:
  /// **'طلب تغيير في الاتفاق'**
  String get adjRequestButton;

  /// No description provided for @adjRequestSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال طلبك للسائق.'**
  String get adjRequestSent;

  /// No description provided for @adjSend.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الطلب'**
  String get adjSend;

  /// No description provided for @adjTypeHelper.
  ///
  /// In ar, this message translates to:
  /// **'عامل إضافي'**
  String get adjTypeHelper;

  /// No description provided for @adjTypeScope.
  ///
  /// In ar, this message translates to:
  /// **'تغيير في الاتفاق'**
  String get adjTypeScope;

  /// No description provided for @dcCustodyTitle.
  ///
  /// In ar, this message translates to:
  /// **'مسار الشحنة'**
  String get dcCustodyTitle;

  /// No description provided for @dcCustodyNotStarted.
  ///
  /// In ar, this message translates to:
  /// **'لم يتم استلام الشحنة بعد.'**
  String get dcCustodyNotStarted;

  /// No description provided for @dcCustodyPickedUp.
  ///
  /// In ar, this message translates to:
  /// **'تم الاستلام من المرسل'**
  String get dcCustodyPickedUp;

  /// No description provided for @dcCustodyInTransit.
  ///
  /// In ar, this message translates to:
  /// **'في الطريق'**
  String get dcCustodyInTransit;

  /// No description provided for @dcCustodyArrived.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى نقطة التسليم'**
  String get dcCustodyArrived;

  /// No description provided for @dcCustodyFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر التسليم'**
  String get dcCustodyFailed;

  /// No description provided for @dcCustodyReturning.
  ///
  /// In ar, this message translates to:
  /// **'في طريق العودة للمرسل'**
  String get dcCustodyReturning;

  /// No description provided for @dcCustodyDelivered.
  ///
  /// In ar, this message translates to:
  /// **'تم التسليم'**
  String get dcCustodyDelivered;

  /// No description provided for @dcCustodyReturned.
  ///
  /// In ar, this message translates to:
  /// **'تم الإرجاع للمرسل'**
  String get dcCustodyReturned;

  /// No description provided for @dcCustodyUnknown.
  ///
  /// In ar, this message translates to:
  /// **'حالة غير معروفة'**
  String get dcCustodyUnknown;

  /// No description provided for @dcOtpTitle.
  ///
  /// In ar, this message translates to:
  /// **'رمز المستلم'**
  String get dcOtpTitle;

  /// No description provided for @dcOtpWaiting.
  ///
  /// In ar, this message translates to:
  /// **'لم يُرسل الرمز بعد'**
  String get dcOtpWaiting;

  /// No description provided for @dcOtpSent.
  ///
  /// In ar, this message translates to:
  /// **'أُرسل الرمز للمستلم'**
  String get dcOtpSent;

  /// No description provided for @dcOtpSmsFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر إرسال الرسالة'**
  String get dcOtpSmsFailed;

  /// No description provided for @dcOtpVerified.
  ///
  /// In ar, this message translates to:
  /// **'تم التحقق'**
  String get dcOtpVerified;

  /// No description provided for @dcOtpLocked.
  ///
  /// In ar, this message translates to:
  /// **'الرمز موقوف'**
  String get dcOtpLocked;

  /// No description provided for @dcOtpAttemptsLeft.
  ///
  /// In ar, this message translates to:
  /// **'المحاولات المتبقية: {left}'**
  String dcOtpAttemptsLeft(int left);

  /// No description provided for @dcOtpShareHint.
  ///
  /// In ar, this message translates to:
  /// **'نرسل رمزاً برسالة نصية إلى المستلم، ويقوله للمندوب عند استلام الشحنة. لا ترسله لأي شخص آخر.'**
  String get dcOtpShareHint;

  /// No description provided for @dcProofsTitle.
  ///
  /// In ar, this message translates to:
  /// **'إثباتات الاستلام والتسليم'**
  String get dcProofsTitle;

  /// No description provided for @dcNoProofs.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد إثباتات بعد.'**
  String get dcNoProofs;

  /// No description provided for @dcProofLoadError.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل الصورة. اضغط للمحاولة.'**
  String get dcProofLoadError;

  /// No description provided for @dcProofPhoto.
  ///
  /// In ar, this message translates to:
  /// **'صورة'**
  String get dcProofPhoto;

  /// No description provided for @dcProofSignature.
  ///
  /// In ar, this message translates to:
  /// **'توقيع'**
  String get dcProofSignature;

  /// No description provided for @dcProofOtp.
  ///
  /// In ar, this message translates to:
  /// **'رمز المستلم'**
  String get dcProofOtp;

  /// No description provided for @dcProofPin.
  ///
  /// In ar, this message translates to:
  /// **'رمز البدء'**
  String get dcProofPin;

  /// No description provided for @dcProofChecklist.
  ///
  /// In ar, this message translates to:
  /// **'قائمة فحص'**
  String get dcProofChecklist;

  /// No description provided for @dcProofWeight.
  ///
  /// In ar, this message translates to:
  /// **'الوزن'**
  String get dcProofWeight;

  /// No description provided for @dcProofOther.
  ///
  /// In ar, this message translates to:
  /// **'إثبات'**
  String get dcProofOther;

  /// No description provided for @dcStagePickup.
  ///
  /// In ar, this message translates to:
  /// **'عند الاستلام'**
  String get dcStagePickup;

  /// No description provided for @dcStageDelivery.
  ///
  /// In ar, this message translates to:
  /// **'عند التسليم'**
  String get dcStageDelivery;

  /// No description provided for @dcStageReturn.
  ///
  /// In ar, this message translates to:
  /// **'عند الإرجاع'**
  String get dcStageReturn;

  /// No description provided for @dcChkSealed.
  ///
  /// In ar, this message translates to:
  /// **'الطرد مغلق'**
  String get dcChkSealed;

  /// No description provided for @dcChkUndamaged.
  ///
  /// In ar, this message translates to:
  /// **'الطرد سليم بلا أضرار'**
  String get dcChkUndamaged;

  /// No description provided for @dcChkLabel.
  ///
  /// In ar, this message translates to:
  /// **'البيانات مطابقة للملصق'**
  String get dcChkLabel;

  /// No description provided for @dcChkRecipient.
  ///
  /// In ar, this message translates to:
  /// **'تم التأكد من هوية المستلم'**
  String get dcChkRecipient;

  /// No description provided for @dcWeightValue.
  ///
  /// In ar, this message translates to:
  /// **'الوزن المقاس: {kg} كجم'**
  String dcWeightValue(String kg);

  /// No description provided for @dcAttemptsTitle.
  ///
  /// In ar, this message translates to:
  /// **'محاولات التسليم'**
  String get dcAttemptsTitle;

  /// No description provided for @dcAttemptNumber.
  ///
  /// In ar, this message translates to:
  /// **'المحاولة {number}'**
  String dcAttemptNumber(int number);

  /// No description provided for @dcAttemptEvidence.
  ///
  /// In ar, this message translates to:
  /// **'مرفق صورة'**
  String get dcAttemptEvidence;

  /// No description provided for @dcPending.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار قرارك'**
  String get dcPending;

  /// No description provided for @dcOutUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'المستلم غير متاح'**
  String get dcOutUnavailable;

  /// No description provided for @dcOutRefused.
  ///
  /// In ar, this message translates to:
  /// **'المستلم رفض الاستلام'**
  String get dcOutRefused;

  /// No description provided for @dcOutWrongAddress.
  ///
  /// In ar, this message translates to:
  /// **'العنوان خاطئ'**
  String get dcOutWrongAddress;

  /// No description provided for @dcOutOtpFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر التحقق من الرمز'**
  String get dcOutOtpFailed;

  /// No description provided for @dcOutRestricted.
  ///
  /// In ar, this message translates to:
  /// **'الشحنة تحتوي على مواد ممنوعة'**
  String get dcOutRestricted;

  /// No description provided for @dcOutOther.
  ///
  /// In ar, this message translates to:
  /// **'سبب آخر'**
  String get dcOutOther;

  /// No description provided for @dcResRetry.
  ///
  /// In ar, this message translates to:
  /// **'إعادة محاولة التسليم'**
  String get dcResRetry;

  /// No description provided for @dcResReturn.
  ///
  /// In ar, this message translates to:
  /// **'إرجاع للمرسل'**
  String get dcResReturn;

  /// No description provided for @dcResOperations.
  ///
  /// In ar, this message translates to:
  /// **'تحويل لفريق العمليات'**
  String get dcResOperations;

  /// No description provided for @dcResUnknown.
  ///
  /// In ar, this message translates to:
  /// **'قرار غير معروف'**
  String get dcResUnknown;

  /// No description provided for @dcRoleSender.
  ///
  /// In ar, this message translates to:
  /// **'المرسل'**
  String get dcRoleSender;

  /// No description provided for @dcRoleRecipient.
  ///
  /// In ar, this message translates to:
  /// **'المستلم'**
  String get dcRoleRecipient;

  /// No description provided for @dcRolePickup.
  ///
  /// In ar, this message translates to:
  /// **'جهة الاتصال عند التحميل'**
  String get dcRolePickup;

  /// No description provided for @dcRoleDropoff.
  ///
  /// In ar, this message translates to:
  /// **'جهة الاتصال عند التسليم'**
  String get dcRoleDropoff;

  /// No description provided for @dcContactsTitle.
  ///
  /// In ar, this message translates to:
  /// **'جهات الاتصال'**
  String get dcContactsTitle;

  /// No description provided for @dcFailedTitle.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تسليم شحنتك'**
  String get dcFailedTitle;

  /// No description provided for @dcFailedChoose.
  ///
  /// In ar, this message translates to:
  /// **'اختر ما يحدث للشحنة الآن.'**
  String get dcFailedChoose;

  /// No description provided for @dcShowProofs.
  ///
  /// In ar, this message translates to:
  /// **'عرض صور الإثبات'**
  String get dcShowProofs;

  /// No description provided for @dcHideProofs.
  ///
  /// In ar, this message translates to:
  /// **'إخفاء صور الإثبات'**
  String get dcHideProofs;

  /// No description provided for @dcErrAttemptDone.
  ///
  /// In ar, this message translates to:
  /// **'تم اتخاذ قرار بشأن هذه المحاولة بالفعل.'**
  String get dcErrAttemptDone;

  /// No description provided for @dcErrAttemptsExhausted.
  ///
  /// In ar, this message translates to:
  /// **'استُنفدت محاولات التسليم. اختر الإرجاع أو العمليات.'**
  String get dcErrAttemptsExhausted;

  /// No description provided for @dcErrScopeMissing.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد اتفاق نقل لهذا الطلب بعد.'**
  String get dcErrScopeMissing;

  /// No description provided for @rgTitle.
  ///
  /// In ar, this message translates to:
  /// **'إقرار بالمواد الممنوعة'**
  String get rgTitle;

  /// No description provided for @rgIntro.
  ///
  /// In ar, this message translates to:
  /// **'اقرأ سياسة المواد الممنوعة قبل إرسال الطرد.'**
  String get rgIntro;

  /// No description provided for @rgReadPolicy.
  ///
  /// In ar, this message translates to:
  /// **'قراءة السياسة'**
  String get rgReadPolicy;

  /// No description provided for @rgAcknowledge.
  ///
  /// In ar, this message translates to:
  /// **'أقر بأن طردي لا يحتوي على مواد ممنوعة (النسخة {version})'**
  String rgAcknowledge(int version);

  /// No description provided for @rgErrRequired.
  ///
  /// In ar, this message translates to:
  /// **'يجب الإقرار بسياسة المواد الممنوعة.'**
  String get rgErrRequired;

  /// No description provided for @rgErrStale.
  ///
  /// In ar, this message translates to:
  /// **'تغيرت السياسة. اقرأها وأقر بها من جديد.'**
  String get rgErrStale;

  /// No description provided for @rgErrUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل سياسة المواد الممنوعة الآن.'**
  String get rgErrUnavailable;

  /// No description provided for @lfLoadTitle.
  ///
  /// In ar, this message translates to:
  /// **'الحمولة'**
  String get lfLoadTitle;

  /// No description provided for @lfLoadWeight.
  ///
  /// In ar, this message translates to:
  /// **'الوزن: {kg} كجم'**
  String lfLoadWeight(String kg);

  /// No description provided for @lfLoadVolume.
  ///
  /// In ar, this message translates to:
  /// **'الحجم: {volume} م³'**
  String lfLoadVolume(String volume);

  /// No description provided for @lfLoadValue.
  ///
  /// In ar, this message translates to:
  /// **'قيمة البضاعة: {value}'**
  String lfLoadValue(String value);

  /// No description provided for @lfLoadWindow.
  ///
  /// In ar, this message translates to:
  /// **'التحميل من {start} إلى {end}'**
  String lfLoadWindow(String start, String end);

  /// No description provided for @lfFileVideo.
  ///
  /// In ar, this message translates to:
  /// **'فيديو'**
  String get lfFileVideo;

  /// No description provided for @lfFileDocument.
  ///
  /// In ar, this message translates to:
  /// **'مستند'**
  String get lfFileDocument;

  /// No description provided for @lfItemNote.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظة عن هذا الغرض'**
  String get lfItemNote;

  /// No description provided for @lfItemWeight.
  ///
  /// In ar, this message translates to:
  /// **'الوزن'**
  String get lfItemWeight;

  /// No description provided for @lfItemDimensionsUnit.
  ///
  /// In ar, this message translates to:
  /// **'سم'**
  String get lfItemDimensionsUnit;

  /// No description provided for @lfPickVideo.
  ///
  /// In ar, this message translates to:
  /// **'فيديو من المعرض'**
  String get lfPickVideo;

  /// No description provided for @lfUploading.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ رفع الملف…'**
  String get lfUploading;

  /// No description provided for @lfErrVolume.
  ///
  /// In ar, this message translates to:
  /// **'حجم الحمولة أكبر من الحد المسموح لهذه الخدمة.'**
  String get lfErrVolume;

  /// No description provided for @lfErrWindowIncomplete.
  ///
  /// In ar, this message translates to:
  /// **'حدد بداية ونهاية موعد التحميل.'**
  String get lfErrWindowIncomplete;

  /// No description provided for @lfErrWindowPast.
  ///
  /// In ar, this message translates to:
  /// **'موعد التحميل في الماضي.'**
  String get lfErrWindowPast;

  /// No description provided for @lfErrWindowInvalid.
  ///
  /// In ar, this message translates to:
  /// **'نهاية موعد التحميل يجب أن تكون بعد بدايته.'**
  String get lfErrWindowInvalid;

  /// No description provided for @lfErrWindowLong.
  ///
  /// In ar, this message translates to:
  /// **'موعد التحميل طويل جداً.'**
  String get lfErrWindowLong;

  /// No description provided for @lfErrAttachmentInvalid.
  ///
  /// In ar, this message translates to:
  /// **'أحد الملفات المرفقة غير صالح. أعد رفعه.'**
  String get lfErrAttachmentInvalid;

  /// No description provided for @lfErrAttachmentLimit.
  ///
  /// In ar, this message translates to:
  /// **'تجاوزت الحد المسموح من الصور أو الفيديوهات أو المستندات.'**
  String get lfErrAttachmentLimit;

  /// No description provided for @lfErrPendingLimit.
  ///
  /// In ar, this message translates to:
  /// **'لديك ملفات مرفوعة كثيرة لم تُستخدم. احذف بعضها أو أكمل الحجز.'**
  String get lfErrPendingLimit;

  /// No description provided for @lfErrInUse.
  ///
  /// In ar, this message translates to:
  /// **'هذا الملف مرتبط بحجز ولا يمكن حذفه.'**
  String get lfErrInUse;

  /// No description provided for @lfErrInfected.
  ///
  /// In ar, this message translates to:
  /// **'رُفض الملف لأسباب أمنية.'**
  String get lfErrInfected;

  /// No description provided for @lfErrTooLarge.
  ///
  /// In ar, this message translates to:
  /// **'حجم الملف أكبر من المسموح.'**
  String get lfErrTooLarge;

  /// No description provided for @lfErrTooSmall.
  ///
  /// In ar, this message translates to:
  /// **'الصورة صغيرة جداً.'**
  String get lfErrTooSmall;

  /// No description provided for @lfErrFileType.
  ///
  /// In ar, this message translates to:
  /// **'نوع الملف غير مدعوم.'**
  String get lfErrFileType;

  /// No description provided for @lfErrScanner.
  ///
  /// In ar, this message translates to:
  /// **'فحص الملفات غير متاح الآن. حاول بعد قليل.'**
  String get lfErrScanner;

  /// No description provided for @lfErrUploadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر رفع الملف. حاول مرة أخرى.'**
  String get lfErrUploadFailed;

  /// No description provided for @stpTitle.
  ///
  /// In ar, this message translates to:
  /// **'محطات إضافية'**
  String get stpTitle;

  /// No description provided for @stpIntro.
  ///
  /// In ar, this message translates to:
  /// **'أضف محطات في الطريق مع اسم وهاتف من سيقابل السائق في كل محطة.'**
  String get stpIntro;

  /// No description provided for @stpAdd.
  ///
  /// In ar, this message translates to:
  /// **'إضافة محطة'**
  String get stpAdd;

  /// No description provided for @stpMax.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى الحد الأقصى للمحطات ({max}).'**
  String stpMax(int max);

  /// No description provided for @stpNumbered.
  ///
  /// In ar, this message translates to:
  /// **'المحطة {number}: {place}'**
  String stpNumbered(int number, String place);

  /// No description provided for @stpMoveUp.
  ///
  /// In ar, this message translates to:
  /// **'تحريك لأعلى'**
  String get stpMoveUp;

  /// No description provided for @stpMoveDown.
  ///
  /// In ar, this message translates to:
  /// **'تحريك لأسفل'**
  String get stpMoveDown;

  /// No description provided for @stpRemove.
  ///
  /// In ar, this message translates to:
  /// **'حذف المحطة'**
  String get stpRemove;

  /// No description provided for @stpContactName.
  ///
  /// In ar, this message translates to:
  /// **'اسم جهة الاتصال'**
  String get stpContactName;

  /// No description provided for @stpContactPhone.
  ///
  /// In ar, this message translates to:
  /// **'هاتف جهة الاتصال'**
  String get stpContactPhone;

  /// No description provided for @stpNote.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظة للسائق'**
  String get stpNote;

  /// No description provided for @stpErrName.
  ///
  /// In ar, this message translates to:
  /// **'اكتب اسم جهة الاتصال.'**
  String get stpErrName;

  /// No description provided for @sfTitle.
  ///
  /// In ar, this message translates to:
  /// **'السلامة والمساعدة'**
  String get sfTitle;

  /// No description provided for @sfHoldLabel.
  ///
  /// In ar, this message translates to:
  /// **'طوارئ'**
  String get sfHoldLabel;

  /// No description provided for @sfHoldHint.
  ///
  /// In ar, this message translates to:
  /// **'اضغط مطولاً لمدة {seconds} ثانية لإرسال تنبيه'**
  String sfHoldHint(int seconds);

  /// No description provided for @sfHolding.
  ///
  /// In ar, this message translates to:
  /// **'استمر بالضغط'**
  String get sfHolding;

  /// No description provided for @sfSending.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ إرسال تنبيهك…'**
  String get sfSending;

  /// No description provided for @sfSentTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال التنبيه'**
  String get sfSentTitle;

  /// No description provided for @sfFailedTitle.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر إرسال التنبيه'**
  String get sfFailedTitle;

  /// No description provided for @sfFailedBody.
  ///
  /// In ar, this message translates to:
  /// **'تحقق من اتصالك. إذا كنت في خطر فاتصل برقم الطوارئ أدناه الآن.'**
  String get sfFailedBody;

  /// No description provided for @sfRetry.
  ///
  /// In ar, this message translates to:
  /// **'حاول مرة أخرى'**
  String get sfRetry;

  /// No description provided for @sfNoJob.
  ///
  /// In ar, this message translates to:
  /// **'يعمل زر الأمان أثناء المهمة. يمكنك الاتصال بأرقام الطوارئ أدناه في أي وقت.'**
  String get sfNoJob;

  /// No description provided for @sfNumbersTitle.
  ///
  /// In ar, this message translates to:
  /// **'أرقام الطوارئ المحلية'**
  String get sfNumbersTitle;

  /// No description provided for @sfCall.
  ///
  /// In ar, this message translates to:
  /// **'اتصل بـ {name} ({number})'**
  String sfCall(String name, String number);

  /// No description provided for @sfNoDispatch.
  ///
  /// In ar, this message translates to:
  /// **'هذا التطبيق لا يتصل بخدمات الطوارئ نيابةً عنك ولا يتم إرسال أحد تلقائيًا. إذا كنت في خطر فاتصل بالرقم بنفسك.'**
  String get sfNoDispatch;

  /// No description provided for @sfWhatWeDid.
  ///
  /// In ar, this message translates to:
  /// **'ما الذي تم'**
  String get sfWhatWeDid;

  /// No description provided for @sfReport.
  ///
  /// In ar, this message translates to:
  /// **'الإبلاغ عن حادث'**
  String get sfReport;

  /// No description provided for @sfShareTrip.
  ///
  /// In ar, this message translates to:
  /// **'مشاركة الرحلة مع جهات الاتصال الموثوقة'**
  String get sfShareTrip;

  /// No description provided for @sfContacts.
  ///
  /// In ar, this message translates to:
  /// **'جهات الاتصال الموثوقة'**
  String get sfContacts;

  /// No description provided for @sfCallSupport.
  ///
  /// In ar, this message translates to:
  /// **'الاتصال بدعم المنصة'**
  String get sfCallSupport;

  /// No description provided for @sfDone.
  ///
  /// In ar, this message translates to:
  /// **'تم'**
  String get sfDone;

  /// No description provided for @sfCancel.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء'**
  String get sfCancel;

  /// No description provided for @sfNumPolice.
  ///
  /// In ar, this message translates to:
  /// **'الشرطة'**
  String get sfNumPolice;

  /// No description provided for @sfNumAmbulance.
  ///
  /// In ar, this message translates to:
  /// **'الإسعاف'**
  String get sfNumAmbulance;

  /// No description provided for @sfNumFire.
  ///
  /// In ar, this message translates to:
  /// **'الإطفاء'**
  String get sfNumFire;

  /// No description provided for @sfNumTourist.
  ///
  /// In ar, this message translates to:
  /// **'شرطة السياحة'**
  String get sfNumTourist;

  /// No description provided for @sfNumOther.
  ///
  /// In ar, this message translates to:
  /// **'الطوارئ'**
  String get sfNumOther;

  /// No description provided for @sfCatEmergency.
  ///
  /// In ar, this message translates to:
  /// **'طارئ'**
  String get sfCatEmergency;

  /// No description provided for @sfCatAccident.
  ///
  /// In ar, this message translates to:
  /// **'حادث'**
  String get sfCatAccident;

  /// No description provided for @sfCatMedical.
  ///
  /// In ar, this message translates to:
  /// **'مشكلة صحية'**
  String get sfCatMedical;

  /// No description provided for @sfCatThreat.
  ///
  /// In ar, this message translates to:
  /// **'تهديد أو عنف'**
  String get sfCatThreat;

  /// No description provided for @sfCatHarassment.
  ///
  /// In ar, this message translates to:
  /// **'تحرش'**
  String get sfCatHarassment;

  /// No description provided for @sfCatUnsafeDriving.
  ///
  /// In ar, this message translates to:
  /// **'قيادة غير آمنة'**
  String get sfCatUnsafeDriving;

  /// No description provided for @sfCatTheft.
  ///
  /// In ar, this message translates to:
  /// **'سرقة أو فقدان أغراض'**
  String get sfCatTheft;

  /// No description provided for @sfCatIdentityMismatch.
  ///
  /// In ar, this message translates to:
  /// **'السائق أو المركبة غير مطابقين'**
  String get sfCatIdentityMismatch;

  /// No description provided for @sfCatOther.
  ///
  /// In ar, this message translates to:
  /// **'مخاوف أخرى تتعلق بالسلامة'**
  String get sfCatOther;

  /// No description provided for @sfReportTitle.
  ///
  /// In ar, this message translates to:
  /// **'الإبلاغ عن حادث'**
  String get sfReportTitle;

  /// No description provided for @sfReportCategory.
  ///
  /// In ar, this message translates to:
  /// **'ماذا حدث؟'**
  String get sfReportCategory;

  /// No description provided for @sfReportNarrative.
  ///
  /// In ar, this message translates to:
  /// **'اشرح ما حدث'**
  String get sfReportNarrative;

  /// No description provided for @sfReportNarrativeHint.
  ///
  /// In ar, this message translates to:
  /// **'المكان والأشخاص وأي تفاصيل تساعد فريق السلامة'**
  String get sfReportNarrativeHint;

  /// No description provided for @sfReportTooShort.
  ///
  /// In ar, this message translates to:
  /// **'اكتب بضع كلمات على الأقل.'**
  String get sfReportTooShort;

  /// No description provided for @sfReportCallback.
  ///
  /// In ar, this message translates to:
  /// **'هاتف للاتصال بك (اختياري)'**
  String get sfReportCallback;

  /// No description provided for @sfReportSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال البلاغ'**
  String get sfReportSubmit;

  /// No description provided for @sfReportSent.
  ///
  /// In ar, this message translates to:
  /// **'تم استلام البلاغ'**
  String get sfReportSent;

  /// No description provided for @sfReportUrgent.
  ///
  /// In ar, this message translates to:
  /// **'إذا كنت في خطر وشيك فارجع واستخدم زر الطوارئ أو اتصل برقم الطوارئ.'**
  String get sfReportUrgent;

  /// No description provided for @sfIncidentsTitle.
  ///
  /// In ar, this message translates to:
  /// **'بلاغات السلامة'**
  String get sfIncidentsTitle;

  /// No description provided for @sfIncidentsEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لم ترفع أي بلاغ سلامة.'**
  String get sfIncidentsEmpty;

  /// No description provided for @sfStatusNew.
  ///
  /// In ar, this message translates to:
  /// **'تم الاستلام'**
  String get sfStatusNew;

  /// No description provided for @sfStatusAcknowledged.
  ///
  /// In ar, this message translates to:
  /// **'قيد المعالجة'**
  String get sfStatusAcknowledged;

  /// No description provided for @sfStatusInvestigating.
  ///
  /// In ar, this message translates to:
  /// **'قيد المراجعة'**
  String get sfStatusInvestigating;

  /// No description provided for @sfStatusEscalated.
  ///
  /// In ar, this message translates to:
  /// **'تم تصعيده إلى مسؤول أعلى'**
  String get sfStatusEscalated;

  /// No description provided for @sfStatusResolved.
  ///
  /// In ar, this message translates to:
  /// **'تم الحل'**
  String get sfStatusResolved;

  /// No description provided for @sfOutcome.
  ///
  /// In ar, this message translates to:
  /// **'النتيجة: {outcome}'**
  String sfOutcome(String outcome);

  /// No description provided for @sfTimeline.
  ///
  /// In ar, this message translates to:
  /// **'ما جرى حتى الآن'**
  String get sfTimeline;

  /// No description provided for @sfIncidentContacts.
  ///
  /// In ar, this message translates to:
  /// **'جهات اتصالك الموثوقة'**
  String get sfIncidentContacts;

  /// No description provided for @sfFiles.
  ///
  /// In ar, this message translates to:
  /// **'الملفات التي أضفتها'**
  String get sfFiles;

  /// No description provided for @sfAddUpdate.
  ///
  /// In ar, this message translates to:
  /// **'إضافة تحديث'**
  String get sfAddUpdate;

  /// No description provided for @sfUpdateHint.
  ///
  /// In ar, this message translates to:
  /// **'أخبر فريق السلامة بالمزيد'**
  String get sfUpdateHint;

  /// No description provided for @sfSendUpdate.
  ///
  /// In ar, this message translates to:
  /// **'إرسال التحديث'**
  String get sfSendUpdate;

  /// No description provided for @sfAddFile.
  ///
  /// In ar, this message translates to:
  /// **'إضافة صورة'**
  String get sfAddFile;

  /// No description provided for @sfResolvedNote.
  ///
  /// In ar, this message translates to:
  /// **'تم إغلاق هذا البلاغ. إذا تغيّر شيء فارفع بلاغاً جديداً.'**
  String get sfResolvedNote;

  /// No description provided for @sfCaseNumber.
  ///
  /// In ar, this message translates to:
  /// **'البلاغ {number}'**
  String sfCaseNumber(String number);

  /// No description provided for @sfActorYou.
  ///
  /// In ar, this message translates to:
  /// **'أنت'**
  String get sfActorYou;

  /// No description provided for @sfActorStaff.
  ///
  /// In ar, this message translates to:
  /// **'فريق السلامة'**
  String get sfActorStaff;

  /// No description provided for @sfActorSystem.
  ///
  /// In ar, this message translates to:
  /// **'النظام'**
  String get sfActorSystem;

  /// No description provided for @sfContactsTitle.
  ///
  /// In ar, this message translates to:
  /// **'جهات الاتصال الموثوقة'**
  String get sfContactsTitle;

  /// No description provided for @sfContactsIntro.
  ///
  /// In ar, this message translates to:
  /// **'أشخاص قد نراسلهم برسالة نصية عند ضغطك على الطوارئ أو مشاركة رحلة. نراسلهم فقط وفق ما تسمح به أدناه وبعد تأكيدك أنهم وافقوا.'**
  String get sfContactsIntro;

  /// No description provided for @sfContactsEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد جهات اتصال موثوقة بعد.'**
  String get sfContactsEmpty;

  /// No description provided for @sfContactsAdd.
  ///
  /// In ar, this message translates to:
  /// **'إضافة جهة اتصال'**
  String get sfContactsAdd;

  /// No description provided for @sfContactsLimit.
  ///
  /// In ar, this message translates to:
  /// **'يمكنك الاحتفاظ بحتى {max} جهات اتصال.'**
  String sfContactsLimit(int max);

  /// No description provided for @sfContactName.
  ///
  /// In ar, this message translates to:
  /// **'الاسم'**
  String get sfContactName;

  /// No description provided for @sfContactPhone.
  ///
  /// In ar, this message translates to:
  /// **'رقم الهاتف'**
  String get sfContactPhone;

  /// No description provided for @sfContactPhoneHint.
  ///
  /// In ar, this message translates to:
  /// **'+201001234567'**
  String get sfContactPhoneHint;

  /// No description provided for @sfContactRelationship.
  ///
  /// In ar, this message translates to:
  /// **'صلة القرابة (اختياري)'**
  String get sfContactRelationship;

  /// No description provided for @sfRelFamily.
  ///
  /// In ar, this message translates to:
  /// **'العائلة'**
  String get sfRelFamily;

  /// No description provided for @sfRelPartner.
  ///
  /// In ar, this message translates to:
  /// **'الشريك'**
  String get sfRelPartner;

  /// No description provided for @sfRelFriend.
  ///
  /// In ar, this message translates to:
  /// **'صديق'**
  String get sfRelFriend;

  /// No description provided for @sfRelColleague.
  ///
  /// In ar, this message translates to:
  /// **'زميل'**
  String get sfRelColleague;

  /// No description provided for @sfRelOther.
  ///
  /// In ar, this message translates to:
  /// **'آخر'**
  String get sfRelOther;

  /// No description provided for @sfConsentTitle.
  ///
  /// In ar, this message translates to:
  /// **'ماذا يمكننا أن نرسل لهذا الشخص؟'**
  String get sfConsentTitle;

  /// No description provided for @sfConsentSos.
  ///
  /// In ar, this message translates to:
  /// **'رسالة نصية عندما أضغط على الطوارئ'**
  String get sfConsentSos;

  /// No description provided for @sfConsentTrip.
  ///
  /// In ar, this message translates to:
  /// **'رابط الرحلة المباشر عندما أشارك رحلة'**
  String get sfConsentTrip;

  /// No description provided for @sfConsentLocation.
  ///
  /// In ar, this message translates to:
  /// **'آخر موقع لي في رسالة الطوارئ'**
  String get sfConsentLocation;

  /// No description provided for @sfConsentConfirm.
  ///
  /// In ar, this message translates to:
  /// **'أؤكد أن هذا الشخص وافق على استلام هذه الرسائل.'**
  String get sfConsentConfirm;

  /// No description provided for @sfContactSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ'**
  String get sfContactSave;

  /// No description provided for @sfContactDelete.
  ///
  /// In ar, this message translates to:
  /// **'حذف جهة الاتصال'**
  String get sfContactDelete;

  /// No description provided for @sfContactDeleteConfirm.
  ///
  /// In ar, this message translates to:
  /// **'حذف جهة الاتصال الموثوقة هذه؟'**
  String get sfContactDeleteConfirm;

  /// No description provided for @sfContactRequired.
  ///
  /// In ar, this message translates to:
  /// **'هذا الحقل مطلوب.'**
  String get sfContactRequired;

  /// No description provided for @sfContactConsentRequired.
  ///
  /// In ar, this message translates to:
  /// **'أكّد أن هذا الشخص وافق.'**
  String get sfContactConsentRequired;

  /// No description provided for @sfShareTitle.
  ///
  /// In ar, this message translates to:
  /// **'مشاركة هذه الرحلة'**
  String get sfShareTitle;

  /// No description provided for @sfShareBody.
  ///
  /// In ar, this message translates to:
  /// **'نرسل رابط الرحلة المباشر برسالة نصية إلى جهات الاتصال الموثوقة التي وافقت على استلامه. يعرض الرابط الاسم الأول للسائق والمركبة والموقع المباشر، ويتوقف عند انتهاء الرحلة.'**
  String get sfShareBody;

  /// No description provided for @sfShareNoContacts.
  ///
  /// In ar, this message translates to:
  /// **'أضف أولاً جهة اتصال موثوقة وافقت على استلام روابط الرحلة.'**
  String get sfShareNoContacts;

  /// No description provided for @sfShareSend.
  ///
  /// In ar, this message translates to:
  /// **'إرسال إلى جهات الاتصال الموثوقة'**
  String get sfShareSend;

  /// No description provided for @sfShareResult.
  ///
  /// In ar, this message translates to:
  /// **'أُرسل إلى {sent} من {total} جهات اتصال'**
  String sfShareResult(int sent, int total);

  /// No description provided for @sfShareCopy.
  ///
  /// In ar, this message translates to:
  /// **'نسخ الرابط'**
  String get sfShareCopy;

  /// No description provided for @sfShareCopied.
  ///
  /// In ar, this message translates to:
  /// **'تم نسخ الرابط'**
  String get sfShareCopied;

  /// No description provided for @sfShareStop.
  ///
  /// In ar, this message translates to:
  /// **'إيقاف المشاركة'**
  String get sfShareStop;

  /// No description provided for @sfShareStopped.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف المشاركة. لم يعد الرابط يعمل.'**
  String get sfShareStopped;

  /// No description provided for @sfShareExpires.
  ///
  /// In ar, this message translates to:
  /// **'يعمل الرابط حتى {time}'**
  String sfShareExpires(String time);

  /// No description provided for @sfSettingsTile.
  ///
  /// In ar, this message translates to:
  /// **'جهات الاتصال الموثوقة والسلامة'**
  String get sfSettingsTile;

  /// No description provided for @actionUpdateNow.
  ///
  /// In ar, this message translates to:
  /// **'حدّث الآن'**
  String get actionUpdateNow;

  /// No description provided for @refTitle.
  ///
  /// In ar, this message translates to:
  /// **'ادعُ أصدقاءك'**
  String get refTitle;

  /// No description provided for @refYourCode.
  ///
  /// In ar, this message translates to:
  /// **'رمز الإحالة الخاص بك'**
  String get refYourCode;

  /// No description provided for @refCopy.
  ///
  /// In ar, this message translates to:
  /// **'نسخ الرمز'**
  String get refCopy;

  /// No description provided for @refCopied.
  ///
  /// In ar, this message translates to:
  /// **'تم نسخ الرمز'**
  String get refCopied;

  /// No description provided for @refShare.
  ///
  /// In ar, this message translates to:
  /// **'مشاركة'**
  String get refShare;

  /// No description provided for @refNoOffer.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد عرض إحالة حاليا. يمكنك مشاركة رمزك على أي حال.'**
  String get refNoOffer;

  /// No description provided for @refOfferTitle.
  ///
  /// In ar, this message translates to:
  /// **'العرض'**
  String get refOfferTitle;

  /// No description provided for @refOfferLine.
  ///
  /// In ar, this message translates to:
  /// **'تحصل على {referrer}، ويحصل صديقك على {referee}.'**
  String refOfferLine(String referrer, String referee);

  /// No description provided for @refMilestone.
  ///
  /// In ar, this message translates to:
  /// **'تُدفع بعد أن يُتم صديقك {jobs} طلب(ات).'**
  String refMilestone(int jobs);

  /// No description provided for @refValidity.
  ///
  /// In ar, this message translates to:
  /// **'الرصيد صالح لمدة {days} يوما.'**
  String refValidity(int days);

  /// No description provided for @refHaveCode.
  ///
  /// In ar, this message translates to:
  /// **'لديك رمز من صديق؟'**
  String get refHaveCode;

  /// No description provided for @refCodeHint.
  ///
  /// In ar, this message translates to:
  /// **'أدخل الرمز'**
  String get refCodeHint;

  /// No description provided for @refApply.
  ///
  /// In ar, this message translates to:
  /// **'تطبيق الرمز'**
  String get refApply;

  /// No description provided for @refApplied.
  ///
  /// In ar, this message translates to:
  /// **'تم تطبيق الرمز. تصلك المكافأة بعد أول طلب مكتمل.'**
  String get refApplied;

  /// No description provided for @refStatReferred.
  ///
  /// In ar, this message translates to:
  /// **'تمت دعوتهم'**
  String get refStatReferred;

  /// No description provided for @refStatRewarded.
  ///
  /// In ar, this message translates to:
  /// **'تمت مكافأتهم'**
  String get refStatRewarded;

  /// No description provided for @refStatPending.
  ///
  /// In ar, this message translates to:
  /// **'قيد التقدم'**
  String get refStatPending;

  /// No description provided for @refListTitle.
  ///
  /// In ar, this message translates to:
  /// **'دعواتك'**
  String get refListTitle;

  /// No description provided for @refListEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لم يستخدم أحد رمزك بعد.'**
  String get refListEmpty;

  /// No description provided for @refStatusPending.
  ///
  /// In ar, this message translates to:
  /// **'قيد التقدم'**
  String get refStatusPending;

  /// No description provided for @refStatusRewarded.
  ///
  /// In ar, this message translates to:
  /// **'تمت المكافأة'**
  String get refStatusRewarded;

  /// No description provided for @refStatusClosed.
  ///
  /// In ar, this message translates to:
  /// **'مغلقة'**
  String get refStatusClosed;

  /// No description provided for @refErrInvalid.
  ///
  /// In ar, this message translates to:
  /// **'هذا الرمز غير صالح.'**
  String get refErrInvalid;

  /// No description provided for @refErrSelf.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكنك استخدام رمزك الخاص.'**
  String get refErrSelf;

  /// No description provided for @refErrNotNew.
  ///
  /// In ar, this message translates to:
  /// **'الرموز مخصصة لمن لم يُتم أي طلب بعد.'**
  String get refErrNotNew;

  /// No description provided for @refErrUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد عرض إحالة حاليا.'**
  String get refErrUnavailable;

  /// No description provided for @refErrCap.
  ///
  /// In ar, this message translates to:
  /// **'بلغ هذا الرمز حده الأقصى.'**
  String get refErrCap;

  /// No description provided for @refErrApplied.
  ///
  /// In ar, this message translates to:
  /// **'تم تطبيق رمز على حسابك مسبقا.'**
  String get refErrApplied;

  /// No description provided for @refErrGeneric.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر تطبيق الرمز. حاول مرة أخرى.'**
  String get refErrGeneric;

  /// No description provided for @refTile.
  ///
  /// In ar, this message translates to:
  /// **'ادعُ أصدقاءك'**
  String get refTile;

  /// No description provided for @credTitle.
  ///
  /// In ar, this message translates to:
  /// **'رصيدي الترويجي'**
  String get credTitle;

  /// No description provided for @credAvailable.
  ///
  /// In ar, this message translates to:
  /// **'الرصيد المتاح'**
  String get credAvailable;

  /// No description provided for @credNone.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد رصيد بعد.'**
  String get credNone;

  /// No description provided for @credSourceReferral.
  ///
  /// In ar, this message translates to:
  /// **'مكافأة إحالة'**
  String get credSourceReferral;

  /// No description provided for @credSourceLoyalty.
  ///
  /// In ar, this message translates to:
  /// **'مكافأة ولاء'**
  String get credSourceLoyalty;

  /// No description provided for @credStateActive.
  ///
  /// In ar, this message translates to:
  /// **'نشط'**
  String get credStateActive;

  /// No description provided for @credStateExpired.
  ///
  /// In ar, this message translates to:
  /// **'منتهي'**
  String get credStateExpired;

  /// No description provided for @credStateReversed.
  ///
  /// In ar, this message translates to:
  /// **'معكوس'**
  String get credStateReversed;

  /// No description provided for @credExpiresOn.
  ///
  /// In ar, this message translates to:
  /// **'ينتهي {date}'**
  String credExpiresOn(String date);

  /// No description provided for @credRemaining.
  ///
  /// In ar, this message translates to:
  /// **'المتبقي: {amount}'**
  String credRemaining(String amount);

  /// No description provided for @credHistory.
  ///
  /// In ar, this message translates to:
  /// **'الأرصدة'**
  String get credHistory;

  /// No description provided for @loyTitle.
  ///
  /// In ar, this message translates to:
  /// **'الولاء'**
  String get loyTitle;

  /// No description provided for @loyCompletedJobs.
  ///
  /// In ar, this message translates to:
  /// **'{jobs} طلب مكتمل'**
  String loyCompletedJobs(int jobs);

  /// No description provided for @loyCurrent.
  ///
  /// In ar, this message translates to:
  /// **'مستواك'**
  String get loyCurrent;

  /// No description provided for @loyNone.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد مستوى بعد'**
  String get loyNone;

  /// No description provided for @loyToNext.
  ///
  /// In ar, this message translates to:
  /// **'{jobs} طلب إضافي للوصول إلى {level}'**
  String loyToNext(int jobs, String level);

  /// No description provided for @loyTop.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى أعلى مستوى'**
  String get loyTop;

  /// No description provided for @loyLevels.
  ///
  /// In ar, this message translates to:
  /// **'المستويات'**
  String get loyLevels;

  /// No description provided for @loyLevelJobs.
  ///
  /// In ar, this message translates to:
  /// **'{jobs} طلب'**
  String loyLevelJobs(int jobs);

  /// No description provided for @loyLevelReward.
  ///
  /// In ar, this message translates to:
  /// **'رصيد: {amount}'**
  String loyLevelReward(String amount);

  /// No description provided for @loyReached.
  ///
  /// In ar, this message translates to:
  /// **'تم بلوغه'**
  String get loyReached;
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
