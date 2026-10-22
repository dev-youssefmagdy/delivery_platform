import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../ui/components.dart';
import '../../ui/inputs.dart';
import '../../ui/tokens.dart';
import '../support_models.dart';
import 'secure_image.dart';
import 'support_strings.dart';

ChipTone _tone(TicketStatusView status) => switch (status) {
  TicketStatusView.open => ChipTone.info,
  TicketStatusView.inProgress => ChipTone.info,
  TicketStatusView.waitingUser => ChipTone.warning,
  TicketStatusView.escalated => ChipTone.warning,
  TicketStatusView.resolved => ChipTone.success,
  TicketStatusView.closed => ChipTone.neutral,
};

String _day(BuildContext context, DateTime date) =>
    DateFormat.yMMMd(Localizations.localeOf(context).toString()).format(date);

/// The list of the person's tickets with the active / finished / all filter.
/// States other than data (loading, error, offline) are the screen's job.
class TicketListView extends StatelessWidget {
  const TicketListView({
    super.key,
    required this.tickets,
    required this.filter,
    required this.onFilter,
    required this.onOpen,
    required this.strings,
    this.hasMore = false,
    this.loadingMore = false,
    this.onLoadMore,
    this.onRefresh,
  });

  final List<SupportTicket> tickets;

  /// `active`, `finished` or null (all).
  final String? filter;
  final ValueChanged<String?> onFilter;
  final ValueChanged<SupportTicket> onOpen;
  final SupportStrings strings;
  final bool hasMore;
  final bool loadingMore;
  final VoidCallback? onLoadMore;
  final Future<void> Function()? onRefresh;

  @override
  Widget build(BuildContext context) {
    final list = tickets.isEmpty
        ? ListView(
            children: [
              SizedBox(
                height: 320,
                child: EmptyState(key: const Key('tickets-empty'), icon: Icons.support_agent, title: strings.emptyTitle, message: strings.emptyBody),
              ),
            ],
          )
        : ListView.separated(
            key: const Key('tickets-list'),
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            itemCount: tickets.length + (hasMore ? 1 : 0),
            separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
            itemBuilder: (context, index) {
              if (index == tickets.length) {
                return Center(
                  child: loadingMore
                      ? const CircularProgressIndicator(strokeWidth: 2)
                      : TextButton(key: const Key('tickets-load-more'), onPressed: onLoadMore, child: Text(strings.loadMore)),
                );
              }
              final ticket = tickets[index];
              return Card(
                margin: EdgeInsets.zero,
                child: ListTile(
                  key: Key('ticket-${ticket.id}'),
                  title: Text(ticket.subject, maxLines: 2, overflow: TextOverflow.ellipsis),
                  subtitle: Padding(
                    padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                    child: Text('${strings.ticketNumber(ticket.number)} · ${ticket.categoryLabel}\n${strings.openedOn(_day(context, ticket.createdAt))}'),
                  ),
                  isThreeLine: true,
                  trailing: StatusChip(label: ticket.statusLabel, tone: _tone(ticket.status)),
                  onTap: () => onOpen(ticket),
                ),
              );
            },
          );

    return Column(
      children: [
        Padding(
          padding: const EdgeInsetsDirectional.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
          child: SegmentedButton<String>(
            key: const Key('tickets-filter'),
            showSelectedIcon: false,
            segments: [
              ButtonSegment(value: 'active', label: Text(strings.filterActive)),
              ButtonSegment(value: 'finished', label: Text(strings.filterFinished)),
              ButtonSegment(value: 'all', label: Text(strings.filterAll)),
            ],
            selected: {filter ?? 'all'},
            onSelectionChanged: (s) => onFilter(s.first == 'all' ? null : s.first),
          ),
        ),
        Expanded(child: onRefresh == null ? list : RefreshIndicator(onRefresh: onRefresh!, child: list)),
      ],
    );
  }
}

/// A ticket and its public thread with a reply box. Staff notes never reach
/// this widget: the API does not return them.
class TicketDetailView extends StatefulWidget {
  const TicketDetailView({
    super.key,
    required this.ticket,
    required this.strings,
    required this.onReply,
    required this.loadImage,
    this.sending = false,
    this.error,
  });

  final SupportTicket ticket;
  final SupportStrings strings;
  final Future<bool> Function(String body, List<String> attachmentPaths) onReply;
  final Future<Uint8List> Function(String url) loadImage;
  final bool sending;
  final String? error;

  @override
  State<TicketDetailView> createState() => _TicketDetailViewState();
}

class _TicketDetailViewState extends State<TicketDetailView> {
  final _controller = TextEditingController();
  final _files = <XFile>[];

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final text = _controller.text.trim();
    if (text.isEmpty) return;
    final sent = await widget.onReply(text, [for (final f in _files) f.path]);
    if (sent && mounted) {
      setState(() {
        _controller.clear();
        _files.clear();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.strings;
    final ticket = widget.ticket;
    final theme = Theme.of(context);
    final messages = ticket.messages ?? const <TicketMessage>[];

    return Column(
      children: [
        Expanded(
          child: ListView(
            key: const Key('ticket-detail'),
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            children: [
              Row(
                children: [
                  Expanded(child: Text(s.ticketNumber(ticket.number), style: theme.textTheme.titleMedium)),
                  StatusChip(label: ticket.statusLabel, tone: _tone(ticket.status)),
                ],
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(ticket.subject, style: theme.textTheme.titleLarge),
              Text(
                [ticket.categoryLabel, if (ticket.bookingCode != null) s.booking(ticket.bookingCode!)].join(' · '),
                style: theme.textTheme.bodySmall,
              ),
              if (ticket.status.isFinished && (ticket.resolution ?? '').isNotEmpty)
                Card(
                  key: const Key('ticket-resolution'),
                  color: theme.colorScheme.secondaryContainer,
                  margin: const EdgeInsetsDirectional.only(top: AppSpacing.md),
                  child: Padding(
                    padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(s.resolution, style: theme.textTheme.labelLarge),
                        const SizedBox(height: AppSpacing.xs),
                        Text(ticket.resolution!),
                      ],
                    ),
                  ),
                ),
              const SizedBox(height: AppSpacing.md),
              for (final message in messages) _MessageCard(message: message, strings: s, loadImage: widget.loadImage),
            ],
          ),
        ),
        if (ticket.canReply)
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.sm),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (widget.error != null)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.xs),
                      child: Text(widget.error!, key: const Key('ticket-reply-error'), style: TextStyle(color: theme.colorScheme.error)),
                    ),
                  AttachmentPicker(
                    files: _files,
                    onChanged: (files) => setState(() {
                      _files
                        ..clear()
                        ..addAll(files);
                    }),
                    addLabel: s.attach,
                    cameraLabel: s.attachCamera,
                    galleryLabel: s.attachGallery,
                    removeLabel: s.removeAttachment,
                    maxFiles: 3,
                  ),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Expanded(
                        child: TextField(
                          key: const Key('ticket-reply-input'),
                          controller: _controller,
                          minLines: 1,
                          maxLines: 5,
                          decoration: InputDecoration(hintText: s.replyHint, border: const OutlineInputBorder(), isDense: true),
                        ),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      IconButton.filled(
                        key: const Key('ticket-reply-send'),
                        tooltip: s.replySend,
                        icon: widget.sending ? const SizedBox.square(dimension: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.send),
                        onPressed: widget.sending ? null : _send,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          )
        else
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Text(s.replyClosed, key: const Key('ticket-reply-closed'), style: theme.textTheme.bodySmall),
            ),
          ),
      ],
    );
  }
}

class _MessageCard extends StatelessWidget {
  const _MessageCard({required this.message, required this.strings, required this.loadImage});

  final TicketMessage message;
  final SupportStrings strings;
  final Future<Uint8List> Function(String url) loadImage;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final support = message.fromSupport;

    return Align(
      alignment: support ? AlignmentDirectional.centerStart : AlignmentDirectional.centerEnd,
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * 0.85),
        child: Container(
          key: Key('ticket-message-${message.id}'),
          margin: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          decoration: BoxDecoration(
            color: support ? theme.colorScheme.surfaceContainerHighest : theme.colorScheme.primaryContainer,
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(support ? strings.fromSupport : strings.fromMe, style: theme.textTheme.labelMedium),
              const SizedBox(height: AppSpacing.xs),
              Text(message.body),
              if (message.attachments.isNotEmpty)
                Padding(
                  padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                  child: Wrap(
                    spacing: AppSpacing.sm,
                    runSpacing: AppSpacing.sm,
                    children: [
                      for (final a in message.attachments)
                        if (a.isImage)
                          SecureImage(cacheKey: a.id, load: () => loadImage(a.url), errorLabel: strings.imageFailed, size: 96)
                        else
                          Chip(avatar: const Icon(Icons.description_outlined, size: 16), label: Text(a.name ?? strings.attachmentLabel)),
                    ],
                  ),
                ),
              const SizedBox(height: AppSpacing.xs),
              Text(DateFormat.yMMMd(Localizations.localeOf(context).toString()).add_Hm().format(message.createdAt), style: theme.textTheme.labelSmall),
            ],
          ),
        ),
      ),
    );
  }
}

/// "Contact support" form: category, subject, description, evidence photos.
class NewTicketForm extends StatefulWidget {
  const NewTicketForm({
    super.key,
    required this.categories,
    required this.strings,
    required this.onSubmit,
    this.bookingCode,
    this.initialCategory,
    this.initialSubject,
    this.submitting = false,
    this.error,
  });

  final List<TicketCategoryOption> categories;
  final SupportStrings strings;
  final Future<void> Function(String category, String subject, String body, List<String> attachmentPaths) onSubmit;
  final String? bookingCode;
  final String? initialCategory;
  final String? initialSubject;
  final bool submitting;
  final String? error;

  @override
  State<NewTicketForm> createState() => _NewTicketFormState();
}

class _NewTicketFormState extends State<NewTicketForm> {
  final _formKey = GlobalKey<FormState>();
  late final _subject = TextEditingController(text: widget.initialSubject);
  final _body = TextEditingController();
  final _files = <XFile>[];
  String? _category;

  @override
  void initState() {
    super.initState();
    _category = widget.categories.any((c) => c.value == widget.initialCategory) ? widget.initialCategory : null;
  }

  @override
  void dispose() {
    _subject.dispose();
    _body.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.strings;
    final theme = Theme.of(context);

    return Form(
      key: _formKey,
      child: ListView(
        key: const Key('new-ticket-form'),
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        children: [
          if (widget.bookingCode != null)
            Padding(padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.md), child: Chip(label: Text(s.booking(widget.bookingCode!)))),
          DropdownButtonFormField<String>(
            key: const Key('new-ticket-category'),
            initialValue: _category,
            isExpanded: true,
            decoration: InputDecoration(labelText: s.category, hintText: s.categoryHint, border: const OutlineInputBorder()),
            items: [for (final c in widget.categories) DropdownMenuItem(value: c.value, child: Text(c.label))],
            onChanged: (v) => setState(() => _category = v),
            validator: (v) => v == null ? s.required : null,
          ),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            key: const Key('new-ticket-subject'),
            controller: _subject,
            maxLength: 160,
            decoration: InputDecoration(labelText: s.subject, hintText: s.subjectHint, border: const OutlineInputBorder()),
            validator: (v) {
              final t = (v ?? '').trim();
              if (t.isEmpty) return s.required;
              return t.length < 3 ? s.tooShort : null;
            },
          ),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            key: const Key('new-ticket-body'),
            controller: _body,
            minLines: 4,
            maxLines: 8,
            maxLength: 4000,
            decoration: InputDecoration(labelText: s.body, hintText: s.bodyHint, border: const OutlineInputBorder(), alignLabelWithHint: true),
            validator: (v) {
              final t = (v ?? '').trim();
              if (t.isEmpty) return s.required;
              return t.length < 5 ? s.tooShort : null;
            },
          ),
          const SizedBox(height: AppSpacing.md),
          AttachmentPicker(
            files: _files,
            onChanged: (files) => setState(() {
              _files
                ..clear()
                ..addAll(files);
            }),
            addLabel: s.attach,
            cameraLabel: s.attachCamera,
            galleryLabel: s.attachGallery,
            removeLabel: s.removeAttachment,
            maxFiles: 3,
          ),
          if (widget.error != null)
            Padding(
              padding: const EdgeInsetsDirectional.only(top: AppSpacing.md),
              child: Text(widget.error!, key: const Key('new-ticket-error'), style: TextStyle(color: theme.colorScheme.error)),
            ),
          const SizedBox(height: AppSpacing.lg),
          AppButton(
            key: const Key('new-ticket-submit'),
            label: s.submit,
            loading: widget.submitting,
            onPressed: () {
              if (_formKey.currentState?.validate() != true) return;
              widget.onSubmit(_category!, _subject.text.trim(), _body.text.trim(), [for (final f in _files) f.path]);
            },
          ),
        ],
      ),
    );
  }
}
