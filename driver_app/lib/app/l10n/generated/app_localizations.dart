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
  /// **'أنت تستوفي كل شروط العمل.'**
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

  /// No description provided for @erLocationStale.
  ///
  /// In ar, this message translates to:
  /// **'موقعك غير متاح أو قديم. فعّل الموقع وحاول مرة أخرى.'**
  String get erLocationStale;

  /// No description provided for @erActiveJobExists.
  ///
  /// In ar, this message translates to:
  /// **'لديك مهمة نشطة بالفعل.'**
  String get erActiveJobExists;

  /// No description provided for @erCashDebtLimitExceeded.
  ///
  /// In ar, this message translates to:
  /// **'العمولة غير المسددة تجاوزت الحد المسموح. سدّدها لتتمكن من الاتصال.'**
  String get erCashDebtLimitExceeded;

  /// No description provided for @avTitle.
  ///
  /// In ar, this message translates to:
  /// **'التواجد'**
  String get avTitle;

  /// No description provided for @avOnlineTitle.
  ///
  /// In ar, this message translates to:
  /// **'أنت متصل'**
  String get avOnlineTitle;

  /// No description provided for @avOfflineTitle.
  ///
  /// In ar, this message translates to:
  /// **'أنت غير متصل'**
  String get avOfflineTitle;

  /// No description provided for @avOnlineBody.
  ///
  /// In ar, this message translates to:
  /// **'ستظهر هنا الطلبات الجديدة القريبة منك.'**
  String get avOnlineBody;

  /// No description provided for @avOfflineBody.
  ///
  /// In ar, this message translates to:
  /// **'فعّل التواجد لاستقبال الطلبات.'**
  String get avOfflineBody;

  /// No description provided for @avSwitchLabel.
  ///
  /// In ar, this message translates to:
  /// **'استقبال الطلبات'**
  String get avSwitchLabel;

  /// No description provided for @avReasonsTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكنك الاتصال بعد:'**
  String get avReasonsTitle;

  /// No description provided for @avVehicleLabel.
  ///
  /// In ar, this message translates to:
  /// **'المركبة'**
  String get avVehicleLabel;

  /// No description provided for @avNoLocationNote.
  ///
  /// In ar, this message translates to:
  /// **'تتم مشاركة موقعك فقط أثناء الاتصال أو تنفيذ مهمة.'**
  String get avNoLocationNote;

  /// No description provided for @avTrackingOff.
  ///
  /// In ar, this message translates to:
  /// **'مشاركة الموقع متوقفة'**
  String get avTrackingOff;

  /// No description provided for @avTrackingIdle.
  ///
  /// In ar, this message translates to:
  /// **'نشارك موقعك أثناء انتظار الطلبات'**
  String get avTrackingIdle;

  /// No description provided for @avTrackingToPickup.
  ///
  /// In ar, this message translates to:
  /// **'نشارك موقعك في الطريق إلى نقطة الاستلام'**
  String get avTrackingToPickup;

  /// No description provided for @avTrackingActive.
  ///
  /// In ar, this message translates to:
  /// **'نشارك موقعك أثناء المهمة'**
  String get avTrackingActive;

  /// No description provided for @avTrackingEvery.
  ///
  /// In ar, this message translates to:
  /// **'يتم التحديث كل {seconds} ثانية'**
  String avTrackingEvery(int seconds);

  /// No description provided for @avUploadFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر إرسال موقعك. نعيد المحاولة.'**
  String get avUploadFailed;

  /// No description provided for @avRealtimeConnected.
  ///
  /// In ar, this message translates to:
  /// **'مباشر'**
  String get avRealtimeConnected;

  /// No description provided for @avRealtimeConnecting.
  ///
  /// In ar, this message translates to:
  /// **'جارٍ الاتصال'**
  String get avRealtimeConnecting;

  /// No description provided for @avRealtimeDisconnected.
  ///
  /// In ar, this message translates to:
  /// **'لا يوجد اتصال مباشر، نعيد المحاولة'**
  String get avRealtimeDisconnected;

  /// No description provided for @avPermissionDenied.
  ///
  /// In ar, this message translates to:
  /// **'اسمح بالوصول إلى الموقع لتتمكن من الاتصال.'**
  String get avPermissionDenied;

  /// No description provided for @avPermissionForever.
  ///
  /// In ar, this message translates to:
  /// **'الموقع محظور لهذا التطبيق. افتح الإعدادات للسماح به.'**
  String get avPermissionForever;

  /// No description provided for @avServiceDisabled.
  ///
  /// In ar, this message translates to:
  /// **'خدمات الموقع متوقفة على هذا الجهاز.'**
  String get avServiceDisabled;

  /// No description provided for @avOpenSettings.
  ///
  /// In ar, this message translates to:
  /// **'فتح الإعدادات'**
  String get avOpenSettings;

  /// No description provided for @avPositionUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحديد موقعك. حاول في مكان إشارته أفضل.'**
  String get avPositionUnavailable;

  /// No description provided for @avOutsideArea.
  ///
  /// In ar, this message translates to:
  /// **'أنت خارج مناطق الخدمة.'**
  String get avOutsideArea;

  /// No description provided for @avVehicleNotUsable.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن استخدام هذه المركبة الآن.'**
  String get avVehicleNotUsable;

  /// No description provided for @avOfflineStale.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف اتصالك لأن موقعك توقف عن التحديث.'**
  String get avOfflineStale;

  /// No description provided for @avOfflineIneligible.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف اتصالك لأنك لم تعد تستوفي الشروط.'**
  String get avOfflineIneligible;

  /// No description provided for @avOfflineAccount.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف اتصالك لأن حالة حسابك تغيرت.'**
  String get avOfflineAccount;

  /// No description provided for @avRationaleTitle.
  ///
  /// In ar, this message translates to:
  /// **'لماذا نحتاج موقعك'**
  String get avRationaleTitle;

  /// No description provided for @avRationaleBody.
  ///
  /// In ar, this message translates to:
  /// **'نستخدم موقعك فقط أثناء الاتصال وأثناء المهام: لإظهارك للعملاء القريبين، وإرشادك إلى نقطة الاستلام، وتتبع الرحلة والحفاظ على أمانها. نتوقف فور إيقاف اتصالك. أثناء الاتصال يبقى إشعار ظاهراً على الشاشة وقد يستمر تحديث الموقع عندما يكون التطبيق في الخلفية.'**
  String get avRationaleBody;

  /// No description provided for @avRationaleContinue.
  ///
  /// In ar, this message translates to:
  /// **'متابعة'**
  String get avRationaleContinue;

  /// No description provided for @avRationaleCancel.
  ///
  /// In ar, this message translates to:
  /// **'ليس الآن'**
  String get avRationaleCancel;

  /// No description provided for @avOfflineConfirmTitle.
  ///
  /// In ar, this message translates to:
  /// **'لديك مهمة جارية'**
  String get avOfflineConfirmTitle;

  /// No description provided for @avOfflineConfirmBody.
  ///
  /// In ar, this message translates to:
  /// **'إيقاف الاتصال يوقف الطلبات الجديدة. تستمر مهمتك الحالية ويستمر إرسال موقعك حتى تنتهي.'**
  String get avOfflineConfirmBody;

  /// No description provided for @avOfflineConfirmAction.
  ///
  /// In ar, this message translates to:
  /// **'إيقاف الاتصال'**
  String get avOfflineConfirmAction;

  /// No description provided for @avStayOnline.
  ///
  /// In ar, this message translates to:
  /// **'البقاء متصلاً'**
  String get avStayOnline;

  /// No description provided for @avActiveJobBanner.
  ///
  /// In ar, this message translates to:
  /// **'مهمة جارية'**
  String get avActiveJobBanner;

  /// No description provided for @avNotificationTitle.
  ///
  /// In ar, this message translates to:
  /// **'أنت متصل'**
  String get avNotificationTitle;

  /// No description provided for @avNotificationText.
  ///
  /// In ar, this message translates to:
  /// **'نشارك موقعك لاستقبال الطلبات'**
  String get avNotificationText;

  /// No description provided for @actionDone.
  ///
  /// In ar, this message translates to:
  /// **'تم'**
  String get actionDone;

  /// No description provided for @jbTabHome.
  ///
  /// In ar, this message translates to:
  /// **'الرئيسية'**
  String get jbTabHome;

  /// No description provided for @jbTabRequests.
  ///
  /// In ar, this message translates to:
  /// **'الطلبات'**
  String get jbTabRequests;

  /// No description provided for @jbTabOffers.
  ///
  /// In ar, this message translates to:
  /// **'عروضي'**
  String get jbTabOffers;

  /// No description provided for @jbTabHistory.
  ///
  /// In ar, this message translates to:
  /// **'السجل'**
  String get jbTabHistory;

  /// No description provided for @jbApplicationEntry.
  ///
  /// In ar, this message translates to:
  /// **'طلب الانضمام والمستندات'**
  String get jbApplicationEntry;

  /// No description provided for @jbApplicationEntryBody.
  ///
  /// In ar, this message translates to:
  /// **'تابع حالة المراجعة والمستندات والمركبات.'**
  String get jbApplicationEntryBody;

  /// No description provided for @jbAlertSound.
  ///
  /// In ar, this message translates to:
  /// **'صوت عند وصول طلب جديد'**
  String get jbAlertSound;

  /// No description provided for @jbAlertVibration.
  ///
  /// In ar, this message translates to:
  /// **'اهتزاز عند وصول طلب جديد'**
  String get jbAlertVibration;

  /// No description provided for @jbGoOnlineTitle.
  ///
  /// In ar, this message translates to:
  /// **'أنت غير متصل'**
  String get jbGoOnlineTitle;

  /// No description provided for @jbGoOnlineBody.
  ///
  /// In ar, this message translates to:
  /// **'فعّل الاتصال من الرئيسية لتستقبل الطلبات القريبة منك.'**
  String get jbGoOnlineBody;

  /// No description provided for @jbGoOnlineAction.
  ///
  /// In ar, this message translates to:
  /// **'الذهاب إلى الرئيسية'**
  String get jbGoOnlineAction;

  /// No description provided for @jbNotEligibleTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكنك استقبال الطلبات الآن'**
  String get jbNotEligibleTitle;

  /// No description provided for @jbNoRequestsTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد طلبات حاليًا'**
  String get jbNoRequestsTitle;

  /// No description provided for @jbNoRequestsBody.
  ///
  /// In ar, this message translates to:
  /// **'سنخبرك فور وصول طلب قريب. اترك التطبيق مفتوحًا.'**
  String get jbNoRequestsBody;

  /// No description provided for @jbRequestExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت المهلة'**
  String get jbRequestExpired;

  /// No description provided for @jbExpiresIn.
  ///
  /// In ar, this message translates to:
  /// **'ينتهي خلال {time}'**
  String jbExpiresIn(String time);

  /// No description provided for @jbCustomerFare.
  ///
  /// In ar, this message translates to:
  /// **'سعر العميل'**
  String get jbCustomerFare;

  /// No description provided for @jbPickupArea.
  ///
  /// In ar, this message translates to:
  /// **'منطقة الالتقاء: {area}'**
  String jbPickupArea(String area);

  /// No description provided for @jbDropoffArea.
  ///
  /// In ar, this message translates to:
  /// **'منطقة الوجهة: {area}'**
  String jbDropoffArea(String area);

  /// No description provided for @jbAreaUnknown.
  ///
  /// In ar, this message translates to:
  /// **'غير محددة'**
  String get jbAreaUnknown;

  /// No description provided for @jbAwayKm.
  ///
  /// In ar, this message translates to:
  /// **'يبعد {distance}'**
  String jbAwayKm(String distance);

  /// No description provided for @jbKm.
  ///
  /// In ar, this message translates to:
  /// **'{value} كم'**
  String jbKm(String value);

  /// No description provided for @jbMinutes.
  ///
  /// In ar, this message translates to:
  /// **'{count} دقيقة'**
  String jbMinutes(int count);

  /// No description provided for @jbPassengers.
  ///
  /// In ar, this message translates to:
  /// **'{count} ركاب'**
  String jbPassengers(int count);

  /// No description provided for @jbStops.
  ///
  /// In ar, this message translates to:
  /// **'{count} محطات'**
  String jbStops(int count);

  /// No description provided for @jbLuggage.
  ///
  /// In ar, this message translates to:
  /// **'{count} حقائب'**
  String jbLuggage(int count);

  /// No description provided for @jbWeightKg.
  ///
  /// In ar, this message translates to:
  /// **'{kg} كجم'**
  String jbWeightKg(String kg);

  /// No description provided for @jbScheduledAt.
  ///
  /// In ar, this message translates to:
  /// **'مجدول: {time}'**
  String jbScheduledAt(String time);

  /// No description provided for @jbRestrictedPickup.
  ///
  /// In ar, this message translates to:
  /// **'منطقة التقاء مقيّدة'**
  String get jbRestrictedPickup;

  /// No description provided for @jbAcceptFare.
  ///
  /// In ar, this message translates to:
  /// **'أقبل سعر العميل'**
  String get jbAcceptFare;

  /// No description provided for @jbCounterOffer.
  ///
  /// In ar, this message translates to:
  /// **'اقترح سعرًا'**
  String get jbCounterOffer;

  /// No description provided for @jbSkip.
  ///
  /// In ar, this message translates to:
  /// **'تخطي'**
  String get jbSkip;

  /// No description provided for @jbReviseOffer.
  ///
  /// In ar, this message translates to:
  /// **'عدّل عرضي'**
  String get jbReviseOffer;

  /// No description provided for @jbWithdraw.
  ///
  /// In ar, this message translates to:
  /// **'سحب العرض'**
  String get jbWithdraw;

  /// No description provided for @jbWithdrawn.
  ///
  /// In ar, this message translates to:
  /// **'تم سحب العرض.'**
  String get jbWithdrawn;

  /// No description provided for @jbOfferSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال عرضك. بانتظار قرار العميل.'**
  String get jbOfferSent;

  /// No description provided for @jbMyOfferAmount.
  ///
  /// In ar, this message translates to:
  /// **'عرضك: {amount}'**
  String jbMyOfferAmount(String amount);

  /// No description provided for @jbCounterTitle.
  ///
  /// In ar, this message translates to:
  /// **'اقترح سعرك'**
  String get jbCounterTitle;

  /// No description provided for @jbCustomerFareIs.
  ///
  /// In ar, this message translates to:
  /// **'سعر العميل: {fare}'**
  String jbCustomerFareIs(String fare);

  /// No description provided for @jbAllowedRange.
  ///
  /// In ar, this message translates to:
  /// **'المسموح من {min} إلى {max}'**
  String jbAllowedRange(String min, String max);

  /// No description provided for @jbAmountBelow.
  ///
  /// In ar, this message translates to:
  /// **'أقل من الحد الأدنى ({min})'**
  String jbAmountBelow(String min);

  /// No description provided for @jbAmountAbove.
  ///
  /// In ar, this message translates to:
  /// **'أعلى من الحد الأقصى ({max})'**
  String jbAmountAbove(String max);

  /// No description provided for @jbAmountSame.
  ///
  /// In ar, this message translates to:
  /// **'هذا هو سعر العميل نفسه. استخدم زر قبول السعر.'**
  String get jbAmountSame;

  /// No description provided for @jbSendOffer.
  ///
  /// In ar, this message translates to:
  /// **'إرسال العرض'**
  String get jbSendOffer;

  /// No description provided for @jbKeypadBackspace.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get jbKeypadBackspace;

  /// No description provided for @jbKeypadDecimal.
  ///
  /// In ar, this message translates to:
  /// **'فاصلة عشرية'**
  String get jbKeypadDecimal;

  /// No description provided for @jbPreviewGross.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ المقترح'**
  String get jbPreviewGross;

  /// No description provided for @jbPreviewCommission.
  ///
  /// In ar, this message translates to:
  /// **'عمولة المنصة'**
  String get jbPreviewCommission;

  /// No description provided for @jbPreviewNet.
  ///
  /// In ar, this message translates to:
  /// **'صافي ربحك'**
  String get jbPreviewNet;

  /// No description provided for @jbNoOffersTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد عروض'**
  String get jbNoOffersTitle;

  /// No description provided for @jbNoOffersBody.
  ///
  /// In ar, this message translates to:
  /// **'عروضك على الطلبات ستظهر هنا.'**
  String get jbNoOffersBody;

  /// No description provided for @jbOfferPending.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار العميل'**
  String get jbOfferPending;

  /// No description provided for @jbOfferSelected.
  ///
  /// In ar, this message translates to:
  /// **'تم اختيارك'**
  String get jbOfferSelected;

  /// No description provided for @jbOfferRejected.
  ///
  /// In ar, this message translates to:
  /// **'لم يُختر'**
  String get jbOfferRejected;

  /// No description provided for @jbOfferExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت المهلة'**
  String get jbOfferExpired;

  /// No description provided for @jbOfferInvalidated.
  ///
  /// In ar, this message translates to:
  /// **'لم يعد صالحًا'**
  String get jbOfferInvalidated;

  /// No description provided for @jbOfferWithdrawn.
  ///
  /// In ar, this message translates to:
  /// **'تم سحبه'**
  String get jbOfferWithdrawn;

  /// No description provided for @jbOfferUnknown.
  ///
  /// In ar, this message translates to:
  /// **'غير معروف'**
  String get jbOfferUnknown;

  /// No description provided for @jbReasonRejected.
  ///
  /// In ar, this message translates to:
  /// **'رفض العميل هذا العرض.'**
  String get jbReasonRejected;

  /// No description provided for @jbReasonLost.
  ///
  /// In ar, this message translates to:
  /// **'اختار العميل سائقًا آخر.'**
  String get jbReasonLost;

  /// No description provided for @jbReasonExpired.
  ///
  /// In ar, this message translates to:
  /// **'انتهت مهلة العرض دون رد.'**
  String get jbReasonExpired;

  /// No description provided for @jbReasonRequestChanged.
  ///
  /// In ar, this message translates to:
  /// **'غيّر العميل تفاصيل الطلب فأُلغي العرض.'**
  String get jbReasonRequestChanged;

  /// No description provided for @jbReasonUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'أُلغي العرض لأنك لم تعد متاحًا.'**
  String get jbReasonUnavailable;

  /// No description provided for @jbReasonWithdrawn.
  ///
  /// In ar, this message translates to:
  /// **'سحبت هذا العرض.'**
  String get jbReasonWithdrawn;

  /// No description provided for @jbKindFare.
  ///
  /// In ar, this message translates to:
  /// **'قبول سعر العميل'**
  String get jbKindFare;

  /// No description provided for @jbKindCounter.
  ///
  /// In ar, this message translates to:
  /// **'سعر مقترح منك'**
  String get jbKindCounter;

  /// No description provided for @jbOpenJob.
  ///
  /// In ar, this message translates to:
  /// **'فتح الرحلة'**
  String get jbOpenJob;

  /// No description provided for @jbNoHistoryTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد رحلات بعد'**
  String get jbNoHistoryTitle;

  /// No description provided for @jbNoHistoryBody.
  ///
  /// In ar, this message translates to:
  /// **'رحلاتك المنتهية ستظهر هنا.'**
  String get jbNoHistoryBody;

  /// No description provided for @jbHistoryJob.
  ///
  /// In ar, this message translates to:
  /// **'رحلة'**
  String get jbHistoryJob;

  /// No description provided for @jbJobTitle.
  ///
  /// In ar, this message translates to:
  /// **'الرحلة'**
  String get jbJobTitle;

  /// No description provided for @jbActiveJob.
  ///
  /// In ar, this message translates to:
  /// **'لديك رحلة جارية'**
  String get jbActiveJob;

  /// No description provided for @jbJobLostTitle.
  ///
  /// In ar, this message translates to:
  /// **'لم تعد هذه الرحلة معك'**
  String get jbJobLostTitle;

  /// No description provided for @jbJobLostBody.
  ///
  /// In ar, this message translates to:
  /// **'أُعيد توزيع الطلب أو أُلغي أو أُسند لسائق آخر.'**
  String get jbJobLostBody;

  /// No description provided for @jbBackHome.
  ///
  /// In ar, this message translates to:
  /// **'العودة للرئيسية'**
  String get jbBackHome;

  /// No description provided for @jbStatusSelected.
  ///
  /// In ar, this message translates to:
  /// **'تم اختيارك'**
  String get jbStatusSelected;

  /// No description provided for @jbStatusHeading.
  ///
  /// In ar, this message translates to:
  /// **'في الطريق إلى العميل'**
  String get jbStatusHeading;

  /// No description provided for @jbStatusArrived.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى العميل'**
  String get jbStatusArrived;

  /// No description provided for @jbStatusInProgress.
  ///
  /// In ar, this message translates to:
  /// **'الرحلة جارية'**
  String get jbStatusInProgress;

  /// No description provided for @jbStatusCompleted.
  ///
  /// In ar, this message translates to:
  /// **'اكتملت الرحلة'**
  String get jbStatusCompleted;

  /// No description provided for @jbStatusCancelledCustomer.
  ///
  /// In ar, this message translates to:
  /// **'ألغاها العميل'**
  String get jbStatusCancelledCustomer;

  /// No description provided for @jbStatusCancelledMe.
  ///
  /// In ar, this message translates to:
  /// **'ألغيتها أنت'**
  String get jbStatusCancelledMe;

  /// No description provided for @jbStatusCancelledOps.
  ///
  /// In ar, this message translates to:
  /// **'ألغتها الإدارة'**
  String get jbStatusCancelledOps;

  /// No description provided for @jbStatusDisputed.
  ///
  /// In ar, this message translates to:
  /// **'قيد النزاع'**
  String get jbStatusDisputed;

  /// No description provided for @jbStatusFailed.
  ///
  /// In ar, this message translates to:
  /// **'فشلت محاولة التسليم'**
  String get jbStatusFailed;

  /// No description provided for @jbStatusSearching.
  ///
  /// In ar, this message translates to:
  /// **'قيد البحث عن سائق'**
  String get jbStatusSearching;

  /// No description provided for @jbStatusOther.
  ///
  /// In ar, this message translates to:
  /// **'حالة أخرى'**
  String get jbStatusOther;

  /// No description provided for @jbCollectCash.
  ///
  /// In ar, this message translates to:
  /// **'حصّل المبلغ نقدًا من العميل'**
  String get jbCollectCash;

  /// No description provided for @jbPayCash.
  ///
  /// In ar, this message translates to:
  /// **'الدفع نقدًا'**
  String get jbPayCash;

  /// No description provided for @jbPayOther.
  ///
  /// In ar, this message translates to:
  /// **'الدفع إلكترونيًا'**
  String get jbPayOther;

  /// No description provided for @jbSupportFlagged.
  ///
  /// In ar, this message translates to:
  /// **'فريق الدعم يتابع هذه الرحلة.'**
  String get jbSupportFlagged;

  /// No description provided for @jbFreeWaitLeft.
  ///
  /// In ar, this message translates to:
  /// **'وقت الانتظار المجاني المتبقي: {time}'**
  String jbFreeWaitLeft(String time);

  /// No description provided for @jbFreeWaitOver.
  ///
  /// In ar, this message translates to:
  /// **'انتهى وقت الانتظار المجاني.'**
  String get jbFreeWaitOver;

  /// No description provided for @jbPinNeeded.
  ///
  /// In ar, this message translates to:
  /// **'اطلب من العميل رمز بدء الرحلة وأدخله.'**
  String get jbPinNeeded;

  /// No description provided for @jbPinVerified.
  ///
  /// In ar, this message translates to:
  /// **'تم التحقق من الرمز.'**
  String get jbPinVerified;

  /// No description provided for @jbRouteTitle.
  ///
  /// In ar, this message translates to:
  /// **'المسار'**
  String get jbRouteTitle;

  /// No description provided for @jbPickup.
  ///
  /// In ar, this message translates to:
  /// **'نقطة الالتقاء'**
  String get jbPickup;

  /// No description provided for @jbDropoff.
  ///
  /// In ar, this message translates to:
  /// **'الوجهة'**
  String get jbDropoff;

  /// No description provided for @jbStopLabel.
  ///
  /// In ar, this message translates to:
  /// **'محطة'**
  String get jbStopLabel;

  /// No description provided for @jbCustomerTitle.
  ///
  /// In ar, this message translates to:
  /// **'العميل'**
  String get jbCustomerTitle;

  /// No description provided for @jbCustomerUnknown.
  ///
  /// In ar, this message translates to:
  /// **'عميل'**
  String get jbCustomerUnknown;

  /// No description provided for @jbCustomerRating.
  ///
  /// In ar, this message translates to:
  /// **'التقييم {avg} ({count})'**
  String jbCustomerRating(String avg, int count);

  /// No description provided for @jbCallCustomer.
  ///
  /// In ar, this message translates to:
  /// **'اتصل بالعميل'**
  String get jbCallCustomer;

  /// No description provided for @jbCallFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر بدء الاتصال.'**
  String get jbCallFailed;

  /// No description provided for @jbContactMasked.
  ///
  /// In ar, this message translates to:
  /// **'رقم العميل مخفي. تواصل عبر الدعم عند الحاجة.'**
  String get jbContactMasked;

  /// No description provided for @jbInstructionsTitle.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل وتعليمات العميل'**
  String get jbInstructionsTitle;

  /// No description provided for @jbActionAcknowledge.
  ///
  /// In ar, this message translates to:
  /// **'أنا في الطريق إلى العميل'**
  String get jbActionAcknowledge;

  /// No description provided for @jbActionArrived.
  ///
  /// In ar, this message translates to:
  /// **'وصلت'**
  String get jbActionArrived;

  /// No description provided for @jbActionPin.
  ///
  /// In ar, this message translates to:
  /// **'إدخال رمز الرحلة'**
  String get jbActionPin;

  /// No description provided for @jbPinLockedButton.
  ///
  /// In ar, this message translates to:
  /// **'الرمز مقفل'**
  String get jbPinLockedButton;

  /// No description provided for @jbActionStart.
  ///
  /// In ar, this message translates to:
  /// **'بدء الرحلة'**
  String get jbActionStart;

  /// No description provided for @jbComplete.
  ///
  /// In ar, this message translates to:
  /// **'إنهاء الرحلة'**
  String get jbComplete;

  /// No description provided for @jbCompleteConfirmTitle.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد إنهاء الرحلة'**
  String get jbCompleteConfirmTitle;

  /// No description provided for @jbCompleteConfirmBody.
  ///
  /// In ar, this message translates to:
  /// **'تأكد أنك وصلت إلى الوجهة.'**
  String get jbCompleteConfirmBody;

  /// No description provided for @jbCompleteConfirmCash.
  ///
  /// In ar, this message translates to:
  /// **'حصّل {amount} نقدًا من العميل ثم أنهِ الرحلة.'**
  String jbCompleteConfirmCash(String amount);

  /// No description provided for @jbNavigatePickup.
  ///
  /// In ar, this message translates to:
  /// **'التنقل إلى العميل'**
  String get jbNavigatePickup;

  /// No description provided for @jbNavigateDropoff.
  ///
  /// In ar, this message translates to:
  /// **'التنقل إلى الوجهة'**
  String get jbNavigateDropoff;

  /// No description provided for @jbNavFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذّر فتح تطبيق الخرائط.'**
  String get jbNavFailed;

  /// No description provided for @jbCancelJob.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الرحلة'**
  String get jbCancelJob;

  /// No description provided for @jbReportIssue.
  ///
  /// In ar, this message translates to:
  /// **'الإبلاغ عن مشكلة'**
  String get jbReportIssue;

  /// No description provided for @jbPinTitle.
  ///
  /// In ar, this message translates to:
  /// **'رمز بدء الرحلة'**
  String get jbPinTitle;

  /// No description provided for @jbPinHint.
  ///
  /// In ar, this message translates to:
  /// **'اطلب الرمز من العميل وأدخله هنا.'**
  String get jbPinHint;

  /// No description provided for @jbPinConfirm.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد الرمز'**
  String get jbPinConfirm;

  /// No description provided for @jbPinAttemptsLeft.
  ///
  /// In ar, this message translates to:
  /// **'المحاولات المتبقية: {count}'**
  String jbPinAttemptsLeft(int count);

  /// No description provided for @jbSummaryDone.
  ///
  /// In ar, this message translates to:
  /// **'اكتملت الرحلة'**
  String get jbSummaryDone;

  /// No description provided for @jbSummaryEnded.
  ///
  /// In ar, this message translates to:
  /// **'انتهت الرحلة'**
  String get jbSummaryEnded;

  /// No description provided for @jbSummaryAmount.
  ///
  /// In ar, this message translates to:
  /// **'قيمة الرحلة'**
  String get jbSummaryAmount;

  /// No description provided for @jbSummaryCollectCash.
  ///
  /// In ar, this message translates to:
  /// **'حصّل {amount} نقدًا من العميل.'**
  String jbSummaryCollectCash(String amount);

  /// No description provided for @jbRateTitle.
  ///
  /// In ar, this message translates to:
  /// **'قيّم العميل'**
  String get jbRateTitle;

  /// No description provided for @jbStarsLabel.
  ///
  /// In ar, this message translates to:
  /// **'{count} نجوم'**
  String jbStarsLabel(int count);

  /// No description provided for @jbRateComment.
  ///
  /// In ar, this message translates to:
  /// **'تعليق (اختياري)'**
  String get jbRateComment;

  /// No description provided for @jbRateSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال التقييم'**
  String get jbRateSubmit;

  /// No description provided for @jbRatingThanks.
  ///
  /// In ar, this message translates to:
  /// **'شكرًا، تم إرسال تقييمك.'**
  String get jbRatingThanks;

  /// No description provided for @jbTagRespectful.
  ///
  /// In ar, this message translates to:
  /// **'محترم'**
  String get jbTagRespectful;

  /// No description provided for @jbTagClear.
  ///
  /// In ar, this message translates to:
  /// **'تعليمات واضحة'**
  String get jbTagClear;

  /// No description provided for @jbTagReady.
  ///
  /// In ar, this message translates to:
  /// **'جاهز عند الالتقاء'**
  String get jbTagReady;

  /// No description provided for @jbTagDisrespectful.
  ///
  /// In ar, this message translates to:
  /// **'غير محترم'**
  String get jbTagDisrespectful;

  /// No description provided for @jbTagNotReady.
  ///
  /// In ar, this message translates to:
  /// **'لم يكن جاهزًا'**
  String get jbTagNotReady;

  /// No description provided for @jbTagLate.
  ///
  /// In ar, this message translates to:
  /// **'متأخر'**
  String get jbTagLate;

  /// No description provided for @jbCancelTitle.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الرحلة'**
  String get jbCancelTitle;

  /// No description provided for @jbCancelPrompt.
  ///
  /// In ar, this message translates to:
  /// **'لماذا تريد الإلغاء؟'**
  String get jbCancelPrompt;

  /// No description provided for @jbCancelNoShow.
  ///
  /// In ar, this message translates to:
  /// **'العميل لم يحضر'**
  String get jbCancelNoShow;

  /// No description provided for @jbCancelUnreachable.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن الوصول للعميل'**
  String get jbCancelUnreachable;

  /// No description provided for @jbCancelVehicle.
  ///
  /// In ar, this message translates to:
  /// **'مشكلة في المركبة'**
  String get jbCancelVehicle;

  /// No description provided for @jbCancelEmergency.
  ///
  /// In ar, this message translates to:
  /// **'حالة طارئة'**
  String get jbCancelEmergency;

  /// No description provided for @jbCancelWrongDetails.
  ///
  /// In ar, this message translates to:
  /// **'تفاصيل الطلب غير صحيحة'**
  String get jbCancelWrongDetails;

  /// No description provided for @jbCancelUnsafe.
  ///
  /// In ar, this message translates to:
  /// **'مكان الالتقاء غير آمن'**
  String get jbCancelUnsafe;

  /// No description provided for @jbCancelOther.
  ///
  /// In ar, this message translates to:
  /// **'سبب آخر'**
  String get jbCancelOther;

  /// No description provided for @jbCancelNote.
  ///
  /// In ar, this message translates to:
  /// **'اكتب السبب'**
  String get jbCancelNote;

  /// No description provided for @jbCancelShowCost.
  ///
  /// In ar, this message translates to:
  /// **'عرض تكلفة الإلغاء'**
  String get jbCancelShowCost;

  /// No description provided for @jbCancelFree.
  ///
  /// In ar, this message translates to:
  /// **'الإلغاء الآن بدون رسوم.'**
  String get jbCancelFree;

  /// No description provided for @jbCancelFee.
  ///
  /// In ar, this message translates to:
  /// **'رسوم الإلغاء: {fee}'**
  String jbCancelFee(String fee);

  /// No description provided for @jbCancelRedispatchNote.
  ///
  /// In ar, this message translates to:
  /// **'سيُعاد الطلب إلى سائقين آخرين.'**
  String get jbCancelRedispatchNote;

  /// No description provided for @jbCancelConfirmAction.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد الإلغاء'**
  String get jbCancelConfirmAction;

  /// No description provided for @jbCancelConfirmTitle.
  ///
  /// In ar, this message translates to:
  /// **'هل تريد إلغاء الرحلة؟'**
  String get jbCancelConfirmTitle;

  /// No description provided for @jbCancelConfirmBody.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن التراجع عن الإلغاء.'**
  String get jbCancelConfirmBody;

  /// No description provided for @jbCancelConfirmRedispatch.
  ///
  /// In ar, this message translates to:
  /// **'سيُعاد الطلب إلى سائقين آخرين ولن تعود إليه.'**
  String get jbCancelConfirmRedispatch;

  /// No description provided for @jbCancelledDone.
  ///
  /// In ar, this message translates to:
  /// **'تم إلغاء الرحلة.'**
  String get jbCancelledDone;

  /// No description provided for @jbCancelledRedispatch.
  ///
  /// In ar, this message translates to:
  /// **'تم الإلغاء وأُعيد الطلب إلى سائقين آخرين.'**
  String get jbCancelledRedispatch;

  /// No description provided for @jbReportTitle.
  ///
  /// In ar, this message translates to:
  /// **'الإبلاغ عن مشكلة'**
  String get jbReportTitle;

  /// No description provided for @jbReportPrompt.
  ///
  /// In ar, this message translates to:
  /// **'ما نوع المشكلة؟'**
  String get jbReportPrompt;

  /// No description provided for @jbReportSafety.
  ///
  /// In ar, this message translates to:
  /// **'سلامة'**
  String get jbReportSafety;

  /// No description provided for @jbReportBehaviour.
  ///
  /// In ar, this message translates to:
  /// **'سلوك العميل'**
  String get jbReportBehaviour;

  /// No description provided for @jbReportFare.
  ///
  /// In ar, this message translates to:
  /// **'خلاف على السعر'**
  String get jbReportFare;

  /// No description provided for @jbReportRoute.
  ///
  /// In ar, this message translates to:
  /// **'المسار أو التأخير'**
  String get jbReportRoute;

  /// No description provided for @jbReportOther.
  ///
  /// In ar, this message translates to:
  /// **'أخرى'**
  String get jbReportOther;

  /// No description provided for @jbReportDescription.
  ///
  /// In ar, this message translates to:
  /// **'وصف المشكلة'**
  String get jbReportDescription;

  /// No description provided for @jbReportDescriptionHelp.
  ///
  /// In ar, this message translates to:
  /// **'10 أحرف على الأقل'**
  String get jbReportDescriptionHelp;

  /// No description provided for @jbReportSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال البلاغ'**
  String get jbReportSubmit;

  /// No description provided for @jbReportSentTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال البلاغ'**
  String get jbReportSentTitle;

  /// No description provided for @jbReportSentBody.
  ///
  /// In ar, this message translates to:
  /// **'سيراجعه فريق الدعم.'**
  String get jbReportSentBody;

  /// No description provided for @jbErrLost.
  ///
  /// In ar, this message translates to:
  /// **'لم يعد هذا الطلب متاحًا (انتهت مهلته أو أُسند لسائق آخر). حدّثنا القائمة.'**
  String get jbErrLost;

  /// No description provided for @jbErrNotEligible.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكنك إرسال عروض الآن. راجع أسباب عدم الأهلية في الرئيسية.'**
  String get jbErrNotEligible;

  /// No description provided for @jbErrVehicle.
  ///
  /// In ar, this message translates to:
  /// **'اختر مركبة معتمدة من الرئيسية أولًا.'**
  String get jbErrVehicle;

  /// No description provided for @jbErrCounterOff.
  ///
  /// In ar, this message translates to:
  /// **'هذا الطلب لا يقبل عروضًا مضادة.'**
  String get jbErrCounterOff;

  /// No description provided for @jbErrAmountInvalid.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ غير صالح.'**
  String get jbErrAmountInvalid;

  /// No description provided for @jbErrAmountBounds.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ خارج الحدود المسموحة.'**
  String get jbErrAmountBounds;

  /// No description provided for @jbErrCurrency.
  ///
  /// In ar, this message translates to:
  /// **'عملة المبلغ غير مطابقة.'**
  String get jbErrCurrency;

  /// No description provided for @jbErrSameAsFare.
  ///
  /// In ar, this message translates to:
  /// **'هذا هو سعر العميل نفسه. استخدم زر قبول السعر.'**
  String get jbErrSameAsFare;

  /// No description provided for @jbErrOfferLimit.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى الحد الأقصى للعروض المفتوحة.'**
  String get jbErrOfferLimit;

  /// No description provided for @jbErrRevisions.
  ///
  /// In ar, this message translates to:
  /// **'استنفدت عدد مرات تعديل هذا العرض.'**
  String get jbErrRevisions;

  /// No description provided for @jbErrNotWithdrawable.
  ///
  /// In ar, this message translates to:
  /// **'لم يعد بالإمكان سحب هذا العرض.'**
  String get jbErrNotWithdrawable;

  /// No description provided for @jbErrWithdrawFirst.
  ///
  /// In ar, this message translates to:
  /// **'اسحب عرضك أولًا قبل التخطي.'**
  String get jbErrWithdrawFirst;

  /// No description provided for @jbErrNotYours.
  ///
  /// In ar, this message translates to:
  /// **'هذا العرض ليس لك.'**
  String get jbErrNotYours;

  /// No description provided for @jbErrTransition.
  ///
  /// In ar, this message translates to:
  /// **'تغيّرت حالة الرحلة. تم تحديثها.'**
  String get jbErrTransition;

  /// No description provided for @jbErrPinRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رمز الرحلة أولًا.'**
  String get jbErrPinRequired;

  /// No description provided for @jbErrProofRequired.
  ///
  /// In ar, this message translates to:
  /// **'مطلوب إثبات قبل المتابعة.'**
  String get jbErrProofRequired;

  /// No description provided for @jbErrPinLocked.
  ///
  /// In ar, this message translates to:
  /// **'الرمز مقفل بعد محاولات خاطئة. تواصل مع الدعم لبدء الرحلة.'**
  String get jbErrPinLocked;

  /// No description provided for @jbErrPinInvalid.
  ///
  /// In ar, this message translates to:
  /// **'الرمز غير صحيح.'**
  String get jbErrPinInvalid;

  /// No description provided for @jbErrPinNotRequired.
  ///
  /// In ar, this message translates to:
  /// **'هذه الرحلة لا تحتاج إلى رمز.'**
  String get jbErrPinNotRequired;

  /// No description provided for @jbErrNotCancellable.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن إلغاء هذه الرحلة الآن.'**
  String get jbErrNotCancellable;

  /// No description provided for @jbErrReasonNotAllowed.
  ///
  /// In ar, this message translates to:
  /// **'سبب الإلغاء غير مسموح به.'**
  String get jbErrReasonNotAllowed;

  /// No description provided for @jbErrNoteRequired.
  ///
  /// In ar, this message translates to:
  /// **'اكتب سبب الإلغاء.'**
  String get jbErrNoteRequired;

  /// No description provided for @jbErrNoShowEarly.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن اختيار عدم الحضور قبل انتهاء وقت الانتظار المجاني.'**
  String get jbErrNoShowEarly;

  /// No description provided for @jbErrReportLimit.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن إرسال بلاغ آخر الآن.'**
  String get jbErrReportLimit;

  /// No description provided for @jbErrRatingClosed.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تقييم هذه الرحلة الآن.'**
  String get jbErrRatingClosed;

  /// No description provided for @jbErrRatingDone.
  ///
  /// In ar, this message translates to:
  /// **'قيّمت هذه الرحلة من قبل.'**
  String get jbErrRatingDone;

  /// No description provided for @jbErrRateLimited.
  ///
  /// In ar, this message translates to:
  /// **'محاولات كثيرة. انتظر قليلًا ثم أعد المحاولة.'**
  String get jbErrRateLimited;

  /// No description provided for @jbErrAccount.
  ///
  /// In ar, this message translates to:
  /// **'حسابك لا يسمح بهذا الإجراء حاليًا.'**
  String get jbErrAccount;

  /// No description provided for @jbErrInProgress.
  ///
  /// In ar, this message translates to:
  /// **'الطلب قيد التنفيذ. انتظر لحظة.'**
  String get jbErrInProgress;

  /// No description provided for @jbErrNotFound.
  ///
  /// In ar, this message translates to:
  /// **'لم نجد هذه الرحلة.'**
  String get jbErrNotFound;

  /// No description provided for @jbErrPinInvalidLeft.
  ///
  /// In ar, this message translates to:
  /// **'الرمز غير صحيح. المحاولات المتبقية: {count}'**
  String jbErrPinInvalidLeft(int count);

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

  /// No description provided for @ernTab.
  ///
  /// In ar, this message translates to:
  /// **'الأرباح'**
  String get ernTab;

  /// No description provided for @ernDay.
  ///
  /// In ar, this message translates to:
  /// **'يوم'**
  String get ernDay;

  /// No description provided for @ernWeek.
  ///
  /// In ar, this message translates to:
  /// **'أسبوع'**
  String get ernWeek;

  /// No description provided for @ernMonth.
  ///
  /// In ar, this message translates to:
  /// **'شهر'**
  String get ernMonth;

  /// No description provided for @ernEarned.
  ///
  /// In ar, this message translates to:
  /// **'ما ربحته'**
  String get ernEarned;

  /// No description provided for @ernJobs.
  ///
  /// In ar, this message translates to:
  /// **'{count} رحلة'**
  String ernJobs(int count);

  /// No description provided for @ernGross.
  ///
  /// In ar, this message translates to:
  /// **'الأجرة'**
  String get ernGross;

  /// No description provided for @ernCommission.
  ///
  /// In ar, this message translates to:
  /// **'عمولة المنصة'**
  String get ernCommission;

  /// No description provided for @ernProviderDiscount.
  ///
  /// In ar, this message translates to:
  /// **'خصم تحملته'**
  String get ernProviderDiscount;

  /// No description provided for @ernPlatformDiscount.
  ///
  /// In ar, this message translates to:
  /// **'خصم تحملته المنصة'**
  String get ernPlatformDiscount;

  /// No description provided for @ernTips.
  ///
  /// In ar, this message translates to:
  /// **'الإكراميات'**
  String get ernTips;

  /// No description provided for @ernNet.
  ///
  /// In ar, this message translates to:
  /// **'الصافي'**
  String get ernNet;

  /// No description provided for @ernCash.
  ///
  /// In ar, this message translates to:
  /// **'النقد المحصّل'**
  String get ernCash;

  /// No description provided for @ernDaily.
  ///
  /// In ar, this message translates to:
  /// **'الأرباح اليومية'**
  String get ernDaily;

  /// No description provided for @ernByCategory.
  ///
  /// In ar, this message translates to:
  /// **'حسب الخدمة'**
  String get ernByCategory;

  /// No description provided for @ernNoJobs.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد رحلات مكتملة في هذه الفترة.'**
  String get ernNoJobs;

  /// No description provided for @ernWalletBalance.
  ///
  /// In ar, this message translates to:
  /// **'رصيد المحفظة'**
  String get ernWalletBalance;

  /// No description provided for @ernWalletReserved.
  ///
  /// In ar, this message translates to:
  /// **'محجوز للسحب'**
  String get ernWalletReserved;

  /// No description provided for @ernWalletAvailable.
  ///
  /// In ar, this message translates to:
  /// **'المتاح للسحب'**
  String get ernWalletAvailable;

  /// No description provided for @ernBreakdownLink.
  ///
  /// In ar, this message translates to:
  /// **'الأرباح لكل رحلة'**
  String get ernBreakdownLink;

  /// No description provided for @ernOpenJob.
  ///
  /// In ar, this message translates to:
  /// **'فتح الرحلة'**
  String get ernOpenJob;

  /// No description provided for @walLedgerTitle.
  ///
  /// In ar, this message translates to:
  /// **'سجل المحفظة'**
  String get walLedgerTitle;

  /// No description provided for @walLedgerEmpty.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد حركات في المحفظة بعد.'**
  String get walLedgerEmpty;

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

  /// No description provided for @cashDebtTitle.
  ///
  /// In ar, this message translates to:
  /// **'عليك {amount} عمولة نقدية'**
  String cashDebtTitle(String amount);

  /// No description provided for @cashDebtWarning.
  ///
  /// In ar, this message translates to:
  /// **'حدّك {limit}. عند تجاوزه تتوقف عن استلام الطلبات حتى تسدد.'**
  String cashDebtWarning(String limit);

  /// No description provided for @cashDebtBlocked.
  ///
  /// In ar, this message translates to:
  /// **'تجاوزت الحد {limit}، لذلك لن تصلك طلبات جديدة حتى تسدد.'**
  String cashDebtBlocked(String limit);

  /// No description provided for @cashDebtHowTo.
  ///
  /// In ar, this message translates to:
  /// **'سدد العمولة النقدية مع فريق المالية.'**
  String get cashDebtHowTo;

  /// No description provided for @cashDebtContact.
  ///
  /// In ar, this message translates to:
  /// **'تواصل مع الدعم'**
  String get cashDebtContact;

  /// No description provided for @cashCollectTitle.
  ///
  /// In ar, this message translates to:
  /// **'أكّد استلام النقد'**
  String get cashCollectTitle;

  /// No description provided for @cashCollectBody.
  ///
  /// In ar, this message translates to:
  /// **'أكّد أنك استلمت {amount} نقداً من العميل.'**
  String cashCollectBody(String amount);

  /// No description provided for @cashCollectBodyNoAmount.
  ///
  /// In ar, this message translates to:
  /// **'أكّد أنك استلمت النقد من العميل.'**
  String get cashCollectBodyNoAmount;

  /// No description provided for @cashConfirm.
  ///
  /// In ar, this message translates to:
  /// **'استلمت النقد'**
  String get cashConfirm;

  /// No description provided for @cashConfirmedTitle.
  ///
  /// In ar, this message translates to:
  /// **'تم تأكيد النقد'**
  String get cashConfirmedTitle;

  /// No description provided for @cashConfirmedBody.
  ///
  /// In ar, this message translates to:
  /// **'شكراً لك. تمت تسوية الرحلة.'**
  String get cashConfirmedBody;

  /// No description provided for @cashErrNotCollectable.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تأكيد نقد هذه الرحلة الآن.'**
  String get cashErrNotCollectable;

  /// No description provided for @poTitle.
  ///
  /// In ar, this message translates to:
  /// **'السحوبات'**
  String get poTitle;

  /// No description provided for @poAvailable.
  ///
  /// In ar, this message translates to:
  /// **'المتاح للسحب'**
  String get poAvailable;

  /// No description provided for @poReservedNote.
  ///
  /// In ar, this message translates to:
  /// **'{amount} محجوز لسحوبات قيد التنفيذ'**
  String poReservedNote(String amount);

  /// No description provided for @poLimits.
  ///
  /// In ar, this message translates to:
  /// **'الحد الأدنى {min} والأقصى {max} لكل سحب'**
  String poLimits(String min, String max);

  /// No description provided for @poRequest.
  ///
  /// In ar, this message translates to:
  /// **'طلب سحب'**
  String get poRequest;

  /// No description provided for @poHistory.
  ///
  /// In ar, this message translates to:
  /// **'السجل'**
  String get poHistory;

  /// No description provided for @poEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد سحوبات بعد'**
  String get poEmptyTitle;

  /// No description provided for @poEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'ستظهر هنا طلبات السحب وحالتها.'**
  String get poEmptyBody;

  /// No description provided for @poAmountLabel.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ ({currency})'**
  String poAmountLabel(String currency);

  /// No description provided for @poAll.
  ///
  /// In ar, this message translates to:
  /// **'الكل'**
  String get poAll;

  /// No description provided for @poMethod.
  ///
  /// In ar, this message translates to:
  /// **'وسيلة السحب'**
  String get poMethod;

  /// No description provided for @poNoMethods.
  ///
  /// In ar, this message translates to:
  /// **'أضف وسيلة سحب أولاً.'**
  String get poNoMethods;

  /// No description provided for @poNoVerifiedMethods.
  ///
  /// In ar, this message translates to:
  /// **'وسائل السحب لديك بانتظار تحقق فريق المالية.'**
  String get poNoVerifiedMethods;

  /// No description provided for @poFeeNote.
  ///
  /// In ar, this message translates to:
  /// **'يُحجز المبلغ في محفظتك الآن. الرسوم والموافقة يحددها فريق المالية.'**
  String get poFeeNote;

  /// No description provided for @poSubmit.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الطلب'**
  String get poSubmit;

  /// No description provided for @poNoBalance.
  ///
  /// In ar, this message translates to:
  /// **'رصيدك غير متاح. حاول مجدداً.'**
  String get poNoBalance;

  /// No description provided for @poErrAmountInvalid.
  ///
  /// In ar, this message translates to:
  /// **'أدخل مبلغاً صحيحاً.'**
  String get poErrAmountInvalid;

  /// No description provided for @poErrBelowMinAmount.
  ///
  /// In ar, this message translates to:
  /// **'الحد الأدنى للسحب {min}.'**
  String poErrBelowMinAmount(String min);

  /// No description provided for @poErrAboveMaxAmount.
  ///
  /// In ar, this message translates to:
  /// **'الحد الأقصى للسحب {max}.'**
  String poErrAboveMaxAmount(String max);

  /// No description provided for @poErrChooseMethod.
  ///
  /// In ar, this message translates to:
  /// **'اختر وسيلة سحب.'**
  String get poErrChooseMethod;

  /// No description provided for @poErrBelowMin.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ أقل من الحد الأدنى للسحب.'**
  String get poErrBelowMin;

  /// No description provided for @poErrAboveMax.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ أكبر من الحد الأقصى للسحب.'**
  String get poErrAboveMax;

  /// No description provided for @poErrInsufficient.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ أكبر من رصيدك المتاح.'**
  String get poErrInsufficient;

  /// No description provided for @poErrNotVerified.
  ///
  /// In ar, this message translates to:
  /// **'وسيلة السحب هذه لم يتم التحقق منها بعد.'**
  String get poErrNotVerified;

  /// No description provided for @poErrNotApproved.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن لحسابك طلب سحب الآن.'**
  String get poErrNotApproved;

  /// No description provided for @poErrMethodInUse.
  ///
  /// In ar, this message translates to:
  /// **'هذه الوسيلة مستخدمة في سحب قيد التنفيذ.'**
  String get poErrMethodInUse;

  /// No description provided for @poErrNotCancellable.
  ///
  /// In ar, this message translates to:
  /// **'لم يعد بالإمكان إلغاء هذا السحب.'**
  String get poErrNotCancellable;

  /// No description provided for @poErrKeyReused.
  ///
  /// In ar, this message translates to:
  /// **'تغيّر شيء في هذا الطلب. ابدأه من جديد.'**
  String get poErrKeyReused;

  /// No description provided for @poDetailTitle.
  ///
  /// In ar, this message translates to:
  /// **'السحب'**
  String get poDetailTitle;

  /// No description provided for @poAmount.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ'**
  String get poAmount;

  /// No description provided for @poFee.
  ///
  /// In ar, this message translates to:
  /// **'الرسوم'**
  String get poFee;

  /// No description provided for @poNet.
  ///
  /// In ar, this message translates to:
  /// **'ما ستستلمه'**
  String get poNet;

  /// No description provided for @poRequestedAt.
  ///
  /// In ar, this message translates to:
  /// **'تاريخ الطلب'**
  String get poRequestedAt;

  /// No description provided for @poPaidAt.
  ///
  /// In ar, this message translates to:
  /// **'تاريخ الدفع'**
  String get poPaidAt;

  /// No description provided for @poReference.
  ///
  /// In ar, this message translates to:
  /// **'مرجع التحويل'**
  String get poReference;

  /// No description provided for @poReason.
  ///
  /// In ar, this message translates to:
  /// **'السبب'**
  String get poReason;

  /// No description provided for @poMoneyBack.
  ///
  /// In ar, this message translates to:
  /// **'عاد المبلغ إلى محفظتك.'**
  String get poMoneyBack;

  /// No description provided for @poCancel.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء الطلب'**
  String get poCancel;

  /// No description provided for @poCancelTitle.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء هذا السحب؟'**
  String get poCancelTitle;

  /// No description provided for @poCancelBody.
  ///
  /// In ar, this message translates to:
  /// **'يعود المبلغ إلى محفظتك.'**
  String get poCancelBody;

  /// No description provided for @poCancelConfirm.
  ///
  /// In ar, this message translates to:
  /// **'إلغاء السحب'**
  String get poCancelConfirm;

  /// No description provided for @poCancelled.
  ///
  /// In ar, this message translates to:
  /// **'تم إلغاء السحب.'**
  String get poCancelled;

  /// No description provided for @poAskSupport.
  ///
  /// In ar, this message translates to:
  /// **'اسأل الدعم عن هذا السحب'**
  String get poAskSupport;

  /// No description provided for @poStatusRequested.
  ///
  /// In ar, this message translates to:
  /// **'تم الطلب'**
  String get poStatusRequested;

  /// No description provided for @poStatusReview.
  ///
  /// In ar, this message translates to:
  /// **'قيد المراجعة'**
  String get poStatusReview;

  /// No description provided for @poStatusApproved.
  ///
  /// In ar, this message translates to:
  /// **'تمت الموافقة'**
  String get poStatusApproved;

  /// No description provided for @poStatusProcessing.
  ///
  /// In ar, this message translates to:
  /// **'قيد التحويل'**
  String get poStatusProcessing;

  /// No description provided for @poStatusPaid.
  ///
  /// In ar, this message translates to:
  /// **'تم الدفع'**
  String get poStatusPaid;

  /// No description provided for @poStatusFailed.
  ///
  /// In ar, this message translates to:
  /// **'فشل'**
  String get poStatusFailed;

  /// No description provided for @poStatusRejected.
  ///
  /// In ar, this message translates to:
  /// **'مرفوض'**
  String get poStatusRejected;

  /// No description provided for @poStatusCancelled.
  ///
  /// In ar, this message translates to:
  /// **'ملغي'**
  String get poStatusCancelled;

  /// No description provided for @poStatusReversed.
  ///
  /// In ar, this message translates to:
  /// **'مُرتجع'**
  String get poStatusReversed;

  /// No description provided for @poStatusUnknown.
  ///
  /// In ar, this message translates to:
  /// **'أخرى'**
  String get poStatusUnknown;

  /// No description provided for @poExplainReview.
  ///
  /// In ar, this message translates to:
  /// **'يراجع فريق المالية طلبك.'**
  String get poExplainReview;

  /// No description provided for @poExplainApproved.
  ///
  /// In ar, this message translates to:
  /// **'تمت الموافقة. سيبدأ التحويل قريباً.'**
  String get poExplainApproved;

  /// No description provided for @poExplainProcessing.
  ///
  /// In ar, this message translates to:
  /// **'التحويل في الطريق.'**
  String get poExplainProcessing;

  /// No description provided for @poExplainPaid.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال المال إلى وسيلة السحب.'**
  String get poExplainPaid;

  /// No description provided for @poExplainRejected.
  ///
  /// In ar, this message translates to:
  /// **'تم رفض الطلب.'**
  String get poExplainRejected;

  /// No description provided for @poExplainFailed.
  ///
  /// In ar, this message translates to:
  /// **'فشل التحويل.'**
  String get poExplainFailed;

  /// No description provided for @poExplainCancelled.
  ///
  /// In ar, this message translates to:
  /// **'ألغيت هذا الطلب.'**
  String get poExplainCancelled;

  /// No description provided for @poExplainReversed.
  ///
  /// In ar, this message translates to:
  /// **'أعاد البنك التحويل.'**
  String get poExplainReversed;

  /// No description provided for @pomTitle.
  ///
  /// In ar, this message translates to:
  /// **'وسائل السحب'**
  String get pomTitle;

  /// No description provided for @pomManage.
  ///
  /// In ar, this message translates to:
  /// **'إدارة وسائل السحب'**
  String get pomManage;

  /// No description provided for @pomAdd.
  ///
  /// In ar, this message translates to:
  /// **'إضافة وسيلة'**
  String get pomAdd;

  /// No description provided for @pomEdit.
  ///
  /// In ar, this message translates to:
  /// **'تعديل'**
  String get pomEdit;

  /// No description provided for @pomEditNote.
  ///
  /// In ar, this message translates to:
  /// **'حفاظاً على أمنك لا تُعرض التفاصيل المحفوظة. أدخلها مجدداً لتعديل الوسيلة، وستحتاج إلى تحقق جديد.'**
  String get pomEditNote;

  /// No description provided for @pomEmptyTitle.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد وسائل سحب'**
  String get pomEmptyTitle;

  /// No description provided for @pomEmptyBody.
  ///
  /// In ar, this message translates to:
  /// **'أضف حساباً بنكياً أو محفظة هاتف لاستلام السحوبات.'**
  String get pomEmptyBody;

  /// No description provided for @pomVerified.
  ///
  /// In ar, this message translates to:
  /// **'تم التحقق'**
  String get pomVerified;

  /// No description provided for @pomUnverified.
  ///
  /// In ar, this message translates to:
  /// **'غير موثّقة'**
  String get pomUnverified;

  /// No description provided for @pomUnverifiedNote.
  ///
  /// In ar, this message translates to:
  /// **'يتحقق فريق المالية من الوسيلة قبل استخدامها.'**
  String get pomUnverifiedNote;

  /// No description provided for @pomDefault.
  ///
  /// In ar, this message translates to:
  /// **'الوسيلة الافتراضية'**
  String get pomDefault;

  /// No description provided for @pomMakeDefault.
  ///
  /// In ar, this message translates to:
  /// **'اجعلها افتراضية'**
  String get pomMakeDefault;

  /// No description provided for @pomDefaultSet.
  ///
  /// In ar, this message translates to:
  /// **'تم تحديث الوسيلة الافتراضية.'**
  String get pomDefaultSet;

  /// No description provided for @pomDelete.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get pomDelete;

  /// No description provided for @pomDeleteTitle.
  ///
  /// In ar, this message translates to:
  /// **'حذف هذه الوسيلة؟'**
  String get pomDeleteTitle;

  /// No description provided for @pomDeleteBody.
  ///
  /// In ar, this message translates to:
  /// **'يمكنك إضافتها مجدداً لاحقاً.'**
  String get pomDeleteBody;

  /// No description provided for @pomDeleted.
  ///
  /// In ar, this message translates to:
  /// **'تم حذف الوسيلة.'**
  String get pomDeleted;

  /// No description provided for @pomBank.
  ///
  /// In ar, this message translates to:
  /// **'حساب بنكي'**
  String get pomBank;

  /// No description provided for @pomWallet.
  ///
  /// In ar, this message translates to:
  /// **'محفظة هاتف'**
  String get pomWallet;

  /// No description provided for @pomLabel.
  ///
  /// In ar, this message translates to:
  /// **'اسم لهذه الوسيلة'**
  String get pomLabel;

  /// No description provided for @pomHolder.
  ///
  /// In ar, this message translates to:
  /// **'اسم صاحب الحساب'**
  String get pomHolder;

  /// No description provided for @pomBankName.
  ///
  /// In ar, this message translates to:
  /// **'اسم البنك'**
  String get pomBankName;

  /// No description provided for @pomAccount.
  ///
  /// In ar, this message translates to:
  /// **'رقم الحساب أو الآيبان'**
  String get pomAccount;

  /// No description provided for @pomAccountInvalid.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم حساب صحيحاً.'**
  String get pomAccountInvalid;

  /// No description provided for @pomWalletNumber.
  ///
  /// In ar, this message translates to:
  /// **'رقم هاتف المحفظة'**
  String get pomWalletNumber;

  /// No description provided for @pomWalletInvalid.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رقم هاتف صحيحاً.'**
  String get pomWalletInvalid;

  /// No description provided for @pomVerifyNote.
  ///
  /// In ar, this message translates to:
  /// **'تُخزَّن التفاصيل مشفّرة ويتحقق منها فريق المالية.'**
  String get pomVerifyNote;

  /// No description provided for @pomSave.
  ///
  /// In ar, this message translates to:
  /// **'حفظ'**
  String get pomSave;

  /// No description provided for @pomSaved.
  ///
  /// In ar, this message translates to:
  /// **'تم حفظ وسيلة السحب.'**
  String get pomSaved;

  /// No description provided for @fieldRequired.
  ///
  /// In ar, this message translates to:
  /// **'هذا الحقل مطلوب.'**
  String get fieldRequired;

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

  /// No description provided for @rcpCustomer.
  ///
  /// In ar, this message translates to:
  /// **'العميل'**
  String get rcpCustomer;

  /// No description provided for @jbReceiptCode.
  ///
  /// In ar, this message translates to:
  /// **'رمز الحجز'**
  String get jbReceiptCode;

  /// No description provided for @rcpAgreed.
  ///
  /// In ar, this message translates to:
  /// **'الأجرة المتفق عليها'**
  String get rcpAgreed;

  /// No description provided for @rcpAdjustment.
  ///
  /// In ar, this message translates to:
  /// **'إضافة: {label}'**
  String rcpAdjustment(String label);

  /// No description provided for @rcpSubtotal.
  ///
  /// In ar, this message translates to:
  /// **'إجمالي الأجرة'**
  String get rcpSubtotal;

  /// No description provided for @rcpTax.
  ///
  /// In ar, this message translates to:
  /// **'الضريبة'**
  String get rcpTax;

  /// No description provided for @rcpOpenPrintable.
  ///
  /// In ar, this message translates to:
  /// **'فتح الإيصال للطباعة'**
  String get rcpOpenPrintable;

  /// No description provided for @rcpErrNotAvailable.
  ///
  /// In ar, this message translates to:
  /// **'يتوفر الإيصال بعد اكتمال الرحلة.'**
  String get rcpErrNotAvailable;

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

  /// No description provided for @dadjTitle.
  ///
  /// In ar, this message translates to:
  /// **'تغييرات الأجرة'**
  String get dadjTitle;

  /// No description provided for @dadjRequest.
  ///
  /// In ar, this message translates to:
  /// **'طلب تغيير الأجرة'**
  String get dadjRequest;

  /// No description provided for @dadjRequestedByYou.
  ///
  /// In ar, this message translates to:
  /// **'طلبته أنت'**
  String get dadjRequestedByYou;

  /// No description provided for @dadjRequestedByCustomer.
  ///
  /// In ar, this message translates to:
  /// **'طلبه العميل'**
  String get dadjRequestedByCustomer;

  /// No description provided for @dadjTypeExtraStop.
  ///
  /// In ar, this message translates to:
  /// **'توقف إضافي'**
  String get dadjTypeExtraStop;

  /// No description provided for @dadjTypeWaiting.
  ///
  /// In ar, this message translates to:
  /// **'وقت انتظار'**
  String get dadjTypeWaiting;

  /// No description provided for @dadjTypeToll.
  ///
  /// In ar, this message translates to:
  /// **'رسوم طريق أو انتظار'**
  String get dadjTypeToll;

  /// No description provided for @dadjTypeScope.
  ///
  /// In ar, this message translates to:
  /// **'تغيير في نطاق الخدمة'**
  String get dadjTypeScope;

  /// No description provided for @dadjAmount.
  ///
  /// In ar, this message translates to:
  /// **'المبلغ'**
  String get dadjAmount;

  /// No description provided for @dadjWaitingHelp.
  ///
  /// In ar, this message translates to:
  /// **'اتركه فارغاً لاستخدام سعر الانتظار.'**
  String get dadjWaitingHelp;

  /// No description provided for @dadjReason.
  ///
  /// In ar, this message translates to:
  /// **'السبب'**
  String get dadjReason;

  /// No description provided for @dadjCustomerApproves.
  ///
  /// In ar, this message translates to:
  /// **'لا تتغير الأجرة إلا إذا وافق العميل.'**
  String get dadjCustomerApproves;

  /// No description provided for @dadjSend.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الطلب'**
  String get dadjSend;

  /// No description provided for @dadjSent.
  ///
  /// In ar, this message translates to:
  /// **'تم إرسال الطلب إلى العميل.'**
  String get dadjSent;

  /// No description provided for @dadjErrNotNow.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن طلب تغيير الأجرة الآن.'**
  String get dadjErrNotNow;

  /// No description provided for @dadjErrOpenLimit.
  ///
  /// In ar, this message translates to:
  /// **'لديك الحد الأقصى من الطلبات المفتوحة.'**
  String get dadjErrOpenLimit;

  /// No description provided for @dadjErrAmount.
  ///
  /// In ar, this message translates to:
  /// **'أدخل مبلغاً صحيحاً.'**
  String get dadjErrAmount;

  /// No description provided for @dadjErrReason.
  ///
  /// In ar, this message translates to:
  /// **'اكتب السبب.'**
  String get dadjErrReason;

  /// No description provided for @dadjErrEvidence.
  ///
  /// In ar, this message translates to:
  /// **'هذا النوع يحتاج إثباتاً. أضف التفاصيل في السبب.'**
  String get dadjErrEvidence;

  /// No description provided for @dadjErrType.
  ///
  /// In ar, this message translates to:
  /// **'هذا النوع من التغيير غير مسموح لهذه الخدمة.'**
  String get dadjErrType;

  /// No description provided for @dadjErrNotPending.
  ///
  /// In ar, this message translates to:
  /// **'تمت الإجابة عن هذا الطلب أو انتهت مدته.'**
  String get dadjErrNotPending;

  /// No description provided for @dadjErrCounterpart.
  ///
  /// In ar, this message translates to:
  /// **'يمكن للطرف الآخر فقط الإجابة عن هذا الطلب.'**
  String get dadjErrCounterpart;

  /// No description provided for @scPackingNone.
  ///
  /// In ar, this message translates to:
  /// **'بدون تغليف'**
  String get scPackingNone;

  /// No description provided for @scPackingWrap.
  ///
  /// In ar, this message translates to:
  /// **'تغطية فقط'**
  String get scPackingWrap;

  /// No description provided for @scPackingFull.
  ///
  /// In ar, this message translates to:
  /// **'تغليف كامل'**
  String get scPackingFull;

  /// No description provided for @scStairsNotNeeded.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد سلالم'**
  String get scStairsNotNeeded;

  /// No description provided for @scStairsIncluded.
  ///
  /// In ar, this message translates to:
  /// **'السلالم مشمولة'**
  String get scStairsIncluded;

  /// No description provided for @scStairsExtra.
  ///
  /// In ar, this message translates to:
  /// **'سلالم بسعر إضافي للدور'**
  String get scStairsExtra;

  /// No description provided for @scAssemblyNone.
  ///
  /// In ar, this message translates to:
  /// **'بدون فك وتركيب'**
  String get scAssemblyNone;

  /// No description provided for @scAssemblyDisassembly.
  ///
  /// In ar, this message translates to:
  /// **'فك فقط'**
  String get scAssemblyDisassembly;

  /// No description provided for @scAssemblyAssembly.
  ///
  /// In ar, this message translates to:
  /// **'تركيب فقط'**
  String get scAssemblyAssembly;

  /// No description provided for @scAssemblyBoth.
  ///
  /// In ar, this message translates to:
  /// **'فك وتركيب'**
  String get scAssemblyBoth;

  /// No description provided for @scEqHandTrolley.
  ///
  /// In ar, this message translates to:
  /// **'عربة يد'**
  String get scEqHandTrolley;

  /// No description provided for @scEqForklift.
  ///
  /// In ar, this message translates to:
  /// **'رافعة شوكية'**
  String get scEqForklift;

  /// No description provided for @scEqCrane.
  ///
  /// In ar, this message translates to:
  /// **'ونش'**
  String get scEqCrane;

  /// No description provided for @scEqTailLift.
  ///
  /// In ar, this message translates to:
  /// **'رافعة خلفية'**
  String get scEqTailLift;

  /// No description provided for @scEqStraps.
  ///
  /// In ar, this message translates to:
  /// **'أحزمة وأغطية'**
  String get scEqStraps;

  /// No description provided for @scEqPalletJack.
  ///
  /// In ar, this message translates to:
  /// **'عربة بالتات'**
  String get scEqPalletJack;

  /// No description provided for @scTreatIncluded.
  ///
  /// In ar, this message translates to:
  /// **'مشمولة في السعر'**
  String get scTreatIncluded;

  /// No description provided for @scTreatAtCost.
  ///
  /// In ar, this message translates to:
  /// **'بالتكلفة مع إيصال'**
  String get scTreatAtCost;

  /// No description provided for @scTreatExcluded.
  ///
  /// In ar, this message translates to:
  /// **'غير مشمولة'**
  String get scTreatExcluded;

  /// No description provided for @scIssueRequired.
  ///
  /// In ar, this message translates to:
  /// **'هذا الحقل مطلوب.'**
  String get scIssueRequired;

  /// No description provided for @scIssueRange.
  ///
  /// In ar, this message translates to:
  /// **'القيمة خارج الحد المسموح.'**
  String get scIssueRange;

  /// No description provided for @scIssueChoice.
  ///
  /// In ar, this message translates to:
  /// **'اختر قيمة من القائمة.'**
  String get scIssueChoice;

  /// No description provided for @scIssueFloors.
  ///
  /// In ar, this message translates to:
  /// **'حدد عدد الأدوار المشمولة.'**
  String get scIssueFloors;

  /// No description provided for @scIssueTooLong.
  ///
  /// In ar, this message translates to:
  /// **'النص أطول من المسموح.'**
  String get scIssueTooLong;

  /// No description provided for @scIssueDuplicate.
  ///
  /// In ar, this message translates to:
  /// **'تم اختيار العنصر أكثر من مرة.'**
  String get scIssueDuplicate;

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

  /// No description provided for @scIncludedFloors.
  ///
  /// In ar, this message translates to:
  /// **'عدد الأدوار المشمولة'**
  String get scIncludedFloors;

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

  /// No description provided for @scMinutesShort.
  ///
  /// In ar, this message translates to:
  /// **'د'**
  String get scMinutesShort;

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

  /// No description provided for @scExclusions.
  ///
  /// In ar, this message translates to:
  /// **'ما لا يشمله العرض'**
  String get scExclusions;

  /// No description provided for @scExclusionsHelp.
  ///
  /// In ar, this message translates to:
  /// **'اختياري، يظهر للعميل بجانب العرض.'**
  String get scExclusionsHelp;

  /// No description provided for @scNoVehicle.
  ///
  /// In ar, this message translates to:
  /// **'اختر مركبتك الحالية وكن متصلاً قبل إرسال عرض نقل.'**
  String get scNoVehicle;

  /// No description provided for @scTitle.
  ///
  /// In ar, this message translates to:
  /// **'ماذا يشمل عرضك؟'**
  String get scTitle;

  /// No description provided for @scIntro.
  ///
  /// In ar, this message translates to:
  /// **'هذه الشروط تظهر للعميل وتصبح اتفاقاً ملزماً عند اختيار عرضك.'**
  String get scIntro;

  /// No description provided for @scRequestedTitle.
  ///
  /// In ar, this message translates to:
  /// **'ما طلبه العميل'**
  String get scRequestedTitle;

  /// No description provided for @scContinue.
  ///
  /// In ar, this message translates to:
  /// **'متابعة إلى السعر'**
  String get scContinue;

  /// No description provided for @scEditScope.
  ///
  /// In ar, this message translates to:
  /// **'تعديل ما يشمله العرض'**
  String get scEditScope;

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

  /// No description provided for @jbErrScopeRequired.
  ///
  /// In ar, this message translates to:
  /// **'أضف ما يشمله عرضك قبل الإرسال.'**
  String get jbErrScopeRequired;

  /// No description provided for @jbErrScopeInvalid.
  ///
  /// In ar, this message translates to:
  /// **'بعض شروط العرض غير صحيحة. راجع الحقول المعلّمة.'**
  String get jbErrScopeInvalid;

  /// No description provided for @jbErrScopeVehicle.
  ///
  /// In ar, this message translates to:
  /// **'فئة المركبة في العرض لا تطابق مركبتك الحالية.'**
  String get jbErrScopeVehicle;

  /// No description provided for @jbErrCapacityBoth.
  ///
  /// In ar, this message translates to:
  /// **'مركبتك لا تتسع لوزن وحجم هذه الحمولة.'**
  String get jbErrCapacityBoth;

  /// No description provided for @jbErrCapacityVolume.
  ///
  /// In ar, this message translates to:
  /// **'حجم الحمولة أكبر من حجم مركبتك.'**
  String get jbErrCapacityVolume;

  /// No description provided for @jbErrCapacityPayload.
  ///
  /// In ar, this message translates to:
  /// **'وزن الحمولة أكبر من حمولة مركبتك.'**
  String get jbErrCapacityPayload;

  /// No description provided for @jbErrCapacity.
  ///
  /// In ar, this message translates to:
  /// **'مركبتك لا تناسب هذه الحمولة.'**
  String get jbErrCapacity;

  /// No description provided for @dlErrProofNow.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تسجيل هذا الإثبات في هذه المرحلة.'**
  String get dlErrProofNow;

  /// No description provided for @dlErrProofDone.
  ///
  /// In ar, this message translates to:
  /// **'هذا الإثبات مسجل بالفعل.'**
  String get dlErrProofDone;

  /// No description provided for @dlErrProofLimit.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى الحد الأقصى من الإثباتات لهذه المرحلة.'**
  String get dlErrProofLimit;

  /// No description provided for @dlErrProofType.
  ///
  /// In ar, this message translates to:
  /// **'هذا النوع من الإثبات غير مطلوب لهذه الخدمة.'**
  String get dlErrProofType;

  /// No description provided for @dlErrChecklist.
  ///
  /// In ar, this message translates to:
  /// **'قائمة الفحص غير صحيحة.'**
  String get dlErrChecklist;

  /// No description provided for @dlErrWeight.
  ///
  /// In ar, this message translates to:
  /// **'أدخل وزناً صحيحاً أكبر من الصفر.'**
  String get dlErrWeight;

  /// No description provided for @dlErrInfected.
  ///
  /// In ar, this message translates to:
  /// **'رفض الملف لأسباب أمنية. التقط صورة أخرى.'**
  String get dlErrInfected;

  /// No description provided for @dlErrScanner.
  ///
  /// In ar, this message translates to:
  /// **'فحص الملفات غير متاح الآن. سنعيد المحاولة تلقائياً.'**
  String get dlErrScanner;

  /// No description provided for @dlErrCustody.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تسجيل هذه الخطوة الآن.'**
  String get dlErrCustody;

  /// No description provided for @dlErrOtpInvalid.
  ///
  /// In ar, this message translates to:
  /// **'الرمز غير صحيح.'**
  String get dlErrOtpInvalid;

  /// No description provided for @dlErrOtpInvalidLeft.
  ///
  /// In ar, this message translates to:
  /// **'الرمز غير صحيح. المحاولات المتبقية: {left}'**
  String dlErrOtpInvalidLeft(int left);

  /// No description provided for @dlErrOtpLocked.
  ///
  /// In ar, this message translates to:
  /// **'تم إيقاف الرمز بعد محاولات خاطئة. تواصل مع الدعم.'**
  String get dlErrOtpLocked;

  /// No description provided for @dlErrOtpNotReady.
  ///
  /// In ar, this message translates to:
  /// **'رمز المستلم غير جاهز بعد.'**
  String get dlErrOtpNotReady;

  /// No description provided for @dlErrOtpResendSoon.
  ///
  /// In ar, this message translates to:
  /// **'انتظر قليلاً قبل إعادة إرسال الرمز.'**
  String get dlErrOtpResendSoon;

  /// No description provided for @dlErrOtpResendLimit.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن إعادة إرسال الرمز أكثر من ذلك.'**
  String get dlErrOtpResendLimit;

  /// No description provided for @dlErrAttemptNotAllowed.
  ///
  /// In ar, this message translates to:
  /// **'لا يمكن تنفيذ هذا الإجراء على محاولة التسليم الآن.'**
  String get dlErrAttemptNotAllowed;

  /// No description provided for @dlErrAttemptsExhausted.
  ///
  /// In ar, this message translates to:
  /// **'استُنفدت محاولات التسليم. اختر الإرجاع أو العمليات.'**
  String get dlErrAttemptsExhausted;

  /// No description provided for @dlErrOtpRequired.
  ///
  /// In ar, this message translates to:
  /// **'أدخل رمز المستلم قبل إنهاء التسليم.'**
  String get dlErrOtpRequired;

  /// No description provided for @dadjErrScopeItem.
  ///
  /// In ar, this message translates to:
  /// **'اختر البند المعني من اتفاق النقل.'**
  String get dadjErrScopeItem;

  /// No description provided for @dadjErrWithinScope.
  ///
  /// In ar, this message translates to:
  /// **'هذا البند مشمول في الاتفاق ولا يمكن تحميل مبلغ إضافي عنه.'**
  String get dadjErrWithinScope;

  /// No description provided for @dadjErrFloors.
  ///
  /// In ar, this message translates to:
  /// **'أدخل عدد الأدوار الإضافية.'**
  String get dadjErrFloors;

  /// No description provided for @dadjErrHelpers.
  ///
  /// In ar, this message translates to:
  /// **'عدد العمال الإضافيين غير مسموح.'**
  String get dadjErrHelpers;

  /// No description provided for @jbVolumeM3.
  ///
  /// In ar, this message translates to:
  /// **'{volume} م³'**
  String jbVolumeM3(String volume);

  /// No description provided for @jbPickupWindow.
  ///
  /// In ar, this message translates to:
  /// **'التحميل من {start} إلى {end}'**
  String jbPickupWindow(String start, String end);

  /// No description provided for @jbRequestedScope.
  ///
  /// In ar, this message translates to:
  /// **'ما يطلبه العميل'**
  String get jbRequestedScope;

  /// No description provided for @jbAcceptScopeNote.
  ///
  /// In ar, this message translates to:
  /// **'بقبول سعر العميل تلتزم بما طلبه العميل كما هو موضح.'**
  String get jbAcceptScopeNote;

  /// No description provided for @dlCustodyTitle.
  ///
  /// In ar, this message translates to:
  /// **'مسار الشحنة'**
  String get dlCustodyTitle;

  /// No description provided for @dlCustodyPickedUp.
  ///
  /// In ar, this message translates to:
  /// **'تم الاستلام من المرسل'**
  String get dlCustodyPickedUp;

  /// No description provided for @dlCustodyInTransit.
  ///
  /// In ar, this message translates to:
  /// **'في الطريق'**
  String get dlCustodyInTransit;

  /// No description provided for @dlCustodyArrived.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى نقطة التسليم'**
  String get dlCustodyArrived;

  /// No description provided for @dlCustodyFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر التسليم'**
  String get dlCustodyFailed;

  /// No description provided for @dlCustodyReturning.
  ///
  /// In ar, this message translates to:
  /// **'في طريق العودة للمرسل'**
  String get dlCustodyReturning;

  /// No description provided for @dlCustodyDelivered.
  ///
  /// In ar, this message translates to:
  /// **'تم التسليم'**
  String get dlCustodyDelivered;

  /// No description provided for @dlCustodyReturned.
  ///
  /// In ar, this message translates to:
  /// **'تم الإرجاع للمرسل'**
  String get dlCustodyReturned;

  /// No description provided for @dlCustodyUnknown.
  ///
  /// In ar, this message translates to:
  /// **'حالة غير معروفة'**
  String get dlCustodyUnknown;

  /// No description provided for @dlOtpTitle.
  ///
  /// In ar, this message translates to:
  /// **'رمز المستلم'**
  String get dlOtpTitle;

  /// No description provided for @dlOtpWaiting.
  ///
  /// In ar, this message translates to:
  /// **'لم يُرسل الرمز بعد'**
  String get dlOtpWaiting;

  /// No description provided for @dlOtpSent.
  ///
  /// In ar, this message translates to:
  /// **'أُرسل الرمز للمستلم'**
  String get dlOtpSent;

  /// No description provided for @dlOtpSmsFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر إرسال الرسالة'**
  String get dlOtpSmsFailed;

  /// No description provided for @dlOtpVerified.
  ///
  /// In ar, this message translates to:
  /// **'تم التحقق'**
  String get dlOtpVerified;

  /// No description provided for @dlOtpLocked.
  ///
  /// In ar, this message translates to:
  /// **'الرمز موقوف'**
  String get dlOtpLocked;

  /// No description provided for @dlOtpAttemptsLeft.
  ///
  /// In ar, this message translates to:
  /// **'المحاولات المتبقية: {left}'**
  String dlOtpAttemptsLeft(int left);

  /// No description provided for @dlOtpAskRecipient.
  ///
  /// In ar, this message translates to:
  /// **'اطلب من المستلم الرمز الذي وصله برسالة نصية.'**
  String get dlOtpAskRecipient;

  /// No description provided for @dlOtpEnter.
  ///
  /// In ar, this message translates to:
  /// **'إدخال رمز المستلم'**
  String get dlOtpEnter;

  /// No description provided for @dlOtpLockedButton.
  ///
  /// In ar, this message translates to:
  /// **'الرمز موقوف'**
  String get dlOtpLockedButton;

  /// No description provided for @dlOtpSheetTitle.
  ///
  /// In ar, this message translates to:
  /// **'رمز التسليم'**
  String get dlOtpSheetTitle;

  /// No description provided for @dlOtpField.
  ///
  /// In ar, this message translates to:
  /// **'الرمز المكون من أرقام'**
  String get dlOtpField;

  /// No description provided for @dlOtpVerify.
  ///
  /// In ar, this message translates to:
  /// **'تأكيد الرمز'**
  String get dlOtpVerify;

  /// No description provided for @dlOtpResend.
  ///
  /// In ar, this message translates to:
  /// **'إعادة إرسال الرمز'**
  String get dlOtpResend;

  /// No description provided for @dlOtpResendIn.
  ///
  /// In ar, this message translates to:
  /// **'إعادة الإرسال بعد {time}'**
  String dlOtpResendIn(String time);

  /// No description provided for @dlProofsTitle.
  ///
  /// In ar, this message translates to:
  /// **'إثباتات الاستلام والتسليم'**
  String get dlProofsTitle;

  /// No description provided for @dlNoProofs.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد إثباتات بعد.'**
  String get dlNoProofs;

  /// No description provided for @dlProofLoadError.
  ///
  /// In ar, this message translates to:
  /// **'تعذر تحميل الصورة. اضغط للمحاولة.'**
  String get dlProofLoadError;

  /// No description provided for @dlProofPhoto.
  ///
  /// In ar, this message translates to:
  /// **'صورة'**
  String get dlProofPhoto;

  /// No description provided for @dlProofSignature.
  ///
  /// In ar, this message translates to:
  /// **'توقيع'**
  String get dlProofSignature;

  /// No description provided for @dlProofOtp.
  ///
  /// In ar, this message translates to:
  /// **'رمز المستلم'**
  String get dlProofOtp;

  /// No description provided for @dlProofPin.
  ///
  /// In ar, this message translates to:
  /// **'رمز البدء'**
  String get dlProofPin;

  /// No description provided for @dlProofChecklist.
  ///
  /// In ar, this message translates to:
  /// **'قائمة فحص'**
  String get dlProofChecklist;

  /// No description provided for @dlProofWeight.
  ///
  /// In ar, this message translates to:
  /// **'الوزن'**
  String get dlProofWeight;

  /// No description provided for @dlProofOther.
  ///
  /// In ar, this message translates to:
  /// **'إثبات'**
  String get dlProofOther;

  /// No description provided for @dlStagePickup.
  ///
  /// In ar, this message translates to:
  /// **'عند الاستلام'**
  String get dlStagePickup;

  /// No description provided for @dlStageDelivery.
  ///
  /// In ar, this message translates to:
  /// **'عند التسليم'**
  String get dlStageDelivery;

  /// No description provided for @dlStageReturn.
  ///
  /// In ar, this message translates to:
  /// **'عند الإرجاع'**
  String get dlStageReturn;

  /// No description provided for @dlChkSealed.
  ///
  /// In ar, this message translates to:
  /// **'الطرد مغلق'**
  String get dlChkSealed;

  /// No description provided for @dlChkUndamaged.
  ///
  /// In ar, this message translates to:
  /// **'الطرد سليم بلا أضرار'**
  String get dlChkUndamaged;

  /// No description provided for @dlChkLabel.
  ///
  /// In ar, this message translates to:
  /// **'البيانات مطابقة للملصق'**
  String get dlChkLabel;

  /// No description provided for @dlChkRecipient.
  ///
  /// In ar, this message translates to:
  /// **'تم التأكد من هوية المستلم'**
  String get dlChkRecipient;

  /// No description provided for @dlWeightValue.
  ///
  /// In ar, this message translates to:
  /// **'الوزن المقاس: {kg} كجم'**
  String dlWeightValue(String kg);

  /// No description provided for @dlAttemptsTitle.
  ///
  /// In ar, this message translates to:
  /// **'محاولات التسليم'**
  String get dlAttemptsTitle;

  /// No description provided for @dlAttemptNumber.
  ///
  /// In ar, this message translates to:
  /// **'المحاولة {number}'**
  String dlAttemptNumber(int number);

  /// No description provided for @dlAttemptEvidence.
  ///
  /// In ar, this message translates to:
  /// **'مرفق صورة'**
  String get dlAttemptEvidence;

  /// No description provided for @dlPending.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار القرار'**
  String get dlPending;

  /// No description provided for @dlOutUnavailable.
  ///
  /// In ar, this message translates to:
  /// **'المستلم غير متاح'**
  String get dlOutUnavailable;

  /// No description provided for @dlOutRefused.
  ///
  /// In ar, this message translates to:
  /// **'المستلم رفض الاستلام'**
  String get dlOutRefused;

  /// No description provided for @dlOutWrongAddress.
  ///
  /// In ar, this message translates to:
  /// **'العنوان خاطئ'**
  String get dlOutWrongAddress;

  /// No description provided for @dlOutOtpFailed.
  ///
  /// In ar, this message translates to:
  /// **'تعذر التحقق من الرمز'**
  String get dlOutOtpFailed;

  /// No description provided for @dlOutRestricted.
  ///
  /// In ar, this message translates to:
  /// **'الشحنة تحتوي على مواد ممنوعة'**
  String get dlOutRestricted;

  /// No description provided for @dlOutOther.
  ///
  /// In ar, this message translates to:
  /// **'سبب آخر'**
  String get dlOutOther;

  /// No description provided for @dlResRetry.
  ///
  /// In ar, this message translates to:
  /// **'إعادة المحاولة'**
  String get dlResRetry;

  /// No description provided for @dlResReturn.
  ///
  /// In ar, this message translates to:
  /// **'إرجاع للمرسل'**
  String get dlResReturn;

  /// No description provided for @dlResOperations.
  ///
  /// In ar, this message translates to:
  /// **'تحويل للعمليات'**
  String get dlResOperations;

  /// No description provided for @dlResUnknown.
  ///
  /// In ar, this message translates to:
  /// **'قرار غير معروف'**
  String get dlResUnknown;

  /// No description provided for @dlCaptureTitle.
  ///
  /// In ar, this message translates to:
  /// **'تسجيل: {type}'**
  String dlCaptureTitle(String type);

  /// No description provided for @dlTakePhoto.
  ///
  /// In ar, this message translates to:
  /// **'التقاط صورة'**
  String get dlTakePhoto;

  /// No description provided for @dlRetakePhoto.
  ///
  /// In ar, this message translates to:
  /// **'إعادة الالتقاط'**
  String get dlRetakePhoto;

  /// No description provided for @dlProofNote.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظة (اختيارية)'**
  String get dlProofNote;

  /// No description provided for @dlSignHint.
  ///
  /// In ar, this message translates to:
  /// **'اطلب من المستلم التوقيع داخل الإطار.'**
  String get dlSignHint;

  /// No description provided for @dlSignClear.
  ///
  /// In ar, this message translates to:
  /// **'مسح التوقيع'**
  String get dlSignClear;

  /// No description provided for @dlWeightLabel.
  ///
  /// In ar, this message translates to:
  /// **'الوزن المقاس'**
  String get dlWeightLabel;

  /// No description provided for @dlKg.
  ///
  /// In ar, this message translates to:
  /// **'كجم'**
  String get dlKg;

  /// No description provided for @dlCm.
  ///
  /// In ar, this message translates to:
  /// **'سم'**
  String get dlCm;

  /// No description provided for @dlSaveProof.
  ///
  /// In ar, this message translates to:
  /// **'حفظ وإرسال'**
  String get dlSaveProof;

  /// No description provided for @dlQueueHint.
  ///
  /// In ar, this message translates to:
  /// **'يُحفظ الإثبات على هاتفك ويُرسل تلقائياً عند توفر الاتصال.'**
  String get dlQueueHint;

  /// No description provided for @dlProofQueued.
  ///
  /// In ar, this message translates to:
  /// **'تم حفظ الإثبات وسيُرسل الآن.'**
  String get dlProofQueued;

  /// No description provided for @dlErrCamera.
  ///
  /// In ar, this message translates to:
  /// **'تعذر فتح الكاميرا.'**
  String get dlErrCamera;

  /// No description provided for @dlErrPhotoFirst.
  ///
  /// In ar, this message translates to:
  /// **'التقط صورة أولاً.'**
  String get dlErrPhotoFirst;

  /// No description provided for @dlErrSignFirst.
  ///
  /// In ar, this message translates to:
  /// **'ارسم التوقيع أولاً.'**
  String get dlErrSignFirst;

  /// No description provided for @dlErrOutcomeFirst.
  ///
  /// In ar, this message translates to:
  /// **'اختر سبب تعذر التسليم.'**
  String get dlErrOutcomeFirst;

  /// No description provided for @dlErrReasonNeeded.
  ///
  /// In ar, this message translates to:
  /// **'اكتب السبب (3 أحرف على الأقل).'**
  String get dlErrReasonNeeded;

  /// No description provided for @dlAddProof.
  ///
  /// In ar, this message translates to:
  /// **'إضافة: {type}'**
  String dlAddProof(String type);

  /// No description provided for @dlProofWaiting.
  ///
  /// In ar, this message translates to:
  /// **'بانتظار الإرسال: {type}'**
  String dlProofWaiting(String type);

  /// No description provided for @dlProofFailed.
  ///
  /// In ar, this message translates to:
  /// **'لم يُقبل {type}: {reason}'**
  String dlProofFailed(String type, String reason);

  /// No description provided for @dlDiscard.
  ///
  /// In ar, this message translates to:
  /// **'حذف'**
  String get dlDiscard;

  /// No description provided for @dlSendNow.
  ///
  /// In ar, this message translates to:
  /// **'إرسال الآن'**
  String get dlSendNow;

  /// No description provided for @dlShowProofs.
  ///
  /// In ar, this message translates to:
  /// **'عرض الإثباتات المسجلة'**
  String get dlShowProofs;

  /// No description provided for @dlHideProofs.
  ///
  /// In ar, this message translates to:
  /// **'إخفاء الإثباتات'**
  String get dlHideProofs;

  /// No description provided for @dlMarkInTransit.
  ///
  /// In ar, this message translates to:
  /// **'الشحنة في الطريق'**
  String get dlMarkInTransit;

  /// No description provided for @dlArrivedDropoff.
  ///
  /// In ar, this message translates to:
  /// **'وصلت إلى نقطة التسليم'**
  String get dlArrivedDropoff;

  /// No description provided for @dlReportAttempt.
  ///
  /// In ar, this message translates to:
  /// **'تعذر التسليم'**
  String get dlReportAttempt;

  /// No description provided for @dlAttemptTitle.
  ///
  /// In ar, this message translates to:
  /// **'لماذا تعذر التسليم؟'**
  String get dlAttemptTitle;

  /// No description provided for @dlAttemptBody.
  ///
  /// In ar, this message translates to:
  /// **'سيُبلَّغ العميل ويقرر الخطوة التالية. أرفق صورة إن أمكن.'**
  String get dlAttemptBody;

  /// No description provided for @dlReasonRequired.
  ///
  /// In ar, this message translates to:
  /// **'اكتب السبب (مطلوب)'**
  String get dlReasonRequired;

  /// No description provided for @dlReasonOptional.
  ///
  /// In ar, this message translates to:
  /// **'ملاحظات (اختيارية)'**
  String get dlReasonOptional;

  /// No description provided for @dlAddEvidence.
  ///
  /// In ar, this message translates to:
  /// **'إضافة صورة كدليل'**
  String get dlAddEvidence;

  /// No description provided for @dlAttemptSend.
  ///
  /// In ar, this message translates to:
  /// **'إرسال البلاغ'**
  String get dlAttemptSend;

  /// No description provided for @dlResolveButton.
  ///
  /// In ar, this message translates to:
  /// **'قرار ما بعد تعذر التسليم'**
  String get dlResolveButton;

  /// No description provided for @dlResolveTitle.
  ///
  /// In ar, this message translates to:
  /// **'ماذا تفعل بالشحنة؟'**
  String get dlResolveTitle;

  /// No description provided for @dlResolveBody.
  ///
  /// In ar, this message translates to:
  /// **'اختر إعادة المحاولة أو إرجاعها للمرسل أو تحويل الحالة للعمليات.'**
  String get dlResolveBody;

  /// No description provided for @dlResolveNothing.
  ///
  /// In ar, this message translates to:
  /// **'لا توجد محاولة تنتظر القرار.'**
  String get dlResolveNothing;

  /// No description provided for @dlContactsTitle.
  ///
  /// In ar, this message translates to:
  /// **'جهات الاتصال'**
  String get dlContactsTitle;

  /// No description provided for @dlRoleSender.
  ///
  /// In ar, this message translates to:
  /// **'المرسل'**
  String get dlRoleSender;

  /// No description provided for @dlRoleRecipient.
  ///
  /// In ar, this message translates to:
  /// **'المستلم'**
  String get dlRoleRecipient;

  /// No description provided for @dlRolePickup.
  ///
  /// In ar, this message translates to:
  /// **'جهة الاتصال عند التحميل'**
  String get dlRolePickup;

  /// No description provided for @dlRoleDropoff.
  ///
  /// In ar, this message translates to:
  /// **'جهة الاتصال عند التسليم'**
  String get dlRoleDropoff;

  /// No description provided for @dlStopsTitle.
  ///
  /// In ar, this message translates to:
  /// **'محطات الرحلة'**
  String get dlStopsTitle;

  /// No description provided for @dlStopNumber.
  ///
  /// In ar, this message translates to:
  /// **'المحطة {number}'**
  String dlStopNumber(int number);

  /// No description provided for @dlNextStop.
  ///
  /// In ar, this message translates to:
  /// **'التالية'**
  String get dlNextStop;

  /// No description provided for @dlLoadTitle.
  ///
  /// In ar, this message translates to:
  /// **'الحمولة'**
  String get dlLoadTitle;

  /// No description provided for @dlLoadWeight.
  ///
  /// In ar, this message translates to:
  /// **'الوزن: {kg} كجم'**
  String dlLoadWeight(String kg);

  /// No description provided for @dlLoadVolume.
  ///
  /// In ar, this message translates to:
  /// **'الحجم: {volume} م³'**
  String dlLoadVolume(String volume);

  /// No description provided for @dlFileVideo.
  ///
  /// In ar, this message translates to:
  /// **'فيديو'**
  String get dlFileVideo;

  /// No description provided for @dlFileDocument.
  ///
  /// In ar, this message translates to:
  /// **'مستند'**
  String get dlFileDocument;

  /// No description provided for @dadjScopeItemLine.
  ///
  /// In ar, this message translates to:
  /// **'بند الاتفاق: {item}'**
  String dadjScopeItemLine(String item);

  /// No description provided for @dadjHelpersLine.
  ///
  /// In ar, this message translates to:
  /// **'{count} عمال'**
  String dadjHelpersLine(int count);

  /// No description provided for @dadjFloorsLine.
  ///
  /// In ar, this message translates to:
  /// **'{count} أدوار'**
  String dadjFloorsLine(int count);

  /// No description provided for @dadjTypeHelper.
  ///
  /// In ar, this message translates to:
  /// **'عامل إضافي'**
  String get dadjTypeHelper;

  /// No description provided for @dadjTypeStairs.
  ///
  /// In ar, this message translates to:
  /// **'سلالم إضافية'**
  String get dadjTypeStairs;

  /// No description provided for @dadjHelpersCount.
  ///
  /// In ar, this message translates to:
  /// **'عدد العمال الإضافيين'**
  String get dadjHelpersCount;

  /// No description provided for @dadjFloorsCount.
  ///
  /// In ar, this message translates to:
  /// **'عدد الأدوار الإضافية'**
  String get dadjFloorsCount;

  /// No description provided for @dadjReceiptRef.
  ///
  /// In ar, this message translates to:
  /// **'رقم الإيصال'**
  String get dadjReceiptRef;

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
