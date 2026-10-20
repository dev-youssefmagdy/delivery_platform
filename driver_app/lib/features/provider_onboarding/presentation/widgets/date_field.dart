import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import 'onboarding_labels.dart';

/// A read-only field that opens the date picker. The value is shown in the
/// app language; pass [onClear] to let the user empty an optional date.
class DateField extends StatelessWidget {
  const DateField({
    super.key,
    required this.label,
    required this.value,
    required this.onPicked,
    required this.firstDate,
    required this.lastDate,
    this.initialDate,
    this.onClear,
    this.errorText,
    this.fieldKey,
  });

  final String label;
  final DateTime? value;
  final ValueChanged<DateTime> onPicked;
  final DateTime firstDate;
  final DateTime lastDate;
  final DateTime? initialDate;
  final VoidCallback? onClear;
  final String? errorText;
  final Key? fieldKey;

  Future<void> _pick(BuildContext context) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: value ?? initialDate ?? lastDate,
      firstDate: firstDate,
      lastDate: lastDate,
    );
    if (picked != null) onPicked(picked);
  }

  @override
  Widget build(BuildContext context) => InkWell(
    key: fieldKey,
    onTap: () => _pick(context),
    borderRadius: BorderRadius.circular(AppRadius.sm),
    child: InputDecorator(
      decoration: InputDecoration(
        labelText: label,
        errorText: errorText,
        suffixIcon: value != null && onClear != null
            ? IconButton(
                icon: const Icon(Icons.clear),
                onPressed: onClear,
                tooltip: MaterialLocalizations.of(context).deleteButtonTooltip,
              )
            : const Icon(Icons.calendar_today_outlined),
      ),
      child: Text(
        value == null ? '' : formatDay(context, value!),
        style: Theme.of(context).textTheme.bodyLarge,
      ),
    ),
  );
}
