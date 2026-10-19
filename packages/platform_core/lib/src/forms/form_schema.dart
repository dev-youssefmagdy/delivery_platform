import '../enums/form_field_type.dart';

class FormFieldOption {
  const FormFieldOption({required this.value, required this.label});
  final String value;
  final String label;
}

/// One field of a server-defined service form schema (versioned snapshots
/// arrive in P1.4; this is the rendering contract).
class FormFieldSchema {
  const FormFieldSchema({
    required this.key,
    required this.type,
    required this.label,
    this.required = false,
    this.hint,
    this.options = const [],
  });

  final String key;
  final FormFieldType type;
  final String label;
  final bool required;
  final String? hint;
  final List<FormFieldOption> options;
}
