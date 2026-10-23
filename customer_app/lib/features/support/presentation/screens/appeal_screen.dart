import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../providers/support_providers.dart';

/// Dispute a cancellation fee: the reason becomes a support ticket that carries
/// the fee decision the customer was shown.
class AppealScreen extends ConsumerStatefulWidget {
  const AppealScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  ConsumerState<AppealScreen> createState() => _AppealScreenState();
}

class _AppealScreenState extends ConsumerState<AppealScreen> {
  final _reason = TextEditingController();
  bool _busy = false;
  String? _error;
  String? _key;

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final l10n = AppLocalizations.of(context);
    if (_reason.text.trim().length < 10) {
      setState(() => _error = l10n.supportAppealShort);
      return;
    }
    final router = GoRouter.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    _key ??= newClientKey();
    try {
      final ticket = await ref
          .read(supportActionsProvider)
          .appeal(widget.bookingId, _reason.text.trim(), _key!);
      messenger.showSnackBar(SnackBar(content: Text(l10n.supportAppealSent)));
      router.pushReplacement(Routes.supportTicket(ticket.id));
    } on Object catch (e) {
      if (!mounted) return;
      if (e is ApiException &&
          e.statusCode != null &&
          e.statusCode! < 500 &&
          !e.isOffline) {
        _key = null;
      }
      setState(() {
        _busy = false;
        _error = supportErrorText(l10n, e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.supportAppealTitle)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          children: [
            Text(l10n.supportAppealIntro),
            const SizedBox(height: AppSpacing.md),
            TextField(
              key: const Key('appeal-reason'),
              controller: _reason,
              minLines: 4,
              maxLines: 8,
              maxLength: 4000,
              decoration: InputDecoration(
                labelText: l10n.supportAppealReason,
                border: const OutlineInputBorder(),
                alignLabelWithHint: true,
              ),
            ),
            if (_error != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                child: Text(
                  _error!,
                  key: const Key('appeal-error'),
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              key: const Key('appeal-submit'),
              label: l10n.supportAppealSubmit,
              loading: _busy,
              onPressed: _submit,
            ),
          ],
        ),
      ),
    );
  }
}
