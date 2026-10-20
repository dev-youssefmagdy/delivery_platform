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

  /// No description provided for @homeLogout.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل الخروج'**
  String get homeLogout;

  /// No description provided for @providerOnboardingTitle.
  ///
  /// In ar, this message translates to:
  /// **'طلب الانضمام كسائق'**
  String get providerOnboardingTitle;

  /// No description provided for @actionBack.
  ///
  /// In ar, this message translates to:
  /// **'رجوع'**
  String get actionBack;

  /// No description provided for @actionSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ'**
  String get actionSave;

  /// No description provided for @actionClose.
  ///
  /// In ar, this message translates to:
  /// **'إغلاق'**
  String get actionClose;

  /// No description provided for @actionRefresh.
  ///
  /// In ar, this message translates to:
  /// **'تحديث'**
  String get actionRefresh;

  /// No description provided for @actionRemove.
  ///
  /// In ar, this message translates to:
  /// **'إزالة'**
  String get actionRemove;

  /// No description provided for @actionEdit.
  ///
  /// In ar, this message translates to:
  /// **'تعديل'**
  String get actionEdit;

  /// No description provided for @actionDelete.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get actionDelete;

  /// No description provided for @actionCancel.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء'**
  String get actionCancel;

  /// No description provided for @photoCamera.
  ///
  /// In ar, this message translates to:
  /// **'التقاط صورة'**
  String get photoCamera;

  /// No description provided for @photoGallery.
  ///
  /// In ar, this message translates to:
  /// **'اختيار من المعرض'**
  String get photoGallery;

  /// No description provided for @obStepProfile.
  ///
  /// In ar, this message translates to:
  /// **'الملف الشخصي'**
  String get obStepProfile;

  /// No description provided for @obStepServices.
  ///
  /// In ar, this message translates to:
  /// **'الخدمات'**
  String get obStepServices;

  /// No description provided for @obStepVehicles.
  ///
  /// In ar, this message translates to:
  /// **'المركبات'**
  String get obStepVehicles;

  /// No description provided for @obStepDocuments.
  ///
  /// In ar, this message translates to:
  /// **'المستندات'**
  String get obStepDocuments;

  /// No description provided for @obStepDeclarations.
  ///
  /// In ar, this message translates to:
  /// **'الإقرارات'**
  String get obStepDeclarations;

  /// No description provided for @obStepReview.
  ///
  /// In ar, this message translates to:
  /// **'المراجعة'**
  String get obStepReview;

  /// No description provided for @obStepCounter.
  ///
  /// In ar, this message translates to:
  /// **'الخطوة {current} من {total}'**
  String obStepCounter(int current, int total);

  /// No description provided for @obStepDone.
  ///
  /// In ar, this message translates to:
  /// **'مكتملة'**
  String get obStepDone;

  /// No description provided for @obProfileTitle.
  ///
  /// In ar, this message translates to:
  /// **'عرّفنا بنفسك'**
  String get obProfileTitle;

  /// No description provided for @obProfileIntro.
  ///
  /// In ar, this message translates to:
  /// **'هذه البيانات مطلوبة لمراجعة طلبك. يمكنك تعديلها قبل الإرسال.'**
  String get obProfileIntro;

  /// No description provided for @obProfileName.
  ///
  /// In ar, this message translates to:
  /// **'الاسم الكامل'**
  String get obProfileName;

  /// No description provided for @obProfileDob.
  ///
  /// In ar, this message translates to:
  /// **'تاريخ الميلاد'**
  String get obProfileDob;

  /// No description provided for @obProfileDobPick.
  ///
  /// In ar, this message translates to:
  /// **'اختر التاريخ'**
  String get obProfileDobPick;

  /// No description provided for @obProfileType.
  ///
  /// In ar, this message translates to:
  /// **'نوع مقدم الخدمة'**
  String get obProfileType;

  /// No description provided for @obProfileCity.
  ///
  /// In ar, this message translates to:
  /// **'المدينة'**
  String get obProfileCity;

  /// No description provided for @obProfileCityPick.
  ///
  /// In ar, this message translates to:
  /// **'اختر المدينة'**
  String get obProfileCityPick;

  /// No description provided for @obProfileSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ ومتابعة'**
  String get obProfileSave;

  /// No description provided for @obProfileCitiesEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد مدن متاحة حالياً.'**
  String get obProfileCitiesEmpty;

  /// No description provided for @obTypeIndividualDriver.
  ///
  /// In ar, this message translates to:
  /// **'سائق فردي'**
  String get obTypeIndividualDriver;

  /// No description provided for @obTypeCourier.
  ///
  /// In ar, this message translates to:
  /// **'مندوب توصيل'**
  String get obTypeCourier;

  /// No description provided for @obTypeMovingCrew.
  ///
  /// In ar, this message translates to:
  /// **'فريق / سائق نقل أثاث'**
  String get obTypeMovingCrew;

  /// No description provided for @obTypeCommercialTransport.
  ///
  /// In ar, this message translates to:
  /// **'مزوّد نقل تجاري'**
  String get obTypeCommercialTransport;

  /// No description provided for @obTypeCompanyFleet.
  ///
  /// In ar, this message translates to:
  /// **'شركة / أسطول'**
  String get obTypeCompanyFleet;

  /// No description provided for @obServicesTitle.
  ///
  /// In ar, this message translates to:
  /// **'ما الخدمات التي تقدمها؟'**
  String get obServicesTitle;

  /// No description provided for @obServicesIntro.
  ///
  /// In ar, this message translates to:
  /// **'اختر خدمة واحدة على الأقل. يمكنك إضافة المزيد لاحقاً وتُراجع كل خدمة قبل اعتمادها.'**
  String get obServicesIntro;

  /// No description provided for @obServicesEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد خدمات متاحة حالياً.'**
  String get obServicesEmpty;

  /// No description provided for @obServicesSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ ومتابعة'**
  String get obServicesSave;

  /// No description provided for @obServiceStatusApproved.
  ///
  /// In ar, this message translates to:
  /// **'معتمدة'**
  String get obServiceStatusApproved;

  /// No description provided for @obServiceStatusRejected.
  ///
  /// In ar, this message translates to:
  /// **'مرفوضة'**
  String get obServiceStatusRejected;

  /// No description provided for @obServiceStatusRequested.
  ///
  /// In ar, this message translates to:
  /// **'قيد المراجعة'**
  String get obServiceStatusRequested;

  /// No description provided for @obVehiclesTitle.
  ///
  /// In ar, this message translates to:
  /// **'مركباتك'**
  String get obVehiclesTitle;

  /// No description provided for @obVehiclesIntro.
  ///
  /// In ar, this message translates to:
  /// **'أضف مركبة واحدة على الأقل بصورها ومستنداتها ثم أرسلها للمراجعة.'**
  String get obVehiclesIntro;

  /// No description provided for @obVehiclesEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لم تضف مركبة بعد.'**
  String get obVehiclesEmpty;

  /// No description provided for @obVehicleAdd.
  ///
  /// In ar, this message translates to:
  /// **'إضافة مركبة'**
  String get obVehicleAdd;

  /// No description provided for @obVehicleFormTitle.
  ///
  /// In ar, this message translates to:
  /// **'بيانات المركبة'**
  String get obVehicleFormTitle;

  /// No description provided for @obVehicleCategory.
  ///
  /// In ar, this message translates to:
  /// **'فئة المركبة'**
  String get obVehicleCategory;

  /// No description provided for @obVehicleCategoryPick.
  ///
  /// In ar, this message translates to:
  /// **'اختر الفئة'**
  String get obVehicleCategoryPick;

  /// No description provided for @obVehicleMake.
  ///
  /// In ar, this message translates to:
  /// **'الماركة'**
  String get obVehicleMake;

  /// No description provided for @obVehicleModel.
  ///
  /// In ar, this message translates to:
  /// **'الطراز'**
  String get obVehicleModel;

  /// No description provided for @obVehicleYear.
  ///
  /// In ar, this message translates to:
  /// **'سنة الصنع'**
  String get obVehicleYear;

  /// No description provided for @obVehicleColor.
  ///
  /// In ar, this message translates to:
  /// **'اللون'**
  String get obVehicleColor;

  /// No description provided for @obVehiclePlate.
  ///
  /// In ar, this message translates to:
  /// **'رقم اللوحة'**
  String get obVehiclePlate;

  /// No description provided for @obVehiclePassengers.
  ///
  /// In ar, this message translates to:
  /// **'عدد الركاب'**
  String get obVehiclePassengers;

  /// No description provided for @obVehiclePayload.
  ///
  /// In ar, this message translates to:
  /// **'الحمولة (كجم)'**
  String get obVehiclePayload;

  /// No description provided for @obVehicleVolume.
  ///
  /// In ar, this message translates to:
  /// **'حجم الشحن (م³)'**
  String get obVehicleVolume;

  /// No description provided for @obVehicleCapabilities.
  ///
  /// In ar, this message translates to:
  /// **'القدرات الخاصة'**
  String get obVehicleCapabilities;

  /// No description provided for @obVehicleSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ المركبة'**
  String get obVehicleSave;

  /// No description provided for @obVehiclePhotos.
  ///
  /// In ar, this message translates to:
  /// **'الصور'**
  String get obVehiclePhotos;

  /// No description provided for @obVehiclePhotosHint.
  ///
  /// In ar, this message translates to:
  /// **'أضف {count} صور على الأقل (أمامية وخلفية).'**
  String obVehiclePhotosHint(int count);

  /// No description provided for @obVehiclePhotoAdd.
  ///
  /// In ar, this message translates to:
  /// **'إضافة صورة'**
  String get obVehiclePhotoAdd;

  /// No description provided for @obVehiclePhotoKindTitle.
  ///
  /// In ar, this message translates to:
  /// **'أي جزء من المركبة؟'**
  String get obVehiclePhotoKindTitle;

  /// No description provided for @obPhotoKindFront.
  ///
  /// In ar, this message translates to:
  /// **'أمامية'**
  String get obPhotoKindFront;

  /// No description provided for @obPhotoKindBack.
  ///
  /// In ar, this message translates to:
  /// **'خلفية'**
  String get obPhotoKindBack;

  /// No description provided for @obPhotoKindSide.
  ///
  /// In ar, this message translates to:
  /// **'جانبية'**
  String get obPhotoKindSide;

  /// No description provided for @obPhotoKindInterior.
  ///
  /// In ar, this message translates to:
  /// **'داخلية'**
  String get obPhotoKindInterior;

  /// No description provided for @obPhotoKindOther.
  ///
  /// In ar, this message translates to:
  /// **'أخرى'**
  String get obPhotoKindOther;

  /// No description provided for @obVehicleDocuments.
  ///
  /// In ar, this message translates to:
  /// **'مستندات المركبة'**
  String get obVehicleDocuments;

  /// No description provided for @obVehicleSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال المركبة للمراجعة'**
  String get obVehicleSubmit;

  /// No description provided for @obVehicleDeleteTitle.
  ///
  /// In ar, this message translates to:
  /// **'إزالة هذه المركبة؟'**
  String get obVehicleDeleteTitle;

  /// No description provided for @obVehicleDeleteBody.
  ///
  /// In ar, this message translates to:
  /// **'تُحذف المركبة وصورها ومستنداتها المرفوعة.'**
  String get obVehicleDeleteBody;

  /// No description provided for @obVehicleSubmitted.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال المركبة للمراجعة.'**
  String get obVehicleSubmitted;

  /// No description provided for @obVehicleLockedHint.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تعديل المركبة وهي قيد المراجعة.'**
  String get obVehicleLockedHint;

  /// No description provided for @obVehicleStatusDraft.
  ///
  /// In ar, this message translates to:
  /// **'مسودة'**
  String get obVehicleStatusDraft;

  /// No description provided for @obVehicleStatusUnderReview.
  ///
  /// In ar, this message translates to:
  /// **'قيد المراجعة'**
  String get obVehicleStatusUnderReview;

  /// No description provided for @obVehicleStatusApproved.
  ///
  /// In ar, this message translates to:
  /// **'معتمدة'**
  String get obVehicleStatusApproved;

  /// No description provided for @obVehicleStatusRejected.
  ///
  /// In ar, this message translates to:
  /// **'مرفوضة'**
  String get obVehicleStatusRejected;

  /// No description provided for @obVehicleStatusSuspended.
  ///
  /// In ar, this message translates to:
  /// **'موقوفة'**
  String get obVehicleStatusSuspended;

  /// No description provided for @obVehicleStatusInactive.
  ///
  /// In ar, this message translates to:
  /// **'غير نشطة'**
  String get obVehicleStatusInactive;

  /// No description provided for @obVehicleRejectedReason.
  ///
  /// In ar, this message translates to:
  /// **'سبب الرفض: {reason}'**
  String obVehicleRejectedReason(String reason);

  /// No description provided for @obCapAirConditioning.
  ///
  /// In ar, this message translates to:
  /// **'تكييف'**
  String get obCapAirConditioning;

  /// No description provided for @obCapChildSeat.
  ///
  /// In ar, this message translates to:
  /// **'مقعد أطفال'**
  String get obCapChildSeat;

  /// No description provided for @obCapWheelchair.
  ///
  /// In ar, this message translates to:
  /// **'مهيأة للكراسي المتحركة'**
  String get obCapWheelchair;

  /// No description provided for @obCapPets.
  ///
  /// In ar, this message translates to:
  /// **'يُسمح بالحيوانات الأليفة'**
  String get obCapPets;

  /// No description provided for @obCapLargeLuggage.
  ///
  /// In ar, this message translates to:
  /// **'حقائب كبيرة'**
  String get obCapLargeLuggage;

  /// No description provided for @obCapCoveredCargo.
  ///
  /// In ar, this message translates to:
  /// **'صندوق شحن مغطى'**
  String get obCapCoveredCargo;

  /// No description provided for @obCapTailLift.
  ///
  /// In ar, this message translates to:
  /// **'رافعة خلفية'**
  String get obCapTailLift;

  /// No description provided for @obCapRefrigerated.
  ///
  /// In ar, this message translates to:
  /// **'مبرّدة'**
  String get obCapRefrigerated;

  /// No description provided for @obCapMovingEquipment.
  ///
  /// In ar, this message translates to:
  /// **'معدات نقل أثاث'**
  String get obCapMovingEquipment;

  /// No description provided for @obDocumentsTitle.
  ///
  /// In ar, this message translates to:
  /// **'المستندات المطلوبة'**
  String get obDocumentsTitle;

  /// No description provided for @obDocumentsIntro.
  ///
  /// In ar, this message translates to:
  /// **'ارفع صورة واضحة لكل مستند. تُراجع المستندات يدوياً وتظهر هنا حالة كل منها.'**
  String get obDocumentsIntro;

  /// No description provided for @obDocumentsEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد مستندات مطلوبة لك حالياً.'**
  String get obDocumentsEmpty;

  /// No description provided for @obDocOptional.
  ///
  /// In ar, this message translates to:
  /// **'اختياري'**
  String get obDocOptional;

  /// No description provided for @obDocStateMissing.
  ///
  /// In ar, this message translates to:
  /// **'لم يُرفع'**
  String get obDocStateMissing;

  /// No description provided for @obDocStatePending.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار المراجعة'**
  String get obDocStatePending;

  /// No description provided for @obDocStateApproved.
  ///
  /// In ar, this message translates to:
  /// **'معتمد'**
  String get obDocStateApproved;

  /// No description provided for @obDocStateRejected.
  ///
  /// In ar, this message translates to:
  /// **'مرفوض'**
  String get obDocStateRejected;

  /// No description provided for @obDocStateExpired.
  ///
  /// In ar, this message translates to:
  /// **'منتهي الصلاحية'**
  String get obDocStateExpired;

  /// No description provided for @obDocExpires.
  ///
  /// In ar, this message translates to:
  /// **'ينتهي في {date}'**
  String obDocExpires(String date);

  /// No description provided for @obDocReason.
  ///
  /// In ar, this message translates to:
  /// **'السبب: {reason}'**
  String obDocReason(String reason);

  /// No description provided for @obDocUpload.
  ///
  /// In ar, this message translates to:
  /// **'رفع'**
  String get obDocUpload;

  /// No description provided for @obDocReplace.
  ///
  /// In ar, this message translates to:
  /// **'رفع مرة أخرى'**
  String get obDocReplace;

  /// No description provided for @obDocRenew.
  ///
  /// In ar, this message translates to:
  /// **'تجديد'**
  String get obDocRenew;

  /// No description provided for @obDocFormTitle.
  ///
  /// In ar, this message translates to:
  /// **'رفع مستند'**
  String get obDocFormTitle;

  /// No description provided for @obDocPhoto.
  ///
  /// In ar, this message translates to:
  /// **'صورة المستند'**
  String get obDocPhoto;

  /// No description provided for @obDocChoosePhoto.
  ///
  /// In ar, this message translates to:
  /// **'التقط أو اختر صورة'**
  String get obDocChoosePhoto;

  /// No description provided for @obDocChangePhoto.
  ///
  /// In ar, this message translates to:
  /// **'تغيير الصورة'**
  String get obDocChangePhoto;

  /// No description provided for @obDocNumber.
  ///
  /// In ar, this message translates to:
  /// **'رقم المستند'**
  String get obDocNumber;

  /// No description provided for @obDocIssuedAt.
  ///
  /// In ar, this message translates to:
  /// **'تاريخ الإصدار'**
  String get obDocIssuedAt;

  /// No description provided for @obDocExpiresAt.
  ///
  /// In ar, this message translates to:
  /// **'تاريخ الانتهاء'**
  String get obDocExpiresAt;

  /// No description provided for @obDocSend.
  ///
  /// In ar, this message translates to:
  /// **'إرسال المستند'**
  String get obDocSend;

  /// No description provided for @obDocSent.
  ///
  /// In ar, this message translates to:
  /// **'تم رفع المستند وسيُراجع قريباً.'**
  String get obDocSent;

  /// No description provided for @obDocHint.
  ///
  /// In ar, this message translates to:
  /// **'الأنواع المسموحة: {types}. الحد الأقصى {size} كيلوبايت.'**
  String obDocHint(String types, int size);

  /// No description provided for @obDocPhotoRequired.
  ///
  /// In ar, this message translates to:
  /// **'اختر صورة المستند أولاً.'**
  String get obDocPhotoRequired;

  /// No description provided for @obDeclTitle.
  ///
  /// In ar, this message translates to:
  /// **'الإقرارات والشروط'**
  String get obDeclTitle;

  /// No description provided for @obDeclIntro.
  ///
  /// In ar, this message translates to:
  /// **'اقرأ الشروط ووافق عليها لإكمال طلبك. يُسجَّل إصدار الشروط ووقت الموافقة.'**
  String get obDeclIntro;

  /// No description provided for @obDeclAccept.
  ///
  /// In ar, this message translates to:
  /// **'قرأت وأوافق على: {title}'**
  String obDeclAccept(String title);

  /// No description provided for @obDeclAcceptAll.
  ///
  /// In ar, this message translates to:
  /// **'موافقة ومتابعة'**
  String get obDeclAcceptAll;

  /// No description provided for @obDeclRead.
  ///
  /// In ar, this message translates to:
  /// **'قراءة النص'**
  String get obDeclRead;

  /// No description provided for @obDeclNone.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد شروط تنتظر موافقتك.'**
  String get obDeclNone;

  /// No description provided for @obDeclAccepted.
  ///
  /// In ar, this message translates to:
  /// **'تمت الموافقة على كل الشروط.'**
  String get obDeclAccepted;

  /// No description provided for @obPolicyTerms.
  ///
  /// In ar, this message translates to:
  /// **'شروط الاستخدام'**
  String get obPolicyTerms;

  /// No description provided for @obPolicyPrivacy.
  ///
  /// In ar, this message translates to:
  /// **'سياسة الخصوصية'**
  String get obPolicyPrivacy;

  /// No description provided for @obPolicyDriverTerms.
  ///
  /// In ar, this message translates to:
  /// **'شروط السائقين'**
  String get obPolicyDriverTerms;

  /// No description provided for @obPolicyOther.
  ///
  /// In ar, this message translates to:
  /// **'سياسة'**
  String get obPolicyOther;

  /// No description provided for @obReviewTitle.
  ///
  /// In ar, this message translates to:
  /// **'المراجعة والإرسال'**
  String get obReviewTitle;

  /// No description provided for @obReviewReady.
  ///
  /// In ar, this message translates to:
  /// **'كل شيء جاهز. أرسل طلبك ليراجعه فريقنا.'**
  String get obReviewReady;

  /// No description provided for @obReviewTodo.
  ///
  /// In ar, this message translates to:
  /// **'ما زال أمامك:'**
  String get obReviewTodo;

  /// No description provided for @obReviewSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الطلب'**
  String get obReviewSubmit;

  /// No description provided for @obReviewSubmitted.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال طلبك للمراجعة.'**
  String get obReviewSubmitted;

  /// No description provided for @obGoToStep.
  ///
  /// In ar, this message translates to:
  /// **'انتقل'**
  String get obGoToStep;

  /// No description provided for @obTaskCompleteProfile.
  ///
  /// In ar, this message translates to:
  /// **'أكمل ملفك الشخصي'**
  String get obTaskCompleteProfile;

  /// No description provided for @obTaskSelectServices.
  ///
  /// In ar, this message translates to:
  /// **'اختر الخدمات التي تقدمها'**
  String get obTaskSelectServices;

  /// No description provided for @obTaskAddVehicle.
  ///
  /// In ar, this message translates to:
  /// **'أضف مركبة'**
  String get obTaskAddVehicle;

  /// No description provided for @obTaskAddVehiclePhotos.
  ///
  /// In ar, this message translates to:
  /// **'أضف صور المركبة'**
  String get obTaskAddVehiclePhotos;

  /// No description provided for @obTaskSubmitVehicle.
  ///
  /// In ar, this message translates to:
  /// **'أرسل المركبة للمراجعة'**
  String get obTaskSubmitVehicle;

  /// No description provided for @obTaskUploadDocument.
  ///
  /// In ar, this message translates to:
  /// **'ارفع: {name}'**
  String obTaskUploadDocument(String name);

  /// No description provided for @obTaskReplaceDocument.
  ///
  /// In ar, this message translates to:
  /// **'استبدل المستند المرفوض: {name}'**
  String obTaskReplaceDocument(String name);

  /// No description provided for @obTaskRenewDocument.
  ///
  /// In ar, this message translates to:
  /// **'جدّد المستند المنتهي: {name}'**
  String obTaskRenewDocument(String name);

  /// No description provided for @obTaskFixVehicle.
  ///
  /// In ar, this message translates to:
  /// **'صحّح بيانات المركبة وأرسلها مرة أخرى'**
  String get obTaskFixVehicle;

  /// No description provided for @obTaskAcceptPolicies.
  ///
  /// In ar, this message translates to:
  /// **'وافق على شروط السائقين'**
  String get obTaskAcceptPolicies;

  /// No description provided for @obTaskSubmitApplication.
  ///
  /// In ar, this message translates to:
  /// **'أرسل طلبك'**
  String get obTaskSubmitApplication;

  /// No description provided for @vsTitle.
  ///
  /// In ar, this message translates to:
  /// **'حالة التحقق'**
  String get vsTitle;

  /// No description provided for @vsUnderReviewTitle.
  ///
  /// In ar, this message translates to:
  /// **'نراجع طلبك'**
  String get vsUnderReviewTitle;

  /// No description provided for @vsUnderReviewBody.
  ///
  /// In ar, this message translates to:
  /// **'سيراجع فريقنا مستنداتك ومركبتك. سنخبرك بالنتيجة، ولا يلزمك أي إجراء الآن.'**
  String get vsUnderReviewBody;

  /// No description provided for @vsMoreInfoTitle.
  ///
  /// In ar, this message translates to:
  /// **'نحتاج إلى معلومات إضافية'**
  String get vsMoreInfoTitle;

  /// No description provided for @vsMoreInfoBody.
  ///
  /// In ar, this message translates to:
  /// **'صحّح ما يلي ثم أعد إرسال طلبك للمراجعة.'**
  String get vsMoreInfoBody;

  /// No description provided for @vsRejectedTitle.
  ///
  /// In ar, this message translates to:
  /// **'لم يُقبل طلبك'**
  String get vsRejectedTitle;

  /// No description provided for @vsRejectedBody.
  ///
  /// In ar, this message translates to:
  /// **'يمكنك تصحيح الأسباب أدناه وإرسال طلبك مجدداً.'**
  String get vsRejectedBody;

  /// No description provided for @vsApprovedTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم اعتماد طلبك'**
  String get vsApprovedTitle;

  /// No description provided for @vsApprovedBody.
  ///
  /// In ar, this message translates to:
  /// **'تم اعتمادك كمقدم خدمة. تأكد من بقاء مستنداتك صالحة.'**
  String get vsApprovedBody;

  /// No description provided for @vsSuspendedTitle.
  ///
  /// In ar, this message translates to:
  /// **'حسابك موقوف مؤقتاً'**
  String get vsSuspendedTitle;

  /// No description provided for @vsSuspendedBody.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكنك العمل حتى يُرفع الإيقاف. تواصل مع الدعم لمعرفة المزيد.'**
  String get vsSuspendedBody;

  /// No description provided for @vsPermanentTitle.
  ///
  /// In ar, this message translates to:
  /// **'حسابك موقوف نهائياً'**
  String get vsPermanentTitle;

  /// No description provided for @vsPermanentBody.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكنك العمل بهذا الحساب. تواصل مع الدعم لمعرفة المزيد.'**
  String get vsPermanentBody;

  /// No description provided for @vsDeletionTitle.
  ///
  /// In ar, this message translates to:
  /// **'طلب حذف الحساب قيد المعالجة'**
  String get vsDeletionTitle;

  /// No description provided for @vsDeletionBody.
  ///
  /// In ar, this message translates to:
  /// **'أُرسل طلب حذف حسابك وهو قيد المعالجة.'**
  String get vsDeletionBody;

  /// No description provided for @vsDraftTitle.
  ///
  /// In ar, this message translates to:
  /// **'أكمل طلبك'**
  String get vsDraftTitle;

  /// No description provided for @vsDraftBody.
  ///
  /// In ar, this message translates to:
  /// **'لم تُرسل طلبك بعد. تابع من حيث توقفت.'**
  String get vsDraftBody;

  /// No description provided for @vsReviewerReason.
  ///
  /// In ar, this message translates to:
  /// **'رسالة من فريق المراجعة'**
  String get vsReviewerReason;

  /// No description provided for @vsTasksTitle.
  ///
  /// In ar, this message translates to:
  /// **'ما يجب فعله الآن'**
  String get vsTasksTitle;

  /// No description provided for @vsNoTasks.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد إجراءات مطلوبة.'**
  String get vsNoTasks;

  /// No description provided for @vsDocumentsTitle.
  ///
  /// In ar, this message translates to:
  /// **'المستندات'**
  String get vsDocumentsTitle;

  /// No description provided for @vsVehiclesTitle.
  ///
  /// In ar, this message translates to:
  /// **'المركبات'**
  String get vsVehiclesTitle;

  /// No description provided for @vsExpiringTitle.
  ///
  /// In ar, this message translates to:
  /// **'مستندات تنتهي قريباً'**
  String get vsExpiringTitle;

  /// No description provided for @vsEligibilityTitle.
  ///
  /// In ar, this message translates to:
  /// **'الجاهزية للعمل'**
  String get vsEligibilityTitle;

  /// No description provided for @vsEligible.
  ///
  /// In ar, this message translates to:
  /// **'تستوفي كل المتطلبات. الاتصال بالإنترنت لاستقبال الطلبات يتوفر في التحديث القادم.'**
  String get vsEligible;

  /// No description provided for @vsNotEligible.
  ///
  /// In ar, this message translates to:
  /// **'لم تكتمل جاهزيتك للعمل بعد:'**
  String get vsNotEligible;

  /// No description provided for @vsContinue.
  ///
  /// In ar, this message translates to:
  /// **'متابعة الطلب'**
  String get vsContinue;

  /// No description provided for @vsUpdate.
  ///
  /// In ar, this message translates to:
  /// **'تحديث الطلب'**
  String get vsUpdate;

  /// No description provided for @vsSupportTitle.
  ///
  /// In ar, this message translates to:
  /// **'تحتاج مساعدة؟'**
  String get vsSupportTitle;

  /// No description provided for @vsSupportPhone.
  ///
  /// In ar, this message translates to:
  /// **'اتصل بالدعم: {phone}'**
  String vsSupportPhone(String phone);

  /// No description provided for @vsSupportEmail.
  ///
  /// In ar, this message translates to:
  /// **'راسل الدعم: {email}'**
  String vsSupportEmail(String email);

  /// No description provided for @vsSubmittedOn.
  ///
  /// In ar, this message translates to:
  /// **'أُرسل في {date}'**
  String vsSubmittedOn(String date);

  /// No description provided for @erNotApproved.
  ///
  /// In ar, this message translates to:
  /// **'لم يتم اعتماد طلبك بعد.'**
  String get erNotApproved;

  /// No description provided for @erProviderSuspended.
  ///
  /// In ar, this message translates to:
  /// **'حسابك موقوف.'**
  String get erProviderSuspended;

  /// No description provided for @erAccountBlocked.
  ///
  /// In ar, this message translates to:
  /// **'الحساب محظور أو مقيّد.'**
  String get erAccountBlocked;

  /// No description provided for @erDocumentMissing.
  ///
  /// In ar, this message translates to:
  /// **'هناك مستند مطلوب غير مرفوع.'**
  String get erDocumentMissing;

  /// No description provided for @erDocumentNotApproved.
  ///
  /// In ar, this message translates to:
  /// **'هناك مستند مطلوب لم يُعتمد بعد.'**
  String get erDocumentNotApproved;

  /// No description provided for @erDocumentExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت صلاحية مستند مطلوب.'**
  String get erDocumentExpired;

  /// No description provided for @erNoApprovedVehicle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد مركبة معتمدة.'**
  String get erNoApprovedVehicle;

  /// No description provided for @erVehicleNotApproved.
  ///
  /// In ar, this message translates to:
  /// **'المركبة غير معتمدة.'**
  String get erVehicleNotApproved;

  /// No description provided for @erServiceNotApproved.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد خدمة معتمدة لهذه الفئة.'**
  String get erServiceNotApproved;

  /// No description provided for @erVehicleCategoryMismatch.
  ///
  /// In ar, this message translates to:
  /// **'فئة المركبة لا تناسب هذه الخدمة.'**
  String get erVehicleCategoryMismatch;

  /// No description provided for @erUnknown.
  ///
  /// In ar, this message translates to:
  /// **'هناك متطلب غير مكتمل.'**
  String get erUnknown;

  /// No description provided for @obErrGeneric.
  ///
  /// In ar, this message translates to:
  /// **'تعذر حفظ التغيير. حاول مرة أخرى.'**
  String get obErrGeneric;

  /// No description provided for @obErrValidation.
  ///
  /// In ar, this message translates to:
  /// **'راجع البيانات المدخلة وحاول مرة أخرى.'**
  String get obErrValidation;

  /// No description provided for @obErrUnderage.
  ///
  /// In ar, this message translates to:
  /// **'يجب ألا يقل عمرك عن {age} سنة.'**
  String obErrUnderage(int age);

  /// No description provided for @obErrCityUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'لا نعمل في هذه المدينة بعد.'**
  String get obErrCityUnavailable;

  /// No description provided for @obErrServiceUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'إحدى الخدمات المختارة غير متاحة.'**
  String get obErrServiceUnavailable;

  /// No description provided for @obErrApplicationLocked.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تعديل الطلب في حالته الحالية.'**
  String get obErrApplicationLocked;

  /// No description provided for @obErrApplicationExists.
  ///
  /// In ar, this message translates to:
  /// **'لقد بدأت طلباً بالفعل.'**
  String get obErrApplicationExists;

  /// No description provided for @obErrIncomplete.
  ///
  /// In ar, this message translates to:
  /// **'الطلب غير مكتمل بعد. أكمل المهام المتبقية.'**
  String get obErrIncomplete;

  /// No description provided for @obErrDocTypeNotAllowed.
  ///
  /// In ar, this message translates to:
  /// **'نوع الملف غير مسموح لهذا المستند.'**
  String get obErrDocTypeNotAllowed;

  /// No description provided for @obErrDocTooLarge.
  ///
  /// In ar, this message translates to:
  /// **'الملف كبير جداً. اختر صورة أصغر.'**
  String get obErrDocTooLarge;

  /// No description provided for @obErrDocUnreadable.
  ///
  /// In ar, this message translates to:
  /// **'الملف فارغ أو لا يمكن قراءته.'**
  String get obErrDocUnreadable;

  /// No description provided for @obErrDocTooSmall.
  ///
  /// In ar, this message translates to:
  /// **'الصورة صغيرة جداً. التقط صورة أوضح.'**
  String get obErrDocTooSmall;

  /// No description provided for @obErrDocInfected.
  ///
  /// In ar, this message translates to:
  /// **'تم رفض الملف بواسطة الفحص الأمني.'**
  String get obErrDocInfected;

  /// No description provided for @obErrScannerDown.
  ///
  /// In ar, this message translates to:
  /// **'تعذر فحص الملفات حالياً. حاول بعد قليل.'**
  String get obErrScannerDown;

  /// No description provided for @obErrNumberRequired.
  ///
  /// In ar, this message translates to:
  /// **'رقم المستند مطلوب.'**
  String get obErrNumberRequired;

  /// No description provided for @obErrExpiryRequired.
  ///
  /// In ar, this message translates to:
  /// **'تاريخ الانتهاء مطلوب لهذا المستند.'**
  String get obErrExpiryRequired;

  /// No description provided for @obErrAlreadyExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت صلاحية هذا المستند بالفعل.'**
  String get obErrAlreadyExpired;

  /// No description provided for @obErrDatesInconsistent.
  ///
  /// In ar, this message translates to:
  /// **'يجب أن يكون تاريخ الانتهاء بعد تاريخ الإصدار.'**
  String get obErrDatesInconsistent;

  /// No description provided for @obErrIssueFuture.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن أن يكون تاريخ الإصدار في المستقبل.'**
  String get obErrIssueFuture;

  /// No description provided for @obErrPlateTaken.
  ///
  /// In ar, this message translates to:
  /// **'توجد مركبة مسجلة بهذه اللوحة بالفعل.'**
  String get obErrPlateTaken;

  /// No description provided for @obErrPlateInvalid.
  ///
  /// In ar, this message translates to:
  /// **'رقم اللوحة غير صالح.'**
  String get obErrPlateInvalid;

  /// No description provided for @obErrYearInvalid.
  ///
  /// In ar, this message translates to:
  /// **'سنة الصنع غير صالحة.'**
  String get obErrYearInvalid;

  /// No description provided for @obErrVehicleNotEditable.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تعديل المركبة في حالتها الحالية.'**
  String get obErrVehicleNotEditable;

  /// No description provided for @obErrVehicleIncomplete.
  ///
  /// In ar, this message translates to:
  /// **'المركبة غير مكتملة: أضف الصور والمستندات الناقصة.'**
  String get obErrVehicleIncomplete;

  /// No description provided for @obErrPhotoLimit.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى الحد الأقصى لعدد الصور.'**
  String get obErrPhotoLimit;

  /// No description provided for @obErrVehicleLimit.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى الحد الأقصى لعدد المركبات.'**
  String get obErrVehicleLimit;

  /// No description provided for @obErrPolicyStale.
  ///
  /// In ar, this message translates to:
  /// **'تغيّرت الشروط. حدّث الصفحة وحاول مرة أخرى.'**
  String get obErrPolicyStale;
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
