import 'package:flutter/foundation.dart';

import 'form_logic.dart';
import 'form_schema.dart';

/// State of a dynamic service form: answers, visibility, validation.
///
/// Issues of a field show once the user touched it or after [validate] was
/// called (pressing "continue"), so an untouched form is not covered in red.
class DynamicFormController extends ChangeNotifier {
  DynamicFormController({
    required List<FormFieldSchema> fields,
    Map<String, Object?> initialValues = const {},
  }) : _fields = List.unmodifiable(fields),
       _values = {...initialValues};

  final List<FormFieldSchema> _fields;
  final Map<String, Object?> _values;
  final Set<String> _touched = {};
  bool _submitted = false;

  List<FormFieldSchema> get fields => _fields;

  /// All answers, including those of currently hidden fields.
  Map<String, Object?> get values => Map.unmodifiable(_values);

  List<FormFieldSchema> get visibleFields =>
      FormLogic.visibleFields(_fields, _values);

  Object? valueOf(String key) => _values[key];

  /// Issues the user should currently see.
  Map<String, FormFieldIssue> get issues {
    final all = FormLogic.validateAll(_fields, _values);
    if (_submitted) return all;
    return {
      for (final e in all.entries)
        if (_touched.contains(e.key)) e.key: e.value,
    };
  }

  bool get isValid => FormLogic.validateAll(_fields, _values).isEmpty;

  /// The `service_details` payload (visible, answered fields).
  Map<String, Object?> get payload => FormLogic.payload(_fields, _values);

  void setValue(String key, Object? value) {
    _values[key] = value;
    _touched.add(key);
    notifyListeners();
  }

  void touch(String key) {
    if (_touched.add(key)) notifyListeners();
  }

  /// Shows every issue; returns true when the form can be submitted.
  bool validate() {
    _submitted = true;
    notifyListeners();
    return isValid;
  }
}
