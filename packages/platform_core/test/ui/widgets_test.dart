import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

Widget host(Widget child, {required TextDirection dir, UiDensity? density}) {
  return MaterialApp(
    theme: AppTheme.light(density: density ?? UiDensity.standard),
    locale: Locale(dir == TextDirection.rtl ? 'ar' : 'en'),
    builder: (context, c) => Directionality(textDirection: dir, child: c!),
    home: Scaffold(body: child),
  );
}

const dirs = [TextDirection.rtl, TextDirection.ltr];

void main() {
  for (final dir in dirs) {
    group('shared widgets $dir', () {
      testWidgets('BlockingNotice shows details and fires its action', (
        t,
      ) async {
        var taps = 0;
        await t.pumpWidget(
          host(
            BlockingNotice(
              icon: Icons.construction_rounded,
              title: 'Maintenance',
              message: 'Back soon',
              details: const ['Support: +20 100 000 0000'],
              actionLabel: 'Check again',
              onAction: () => taps++,
            ),
            dir: dir,
          ),
        );
        expect(find.text('Maintenance'), findsOneWidget);
        expect(find.text('Back soon'), findsOneWidget);
        expect(find.text('Support: +20 100 000 0000'), findsOneWidget);
        await t.tap(find.text('Check again'));
        expect(taps, 1);
      });

      testWidgets('AppButton fires, and loading disables', (t) async {
        var taps = 0;
        await t.pumpWidget(
          host(
            AppButton(label: 'Go', onPressed: () => taps++),
            dir: dir,
          ),
        );
        await t.tap(find.text('Go'));
        expect(taps, 1);

        await t.pumpWidget(
          host(
            AppButton(label: 'Go', onPressed: () => taps++, loading: true),
            dir: dir,
          ),
        );
        expect(find.byType(CircularProgressIndicator), findsOneWidget);
        await t.tap(find.byType(FilledButton));
        expect(taps, 1);
      });

      testWidgets('ErrorState shows retry and calls it', (t) async {
        var retried = false;
        await t.pumpWidget(
          host(
            ErrorState(
              title: 'Oops',
              retryLabel: 'Retry',
              onRetry: () => retried = true,
            ),
            dir: dir,
          ),
        );
        await t.tap(find.text('Retry'));
        expect(retried, isTrue);
      });

      testWidgets('EmptyState renders title and message', (t) async {
        await t.pumpWidget(
          host(
            const EmptyState(title: 'Nothing', message: 'Here'),
            dir: dir,
          ),
        );
        expect(find.text('Nothing'), findsOneWidget);
        expect(find.text('Here'), findsOneWidget);
      });

      testWidgets('MoneyText and StatusChip render', (t) async {
        await t.pumpWidget(
          host(
            Column(
              children: [
                MoneyText(
                  money: const Money(minor: 5000, currency: 'EGP'),
                  formatter: MoneyFormatter(
                    languageCode: dir == TextDirection.rtl ? 'ar' : 'en',
                  ),
                ),
                const StatusChip(label: 'Active', tone: ChipTone.success),
              ],
            ),
            dir: dir,
          ),
        );
        expect(
          find.text(dir == TextDirection.rtl ? '٥٠٫٠٠ ج.م' : 'EGP 50.00'),
          findsOneWidget,
        );
        expect(find.text('Active'), findsOneWidget);
      });

      testWidgets('Timeline rail sits on the start side', (t) async {
        await t.pumpWidget(
          host(
            const Timeline(
              steps: [
                TimelineStep(title: 'A', done: true),
                TimelineStep(title: 'B'),
              ],
            ),
            dir: dir,
          ),
        );
        final icon = t.getTopLeft(find.byIcon(Icons.check_circle));
        final title = t.getTopLeft(find.text('A'));
        if (dir == TextDirection.rtl) {
          expect(icon.dx, greaterThan(title.dx));
        } else {
          expect(icon.dx, lessThan(title.dx));
        }
      });

      testWidgets('RatingStars reports tapped value', (t) async {
        var v = 0;
        await t.pumpWidget(
          host(RatingStars(value: 2, onChanged: (x) => v = x), dir: dir),
        );
        await t.tap(find.byType(IconButton).at(3));
        expect(v, 4);
      });

      testWidgets('ConfirmSheet resolves true on confirm', (t) async {
        bool? result;
        await t.pumpWidget(
          host(
            Builder(
              builder: (ctx) => TextButton(
                onPressed: () async => result = await showConfirmSheet(
                  ctx,
                  title: 'Sure?',
                  confirmLabel: 'Yes',
                  cancelLabel: 'No',
                ),
                child: const Text('open'),
              ),
            ),
            dir: dir,
          ),
        );
        await t.tap(find.text('open'));
        await t.pumpAndSettle();
        await t.tap(find.text('Yes'));
        await t.pumpAndSettle();
        expect(result, isTrue);
      });

      testWidgets('OtpField completes after entering digits', (t) async {
        String? done;
        await t.pumpWidget(
          host(OtpField(length: 4, onCompleted: (c) => done = c), dir: dir),
        );
        await t.enterText(find.byType(EditableText), '1234');
        await t.pump();
        expect(done, '1234');
      });

      testWidgets('OfflineBanner shows message', (t) async {
        await t.pumpWidget(
          host(const OfflineBanner(message: 'Offline'), dir: dir),
        );
        expect(find.text('Offline'), findsOneWidget);
      });
    });
  }

  testWidgets('driver density enlarges buttons', (t) async {
    await t.pumpWidget(
      host(
        AppButton(label: 'Go', onPressed: () {}),
        dir: TextDirection.rtl,
        density: UiDensity.driver,
      ),
    );
    expect(t.getSize(find.byType(FilledButton)).height, 64);
  });

  testWidgets('PhoneField preselects Egypt', (t) async {
    await t.pumpWidget(
      host(
        PhoneField(label: 'Phone', onChanged: (_, _) {}),
        dir: TextDirection.ltr,
      ),
    );
    expect(find.text('+20'), findsOneWidget);
  });

  testWidgets(
    'DynamicFormRenderer reports values and skips unsupported types',
    (t) async {
      Map<String, Object?> values = {};
      await t.pumpWidget(
        host(
          DynamicFormRenderer(
            requiredMessage: 'req',
            fields: const [
              FormFieldSchema(
                key: 'note',
                type: FormFieldType.text,
                label: 'Note',
              ),
              FormFieldSchema(
                key: 'p',
                type: FormFieldType.photo,
                label: 'Photo',
              ),
            ],
            onChanged: (v) => values = v,
          ),
          dir: TextDirection.rtl,
        ),
      );
      expect(find.text('Photo'), findsNothing);
      await t.enterText(find.byType(TextFormField), 'hi');
      expect(values['note'], 'hi');
      expect(DynamicFormRenderer.supports(FormFieldType.photo), isFalse);
    },
  );
}
