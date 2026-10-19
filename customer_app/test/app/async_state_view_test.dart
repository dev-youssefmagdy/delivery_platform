import 'package:customer_app/app/l10n/generated/app_localizations.dart';
import 'package:customer_app/core/async_state_view.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

Widget host(AsyncValue<List<int>> v, Locale locale, VoidCallback retry) =>
    MaterialApp(
      locale: locale,
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: Scaffold(
        body: AsyncStateView<List<int>>(
          value: v,
          isEmpty: (d) => d.isEmpty,
          onRetry: retry,
          builder: (_, d) => Text('count ${d.length}'),
        ),
      ),
    );

ApiException err(ApiFailureKind k, {String? rid}) =>
    ApiException(kind: k, code: 'x', message: 'server says', requestId: rid);

void main() {
  for (final locale in [const Locale('ar'), const Locale('en')]) {
    final ar = locale.languageCode == 'ar';
    group('AsyncStateView ${locale.languageCode}', () {
      testWidgets('loading', (t) async {
        await t.pumpWidget(host(const AsyncLoading(), locale, () {}));
        expect(find.byType(CircularProgressIndicator), findsOneWidget);
      });

      testWidgets('data and empty', (t) async {
        await t.pumpWidget(host(const AsyncData([1, 2]), locale, () {}));
        expect(find.text('count 2'), findsOneWidget);
        await t.pumpWidget(host(const AsyncData([]), locale, () {}));
        expect(
          find.text(ar ? 'لا توجد بيانات' : 'Nothing here yet'),
          findsOneWidget,
        );
      });

      testWidgets('error shows server message, request id and retries', (
        t,
      ) async {
        var retried = 0;
        await t.pumpWidget(
          host(
            AsyncError(
              err(ApiFailureKind.server, rid: 'rid-9'),
              StackTrace.empty,
            ),
            locale,
            () => retried++,
          ),
        );
        expect(find.textContaining('server says'), findsOneWidget);
        expect(find.textContaining('rid-9'), findsOneWidget);
        await t.tap(find.text(ar ? 'إعادة المحاولة' : 'Try again'));
        expect(retried, 1);
      });

      testWidgets('offline', (t) async {
        await t.pumpWidget(
          host(
            AsyncError(err(ApiFailureKind.network), StackTrace.empty),
            locale,
            () {},
          ),
        );
        expect(
          find.text(ar ? 'لا يوجد اتصال' : 'You are offline'),
          findsOneWidget,
        );
        expect(find.byIcon(Icons.wifi_off_rounded), findsOneWidget);
      });

      testWidgets('session expired', (t) async {
        await t.pumpWidget(
          host(
            AsyncError(err(ApiFailureKind.unauthenticated), StackTrace.empty),
            locale,
            () {},
          ),
        );
        expect(
          find.text(ar ? 'انتهت الجلسة' : 'Session expired'),
          findsOneWidget,
        );
      });
    });
  }
}
