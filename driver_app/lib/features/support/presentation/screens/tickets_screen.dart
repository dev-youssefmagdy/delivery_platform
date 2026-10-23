import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../providers/support_providers.dart';
import '../widgets/support_texts.dart';

/// Help center: the person's requests (active / finished) and the way to open a
/// new one. Every state is handled by [AsyncStateView].
class TicketsScreen extends ConsumerWidget {
  const TicketsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final tickets = ref.watch(ticketsProvider);
    final filter = ref.watch(ticketFilterProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.supportTitle)),
      floatingActionButton: FloatingActionButton.extended(
        key: const Key('support-new'),
        icon: const Icon(Icons.add),
        label: Text(l10n.supportNew),
        onPressed: () => context.push(Routes.supportNew),
      ),
      body: SafeArea(
        child: AsyncStateView<TicketsState>(
          value: tickets,
          onRetry: () => ref.invalidate(ticketsProvider),
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => TicketListView(
            tickets: data.items,
            filter: filter,
            hasMore: data.hasMore,
            loadingMore: data.loadingMore,
            strings: supportStrings(l10n),
            onFilter: (value) =>
                ref.read(ticketFilterProvider.notifier).select(value),
            onLoadMore: () => ref.read(ticketsProvider.notifier).loadMore(),
            onRefresh: () async => ref.invalidate(ticketsProvider),
            onOpen: (ticket) => context.push(Routes.supportTicket(ticket.id)),
          ),
        ),
      ),
    );
  }
}
