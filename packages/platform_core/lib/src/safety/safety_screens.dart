import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:uuid/uuid.dart';

import '../api/api_exception.dart';
import '../ui/components.dart';
import '../ui/inputs.dart';
import '../ui/tokens.dart';
import 'safety_models.dart';
import 'safety_state.dart';
import 'safety_strings.dart';
import 'safety_widgets.dart';

const _uuid = Uuid();

String safetyErrorMessage(SafetyStrings strings, Object error) {
  if (error is ApiException) {
    if (error.isOffline) return strings.errorOffline;
    return strings.errorText(error) ?? error.message ?? strings.errorGeneric;
  }
  return strings.errorGeneric;
}

/// Content of the safety sheet opened from the safety button of an active job (both apps). [bookingId] is null outside a job:
/// then only the emergency numbers and the support contact are offered (the sheet says so).
class SafetySheetBody extends ConsumerWidget {
  const SafetySheetBody({
    super.key,
    required this.providers,
    required this.strings,
    required this.locate,
    this.bookingId,
    this.sosAllowed = true,
    this.shareAllowed = true,
    this.supportPhone,
    this.onReport,
    this.onShare,
    this.onContacts,
    this.onOpenIncident,
    this.onCall,
  });

  final SafetyProviders providers;
  final SafetyStrings strings;
  final Future<SafetyFix?> Function() locate;
  final String? bookingId;
  final bool sosAllowed;
  final bool shareAllowed;
  final String? supportPhone;
  final VoidCallback? onReport;
  final VoidCallback? onShare;
  final VoidCallback? onContacts;
  final void Function(String incidentId)? onOpenIncident;
  final Future<void> Function(String number)? onCall;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sos = ref.watch(providers.sos);
    final config = ref.watch(providers.config).value;
    final numbers = sos.incident?.emergencyNumbers.isNotEmpty == true ? sos.incident!.emergencyNumbers : (config?.numbers ?? const <EmergencyNumber>[]);
    final canSos = bookingId != null && sosAllowed;

    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsetsDirectional.fromSTEB(AppSpacing.md, 0, AppSpacing.md, AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(strings.title, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: AppSpacing.md),
            if (canSos && sos.phase == SosPhase.idle)
              Center(
                child: SosHoldButton(
                  label: strings.sosHoldLabel,
                  hint: strings.sosHoldHint(config?.holdSeconds ?? 2),
                  holdingLabel: strings.sosHolding,
                  holdSeconds: config?.holdSeconds ?? 2,
                  onActivated: () => unawaited(ref.read(providers.sos.notifier).trigger(bookingId!, locate)),
                ),
              )
            else if (!canSos)
              Text(strings.sosNoJob, key: const Key('sos-no-job')),
            if (sos.phase == SosPhase.sending)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
                child: Column(children: [const CircularProgressIndicator(), const SizedBox(height: AppSpacing.sm), Text(strings.sosSending)]),
              ),
            if (sos.phase == SosPhase.sent && sos.incident != null) ...[
              StatusChip(label: strings.sosSentTitle, tone: ChipTone.success),
              const SizedBox(height: AppSpacing.sm),
              SafetyActionList(actions: sos.incident!.actions, title: strings.whatWeDid),
              if (onOpenIncident != null)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.sm),
                  child: AppButton(label: strings.incidentNumber(sos.incident!.number), secondary: true, icon: Icons.assignment_outlined, onPressed: () => onOpenIncident!(sos.incident!.id)),
                ),
            ],
            if (sos.phase == SosPhase.failed) ...[
              Text(strings.sosFailedTitle, style: Theme.of(context).textTheme.titleMedium?.copyWith(color: AppColors.danger)),
              const SizedBox(height: AppSpacing.xs),
              Text(sos.error == null ? strings.sosFailedBody : safetyErrorMessage(strings, sos.error!), key: const Key('sos-error')),
              const SizedBox(height: AppSpacing.sm),
              AppButton(label: strings.sosRetry, icon: Icons.refresh, onPressed: () => unawaited(ref.read(providers.sos.notifier).trigger(bookingId!, locate))),
            ],
            const SizedBox(height: AppSpacing.md),
            EmergencyNumbersCard(numbers: numbers, strings: strings, onCall: onCall),
            const SizedBox(height: AppSpacing.md),
            if (bookingId != null && onReport != null) _tile(Icons.report_gmailerrorred_outlined, strings.reportIncident, onReport!, const Key('safety-report')),
            if (bookingId != null && shareAllowed && onShare != null) _tile(Icons.share_location_outlined, strings.shareTrip, onShare!, const Key('safety-share')),
            if (onContacts != null) _tile(Icons.contacts_outlined, strings.manageContacts, onContacts!, const Key('safety-contacts')),
            if (supportPhone != null && supportPhone!.trim().isNotEmpty)
              _tile(Icons.support_agent, strings.callSupport, () => (onCall ?? (_) async {})(supportPhone!.trim()), const Key('safety-support')),
          ],
        ),
      ),
    );
  }

  Widget _tile(IconData icon, String label, VoidCallback onTap, Key key) =>
      ListTile(key: key, leading: Icon(icon), title: Text(label), trailing: const Icon(Icons.chevron_right), onTap: onTap);
}

/// Incident report form: category, what happened, an optional callback number and photos. Photos are added after the case exists,
/// so a failing upload never loses the report.
class IncidentReportScreen extends ConsumerStatefulWidget {
  const IncidentReportScreen({super.key, required this.providers, required this.strings, required this.bookingId, required this.locate, this.onOpenIncident, this.initialCategory});

  final SafetyProviders providers;
  final SafetyStrings strings;
  final String bookingId;
  final Future<SafetyFix?> Function() locate;
  final void Function(String incidentId)? onOpenIncident;
  final String? initialCategory;

  @override
  ConsumerState<IncidentReportScreen> createState() => _IncidentReportScreenState();
}

class _IncidentReportScreenState extends ConsumerState<IncidentReportScreen> {
  final _narrative = TextEditingController();
  final _callback = TextEditingController();
  late String _category = widget.initialCategory ?? 'harassment';
  final _photos = <XFile>[];
  SafetyIncident? _created;
  String? _error;
  String? _narrativeError;
  int _uploaded = 0;
  bool _uploadFailed = false;
  final _keys = <String, String>{};

  @override
  void dispose() {
    _narrative.dispose();
    _callback.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final strings = widget.strings;
    if (_narrative.text.trim().length < 5) {
      setState(() => _narrativeError = strings.reportTooShort);
      return;
    }
    setState(() {
      _error = null;
      _narrativeError = null;
    });
    SafetyFix? fix;
    try {
      fix = await widget.locate().timeout(const Duration(seconds: 3));
    } on Object {
      fix = null;
    }
    try {
      final incident = await ref.read(widget.providers.actions.notifier).report(
        bookingId: widget.bookingId,
        category: _category,
        narrative: _narrative.text.trim(),
        fix: fix,
        callbackPhone: _callback.text.trim().isEmpty ? null : _callback.text.trim(),
      );
      if (!mounted) return;
      setState(() => _created = incident);
      await _uploadPhotos(incident.id);
    } on Object catch (e) {
      if (mounted) setState(() => _error = safetyErrorMessage(strings, e));
    }
  }

  Future<void> _uploadPhotos(String id) async {
    setState(() => _uploadFailed = false);
    for (var i = _uploaded; i < _photos.length; i++) {
      try {
        final key = _keys.putIfAbsent(_photos[i].path, _uuid.v4);
        await ref.read(widget.providers.actions.notifier).addFile(id, key: key, path: _photos[i].path);
        _uploaded = i + 1;
      } on Object {
        if (mounted) setState(() => _uploadFailed = true);
        return;
      }
    }
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final strings = widget.strings;
    final busy = ref.watch(widget.providers.actions);
    final created = _created;
    final config = ref.watch(widget.providers.config).value;

    return Scaffold(
      appBar: AppBar(title: Text(strings.reportTitle)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.md),
          children: [
            if (created == null) ...[
              Text(strings.reportUrgentNote, key: const Key('report-urgent-note')),
              const SizedBox(height: AppSpacing.md),
              DropdownButtonFormField<String>(
                key: const Key('report-category'),
                initialValue: _category,
                decoration: InputDecoration(labelText: strings.reportCategory),
                items: [for (final c in incidentCategories) DropdownMenuItem(value: c, child: Text(strings.categoryName(c)))],
                onChanged: busy ? null : (v) => setState(() => _category = v ?? _category),
              ),
              const SizedBox(height: AppSpacing.md),
              AppTextField(
                key: const Key('report-narrative'),
                label: strings.reportNarrative,
                hint: strings.reportNarrativeHint,
                controller: _narrative,
                maxLines: 6,
                errorText: _narrativeError,
                enabled: !busy,
              ),
              const SizedBox(height: AppSpacing.md),
              AppTextField(label: strings.reportCallback, controller: _callback, keyboardType: TextInputType.phone, textDirection: TextDirection.ltr, enabled: !busy),
              const SizedBox(height: AppSpacing.md),
              AttachmentPicker(
                files: _photos,
                onChanged: (files) => setState(() => _photos
                  ..clear()
                  ..addAll(files)),
                addLabel: strings.incidentAddFile,
                cameraLabel: strings.incidentAddFile,
                galleryLabel: strings.incidentAddFile,
                removeLabel: strings.cancel,
                maxFiles: 5,
              ),
              if (_error != null)
                Padding(padding: const EdgeInsets.only(top: AppSpacing.md), child: Text(_error!, key: const Key('report-error'), style: TextStyle(color: Theme.of(context).colorScheme.error))),
              const SizedBox(height: AppSpacing.md),
              AppButton(key: const Key('report-submit'), label: strings.reportSubmit, loading: busy, onPressed: _submit),
            ] else ...[
              StatusChip(label: strings.reportSentTitle, tone: ChipTone.success),
              const SizedBox(height: AppSpacing.md),
              Text(strings.incidentNumber(created.number), style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: AppSpacing.md),
              SafetyActionList(actions: created.actions, title: strings.whatWeDid),
              if (_uploadFailed)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.md),
                  child: AppButton(label: strings.retry, secondary: true, icon: Icons.refresh, loading: busy, onPressed: () => _uploadPhotos(created.id)),
                ),
              const SizedBox(height: AppSpacing.md),
              EmergencyNumbersCard(numbers: created.emergencyNumbers.isNotEmpty ? created.emergencyNumbers : (config?.numbers ?? const []), strings: strings),
              const SizedBox(height: AppSpacing.md),
              if (widget.onOpenIncident != null) AppButton(label: strings.incidentsTitle, secondary: true, onPressed: () => widget.onOpenIncident!(created.id)),
              const SizedBox(height: AppSpacing.sm),
              AppButton(label: strings.done, onPressed: () => Navigator.of(context).maybePop()),
            ],
          ],
        ),
      ),
    );
  }
}

class IncidentsScreen extends ConsumerWidget {
  const IncidentsScreen({super.key, required this.providers, required this.strings, required this.onOpen});

  final SafetyProviders providers;
  final SafetyStrings strings;
  final void Function(String incidentId) onOpen;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final page = ref.watch(providers.incidents);
    return Scaffold(
      appBar: AppBar(title: Text(strings.incidentsTitle)),
      body: page.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorState(
          title: strings.errorGeneric,
          message: safetyErrorMessage(strings, e),
          retryLabel: strings.retry,
          offline: e is ApiException && e.isOffline,
          onRetry: () => ref.invalidate(providers.incidents),
        ),
        data: (data) => data.items.isEmpty
            ? EmptyState(title: strings.incidentsEmpty, icon: Icons.verified_user_outlined)
            : RefreshIndicator(
                onRefresh: () async => ref.refresh(providers.incidents.future),
                child: ListView.separated(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  itemCount: data.items.length,
                  separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
                  itemBuilder: (context, i) {
                    final item = data.items[i];
                    return Card(
                      child: ListTile(
                        key: Key('incident-${item.id}'),
                        leading: Icon(item.isSos ? Icons.sos_rounded : Icons.report_gmailerrorred_outlined, color: item.isResolved ? AppColors.success : AppColors.danger),
                        title: Text('${item.number} · ${strings.categoryName(item.category)}'),
                        subtitle: Text([strings.incidentStatus(item.status, item.statusLabel), ?item.bookingCode].join(' · ')),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => onOpen(item.id),
                      ),
                    );
                  },
                ),
              ),
      ),
    );
  }
}

class IncidentDetailScreen extends ConsumerStatefulWidget {
  const IncidentDetailScreen({super.key, required this.providers, required this.strings, required this.incidentId, required this.loadFile});

  final SafetyProviders providers;
  final SafetyStrings strings;
  final String incidentId;
  final Future<Uint8List> Function(String url) loadFile;

  @override
  ConsumerState<IncidentDetailScreen> createState() => _IncidentDetailScreenState();
}

class _IncidentDetailScreenState extends ConsumerState<IncidentDetailScreen> {
  final _update = TextEditingController();
  String _updateKey = _uuid.v4();
  String? _error;

  @override
  void dispose() {
    _update.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final text = _update.text.trim();
    if (text.length < 2) return;
    try {
      await ref.read(widget.providers.actions.notifier).addUpdate(widget.incidentId, key: _updateKey, body: text);
      _update.clear();
      _updateKey = _uuid.v4();
      ref.invalidate(widget.providers.incident(widget.incidentId));
      if (mounted) setState(() => _error = null);
    } on Object catch (e) {
      if (mounted) setState(() => _error = safetyErrorMessage(widget.strings, e));
    }
  }

  Future<void> _addFile() async {
    final picked = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 85, maxWidth: 2048);
    if (picked == null) return;
    try {
      await ref.read(widget.providers.actions.notifier).addFile(widget.incidentId, key: _uuid.v4(), path: picked.path);
      ref.invalidate(widget.providers.incident(widget.incidentId));
    } on Object catch (e) {
      if (mounted) setState(() => _error = safetyErrorMessage(widget.strings, e));
    }
  }

  @override
  Widget build(BuildContext context) {
    final strings = widget.strings;
    final async = ref.watch(widget.providers.incident(widget.incidentId));
    final busy = ref.watch(widget.providers.actions);

    return Scaffold(
      appBar: AppBar(title: Text(async.value == null ? strings.incidentsTitle : strings.incidentNumber(async.value!.number))),
      body: async.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorState(
          title: strings.errorGeneric,
          message: safetyErrorMessage(strings, e),
          retryLabel: strings.retry,
          offline: e is ApiException && e.isOffline,
          onRetry: () => ref.invalidate(widget.providers.incident(widget.incidentId)),
        ),
        data: (incident) => RefreshIndicator(
          onRefresh: () async => ref.refresh(widget.providers.incident(widget.incidentId).future),
          child: ListView(
            padding: const EdgeInsets.all(AppSpacing.md),
            children: [
              Row(children: [
                StatusChip(label: strings.incidentStatus(incident.status, incident.statusLabel), tone: incident.isResolved ? ChipTone.success : ChipTone.warning),
                const SizedBox(width: AppSpacing.sm),
                Expanded(child: Text(strings.categoryName(incident.category), style: Theme.of(context).textTheme.titleMedium)),
              ]),
              if (incident.outcomeLabel != null) Padding(padding: const EdgeInsets.only(top: AppSpacing.sm), child: Text(strings.incidentOutcome(incident.outcomeLabel!))),
              if (incident.isResolved) Padding(padding: const EdgeInsets.only(top: AppSpacing.sm), child: Text(strings.incidentResolvedNote)),
              if (incident.narrative != null) Padding(padding: const EdgeInsets.only(top: AppSpacing.md), child: Text(incident.narrative!)),
              const SizedBox(height: AppSpacing.md),
              SafetyActionList(actions: incident.actions, title: strings.whatWeDid),
              if (incident.trustedContacts.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.md),
                Text(strings.incidentContacts, style: Theme.of(context).textTheme.titleMedium),
                for (final c in incident.trustedContacts) ListTile(dense: true, leading: const Icon(Icons.sms_outlined), title: Text(c.name ?? '—'), subtitle: Text(c.statusLabel)),
              ],
              const SizedBox(height: AppSpacing.md),
              EmergencyNumbersCard(numbers: incident.emergencyNumbers, strings: strings),
              const SizedBox(height: AppSpacing.md),
              Text(strings.incidentTimeline, style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: AppSpacing.sm),
              for (final e in incident.timeline)
                ListTile(
                  dense: true,
                  leading: Icon(e.actor == 'staff' ? Icons.support_agent : Icons.circle, size: e.actor == 'staff' ? 24 : 10),
                  title: Text(e.message ?? e.label),
                  subtitle: Text([strings.eventActor(e.actor), if (e.message != null) e.label, if (e.at != null) MaterialLocalizations.of(context).formatShortDate(e.at!)].join(' · ')),
                ),
              if (incident.files.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.md),
                Text(strings.incidentFiles, style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: AppSpacing.sm),
                Wrap(spacing: AppSpacing.sm, runSpacing: AppSpacing.sm, children: [
                  for (final f in incident.files)
                    f.isImage
                        ? SizedBox.square(dimension: 80, child: _FileThumb(load: () => widget.loadFile(f.url)))
                        : const Icon(Icons.description_outlined, size: 40),
                ]),
              ],
              if (incident.canAddUpdate) ...[
                const SizedBox(height: AppSpacing.lg),
                Text(strings.incidentAddUpdate, style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: AppSpacing.sm),
                AppTextField(key: const Key('incident-update'), label: strings.incidentUpdateHint, controller: _update, maxLines: 3, enabled: !busy),
                if (_error != null) Padding(padding: const EdgeInsets.only(top: AppSpacing.sm), child: Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error))),
                const SizedBox(height: AppSpacing.sm),
                AppButton(label: strings.incidentSendUpdate, loading: busy, onPressed: _send),
                const SizedBox(height: AppSpacing.sm),
                AppButton(label: strings.incidentAddFile, secondary: true, icon: Icons.add_a_photo_outlined, onPressed: busy ? null : _addFile),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _FileThumb extends StatelessWidget {
  const _FileThumb({required this.load});

  final Future<Uint8List> Function() load;

  @override
  Widget build(BuildContext context) => FutureBuilder<Uint8List>(
    future: load(),
    builder: (context, snapshot) => snapshot.hasData
        ? ClipRRect(borderRadius: BorderRadius.circular(AppRadius.sm), child: Image.memory(snapshot.data!, fit: BoxFit.cover))
        : snapshot.hasError
        ? const Icon(Icons.broken_image_outlined)
        : const Center(child: CircularProgressIndicator(strokeWidth: 2)),
  );
}

/// Trusted contacts: list, add, edit and remove, always with the consent choices spelled out.
class TrustedContactsScreen extends ConsumerWidget {
  const TrustedContactsScreen({super.key, required this.providers, required this.strings});

  final SafetyProviders providers;
  final SafetyStrings strings;

  Future<void> _edit(BuildContext context, WidgetRef ref, {TrustedContact? existing}) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
        child: TrustedContactForm(providers: providers, strings: strings, existing: existing),
      ),
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(providers.contacts);
    return Scaffold(
      appBar: AppBar(title: Text(strings.contactsTitle)),
      floatingActionButton: async.value == null || async.value!.isFull
          ? null
          : FloatingActionButton.extended(key: const Key('contacts-add'), onPressed: () => _edit(context, ref), icon: const Icon(Icons.person_add_alt_1), label: Text(strings.contactsAdd)),
      body: async.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorState(
          title: strings.errorGeneric,
          message: safetyErrorMessage(strings, e),
          retryLabel: strings.retry,
          offline: e is ApiException && e.isOffline,
          onRetry: () => ref.invalidate(providers.contacts),
        ),
        data: (list) => ListView(
          padding: const EdgeInsets.all(AppSpacing.md),
          children: [
            Text(strings.contactsIntro),
            const SizedBox(height: AppSpacing.md),
            if (list.items.isEmpty) EmptyState(title: strings.contactsEmpty, icon: Icons.contacts_outlined),
            for (final c in list.items)
              Card(
                child: ListTile(
                  key: Key('contact-${c.id}'),
                  leading: const Icon(Icons.person_outline),
                  title: Text(c.name),
                  subtitle: Text([ltrIsolate(c.phoneMasked), if (c.relationship != null) strings.relationshipName(c.relationship!)].join(' · ')),
                  trailing: Wrap(spacing: 4, children: [if (c.notifyOnSos) const Icon(Icons.sos_rounded, size: 18), if (c.shareTrip) const Icon(Icons.share_location_outlined, size: 18)]),
                  onTap: () => _edit(context, ref, existing: c),
                ),
              ),
            if (list.isFull) Padding(padding: const EdgeInsets.only(top: AppSpacing.md), child: Text(strings.contactsLimit(list.max))),
            const SizedBox(height: 88),
          ],
        ),
      ),
    );
  }
}

class TrustedContactForm extends ConsumerStatefulWidget {
  const TrustedContactForm({super.key, required this.providers, required this.strings, this.existing});

  final SafetyProviders providers;
  final SafetyStrings strings;
  final TrustedContact? existing;

  @override
  ConsumerState<TrustedContactForm> createState() => _TrustedContactFormState();
}

class _TrustedContactFormState extends ConsumerState<TrustedContactForm> {
  late final _name = TextEditingController(text: widget.existing?.name);
  final _phone = TextEditingController();
  late String? _relationship = widget.existing?.relationship;
  late bool _sos = widget.existing?.notifyOnSos ?? true;
  late bool _trip = widget.existing?.shareTrip ?? true;
  late bool _location = widget.existing?.shareLocationOnSos ?? false;
  bool _consent = false;
  bool _busy = false;
  String? _error;
  String? _nameError;
  String? _phoneError;
  String? _consentError;
  final _key = _uuid.v4();

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final strings = widget.strings;
    setState(() {
      _nameError = _name.text.trim().length < 2 ? strings.contactRequired : null;
      _phoneError = _phone.text.trim().isEmpty ? strings.contactRequired : null;
      _consentError = _consent ? null : strings.contactConsentRequired;
      _error = null;
    });
    if (_nameError != null || _phoneError != null || _consentError != null) return;
    setState(() => _busy = true);
    final input = TrustedContactInput(
      name: _name.text.trim(),
      phone: _phone.text.trim(),
      consentConfirmed: true,
      relationship: _relationship,
      notifyOnSos: _sos,
      shareTrip: _trip,
      shareLocationOnSos: _location && _sos,
    );
    try {
      final notifier = ref.read(widget.providers.contacts.notifier);
      if (widget.existing == null) {
        await notifier.add(input, key: _key);
      } else {
        await notifier.change(widget.existing!.id, input);
      }
      if (mounted) Navigator.of(context).pop();
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _phoneError = e.fieldErrors['phone']?.first;
        _error = safetyErrorMessage(strings, e);
      });
    } on Object catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = safetyErrorMessage(strings, e);
        });
      }
    }
  }

  Future<void> _delete() async {
    final strings = widget.strings;
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        content: Text(strings.contactDeleteConfirm),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(strings.cancel)),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: Text(strings.contactDelete)),
        ],
      ),
    );
    if (ok != true) return;
    setState(() => _busy = true);
    try {
      await ref.read(widget.providers.contacts.notifier).remove(widget.existing!.id);
      if (mounted) Navigator.of(context).pop();
    } on Object catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = safetyErrorMessage(strings, e);
        });
      }
    }
  }

  SafetyStrings get strings => widget.strings;

  @override
  Widget build(BuildContext context) => SafeArea(
    child: SingleChildScrollView(
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AppTextField(key: const Key('contact-name'), label: strings.contactName, controller: _name, errorText: _nameError, enabled: !_busy),
          const SizedBox(height: AppSpacing.md),
          AppTextField(
            key: const Key('contact-phone'),
            label: strings.contactPhone,
            hint: widget.existing == null ? strings.contactPhoneHint : '${widget.existing!.phoneMasked}  ·  ${strings.contactPhoneHint}',
            controller: _phone,
            keyboardType: TextInputType.phone,
            textDirection: TextDirection.ltr,
            errorText: _phoneError,
            enabled: !_busy,
          ),
          const SizedBox(height: AppSpacing.md),
          DropdownButtonFormField<String?>(
            initialValue: _relationship,
            decoration: InputDecoration(labelText: strings.contactRelationship),
            items: [for (final r in contactRelationships) DropdownMenuItem(value: r, child: Text(strings.relationshipName(r)))],
            onChanged: _busy ? null : (v) => setState(() => _relationship = v),
          ),
          const SizedBox(height: AppSpacing.md),
          Text(strings.consentTitle, style: Theme.of(context).textTheme.titleSmall),
          SwitchListTile(contentPadding: EdgeInsets.zero, title: Text(strings.consentSos), value: _sos, onChanged: _busy ? null : (v) => setState(() => _sos = v)),
          SwitchListTile(contentPadding: EdgeInsets.zero, title: Text(strings.consentTrip), value: _trip, onChanged: _busy ? null : (v) => setState(() => _trip = v)),
          SwitchListTile(contentPadding: EdgeInsets.zero, title: Text(strings.consentLocation), value: _location && _sos, onChanged: _busy || !_sos ? null : (v) => setState(() => _location = v)),
          CheckboxListTile(
            key: const Key('contact-consent'),
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            title: Text(strings.consentConfirm),
            subtitle: _consentError == null ? null : Text(_consentError!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
            value: _consent,
            onChanged: _busy ? null : (v) => setState(() => _consent = v ?? false),
          ),
          if (_error != null) Padding(padding: const EdgeInsets.only(bottom: AppSpacing.sm), child: Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error))),
          AppButton(key: const Key('contact-save'), label: strings.contactSave, loading: _busy, onPressed: _save),
          if (widget.existing != null) ...[
            const SizedBox(height: AppSpacing.sm),
            AppButton(label: strings.contactDelete, secondary: true, icon: Icons.delete_outline, onPressed: _busy ? null : _delete),
          ],
        ],
      ),
    ),
  );
}

/// "Share trip with my trusted contacts": sends the link by SMS to the contacts who consented, shows what happened, lets the person
/// copy the link or stop sharing.
class TripShareSheet extends ConsumerStatefulWidget {
  const TripShareSheet({super.key, required this.providers, required this.strings, required this.bookingId, this.onContacts});

  final SafetyProviders providers;
  final SafetyStrings strings;
  final String bookingId;
  final VoidCallback? onContacts;

  @override
  ConsumerState<TripShareSheet> createState() => _TripShareSheetState();
}

class _TripShareSheetState extends ConsumerState<TripShareSheet> {
  TripShareResult? _result;
  String? _error;
  bool _stopped = false;

  Future<void> _share() async {
    setState(() {
      _error = null;
      _stopped = false;
    });
    try {
      final result = await ref.read(widget.providers.actions.notifier).shareTrip(widget.bookingId);
      if (mounted) setState(() => _result = result);
    } on Object catch (e) {
      if (mounted) setState(() => _error = safetyErrorMessage(widget.strings, e));
    }
  }

  Future<void> _stop() async {
    try {
      await ref.read(widget.providers.actions.notifier).stopShare(widget.bookingId);
      if (mounted) {
        setState(() {
          _result = null;
          _stopped = true;
        });
      }
    } on Object catch (e) {
      if (mounted) setState(() => _error = safetyErrorMessage(widget.strings, e));
    }
  }

  @override
  Widget build(BuildContext context) {
    final strings = widget.strings;
    final busy = ref.watch(widget.providers.actions);
    final contacts = ref.watch(widget.providers.contacts).value;
    final result = _result;
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(AppSpacing.md, 0, AppSpacing.md, AppSpacing.md),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(strings.shareTitle, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: AppSpacing.sm),
            Text(strings.shareBody),
            const SizedBox(height: AppSpacing.md),
            if (contacts != null && contacts.items.where((c) => c.shareTrip).isEmpty) ...[
              Text(strings.shareNoContacts, key: const Key('share-no-contacts')),
              if (widget.onContacts != null) ...[const SizedBox(height: AppSpacing.sm), AppButton(label: strings.manageContacts, secondary: true, onPressed: widget.onContacts)],
            ] else if (result == null)
              AppButton(key: const Key('share-send'), label: strings.shareSend, icon: Icons.send, loading: busy, onPressed: _share),
            if (result != null) ...[
              StatusChip(label: strings.shareResult(result.sent, result.total), tone: result.failed > 0 ? ChipTone.warning : ChipTone.success),
              if (result.expiresAt != null) Padding(padding: const EdgeInsets.only(top: AppSpacing.sm), child: Text(strings.shareExpires(TimeOfDay.fromDateTime(result.expiresAt!).format(context)))),
              const SizedBox(height: AppSpacing.sm),
              AppButton(
                label: strings.shareCopy,
                secondary: true,
                icon: Icons.copy,
                onPressed: () async {
                  await Clipboard.setData(ClipboardData(text: result.url));
                  if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(strings.shareCopied)));
                },
              ),
              const SizedBox(height: AppSpacing.sm),
              AppButton(label: strings.shareStop, secondary: true, icon: Icons.stop_circle_outlined, loading: busy, onPressed: _stop),
            ],
            if (_stopped) Padding(padding: const EdgeInsets.only(top: AppSpacing.sm), child: Text(strings.shareStopped)),
            if (_error != null) Padding(padding: const EdgeInsets.only(top: AppSpacing.sm), child: Text(_error!, key: const Key('share-error'), style: TextStyle(color: Theme.of(context).colorScheme.error))),
          ],
        ),
      ),
    );
  }
}
