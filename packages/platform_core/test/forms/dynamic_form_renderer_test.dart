import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import 'form_test_support.dart';

Future<void> _scrollTo(WidgetTester t, Finder f) async {
  await t.ensureVisible(f);
  await t.pumpAndSettle();
}

void main() {
  for (final dir in dirs) {
    group('DynamicFormRenderer $dir', () {
      testWidgets('shows localized labels, help text and a required marker', (
        t,
      ) async {
        await pumpForm(
          t,
          [
            field(
              'note',
              FormFieldType.text,
              ar: 'ملاحظة',
              en: 'Note',
              required: true,
              helpAr: 'اكتب هنا',
            ),
          ],
          dir: dir,
          languageCode: 'ar',
        );
        expect(find.text('ملاحظة *'), findsOneWidget);
        expect(find.text('اكتب هنا'), findsOneWidget);
        expect(find.text('Note *'), findsNothing);
      });

      testWidgets('uses English labels for English and falls back to Arabic', (
        t,
      ) async {
        await pumpForm(
          t,
          [
            field('a', FormFieldType.text, ar: 'عربي', en: 'English'),
            FormFieldSchema(
              key: 'b',
              type: FormFieldType.text,
              label: const LocalizedText({'ar': 'فقط عربي'}),
            ),
          ],
          dir: dir,
          languageCode: 'en',
        );
        expect(find.text('English'), findsOneWidget);
        expect(find.text('فقط عربي'), findsOneWidget);
      });

      testWidgets('text field stores the answer and enforces length bounds', (
        t,
      ) async {
        final c = await pumpForm(t, [
          field('name', FormFieldType.text, required: true, min: 3, max: 5),
        ], dir: dir);
        await t.enterText(find.byKey(const ValueKey('name-input')), 'ab');
        await t.pump();
        expect(c.values['name'], 'ab');
        expect(find.text('min length 3'), findsOneWidget);
        await t.enterText(find.byKey(const ValueKey('name-input')), 'abcdef');
        await t.pump();
        expect(find.text('max length 5'), findsOneWidget);
        await t.enterText(find.byKey(const ValueKey('name-input')), 'abcd');
        await t.pump();
        expect(c.validate(), isTrue);
        expect(c.payload, {'name': 'abcd'});
      });

      testWidgets('an untouched required field only errors after validate()', (
        t,
      ) async {
        final c = await pumpForm(t, [
          field('name', FormFieldType.text, required: true),
        ], dir: dir);
        expect(find.text('required!'), findsNothing);
        expect(c.validate(), isFalse);
        await t.pump();
        expect(find.text('required!'), findsOneWidget);
      });

      testWidgets('textarea is multiline', (t) async {
        final c = await pumpForm(t, [
          field('notes', FormFieldType.textarea, max: 20),
        ], dir: dir);
        final input = t.widget<TextField>(
          find.descendant(
            of: find.byKey(const ValueKey('notes-input')),
            matching: find.byType(TextField),
          ),
        );
        expect(input.maxLines, greaterThan(1));
        await t.enterText(
          find.byKey(const ValueKey('notes-input')),
          'line one\nline two',
        );
        expect(c.values['notes'], 'line one\nline two');
      });

      testWidgets('integer accepts Arabic digits and rejects bad input', (
        t,
      ) async {
        final c = await pumpForm(t, [
          field('seats', FormFieldType.integer, required: true, min: 1, max: 4),
        ], dir: dir);
        final input = find.byKey(const ValueKey('seats-input'));
        await t.enterText(input, '٣');
        await t.pump();
        expect(c.values['seats'], 3);
        expect(c.validate(), isTrue);
        await t.enterText(input, 'abc');
        await t.pump();
        expect(find.text('not a number'), findsOneWidget);
        await t.enterText(input, '2.5');
        await t.pump();
        expect(find.text('not whole'), findsOneWidget);
        await t.enterText(input, '9');
        await t.pump();
        expect(find.text('at most 4'), findsOneWidget);
        await t.enterText(input, '0');
        await t.pump();
        expect(find.text('at least 1'), findsOneWidget);
      });

      testWidgets(
        'decimal keeps fractions; weight shows the unit and a positive default minimum',
        (t) async {
          final c = await pumpForm(t, [
            field('value', FormFieldType.decimal, min: 0),
            field('weight', FormFieldType.weight, required: true, max: 20),
          ], dir: dir);
          await t.enterText(find.byKey(const ValueKey('value-input')), '2.75');
          await t.enterText(
            find.byKey(const ValueKey('weight-input')),
            '٠٫٠٠٥',
          );
          await t.pump();
          expect(c.values['value'], 2.75);
          expect(find.text('at least 0.01'), findsOneWidget);
          expect(find.text('kg'), findsOneWidget);
          await t.enterText(find.byKey(const ValueKey('weight-input')), '12.5');
          await t.pump();
          expect(c.validate(), isTrue);
          expect(c.payload, {'value': 2.75, 'weight': 12.5});
        },
      );

      testWidgets('boolean offers yes/no, can be unanswered when optional', (
        t,
      ) async {
        final c = await pumpForm(t, [
          field('fragile', FormFieldType.boolean),
          field('insured', FormFieldType.boolean, required: true),
        ], dir: dir);
        await t.tap(
          find.descendant(
            of: find.byKey(const ValueKey('fragile-segments')),
            matching: find.text('Yes'),
          ),
        );
        await t.pump();
        expect(c.values['fragile'], isTrue);
        await t.tap(
          find.descendant(
            of: find.byKey(const ValueKey('fragile-segments')),
            matching: find.text('No'),
          ),
        );
        await t.pump();
        expect(c.values['fragile'], isFalse);
        await t.tap(
          find.descendant(
            of: find.byKey(const ValueKey('fragile-segments')),
            matching: find.text('No'),
          ),
        );
        await t.pump();
        expect(c.values['fragile'], isNull);
        expect(c.validate(), isFalse);
        await t.pump();
        expect(find.text('required!'), findsOneWidget);
        await t.tap(
          find.descendant(
            of: find.byKey(const ValueKey('insured-segments')),
            matching: find.text('No'),
          ),
        );
        await t.pump();
        expect(c.values['insured'], isFalse);
        expect(c.validate(), isTrue);
        expect(c.payload, {'insured': false});
      });

      testWidgets('select lists the localized options', (t) async {
        final c = await pumpForm(t, [
          field(
            'size',
            FormFieldType.select,
            required: true,
            options: [('small', 'صغير', 'Small'), ('large', 'كبير', 'Large')],
          ),
        ], dir: dir);
        await t.tap(find.byKey(const ValueKey('size-dropdown')));
        await t.pumpAndSettle();
        final label = dir == TextDirection.rtl ? 'كبير' : 'Large';
        await t.tap(find.text(label).last);
        await t.pumpAndSettle();
        expect(c.values['size'], 'large');
        expect(c.validate(), isTrue);
      });

      testWidgets('multiselect toggles chips and enforces the item limit', (
        t,
      ) async {
        final c = await pumpForm(t, [
          field(
            'equipment',
            FormFieldType.multiselect,
            max: 2,
            options: [('a', 'أ', 'A'), ('b', 'ب', 'B'), ('c', 'ج', 'C')],
          ),
        ], dir: dir);
        Finder chip(String v) => find.byKey(ValueKey('equipment-chip-$v'));
        await t.tap(chip('a'));
        await t.pump();
        await t.tap(chip('c'));
        await t.pump();
        expect(c.values['equipment'], ['a', 'c']);
        await t.tap(chip('b'));
        await t.pump();
        expect(find.text('pick at most 2'), findsOneWidget);
        await t.tap(chip('c'));
        await t.pump();
        expect(c.values['equipment'], ['a', 'b']);
        expect(c.validate(), isTrue);
      });

      testWidgets('date opens a picker and stores yyyy-MM-dd', (t) async {
        final c = await pumpForm(t, [
          field('day', FormFieldType.date, required: true),
        ], dir: dir);
        await t.tap(find.byKey(const ValueKey('day-field')));
        await t.pumpAndSettle();
        await t.tap(find.text('OK'));
        await t.pumpAndSettle();
        final now = DateTime.now();
        final expected =
            '${now.year.toString().padLeft(4, '0')}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
        expect(c.values['day'], expected);
        expect(c.validate(), isTrue);
      });

      testWidgets('time opens a picker and stores HH:mm', (t) async {
        final c = await pumpForm(t, [
          field('at', FormFieldType.time),
        ], dir: dir);
        await t.tap(find.byKey(const ValueKey('at-field')));
        await t.pumpAndSettle();
        await t.tap(find.text('OK'));
        await t.pumpAndSettle();
        expect(c.values['at'], matches(RegExp(r'^\d{2}:\d{2}$')));
        expect(FormLogic.validate(c.fields.first, c.values['at']), isNull);
      });

      testWidgets('datetime picks a date then a time', (t) async {
        final c = await pumpForm(t, [
          field('when', FormFieldType.datetime),
        ], dir: dir);
        await t.tap(find.byKey(const ValueKey('when-field')));
        await t.pumpAndSettle();
        await t.tap(find.text('OK'));
        await t.pumpAndSettle();
        await t.tap(find.text('OK'));
        await t.pumpAndSettle();
        expect(
          c.values['when'],
          matches(RegExp(r'^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$')),
        );
        expect(FormLogic.validate(c.fields.first, c.values['when']), isNull);
      });

      testWidgets('cancelling a picker keeps the previous answer', (t) async {
        final c = await pumpForm(t, [
          field('day', FormFieldType.date),
        ], dir: dir);
        await t.tap(find.byKey(const ValueKey('day-field')));
        await t.pumpAndSettle();
        await t.tap(find.text('Cancel'));
        await t.pumpAndSettle();
        expect(c.values['day'], isNull);
      });

      testWidgets('contact needs name and an international phone', (t) async {
        final c = await pumpForm(t, [
          field('recipient', FormFieldType.contact, required: true),
        ], dir: dir);
        await t.enterText(find.byKey(const ValueKey('recipient-name')), 'Mona');
        await t.pump();
        expect(c.validate(), isFalse);
        await t.pump();
        expect(find.text('incomplete'), findsOneWidget);
        await t.enterText(find.byKey(const ValueKey('recipient-phone')), '123');
        await t.pump();
        expect(find.text('bad phone'), findsOneWidget);
        await t.enterText(
          find.byKey(const ValueKey('recipient-phone')),
          '٠٠٢٠١٠٠١٢٣٤٥٦٧',
        );
        await t.pump();
        expect(c.validate(), isTrue);
        expect(c.payload, {
          'recipient': {'name': 'Mona', 'phone': '+201001234567'},
        });
      });

      testWidgets('photo adds and removes references via the handler', (
        t,
      ) async {
        final handler = FakePhotoHandler(['/tmp/a.jpg', '/tmp/b.jpg']);
        final c = await pumpForm(
          t,
          [field('photos', FormFieldType.photo, required: true, max: 2)],
          dir: dir,
          photoHandler: handler,
        );
        expect(c.validate(), isFalse);
        await t.pump();
        expect(find.text('required!'), findsOneWidget);
        await t.tap(find.byKey(const ValueKey('photos-add')));
        await t.pumpAndSettle();
        await t.tap(find.byKey(const ValueKey('photos-add')));
        await t.pumpAndSettle();
        expect(c.values['photos'], ['/tmp/a.jpg', '/tmp/b.jpg']);
        expect(
          find.byKey(const ValueKey('photos-add')),
          findsNothing,
          reason: 'limit reached',
        );
        await t.tap(find.byKey(const ValueKey('photos-remove-/tmp/a.jpg')));
        await t.pumpAndSettle();
        expect(c.values['photos'], ['/tmp/b.jpg']);
        expect(c.validate(), isTrue);
      });

      testWidgets('photo without a handler renders no dead add button', (
        t,
      ) async {
        await pumpForm(t, [field('photos', FormFieldType.photo)], dir: dir);
        expect(find.byKey(const ValueKey('photos-add')), findsNothing);
      });

      testWidgets('item checklist selects items with quantities', (t) async {
        final c = await pumpForm(t, [
          field(
            'items',
            FormFieldType.itemChecklist,
            required: true,
            options: [('sofa', 'كنبة', 'Sofa'), ('bed', 'سرير', 'Bed')],
          ),
        ], dir: dir);
        await t.tap(find.byKey(const ValueKey('items-item-bed')));
        await t.pump();
        await t.tap(find.byKey(const ValueKey('items-plus-bed')));
        await t.pump();
        await t.tap(find.byKey(const ValueKey('items-plus-bed')));
        await t.pump();
        await t.tap(find.byKey(const ValueKey('items-item-sofa')));
        await t.pump();
        expect(c.values['items'], [
          {'item': 'sofa', 'quantity': 1},
          {'item': 'bed', 'quantity': 3},
        ]);
        expect(find.byKey(const ValueKey('items-qty-bed')), findsOneWidget);
        await t.tap(find.byKey(const ValueKey('items-minus-bed')));
        await t.pump();
        expect((c.values['items']! as List).last, {
          'item': 'bed',
          'quantity': 2,
        });
        await t.tap(find.byKey(const ValueKey('items-item-bed')));
        await t.pump();
        await t.tap(find.byKey(const ValueKey('items-item-sofa')));
        await t.pump();
        expect(c.validate(), isFalse);
      });

      testWidgets('dimensions needs all three sides within the bounds', (
        t,
      ) async {
        final c = await pumpForm(t, [
          field('size', FormFieldType.dimensions, required: true, max: 300),
        ], dir: dir);
        await t.enterText(find.byKey(const ValueKey('size-length')), '120');
        await t.enterText(find.byKey(const ValueKey('size-width')), '60.5');
        await t.pump();
        expect(c.validate(), isFalse);
        await t.pump();
        expect(find.text('incomplete'), findsOneWidget);
        await t.enterText(find.byKey(const ValueKey('size-height')), '500');
        await t.pump();
        expect(find.text('at most 300'), findsOneWidget);
        await t.enterText(find.byKey(const ValueKey('size-height')), '40');
        await t.pump();
        expect(c.validate(), isTrue);
        expect(c.payload, {
          'size': {'length': 120, 'width': 60.5, 'height': 40},
        });
      });

      testWidgets(
        'visible_if shows and hides fields and drops hidden answers',
        (t) async {
          final c = await pumpForm(t, [
            field('elevator', FormFieldType.boolean),
            field(
              'carry',
              FormFieldType.integer,
              required: true,
              visibleIf: const FormFieldVisibility(
                field: 'elevator',
                operator: VisibilityOperator.equals,
                value: false,
              ),
            ),
          ], dir: dir);
          expect(find.byKey(const ValueKey('carry-input')), findsNothing);
          expect(
            c.validate(),
            isTrue,
            reason: 'hidden required fields are not required',
          );
          await t.tap(
            find.descendant(
              of: find.byKey(const ValueKey('elevator-segments')),
              matching: find.text('No'),
            ),
          );
          await t.pump();
          expect(find.byKey(const ValueKey('carry-input')), findsOneWidget);
          expect(c.validate(), isFalse);
          await t.pump();
          expect(find.text('required!'), findsOneWidget);
          await t.enterText(find.byKey(const ValueKey('carry-input')), '20');
          await t.pump();
          expect(c.payload, {'elevator': false, 'carry': 20});
          await t.tap(
            find.descendant(
              of: find.byKey(const ValueKey('elevator-segments')),
              matching: find.text('Yes'),
            ),
          );
          await t.pump();
          expect(find.byKey(const ValueKey('carry-input')), findsNothing);
          expect(c.payload, {
            'elevator': true,
          }, reason: 'hidden answer is not submitted');
        },
      );

      testWidgets('renders every field type together without overflow', (
        t,
      ) async {
        await pumpForm(
          t,
          [
            field('a', FormFieldType.text),
            field('b', FormFieldType.textarea),
            field('c', FormFieldType.integer),
            field('d', FormFieldType.decimal),
            field('e', FormFieldType.boolean),
            field('f', FormFieldType.select, options: [('x', 'س', 'X')]),
            field('g', FormFieldType.multiselect, options: [('x', 'س', 'X')]),
            field('h', FormFieldType.date),
            field('i', FormFieldType.time),
            field('j', FormFieldType.datetime),
            field('k', FormFieldType.contact),
            field('l', FormFieldType.photo),
            field('m', FormFieldType.itemChecklist, options: [('x', 'س', 'X')]),
            field('n', FormFieldType.dimensions),
            field('o', FormFieldType.weight),
          ],
          dir: dir,
          photoHandler: FakePhotoHandler(const []),
        );
        await _scrollTo(t, find.byKey(const ValueKey('form-field-o')));
        expect(t.takeException(), isNull);
        for (final key in 'abcdefghijklmno'.split('')) {
          expect(
            find.byKey(ValueKey('form-field-$key')),
            findsOneWidget,
            reason: key,
          );
        }
      });
    });
  }
}
