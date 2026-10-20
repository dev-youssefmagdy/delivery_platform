import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import 'form_test_support.dart';

FormFieldIssue? check(FormFieldSchema f, Object? v) => FormLogic.validate(f, v);

void main() {
  group('VisibilityOperator parity with the backend', () {
    // Same table as backend tests/Unit/Catalog/VisibilityOperatorTest.php.
    final cases = <(String, VisibilityOperator, Object?, Object?, bool)>[
      ('eq bool vs 1', VisibilityOperator.equals, true, 1, true),
      ('eq string vs number', VisibilityOperator.equals, '5', 5, true),
      ('eq unanswered', VisibilityOperator.equals, null, 'x', false),
      ('neq unanswered', VisibilityOperator.notEquals, null, 'x', true),
      ('neq different', VisibilityOperator.notEquals, 'a', 'b', true),
      ('in list', VisibilityOperator.isIn, 'b', ['a', 'b'], true),
      ('in multiselect', VisibilityOperator.isIn, ['x', 'b'], ['b'], true),
      ('not_in', VisibilityOperator.notIn, 'z', ['a', 'b'], true),
      ('gt', VisibilityOperator.greaterThan, 3, 2, true),
      ('gt equal', VisibilityOperator.greaterThan, 2, 2, false),
      ('gte', VisibilityOperator.greaterOrEqual, 2, 2, true),
      ('lt non numeric', VisibilityOperator.lessThan, 'abc', 2, false),
      ('lte', VisibilityOperator.lessOrEqual, '2', 2, true),
      ('filled zero', VisibilityOperator.filled, 0, null, true),
      (
        'filled empty array',
        VisibilityOperator.filled,
        <String>[],
        null,
        false,
      ),
      ('empty string', VisibilityOperator.empty, '', null, true),
      ('false is filled', VisibilityOperator.filled, false, null, true),
      ('eq 2.0 vs 2', VisibilityOperator.equals, 2.0, 2, true),
    ];
    for (final c in cases) {
      test(c.$1, () => expect(c.$2.matches(c.$3, c.$4), c.$5));
    }

    test('unknown operators never match and parse with a fallback', () {
      expect(VisibilityOperator.parse('future_op'), VisibilityOperator.unknown);
      expect(VisibilityOperator.unknown.matches('a', 'a'), isFalse);
      expect(VisibilityOperator.parse('not_in'), VisibilityOperator.notIn);
    });
  });

  group('schema parsing', () {
    test('parses the API shape, tolerating unknown types and empty maps', () {
      final schema = ServiceFormSchema.fromJson({
        'version': 3,
        'fields': [
          {
            'key': 'kind',
            'type': 'select',
            'label': {'ar': 'النوع', 'en': 'Kind'},
            'help': null,
            'required': true,
            'min': null,
            'max': null,
            'options': [
              {
                'value': 'a',
                'label': {'ar': 'أ', 'en': 'A'},
              },
            ],
            'visible_if': null,
          },
          {
            'key': 'extra',
            'type': 'hologram',
            'label': {},
            'visible_if': {
              'field': 'kind',
              'op': 'in',
              'value': ['a', 'b'],
            },
            'min': 1.5,
          },
        ],
      });
      expect(schema.version, 3);
      expect(schema.fields.first.type, FormFieldType.select);
      expect(schema.fields.first.label.resolve('en'), 'Kind');
      expect(schema.fields.first.optionFor('a')!.label.resolve('ar'), 'أ');
      expect(schema.fields.first.help.isEmpty, isTrue);
      expect(schema.fields.last.type, FormFieldType.unknown);
      expect(schema.fields.last.label.resolve('ar'), '');
      expect(schema.fields.last.min, 1.5);
      expect(schema.fields.last.visibleIf!.operator, VisibilityOperator.isIn);
    });

    test('non-map schema is an empty form', () {
      expect(ServiceFormSchema.fromJson(null).fields, isEmpty);
    });

    test('LocalizedText falls back ar -> en -> first', () {
      expect(const LocalizedText({'en': 'E', 'fr': 'F'}).resolve('ar'), 'E');
      expect(const LocalizedText({'fr': 'F'}).resolve('ar'), 'F');
      expect(const LocalizedText({'ar': 'ع', 'en': 'E'}).resolve('en'), 'E');
    });
  });

  group('validation rules mirror ServiceFormValidator', () {
    test('required: empty values, whitespace, empty lists and empty maps', () {
      for (final type in FormFieldType.values.where(
        (t) => t != FormFieldType.unknown,
      )) {
        final f = field('x', type, required: true);
        expect(
          check(f, null)?.kind,
          FormFieldIssueKind.required,
          reason: '$type',
        );
      }
      final text = field('x', FormFieldType.text, required: true);
      expect(check(text, '   ')?.kind, FormFieldIssueKind.required);
      expect(
        check(
          field('x', FormFieldType.photo, required: true),
          <String>[],
        )?.kind,
        FormFieldIssueKind.required,
      );
      expect(
        check(field('x', FormFieldType.contact, required: true), {
          'name': '',
          'phone': '',
        })?.kind,
        FormFieldIssueKind.required,
      );
      expect(
        check(field('x', FormFieldType.boolean, required: true), false),
        isNull,
      );
      expect(check(field('x', FormFieldType.text), null), isNull);
    });

    test('text and textarea use the documented default maxima', () {
      expect(check(field('x', FormFieldType.text), 'a' * 255), isNull);
      expect(
        check(field('x', FormFieldType.text), 'a' * 256),
        const FormFieldIssue(FormFieldIssueKind.tooLong, 255),
      );
      expect(check(field('x', FormFieldType.textarea), 'a' * 2000), isNull);
      expect(
        check(field('x', FormFieldType.textarea), 'a' * 2001),
        const FormFieldIssue(FormFieldIssueKind.tooLong, 2000),
      );
      expect(
        check(field('x', FormFieldType.text, min: 2), 'a'),
        const FormFieldIssue(FormFieldIssueKind.tooShort, 2),
      );
    });

    test('numbers: integer, decimal and weight bounds', () {
      final i = field('x', FormFieldType.integer, min: 1, max: 5);
      expect(check(i, 3), isNull);
      expect(check(i, '3'), isNull);
      expect(check(i, 3.5)?.kind, FormFieldIssueKind.notAWholeNumber);
      expect(check(i, 'abc')?.kind, FormFieldIssueKind.notANumber);
      expect(check(i, 0), const FormFieldIssue(FormFieldIssueKind.tooSmall, 1));
      expect(check(i, 6), const FormFieldIssue(FormFieldIssueKind.tooLarge, 5));
      expect(
        check(field('x', FormFieldType.decimal, min: 0.5), 0.25)?.kind,
        FormFieldIssueKind.tooSmall,
      );
      expect(
        check(field('x', FormFieldType.weight), 0.005)?.kind,
        FormFieldIssueKind.tooSmall,
      );
      expect(check(field('x', FormFieldType.weight), 0.01), isNull);
    });

    test('select, multiselect and boolean', () {
      final s = field('x', FormFieldType.select, options: [('a', 'أ', 'A')]);
      expect(check(s, 'a'), isNull);
      expect(check(s, 'z')?.kind, FormFieldIssueKind.invalidChoice);
      final m = field(
        'x',
        FormFieldType.multiselect,
        max: 2,
        options: [('a', 'أ', 'A'), ('b', 'ب', 'B'), ('c', 'ج', 'C')],
      );
      expect(check(m, ['a', 'b']), isNull);
      expect(
        check(m, ['a', 'b', 'c']),
        const FormFieldIssue(FormFieldIssueKind.tooManyItems, 2),
      );
      expect(check(m, ['a', 'a'])?.kind, FormFieldIssueKind.invalidChoice);
      expect(check(m, ['zz'])?.kind, FormFieldIssueKind.invalidChoice);
      expect(
        check(field('x', FormFieldType.boolean), 'yes')?.kind,
        FormFieldIssueKind.invalidChoice,
      );
    });

    test('date, time and datetime formats', () {
      final d = field('x', FormFieldType.date);
      expect(check(d, '2026-10-20'), isNull);
      expect(check(d, '2026-02-30')?.kind, FormFieldIssueKind.invalidDate);
      expect(check(d, '20-10-2026')?.kind, FormFieldIssueKind.invalidDate);
      final t = field('x', FormFieldType.time);
      expect(check(t, '23:59'), isNull);
      expect(check(t, '24:00')?.kind, FormFieldIssueKind.invalidDate);
      final dt = field('x', FormFieldType.datetime);
      expect(check(dt, '2026-10-20T09:30'), isNull);
      expect(
        check(dt, '2026-10-20 09:30')?.kind,
        FormFieldIssueKind.invalidDate,
      );
      expect(
        check(dt, '2026-10-20T25:00')?.kind,
        FormFieldIssueKind.invalidDate,
      );
    });

    test('contact: partial, invalid phone, normalised payload', () {
      final f = field('x', FormFieldType.contact);
      expect(
        check(f, {'name': 'Ali', 'phone': ''})?.kind,
        FormFieldIssueKind.incomplete,
      );
      expect(
        check(f, {'name': 'Ali', 'phone': '12'})?.kind,
        FormFieldIssueKind.invalidPhone,
      );
      expect(
        check(f, {'name': 'a' * 101, 'phone': '+201001234567'})?.kind,
        FormFieldIssueKind.tooLong,
      );
      expect(
        check(f, {'name': 'Ali', 'phone': '٠١٠٠ ١٢٣-٤٥٦٧'})?.kind,
        FormFieldIssueKind.invalidPhone,
      );
      expect(check(f, {'name': 'Ali', 'phone': '+20 100 123 4567'}), isNull);
      expect(FormLogic.normalizePhone('0020 100 123 4567'), '+201001234567');
      expect(FormLogic.normalizePhone('01001234567'), isNull);
    });

    test('photo and item checklist counts', () {
      final p = field('x', FormFieldType.photo, max: 1);
      expect(check(p, ['a']), isNull);
      expect(
        check(p, ['a', 'b']),
        const FormFieldIssue(FormFieldIssueKind.tooManyItems, 1),
      );
      expect(
        check(field('x', FormFieldType.photo), List.filled(11, 'a')),
        const FormFieldIssue(FormFieldIssueKind.tooManyItems, 10),
      );
      final c = field(
        'x',
        FormFieldType.itemChecklist,
        min: 1,
        options: [('sofa', 'ك', 'S'), ('bed', 'س', 'B')],
      );
      expect(
        check(c, [
          {'item': 'sofa', 'quantity': 2},
        ]),
        isNull,
      );
      expect(
        check(c, [
          {'item': 'piano', 'quantity': 1},
        ])?.kind,
        FormFieldIssueKind.invalidChoice,
      );
      expect(
        check(c, [
          {'item': 'sofa', 'quantity': 0},
        ])?.kind,
        FormFieldIssueKind.tooSmall,
      );
      expect(
        check(c, [
          {'item': 'sofa', 'quantity': 1000},
        ])?.kind,
        FormFieldIssueKind.tooLarge,
      );
      expect(
        check(c, [
          {'item': 'sofa', 'quantity': 1},
          {'item': 'sofa', 'quantity': 1},
        ])?.kind,
        FormFieldIssueKind.invalidChoice,
      );
    });

    test('dimensions need three positive sides within max', () {
      final f = field('x', FormFieldType.dimensions, max: 100);
      expect(check(f, {'length': 10, 'width': 5, 'height': 2.5}), isNull);
      expect(
        check(f, {'length': 10, 'width': 5})?.kind,
        FormFieldIssueKind.incomplete,
      );
      expect(
        check(f, {'length': 10, 'width': 5, 'height': 0})?.kind,
        FormFieldIssueKind.tooSmall,
      );
      expect(
        check(f, {'length': 101, 'width': 5, 'height': 1})?.kind,
        FormFieldIssueKind.tooLarge,
      );
      expect(
        check(f, {'length': 'x', 'width': 5, 'height': 1})?.kind,
        FormFieldIssueKind.notANumber,
      );
    });
  });

  group('payload', () {
    test('contains only visible, answered fields, typed for the server', () {
      final fields = [
        field('n', FormFieldType.text),
        field('i', FormFieldType.integer),
        field('d', FormFieldType.decimal),
        field('flag', FormFieldType.boolean),
        field(
          'hidden',
          FormFieldType.text,
          visibleIf: const FormFieldVisibility(
            field: 'flag',
            operator: VisibilityOperator.equals,
            value: true,
          ),
        ),
        field('skip', FormFieldType.text),
        field('who', FormFieldType.contact),
        field('dim', FormFieldType.dimensions),
        field('items', FormFieldType.itemChecklist, options: [('a', 'أ', 'A')]),
      ];
      final payload = FormLogic.payload(fields, {
        'n': '  hi  ',
        'i': '٤',
        'd': 2.5,
        'flag': false,
        'hidden': 'never sent',
        'skip': '',
        'who': {'name': ' Ali ', 'phone': '٠٠٢٠١٠٠١٢٣٤٥٦٧'},
        'dim': {'length': '10', 'width': 5, 'height': '2.5'},
        'items': [
          {'item': 'a', 'quantity': 2},
        ],
      });
      expect(payload, {
        'n': 'hi',
        'i': 4,
        'd': 2.5,
        'flag': false,
        'who': {'name': 'Ali', 'phone': '+201001234567'},
        'dim': {'length': 10, 'width': 5, 'height': 2.5},
        'items': [
          {'item': 'a', 'quantity': 2},
        ],
      });
    });

    test('visibility chains through hidden fields', () {
      final fields = [
        field('a', FormFieldType.boolean),
        field(
          'b',
          FormFieldType.integer,
          visibleIf: const FormFieldVisibility(
            field: 'a',
            operator: VisibilityOperator.equals,
            value: true,
          ),
        ),
        field(
          'c',
          FormFieldType.text,
          required: true,
          visibleIf: const FormFieldVisibility(
            field: 'b',
            operator: VisibilityOperator.greaterOrEqual,
            value: 3,
          ),
        ),
      ];
      expect(
        FormLogic.visibleFields(fields, {'a': false, 'b': 5}).map((f) => f.key),
        ['a'],
      );
      expect(FormLogic.validateAll(fields, {'a': false, 'b': 5}), isEmpty);
      expect(FormLogic.validateAll(fields, {'a': true, 'b': 5}).keys, ['c']);
    });
  });
}
