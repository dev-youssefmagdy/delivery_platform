import '../enums/form_field_type.dart';
import 'form_schema.dart';

/// Why a field's answer is not acceptable. Mirrors the checks that the
/// backend's `ServiceFormValidator` compiles from the same schema, so the
/// client rejects what the server would reject (the server stays the
/// authority).
enum FormFieldIssueKind {
  required,
  tooShort,
  tooLong,
  tooSmall,
  tooLarge,
  notANumber,
  notAWholeNumber,
  invalidChoice,
  tooFewItems,
  tooManyItems,
  invalidPhone,
  invalidDate,
  incomplete,
}

class FormFieldIssue {
  const FormFieldIssue(this.kind, [this.limit]);

  final FormFieldIssueKind kind;

  /// The bound that was violated (length, number or item count), if any.
  final num? limit;

  @override
  bool operator ==(Object other) =>
      other is FormFieldIssue && other.kind == kind && other.limit == limit;

  @override
  int get hashCode => Object.hash(kind, limit);

  @override
  String toString() => 'FormFieldIssue($kind, $limit)';
}

/// Pure schema logic: visibility, validation and the submit payload. No
/// widgets, so it is shared with tests and non-UI callers.
abstract final class FormLogic {
  static const defaultTextMax = 255;
  static const defaultTextareaMax = 2000;
  static const defaultPhotoMax = 10;
  static const contactNameMax = 100;
  static const quantityMax = 999;
  static const _minPositive = 0.01;
  static const dimensionSides = ['length', 'width', 'height'];

  /// Fields shown for [values], in schema order. A field depending on a
  /// hidden field is evaluated against "no answer" (same as the server).
  static List<FormFieldSchema> visibleFields(
    List<FormFieldSchema> fields,
    Map<String, Object?> values,
  ) {
    final visible = <FormFieldSchema>[];
    final answers = <String, Object?>{};
    for (final f in fields) {
      final rule = f.visibleIf;
      if (rule != null && !rule.isSatisfiedBy(answers)) continue;
      visible.add(f);
      if (values.containsKey(f.key)) answers[f.key] = values[f.key];
    }
    return visible;
  }

  /// Issues of every visible field (hidden fields are never validated).
  static Map<String, FormFieldIssue> validateAll(
    List<FormFieldSchema> fields,
    Map<String, Object?> values,
  ) {
    final issues = <String, FormFieldIssue>{};
    for (final f in visibleFields(fields, values)) {
      final issue = validate(f, values[f.key]);
      if (issue != null) issues[f.key] = issue;
    }
    return issues;
  }

  static FormFieldIssue? validate(FormFieldSchema field, Object? value) {
    if (isEmpty(field, value)) {
      return field.required
          ? const FormFieldIssue(FormFieldIssueKind.required)
          : null;
    }
    switch (field.type) {
      case FormFieldType.text:
        return _length(value, field.min, field.max ?? defaultTextMax);
      case FormFieldType.textarea:
        return _length(value, field.min, field.max ?? defaultTextareaMax);
      case FormFieldType.integer:
        return _number(value, field.min, field.max, whole: true);
      case FormFieldType.decimal:
        return _number(value, field.min, field.max);
      case FormFieldType.weight:
        return _number(value, field.min ?? _minPositive, field.max);
      case FormFieldType.boolean:
        return value is bool
            ? null
            : const FormFieldIssue(FormFieldIssueKind.invalidChoice);
      case FormFieldType.select:
        return field.optionFor('$value') == null
            ? const FormFieldIssue(FormFieldIssueKind.invalidChoice)
            : null;
      case FormFieldType.multiselect:
        return _multiselect(field, value);
      case FormFieldType.date:
        return _dateLike(value, _dateFormat);
      case FormFieldType.time:
        return _dateLike(value, _timeFormat);
      case FormFieldType.datetime:
        return _dateLike(value, _datetimeFormat);
      case FormFieldType.contact:
        return _contact(value);
      case FormFieldType.photo:
        return _count(
          value is List ? value.length : 0,
          field.min,
          field.max ?? defaultPhotoMax,
        );
      case FormFieldType.itemChecklist:
        return _checklist(field, value);
      case FormFieldType.dimensions:
        return _dimensions(field, value);
      case FormFieldType.unknown:
        return null;
    }
  }

  /// True when the field has no answer (an unanswered optional field passes).
  static bool isEmpty(FormFieldSchema field, Object? value) {
    if (value == null) return true;
    if (value is String) return value.trim().isEmpty;
    if (value is List) return value.isEmpty;
    if (value is Map) {
      return value.values.every((v) => v == null || '$v'.trim().isEmpty);
    }
    return false;
  }

  /// The `service_details` payload: visible, answered fields only, typed the
  /// way the server expects. Call after [validateAll] reports no issues.
  static Map<String, Object?> payload(
    List<FormFieldSchema> fields,
    Map<String, Object?> values,
  ) {
    final result = <String, Object?>{};
    for (final f in visibleFields(fields, values)) {
      final value = values[f.key];
      if (isEmpty(f, value)) continue;
      result[f.key] = _normalise(f, value);
    }
    return result;
  }

  static Object? _normalise(FormFieldSchema f, Object? value) {
    switch (f.type) {
      case FormFieldType.text:
      case FormFieldType.textarea:
        return (value! as String).trim();
      case FormFieldType.integer:
        return value is int ? value : parseNumber('$value')?.toInt();
      case FormFieldType.decimal:
      case FormFieldType.weight:
        return value is num ? value : parseNumber('$value');
      case FormFieldType.contact:
        final m = value! as Map;
        return {
          'name': '${m['name']}'.trim(),
          'phone': normalizePhone('${m['phone']}') ?? '${m['phone']}',
        };
      case FormFieldType.dimensions:
        final m = value! as Map;
        return {
          for (final side in dimensionSides)
            side: m[side] is num ? m[side] : parseNumber('${m[side]}'),
        };
      case FormFieldType.itemChecklist:
        return [
          for (final e in value! as List)
            {
              'item': '${(e as Map)['item']}',
              'quantity': e['quantity'] is int
                  ? e['quantity']
                  : parseNumber('${e['quantity']}')?.toInt(),
              ..._itemExtras(e),
            },
        ];
      default:
        return value;
    }
  }

  /// Optional per-item facts of a checklist row (moving): note, weight and
  /// dimensions in cm. Anything unparsable is left out; the server validates
  /// what is sent.
  static Map<String, Object?> _itemExtras(Map row) {
    final extras = <String, Object?>{};
    final note = '${row['note'] ?? ''}'.trim();
    if (note.isNotEmpty) extras['note'] = note;
    final weight = row['weight_kg'] == null
        ? null
        : (row['weight_kg'] is num
              ? row['weight_kg'] as num
              : parseNumber('${row['weight_kg']}'));
    if (weight != null) extras['weight_kg'] = weight;
    final dims = row['dimensions'];
    if (dims is Map) {
      final parsed = {
        for (final side in dimensionSides)
          side: dims[side] is num
              ? dims[side] as num
              : parseNumber('${dims[side] ?? ''}'),
      };
      if (parsed.values.every((v) => v != null)) extras['dimensions'] = parsed;
    }
    return extras;
  }

  static FormFieldIssue? _length(Object? value, num? min, num max) {
    final length = (value! as String).trim().runes.length;
    if (min != null && length < min) {
      return FormFieldIssue(FormFieldIssueKind.tooShort, min);
    }
    if (length > max) return FormFieldIssue(FormFieldIssueKind.tooLong, max);
    return null;
  }

  static FormFieldIssue? _number(
    Object? value,
    num? min,
    num? max, {
    bool whole = false,
  }) {
    final number = value is num ? value : parseNumber('$value');
    if (number == null) {
      return const FormFieldIssue(FormFieldIssueKind.notANumber);
    }
    if (whole && number != number.truncate()) {
      return const FormFieldIssue(FormFieldIssueKind.notAWholeNumber);
    }
    if (min != null && number < min) {
      return FormFieldIssue(FormFieldIssueKind.tooSmall, min);
    }
    if (max != null && number > max) {
      return FormFieldIssue(FormFieldIssueKind.tooLarge, max);
    }
    return null;
  }

  static FormFieldIssue? _count(int count, num? min, num max) {
    if (min != null && count < min) {
      return FormFieldIssue(FormFieldIssueKind.tooFewItems, min);
    }
    if (count > max) {
      return FormFieldIssue(FormFieldIssueKind.tooManyItems, max);
    }
    return null;
  }

  static FormFieldIssue? _multiselect(FormFieldSchema field, Object? value) {
    if (value is! List) {
      return const FormFieldIssue(FormFieldIssueKind.invalidChoice);
    }
    final seen = <String>{};
    for (final v in value) {
      if (field.optionFor('$v') == null || !seen.add('$v')) {
        return const FormFieldIssue(FormFieldIssueKind.invalidChoice);
      }
    }
    return _count(value.length, field.min, field.max ?? field.options.length);
  }

  static final _dateFormat = RegExp(r'^(\d{4})-(\d{2})-(\d{2})$');
  static final _timeFormat = RegExp(r'^(\d{2}):(\d{2})$');
  static final _datetimeFormat = RegExp(
    r'^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$',
  );

  static FormFieldIssue? _dateLike(Object? value, RegExp format) {
    final m = value is String ? format.firstMatch(value) : null;
    if (m == null) return const FormFieldIssue(FormFieldIssueKind.invalidDate);
    final parts = [for (var i = 1; i <= m.groupCount; i++) int.parse(m[i]!)];
    final hasDate = format != _timeFormat;
    final timeAt = format == _timeFormat
        ? 0
        : (format == _datetimeFormat ? 3 : -1);
    if (hasDate) {
      final d = DateTime(parts[0], parts[1], parts[2]);
      if (d.year != parts[0] || d.month != parts[1] || d.day != parts[2]) {
        return const FormFieldIssue(FormFieldIssueKind.invalidDate);
      }
    }
    if (timeAt >= 0 && (parts[timeAt] > 23 || parts[timeAt + 1] > 59)) {
      return const FormFieldIssue(FormFieldIssueKind.invalidDate);
    }
    return null;
  }

  static FormFieldIssue? _contact(Object? value) {
    final m = value is Map ? value : const {};
    final name = '${m['name'] ?? ''}'.trim();
    final phone = '${m['phone'] ?? ''}'.trim();
    if (name.isEmpty || phone.isEmpty) {
      return const FormFieldIssue(FormFieldIssueKind.incomplete);
    }
    if (name.runes.length > contactNameMax) {
      return const FormFieldIssue(FormFieldIssueKind.tooLong, contactNameMax);
    }
    if (normalizePhone(phone) == null) {
      return const FormFieldIssue(FormFieldIssueKind.invalidPhone);
    }
    return null;
  }

  static FormFieldIssue? _checklist(FormFieldSchema field, Object? value) {
    if (value is! List) {
      return const FormFieldIssue(FormFieldIssueKind.invalidChoice);
    }
    final seen = <String>{};
    for (final entry in value) {
      if (entry is! Map ||
          field.optionFor('${entry['item']}') == null ||
          !seen.add('${entry['item']}')) {
        return const FormFieldIssue(FormFieldIssueKind.invalidChoice);
      }
      final quantity = entry['quantity'] is num
          ? entry['quantity'] as num
          : parseNumber('${entry['quantity']}');
      if (quantity == null || quantity != quantity.truncate()) {
        return const FormFieldIssue(FormFieldIssueKind.notAWholeNumber);
      }
      if (quantity < 1) {
        return const FormFieldIssue(FormFieldIssueKind.tooSmall, 1);
      }
      if (quantity > quantityMax) {
        return const FormFieldIssue(FormFieldIssueKind.tooLarge, quantityMax);
      }
    }
    return _count(value.length, field.min, field.max ?? field.options.length);
  }

  static FormFieldIssue? _dimensions(FormFieldSchema field, Object? value) {
    final m = value is Map ? value : const {};
    final min = field.min ?? _minPositive;
    for (final side in dimensionSides) {
      final raw = m[side];
      if (raw == null || '$raw'.trim().isEmpty) {
        return const FormFieldIssue(FormFieldIssueKind.incomplete);
      }
      final issue = _number(raw, min, field.max);
      if (issue != null) return issue;
    }
    return null;
  }

  /// Parses typed numbers, accepting Arabic-Indic digits and separators.
  static num? parseNumber(String raw) {
    final text = _latinDigits(raw).trim().replaceAll(RegExp(r'[,٬\s]'), '');
    return num.tryParse(text.replaceAll('٫', '.'));
  }

  /// `+201001234567` style E.164 (same rule as the backend `PhoneNumber`).
  static String? normalizePhone(String raw) {
    var digits = _latinDigits(raw.trim()).replaceAll(RegExp(r'[\s\-().]'), '');
    if (digits.startsWith('00')) digits = '+${digits.substring(2)}';
    return RegExp(r'^\+[1-9][0-9]{7,14}$').hasMatch(digits) ? digits : null;
  }

  static String _latinDigits(String input) {
    const arabic = '٠١٢٣٤٥٦٧٨٩';
    const persian = '۰۱۲۳۴۵۶۷۸۹';
    final out = StringBuffer();
    for (final rune in input.runes) {
      final ch = String.fromCharCode(rune);
      final a = arabic.indexOf(ch);
      final p = persian.indexOf(ch);
      out.write(a >= 0 ? a : (p >= 0 ? p : ch));
    }
    return out.toString();
  }
}
