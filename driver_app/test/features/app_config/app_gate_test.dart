import 'package:driver_app/core/providers.dart';
import 'package:driver_app/features/app_config/domain/entities/app_config.dart';
import 'package:driver_app/features/app_config/presentation/providers/app_config_providers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import '../../helpers.dart';

AppConfig config({
  bool maintenance = false,
  String? minVersion,
  bool forceUpdate = false,
  SupportContacts support = const SupportContacts(),
  String defaultLanguageCode = 'ar',
}) => AppConfig(
  defaultLanguageCode: defaultLanguageCode,
  languageCodes: const ['ar', 'en'],
  countryCode: 'EG',
  phoneCode: '+20',
  currencyCode: 'EGP',
  currencyExponent: 2,
  digitStyle: null,
  support: support,
  maintenance: maintenance,
  minVersion: minVersion,
  forceUpdate: forceUpdate,
);

const settings = {'language_code': 'ar', 'onboarding_done': true};

void main() {
  group('force update', () {
    testWidgets('blocks a version below the minimum (Arabic, RTL)', (
      tester,
    ) async {
      await pumpApp(
        tester,
        prefs: settings,
        version: '1.0.0',
        config: config(minVersion: '1.2.0', forceUpdate: true),
      );
      expect(find.byKey(const Key('force-update-screen')), findsOneWidget);
      expect(find.text('تحديث مطلوب'), findsOneWidget);
      expect(find.text('الإصدار المطلوب: 1.2.0'), findsOneWidget);
      expect(find.text('أدخل رقم هاتفك'), findsNothing);
    });

    testWidgets('English, LTR', (tester) async {
      await pumpApp(
        tester,
        prefs: {'language_code': 'en', 'onboarding_done': true},
        version: '1.0.0',
        config: config(minVersion: '1.2.0', forceUpdate: true),
      );
      expect(find.text('Update required'), findsOneWidget);
      expect(find.text('Required version: 1.2.0'), findsOneWidget);
      expect(
        directionOf(tester, find.text('Update required')),
        TextDirection.ltr,
      );
    });

    testWidgets('lets an up-to-date version through', (tester) async {
      await pumpApp(
        tester,
        prefs: settings,
        version: '1.2.0',
        config: config(minVersion: '1.2.0', forceUpdate: true),
      );
      expect(find.byKey(const Key('force-update-screen')), findsNothing);
      expect(find.text('أدخل رقم هاتفك'), findsOneWidget);
    });

    testWidgets('does not block when the update is not forced', (tester) async {
      await pumpApp(
        tester,
        prefs: settings,
        version: '1.0.0',
        config: config(minVersion: '2.0.0'),
      );
      expect(find.byKey(const Key('force-update-screen')), findsNothing);
    });
  });

  group('maintenance', () {
    testWidgets('shows the maintenance screen with the support contacts', (
      tester,
    ) async {
      await pumpApp(
        tester,
        prefs: settings,
        config: config(
          maintenance: true,
          support: const SupportContacts(
            phone: '+20 100 000 0000',
            email: 'help@example.com',
          ),
        ),
      );
      expect(find.byKey(const Key('maintenance-screen')), findsOneWidget);
      expect(find.text('التطبيق تحت الصيانة'), findsOneWidget);
      expect(find.text('الدعم: +20 100 000 0000'), findsOneWidget);
      expect(find.text('الدعم: help@example.com'), findsOneWidget);
    });

    testWidgets('wins over a forced update', (tester) async {
      await pumpApp(
        tester,
        prefs: settings,
        version: '1.0.0',
        config: config(
          maintenance: true,
          minVersion: '9.0.0',
          forceUpdate: true,
        ),
      );
      expect(find.byKey(const Key('maintenance-screen')), findsOneWidget);
      expect(find.byKey(const Key('force-update-screen')), findsNothing);
    });

    testWidgets('"Check again" re-reads the config and leaves maintenance', (
      tester,
    ) async {
      var calls = 0;
      await pumpApp(
        tester,
        prefs: settings,
        configBuilder: () async {
          calls++;
          return config(maintenance: calls == 1);
        },
      );
      expect(find.byKey(const Key('maintenance-screen')), findsOneWidget);

      await tester.tap(find.text('تحقق مرة أخرى'));
      await tester.pumpAndSettle();

      expect(calls, 2);
      expect(find.byKey(const Key('maintenance-screen')), findsNothing);
      expect(find.text('أدخل رقم هاتفك'), findsOneWidget);
    });
  });

  testWidgets('a failing config never blocks the app (fails open)', (
    tester,
  ) async {
    await pumpApp(
      tester,
      prefs: settings,
      configBuilder: () async => throw const ApiException(
        kind: ApiFailureKind.network,
        code: 'network_error',
        message: null,
      ),
    );
    expect(find.text('أدخل رقم هاتفك'), findsOneWidget);
  });

  testWidgets('the server default language applies until the user chooses', (
    tester,
  ) async {
    final container = await pumpApp(
      tester,
      config: config(defaultLanguageCode: 'en'),
    );
    expect(container.read(localeProvider).languageCode, 'en');
    expect(find.text('Choose your language'), findsOneWidget);
  });

  testWidgets('a chosen language wins over the server default', (tester) async {
    final container = await pumpApp(
      tester,
      prefs: {'language_code': 'ar'},
      config: config(defaultLanguageCode: 'en'),
    );
    expect(container.read(localeProvider).languageCode, 'ar');
  });

  testWidgets('money formatting uses the server digit style and symbols', (
    tester,
  ) async {
    final container = await pumpApp(
      tester,
      prefs: settings,
      config: const AppConfig(
        defaultLanguageCode: 'ar',
        languageCodes: ['ar', 'en'],
        countryCode: 'EG',
        phoneCode: '+20',
        currencyCode: 'EGP',
        currencyExponent: 2,
        digitStyle: DigitStyle.western,
        support: SupportContacts(),
        maintenance: false,
        currencySymbols: ('EGP', 'جنيه'),
      ),
    );
    final text = container
        .read(moneyFormatterProvider)
        .format(const Money(minor: 12550, currency: 'EGP'));
    expect(text, '125.50 جنيه');
  });
}
