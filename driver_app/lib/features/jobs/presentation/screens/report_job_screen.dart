import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/job_providers.dart';
import '../widgets/job_texts.dart';

/// Report a problem with a job: one category, a short description. Input
/// survives errors; a repeated send of the same report reuses its key.
class ReportJobScreen extends ConsumerStatefulWidget {
  const ReportJobScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  ConsumerState<ReportJobScreen> createState() => _ReportJobScreenState();
}

class _ReportJobScreenState extends ConsumerState<ReportJobScreen> {
  DriverReportCategory? _category;
  final _description = TextEditingController();
  bool _busy = false;
  bool _sent = false;
  Object? _error;

  @override
  void dispose() {
    _description.dispose();
    super.dispose();
  }

  bool get _valid => _category != null && _description.text.trim().length >= 10;

  Future<void> _submit() async {
    if (_busy || !_valid) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref
          .read(jobActionsProvider)
          .report(
            widget.bookingId,
            category: _category!,
            description: _description.text.trim(),
          );
      if (mounted) setState(() => _sent = true);
    } on Object catch (e) {
      if (mounted) setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.jbReportTitle)),
      body: SafeArea(
        child: _sent
            ? EmptyState(
                key: const Key('report-sent'),
                icon: Icons.check_circle_outline,
                title: l10n.jbReportSentTitle,
                message: l10n.jbReportSentBody,
                actionLabel: l10n.actionBack,
                onAction: () => context.pop(),
              )
            : ListView(
                padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                children: [
                  Text(l10n.jbReportPrompt, style: theme.textTheme.titleMedium),
                  RadioGroup<DriverReportCategory>(
                    groupValue: _category,
                    onChanged: (v) {
                      if (!_busy) setState(() => _category = v);
                    },
                    child: Column(
                      children: [
                        for (final c in DriverReportCategory.values)
                          RadioListTile<DriverReportCategory>(
                            key: Key('report-category-${c.value}'),
                            value: c,
                            title: Text(
                              reportCategoryText(l10n, c),
                              style: theme.textTheme.titleMedium,
                            ),
                          ),
                      ],
                    ),
                  ),
                  TextField(
                    key: const Key('report-description'),
                    controller: _description,
                    maxLines: 4,
                    maxLength: 1000,
                    onChanged: (_) => setState(() {}),
                    decoration: InputDecoration(
                      labelText: l10n.jbReportDescription,
                      helperText: l10n.jbReportDescriptionHelp,
                    ),
                  ),
                  if (_error != null)
                    Text(
                      jobErrorText(l10n, _error!),
                      key: const Key('report-error'),
                      style: TextStyle(color: theme.colorScheme.error),
                    ),
                  const SizedBox(height: AppSpacing.md),
                  AppButton(
                    key: const Key('report-submit'),
                    label: l10n.jbReportSubmit,
                    loading: _busy,
                    onPressed: _valid ? _submit : null,
                  ),
                ],
              ),
      ),
    );
  }
}
