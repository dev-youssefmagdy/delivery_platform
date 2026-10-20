import '../enums/form_field_type.dart';
import '../enums/visibility_operator.dart';

/// Text in several languages (`{ar: ..., en: ...}`), as the API sends names,
/// labels and help texts. Falls back to Arabic, then English, then anything.
class LocalizedText {
  const LocalizedText(this.values);

  factory LocalizedText.fromJson(Object? json) {
    if (json is! Map) return const LocalizedText({});
    return LocalizedText({
      for (final e in json.entries)
        if (e.value is String && (e.value as String).trim().isNotEmpty)
          '${e.key}': (e.value as String).trim(),
    });
  }

  /// Single-language text, handy for tests.
  factory LocalizedText.single(String text, {String language = 'ar'}) =>
      LocalizedText({language: text});

  final Map<String, String> values;

  bool get isEmpty => values.isEmpty;

  String resolve(String languageCode) =>
      values[languageCode] ??
      values['ar'] ??
      values['en'] ??
      (values.isEmpty ? '' : values.values.first);

  String? resolveOrNull(String languageCode) =>
      isEmpty ? null : resolve(languageCode);
}

class FormFieldOption {
  const FormFieldOption({required this.value, required this.label});

  factory FormFieldOption.fromJson(Map<String, dynamic> json) =>
      FormFieldOption(
        value: '${json['value']}',
        label: LocalizedText.fromJson(json['label']),
      );

  final String value;
  final LocalizedText label;
}

/// `visible_if`: the field is shown (and validated) only while the controlling
/// field satisfies the condition.
class FormFieldVisibility {
  const FormFieldVisibility({
    required this.field,
    required this.operator,
    this.value,
  });

  static FormFieldVisibility? fromJson(Object? json) {
    if (json is! Map) return null;
    return FormFieldVisibility(
      field: '${json['field']}',
      operator: VisibilityOperator.parse(json['op'] as String?),
      value: json['value'],
    );
  }

  final String field;
  final VisibilityOperator operator;
  final Object? value;

  bool isSatisfiedBy(Map<String, Object?> answers) =>
      operator.matches(answers[field], value);
}

/// One field of a server-defined service form (docs/API.md 8.6). This is the
/// rendering contract shared with the backend's `ServiceFormValidator`.
class FormFieldSchema {
  const FormFieldSchema({
    required this.key,
    required this.type,
    required this.label,
    this.help = const LocalizedText({}),
    this.required = false,
    this.min,
    this.max,
    this.options = const [],
    this.visibleIf,
  });

  factory FormFieldSchema.fromJson(Map<String, dynamic> json) =>
      FormFieldSchema(
        key: '${json['key']}',
        type: FormFieldType.parse(json['type'] as String?),
        label: LocalizedText.fromJson(json['label']),
        help: LocalizedText.fromJson(json['help']),
        required: json['required'] == true,
        min: json['min'] as num?,
        max: json['max'] as num?,
        options: [
          for (final o in (json['options'] as List?) ?? const [])
            if (o is Map)
              FormFieldOption.fromJson(Map<String, dynamic>.from(o)),
        ],
        visibleIf: FormFieldVisibility.fromJson(json['visible_if']),
      );

  final String key;
  final FormFieldType type;
  final LocalizedText label;
  final LocalizedText help;
  final bool required;
  final num? min;
  final num? max;
  final List<FormFieldOption> options;
  final FormFieldVisibility? visibleIf;

  FormFieldOption? optionFor(String value) {
    for (final o in options) {
      if (o.value == value) return o;
    }
    return null;
  }
}

/// A published form schema version (`form_schema` of a service category).
class ServiceFormSchema {
  const ServiceFormSchema({required this.version, required this.fields});

  factory ServiceFormSchema.fromJson(Object? json) {
    if (json is! Map) return const ServiceFormSchema(version: 0, fields: []);
    return ServiceFormSchema(
      version: (json['version'] as num?)?.toInt() ?? 0,
      fields: [
        for (final f in (json['fields'] as List?) ?? const [])
          if (f is Map) FormFieldSchema.fromJson(Map<String, dynamic>.from(f)),
      ],
    );
  }

  final int version;
  final List<FormFieldSchema> fields;
}
