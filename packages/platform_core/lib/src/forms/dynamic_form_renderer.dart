import 'package:flutter/material.dart';

import '../enums/form_field_type.dart';
import 'form_schema.dart';

/// Renders a list of [FormFieldSchema] and reports value changes as a map.
///
/// Supported now: text, textarea, integer, decimal, boolean, select,
/// multiselect. The remaining [FormFieldType]s (date, time, datetime, contact,
/// photo, item_checklist, dimensions, weight) are rendered by the milestones
/// that need them (P1.4/P2.x/P4.x); [supports] lets callers check, and
/// unsupported fields are skipped, never shown as dead controls.
class DynamicFormRenderer extends StatefulWidget {
  const DynamicFormRenderer({
    super.key,
    required this.fields,
    required this.onChanged,
    required this.requiredMessage,
    this.initialValues = const {},
  });

  final List<FormFieldSchema> fields;
  final Map<String, Object?> initialValues;
  final ValueChanged<Map<String, Object?>> onChanged;

  /// Localized "this field is required" message supplied by the caller.
  final String requiredMessage;

  static const _supported = {
    FormFieldType.text,
    FormFieldType.textarea,
    FormFieldType.integer,
    FormFieldType.decimal,
    FormFieldType.boolean,
    FormFieldType.select,
    FormFieldType.multiselect,
  };

  static bool supports(FormFieldType type) => _supported.contains(type);

  @override
  State<DynamicFormRenderer> createState() => _DynamicFormRendererState();
}

class _DynamicFormRendererState extends State<DynamicFormRenderer> {
  late final Map<String, Object?> _values = {...widget.initialValues};

  void _set(String key, Object? value) {
    setState(() => _values[key] = value);
    widget.onChanged(Map.unmodifiable(_values));
  }

  @override
  Widget build(BuildContext context) {
    final fields = widget.fields.where(
      (f) => DynamicFormRenderer.supports(f.type),
    );
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        for (final f in fields)
          Padding(
            padding: const EdgeInsetsDirectional.only(bottom: 16),
            child: _build(f),
          ),
      ],
    );
  }

  Widget _build(FormFieldSchema f) {
    final validator = f.required
        ? (String? v) =>
              (v == null || v.trim().isEmpty) ? widget.requiredMessage : null
        : null;
    final deco = InputDecoration(labelText: f.label, hintText: f.hint);
    switch (f.type) {
      case FormFieldType.textarea:
        return TextFormField(
          initialValue: _values[f.key] as String?,
          decoration: deco,
          minLines: 3,
          maxLines: 6,
          validator: validator,
          onChanged: (v) => _set(f.key, v),
        );
      case FormFieldType.integer:
      case FormFieldType.decimal:
        final isInt = f.type == FormFieldType.integer;
        return TextFormField(
          initialValue: _values[f.key]?.toString(),
          decoration: deco,
          keyboardType: TextInputType.numberWithOptions(decimal: !isInt),
          validator: validator,
          onChanged: (v) =>
              _set(f.key, isInt ? int.tryParse(v) : double.tryParse(v)),
        );
      case FormFieldType.boolean:
        return SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(f.label),
          value: (_values[f.key] as bool?) ?? false,
          onChanged: (v) => _set(f.key, v),
        );
      case FormFieldType.select:
        return DropdownButtonFormField<String>(
          initialValue: _values[f.key] as String?,
          decoration: deco,
          items: [
            for (final o in f.options)
              DropdownMenuItem(value: o.value, child: Text(o.label)),
          ],
          validator: f.required
              ? (v) => v == null ? widget.requiredMessage : null
              : null,
          onChanged: (v) => _set(f.key, v),
        );
      case FormFieldType.multiselect:
        final selected = {...((_values[f.key] as List?)?.cast<String>() ?? [])};
        return InputDecorator(
          decoration: deco,
          child: Wrap(
            spacing: 8,
            children: [
              for (final o in f.options)
                FilterChip(
                  label: Text(o.label),
                  selected: selected.contains(o.value),
                  onSelected: (on) {
                    on ? selected.add(o.value) : selected.remove(o.value);
                    _set(f.key, selected.toList());
                  },
                ),
            ],
          ),
        );
      default:
        return TextFormField(
          initialValue: _values[f.key] as String?,
          decoration: deco,
          validator: validator,
          onChanged: (v) => _set(f.key, v),
        );
    }
  }
}
