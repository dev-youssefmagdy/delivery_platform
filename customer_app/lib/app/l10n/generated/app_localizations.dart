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
