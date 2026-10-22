import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../providers/support_providers.dart';
import '../widgets/support_texts.dart';

/// Contact support: optionally about one booking (opened from the booking screen).
class NewTicketScreen extends ConsumerStatefulWidget {
  const NewTicketScreen({super.key, this.bookingId, this.bookingCode, this.paymentId, this.payoutId});

  /// Money questions link the payment or payout (ids the person owns).
  final String? paymentId;
  final String? payoutId;

  final String? bookingId;
  final String? bookingCode;

  @override
  ConsumerState<NewTicketScreen> createState() => _NewTicketScreenState();
}

class _NewTicketScreenState extends ConsumerState<NewTicketScreen> {
  bool _busy = false;
  String? _error;
  String? _key;

  Future<void> _submit(String category, String subject, String body, List<String> paths) async {
    final l10n = AppLocalizations.of(context);
    final router = GoRouter.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    _key ??= newClientKey();
    try {
      final ticket = await ref.read(supportActionsProvider).open(
        NewTicketInput(category: category, subject: subject, body: body, bookingId: widget.bookingId, paymentId: widget.paymentId, payoutId: widget.payoutId, attachmentPaths: paths),
        _key!,
      );
      messenger.showSnackBar(SnackBar(content: Text(l10n.supportSent)));
      router.pushReplacement(Routes.supportTicket(ticket.id));
    } on Object catch (e) {
      if (!mounted) return;
      if (e is ApiException && e.statusCode != null && e.statusCode! < 500 && !e.isOffline) _key = null;
      setState(() {
        _busy = false;
        _error = supportErrorText(l10n, e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final categories = ref.watch(ticketCategoriesProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.supportNew)),
      body: SafeArea(
        child: AsyncStateView<List<TicketCategoryOption>>(
          value: categories,
          onRetry: () => ref.invalidate(ticketCategoriesProvider),
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => NewTicketForm(
            categories: data,
            strings: supportStrings(l10n),
            bookingCode: widget.bookingCode,
            submitting: _busy,
            error: _error,
            onSubmit: _submit,
          ),
        ),
      ),
    );
  }
}
