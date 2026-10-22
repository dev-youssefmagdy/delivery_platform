import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../providers/support_providers.dart';
import '../widgets/support_texts.dart';

/// Chat with the selected driver for one booking. Only the two sides of the
/// booking can open it; after the window closes it stays readable.
class ChatScreen extends ConsumerWidget {
  const ChatScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final chat = ref.watch(chatProvider(bookingId));
    final notifier = ref.read(chatProvider(bookingId).notifier);
    final forbidden = chat.error is ApiException &&
        ((chat.error! as ApiException).kind == ApiFailureKind.forbidden || (chat.error! as ApiException).kind == ApiFailureKind.notFound);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.chatTitle),
        leading: BackButton(onPressed: () => context.canPop() ? context.pop() : context.go(Routes.booking(bookingId))),
      ),
      body: SafeArea(
        child: forbidden
            ? EmptyState(
                key: const Key('chat-unavailable'),
                icon: Icons.chat_bubble_outline,
                title: l10n.chatUnavailableTitle,
                message: l10n.chatUnavailableBody,
                actionLabel: l10n.actionBack,
                onAction: () => context.canPop() ? context.pop() : context.go(Routes.booking(bookingId)),
              )
            : AsyncStateView<ChatState>(
                value: chat,
                onRetry: notifier.reload,
                onSignIn: () => context.go(Routes.auth),
                builder: (context, data) => ChatThreadView(
                  messages: data.messages,
                  canSend: data.canSend,
                  maxChars: data.maxChars,
                  closesAt: data.closesAt,
                  hasMore: data.hasMore,
                  loadingMore: data.loadingMore,
                  onLoadOlder: notifier.loadOlder,
                  strings: chatStrings(l10n),
                  sendError: data.sendError == null ? null : (supportCodeText(l10n, data.sendError!) ?? l10n.errorGenericBody),
                  onSend: notifier.send,
                  onRetry: notifier.retry,
                  onDiscard: notifier.discard,
                  loadImage: ref.read(chatRepositoryProvider).loadImage,
                ),
              ),
      ),
    );
  }
}
