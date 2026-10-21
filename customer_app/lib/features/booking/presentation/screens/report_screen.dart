import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/booking_entities.dart';
import '../providers/booking_providers.dart';
import '../widgets/booking_texts.dart';

/// Report an issue with a trip (category and description).
class ReportScreen extends ConsumerStatefulWidget {
  const ReportScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  ConsumerState<ReportScreen> createState() => _ReportScreenState();
}

class _ReportScreenState extends ConsumerState<ReportScreen> {
  final _description = TextEditingController();
  ReportCategory? _category;
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _description.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final l10n = AppLocalizations.of(context);
    final category = _category;
    if (category == null || _description.text.trim().length < 10) {
      setState(() => _error = category == null
          ? l10n.bkReportPickCategory
          : l10n.bkReportDescriptionShort);
      return;
    }
    final router = GoRouter.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref
          .read(bookingActionsProvider)
          .report(
            widget.bookingId,
            category: category,
            description: _description.text.trim(),
          );
      messenger.showSnackBar(SnackBar(content: Text(l10n.bkReportSent)));
      router.pop();
    } on Object catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = bookingErrorText(l10n, e);
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.bkReportTitle)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          children: [
            RadioGroup<ReportCategory>(
              groupValue: _category,
              onChanged: (v) {
                if (!_busy) setState(() => _category = v);
              },
              child: Column(
                children: [
                  for (final c in ReportCategory.values)
                    RadioListTile<ReportCategory>(
                      key: Key('report-category-${c.value}'),
                      contentPadding: EdgeInsets.zero,
                      value: c,
                      title: Text(reportCategoryText(l10n, c)),
                    ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              key: const Key('report-description'),
              controller: _description,
              enabled: !_busy,
              maxLines: 5,
              maxLength: 2000,
              decoration: InputDecoration(labelText: l10n.bkReportDescription),
            ),
            if (_error != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
                child: Text(
                  _error!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            AppButton(
              key: const Key('report-submit'),
              label: l10n.bkReportSubmit,
              loading: _busy,
              onPressed: _submit,
            ),
          ],
        ),
      ),
    );
  }
}
