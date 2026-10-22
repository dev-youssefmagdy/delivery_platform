import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../providers/support_providers.dart';
import '../widgets/support_texts.dart';

/// One request with its conversation and a reply box.
class TicketDetailScreen extends ConsumerStatefulWidget {
  const TicketDetailScreen({super.key, required this.ticketId});

  final String ticketId;

  @override
  ConsumerState<TicketDetailScreen> createState() => _TicketDetailScreenState();
}

class _TicketDetailScreenState extends ConsumerState<TicketDetailScreen> {
  bool _sending = false;
  String? _error;
  String? _key;

  Future<bool> _reply(String body, List<String> paths) async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _sending = true;
      _error = null;
    });
    // One key per intent: a retry after a timeout reuses it so the reply is stored once.
    _key ??= newClientKey();
    try {
      await ref.read(ticketDetailProvider(widget.ticketId).notifier).reply(body, paths, _key!);
      _key = null;
      if (mounted) setState(() => _sending = false);
      return true;
    } on Object catch (e) {
      if (mounted) {
        setState(() {
          _sending = false;
          _error = supportErrorText(l10n, e);
        });
      }
      if (e is ApiException && e.statusCode != null && e.statusCode! < 500 && !e.isOffline) _key = null;
      return false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final ticket = ref.watch(ticketDetailProvider(widget.ticketId));
    final notifier = ref.read(ticketDetailProvider(widget.ticketId).notifier);

    return Scaffold(
      appBar: AppBar(
        title: Text(ticket.value == null ? l10n.supportTicketTitle : l10n.supportTicketNumber(ticket.value!.number)),
        leading: BackButton(onPressed: () => context.canPop() ? context.pop() : context.go(Routes.support)),
        actions: [
          IconButton(key: const Key('ticket-refresh'), tooltip: l10n.actionRefresh, icon: const Icon(Icons.refresh), onPressed: notifier.refresh),
        ],
      ),
      body: SafeArea(
        child: AsyncStateView<SupportTicket>(
          value: ticket,
          onRetry: () => ref.invalidate(ticketDetailProvider(widget.ticketId)),
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => TicketDetailView(
            ticket: data,
            strings: supportStrings(l10n),
            sending: _sending,
            error: _error,
            onReply: _reply,
            loadImage: ref.read(supportRepositoryProvider).loadImage,
          ),
        ),
      ),
    );
  }
}
