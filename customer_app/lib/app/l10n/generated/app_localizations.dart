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
