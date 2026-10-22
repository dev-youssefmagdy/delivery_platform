import 'dart:typed_data';

import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../ui/components.dart';
import '../../ui/tokens.dart';
import '../support_models.dart';
import 'secure_image.dart';
import 'support_strings.dart';

/// The booking conversation: bubbles (mine at the end, theirs at the start in
/// either text direction), delivery and read marks, optimistic messages with
/// retry, a composer with one image, and a closed-window banner. Pure UI: the
/// screen's notifier owns the data and the actions.
class ChatThreadView extends StatefulWidget {
  const ChatThreadView({
    super.key,
    required this.messages,
    required this.canSend,
    required this.maxChars,
    required this.strings,
    required this.onSend,
    required this.onRetry,
    required this.onDiscard,
    required this.loadImage,
    this.closesAt,
    this.hasMore = false,
    this.loadingMore = false,
    this.onLoadOlder,
    this.sendError,
  });

  final List<ChatMessage> messages;
  final bool canSend;
  final int maxChars;
  final ChatStrings strings;
  final DateTime? closesAt;
  final bool hasMore;
  final bool loadingMore;
  final VoidCallback? onLoadOlder;
  final void Function(String? text, String? imagePath) onSend;
  final ValueChanged<ChatMessage> onRetry;
  final ValueChanged<ChatMessage> onDiscard;
  final Future<Uint8List> Function(String url) loadImage;

  /// A rejection from the server the composer shows under the field.
  final String? sendError;

  @override
  State<ChatThreadView> createState() => _ChatThreadViewState();
}

class _ChatThreadViewState extends State<ChatThreadView> {
  final _controller = TextEditingController();
  final _scroll = ScrollController();
  String? _imagePath;

  @override
  void dispose() {
    _controller.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _pick() async {
    final s = widget.strings;
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: Text(s.attachCamera),
              onTap: () => Navigator.pop(ctx, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text(s.attachGallery),
              onTap: () => Navigator.pop(ctx, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return;
    final picked = await ImagePicker().pickImage(source: source, imageQuality: 85, maxWidth: 1600);
    if (picked != null && mounted) setState(() => _imagePath = picked.path);
  }

  void _submit() {
    final text = _controller.text.trim();
    if (text.isEmpty && _imagePath == null) return;
    if (text.length > widget.maxChars) return;
    widget.onSend(text.isEmpty ? null : text, _imagePath);
    setState(() {
      _controller.clear();
      _imagePath = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.strings;
    final theme = Theme.of(context);
    final items = widget.messages;
    final tooLong = _controller.text.trim().length > widget.maxChars;

    return Column(
      children: [
        if (!widget.canSend)
          MaterialBanner(
            key: const Key('chat-closed'),
            content: Text('${s.closedTitle}\n${s.closedBody}'),
            leading: const Icon(Icons.lock_outline),
            actions: const [SizedBox.shrink()],
          )
        else if (widget.closesAt != null)
          Padding(
            padding: const EdgeInsetsDirectional.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.xs),
            child: Text(
              s.closesSoon(DateFormat.Hm(Localizations.localeOf(context).toString()).format(widget.closesAt!)),
              style: theme.textTheme.labelSmall,
            ),
          ),
        Expanded(
          child: items.isEmpty
              ? EmptyState(key: const Key('chat-empty'), icon: Icons.chat_bubble_outline, title: s.empty, message: s.emptyHint)
              : ListView.builder(
                  key: const Key('chat-list'),
                  controller: _scroll,
                  reverse: true,
                  padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                  itemCount: items.length + (widget.hasMore ? 1 : 0),
                  itemBuilder: (context, index) {
                    if (index == items.length) {
                      return Center(
                        child: widget.loadingMore
                            ? const Padding(padding: EdgeInsets.all(AppSpacing.md), child: CircularProgressIndicator(strokeWidth: 2))
                            : TextButton(key: const Key('chat-load-older'), onPressed: widget.onLoadOlder, child: Text(s.loadOlder)),
                      );
                    }
                    final message = items[items.length - 1 - index];
                    return _Bubble(
                      message: message,
                      strings: s,
                      loadImage: widget.loadImage,
                      onRetry: () => widget.onRetry(message),
                      onDiscard: () => widget.onDiscard(message),
                    );
                  },
                ),
        ),
        if (widget.canSend)
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsetsDirectional.fromSTEB(AppSpacing.sm, AppSpacing.xs, AppSpacing.sm, AppSpacing.sm),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(s.phoneNotice, style: theme.textTheme.labelSmall?.copyWith(color: theme.colorScheme.outline)),
                  if (widget.sendError != null)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                      child: Text(widget.sendError!, key: const Key('chat-send-error'), style: TextStyle(color: theme.colorScheme.error)),
                    ),
                  if (_imagePath != null)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                      child: Stack(
                        children: [
                          ClipRRect(borderRadius: BorderRadius.circular(AppRadius.sm), child: Image.file(File(_imagePath!), width: 72, height: 72, fit: BoxFit.cover)),
                          PositionedDirectional(
                            top: 0,
                            end: 0,
                            child: IconButton.filledTonal(
                              key: const Key('chat-remove-image'),
                              tooltip: s.removeAttachment,
                              visualDensity: VisualDensity.compact,
                              icon: const Icon(Icons.close, size: 16),
                              onPressed: () => setState(() => _imagePath = null),
                            ),
                          ),
                        ],
                      ),
                    ),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      IconButton(key: const Key('chat-attach'), tooltip: s.attach, icon: const Icon(Icons.attach_file), onPressed: _pick),
                      Expanded(
                        child: TextField(
                          key: const Key('chat-input'),
                          controller: _controller,
                          minLines: 1,
                          maxLines: 4,
                          textInputAction: TextInputAction.newline,
                          onChanged: (_) => setState(() {}),
                          decoration: InputDecoration(
                            hintText: s.inputHint,
                            errorText: tooLong ? s.tooLong(widget.maxChars) : null,
                            border: const OutlineInputBorder(),
                            isDense: true,
                          ),
                        ),
                      ),
                      IconButton.filled(key: const Key('chat-send'), tooltip: s.send, icon: const Icon(Icons.send), onPressed: tooLong ? null : _submit),
                    ],
                  ),
                ],
              ),
            ),
          ),
      ],
    );
  }
}

class _Bubble extends StatelessWidget {
  const _Bubble({
    required this.message,
    required this.strings,
    required this.loadImage,
    required this.onRetry,
    required this.onDiscard,
  });

  final ChatMessage message;
  final ChatStrings strings;
  final Future<Uint8List> Function(String url) loadImage;
  final VoidCallback onRetry;
  final VoidCallback onDiscard;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final mine = message.mine;
    final background = mine ? theme.colorScheme.primaryContainer : theme.colorScheme.surfaceContainerHighest;
    final time = DateFormat.Hm(Localizations.localeOf(context).toString()).format(message.createdAt);
    final attachment = message.attachment;

    return Align(
      alignment: mine ? AlignmentDirectional.centerEnd : AlignmentDirectional.centerStart,
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * 0.78),
        child: Container(
          key: Key('chat-message-${message.id}'),
          margin: const EdgeInsetsDirectional.symmetric(vertical: AppSpacing.xs),
          padding: const EdgeInsetsDirectional.all(AppSpacing.sm + 2),
          decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(AppRadius.md)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (message.purged)
                Text(strings.removedByRetention, style: theme.textTheme.bodySmall?.copyWith(fontStyle: FontStyle.italic))
              else ...[
                if (attachment != null)
                  Padding(
                    padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.xs),
                    child: Semantics(
                      label: strings.imageLabel,
                      child: SecureImage(cacheKey: message.id, load: () => loadImage(attachment.url), errorLabel: strings.imageFailed),
                    ),
                  )
                else if (message.localAttachmentPath != null)
                  Padding(
                    padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.xs),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(AppRadius.sm),
                      child: Image.file(File(message.localAttachmentPath!), width: 160, height: 160, fit: BoxFit.cover),
                    ),
                  ),
                if (message.body != null && message.body!.isNotEmpty) Text(message.body!),
              ],
              const SizedBox(height: AppSpacing.xs),
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(time, style: theme.textTheme.labelSmall),
                  if (mine) ...[
                    const SizedBox(width: AppSpacing.xs),
                    switch (message.delivery) {
                      ChatDelivery.sending => Text(strings.sending, style: theme.textTheme.labelSmall),
                      ChatDelivery.failed => Text(strings.failed, style: theme.textTheme.labelSmall?.copyWith(color: theme.colorScheme.error)),
                      ChatDelivery.read => Row(mainAxisSize: MainAxisSize.min, children: [
                        Icon(Icons.done_all, size: 14, color: theme.colorScheme.primary),
                        const SizedBox(width: 2),
                        Text(strings.read, style: theme.textTheme.labelSmall),
                      ]),
                      ChatDelivery.sent => Row(mainAxisSize: MainAxisSize.min, children: [
                        const Icon(Icons.done, size: 14),
                        const SizedBox(width: 2),
                        Text(strings.delivered, style: theme.textTheme.labelSmall),
                      ]),
                    },
                  ],
                ],
              ),
              if (message.delivery == ChatDelivery.failed)
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextButton(key: Key('chat-retry-${message.id}'), onPressed: onRetry, child: Text(strings.retry)),
                    TextButton(key: Key('chat-discard-${message.id}'), onPressed: onDiscard, child: Text(strings.discard)),
                  ],
                ),
            ],
          ),
        ),
      ),
    );
  }
}
