import 'package:flutter/material.dart';

import '../ui/tokens.dart';
import 'dynamic_form_controller.dart';
import 'dynamic_form_strings.dart';
import 'form_field_widgets.dart';
import 'photo_field_handler.dart';

/// Renders a service form schema (Plan §2.3) for every `FormFieldType`: the
/// same schema the backend validates. Shows only fields whose `visible_if`
/// holds, with Arabic/English labels and Directional layout, and reports all
/// state through the [controller] (answers, issues, payload).
///
/// There is no per-service branching: a new service category only needs a
/// new schema on the server.
class DynamicFormRenderer extends StatelessWidget {
  const DynamicFormRenderer({
    super.key,
    required this.controller,
    required this.languageCode,
    required this.strings,
    this.photoHandler,
  });

  final DynamicFormController controller;

  /// Language for labels, help texts and option names (`ar` / `en`).
  final String languageCode;
  final DynamicFormStrings strings;

  /// Needed only for schemas that contain `photo` fields; without it those
  /// fields render without an add button.
  final PhotoFieldHandler? photoHandler;

  @override
  Widget build(BuildContext context) => ListenableBuilder(
    listenable: controller,
    builder: (context, _) {
      final issues = controller.issues;
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          for (final field in controller.visibleFields)
            Padding(
              key: ValueKey('form-field-${field.key}'),
              padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.md),
              child: FormFieldView(
                field: field,
                value: controller.valueOf(field.key),
                error: issues[field.key] == null
                    ? null
                    : strings.messageFor(issues[field.key]!),
                languageCode: languageCode,
                strings: strings,
                photoHandler: photoHandler,
                onChanged: (v) => controller.setValue(field.key, v),
              ),
            ),
        ],
      );
    },
  );
}
