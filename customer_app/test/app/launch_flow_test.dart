import 'package:customer_app/core/providers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../helpers.dart';

void main() {
  testWidgets('first launch lands on the language screen in Arabic RTL, '
      'switches to English LTR and persists', (tester) async {
    final container = await pumpApp(tester);

    expect(find.text('اختر لغتك'), findsOneWidget);
    expect(directionOf(tester, find.text('اختر لغتك')), TextDirection.rtl);
    expect(container.read(localeProvider).languageCode, 'ar');

    await tester.tap(find.byKey(const Key('language-en')));
    await tester.pumpAndSettle();

    expect(find.text('Choose your language'), findsOneWidget);
    expect(
      directionOf(tester, find.text('Choose your language')),
      TextDirection.ltr,
    );
    expect(container.read(localePreferenceProvider).stored, 'en');
  });

  testWidgets('Continue goes to onboarding, then auth after finishing', (
    tester,
  ) async {
    await pumpApp(tester);
    await tester.tap(find.text('متابعة'));
    await tester.pumpAndSettle();
    expect(find.text('حدّد سعر رحلتك'), findsOneWidget);

    await tester.tap(find.text('التالي'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('التالي'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('ابدأ الآن'));
    await tester.pumpAndSettle();
    expect(find.text('أدخل رقم هاتفك'), findsOneWidget);
  });

  testWidgets('returning user with chosen language skips to onboarding/auth', (
    tester,
  ) async {
    await pumpApp(
      tester,
      prefs: {'language_code': 'en', 'onboarding_done': true},
    );
    expect(find.text('Enter your phone number'), findsOneWidget);
  });

  testWidgets('authenticated user lands on home and can reopen language', (
    tester,
  ) async {
    await pumpApp(
      tester,
      prefs: {'language_code': 'ar', 'onboarding_done': true},
      token: 'abc',
    );
    expect(find.text('الرئيسية'), findsOneWidget);
    await tester.tap(find.text('تغيير اللغة'));
    await tester.pumpAndSettle();
    expect(find.text('اختر لغتك'), findsOneWidget);
    await tester.tap(find.text('متابعة'));
    await tester.pumpAndSettle();
    expect(find.text('الرئيسية'), findsOneWidget);
  });
}
