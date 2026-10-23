import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../map/domain/place_selection.dart';
import '../../../map/presentation/providers/map_providers.dart';
import '../../../map/presentation/screens/map_screen_args.dart';
import '../providers/load_form_providers.dart';

/// Courier: the customer reads the CURRENT restricted-goods policy and
/// acknowledges it. The acknowledgement is tied to the policy id that was on
/// screen; the server refuses a stale one and the policy is re-read.
class RestrictedGoodsSection extends ConsumerWidget {
  const RestrictedGoodsSection({super.key, this.showError = false});

  /// Highlights the missing acknowledgement after a submit attempt.
  final bool showError;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final policy = ref.watch(restrictedGoodsPolicyProvider);
    final acknowledged = ref.watch(restrictedGoodsAckProvider);
    return Card(
      key: const Key('restricted-goods'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: policy.when(
          loading: () => const Skeleton(height: 96),
          error: (e, _) => Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                e is ApiException && e.isOffline
                    ? l10n.errorOfflineBody
                    : l10n.rgErrUnavailable,
                key: const Key('restricted-goods-error'),
              ),
              TextButton(
                key: const Key('restricted-goods-retry'),
                onPressed: () => ref.invalidate(restrictedGoodsPolicyProvider),
                child: Text(l10n.actionRetry),
              ),
            ],
          ),
          data: (doc) {
            final ticked = acknowledged == doc.id;
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l10n.rgTitle, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.xs),
                Text(l10n.rgIntro, style: theme.textTheme.bodyMedium),
                ExpansionTile(
                  key: const Key('restricted-goods-policy'),
                  tilePadding: EdgeInsets.zero,
                  title: Text(
                    doc.title.isEmpty ? l10n.rgReadPolicy : doc.title,
                  ),
                  children: [
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        bottom: AppSpacing.sm,
                      ),
                      child: Text(
                        doc.content,
                        key: const Key('restricted-goods-content'),
                      ),
                    ),
                  ],
                ),
                CheckboxListTile(
                  key: const Key('restricted-goods-ack'),
                  contentPadding: EdgeInsets.zero,
                  controlAffinity: ListTileControlAffinity.leading,
                  title: Text(l10n.rgAcknowledge(doc.version)),
                  value: ticked,
                  onChanged: (on) {
                    final notifier = ref.read(
                      restrictedGoodsAckProvider.notifier,
                    );
                    on == true
                        ? notifier.acknowledge(doc.id)
                        : notifier.clear();
                  },
                ),
                if (showError && !ticked)
                  Text(
                    l10n.rgErrRequired,
                    key: const Key('restricted-goods-required'),
                    style: TextStyle(color: theme.colorScheme.error),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

/// Intermediate stops (moving / freight) each with the person to meet there.
/// The contact travels encrypted and only the selected provider sees it.
class StopsSection extends ConsumerWidget {
  const StopsSection({
    super.key,
    required this.maxStops,
    this.showErrors = false,
  });

  final int maxStops;
  final bool showErrors;

  Future<void> _add(BuildContext context, WidgetRef ref) async {
    final endpoints = ref.read(tripEndpointsProvider);
    final picked = await context.push<PlaceSelection>(
      Routes.placeSearch,
      extra: PlaceSearchArgs(
        mode: PlaceSearchMode.destination,
        near: endpoints.pickup?.point,
      ),
    );
    if (picked != null) ref.read(bookingStopsProvider.notifier).add(picked);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final stops = ref.watch(bookingStopsProvider);
    return Column(
      key: const Key('stops-section'),
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(l10n.stpTitle, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.xs),
        Text(l10n.stpIntro, style: theme.textTheme.bodySmall),
        for (var i = 0; i < stops.length; i++)
          _StopCard(
            key: ValueKey(
              'stop-$i-${stops[i].place.point.latitude}-${stops[i].place.point.longitude}',
            ),
            index: i,
            count: stops.length,
            draft: stops[i],
            showIssue: showErrors,
          ),
        const SizedBox(height: AppSpacing.sm),
        if (stops.length < maxStops)
          AppButton(
            key: const Key('stop-add'),
            label: l10n.stpAdd,
            icon: Icons.add_location_alt_outlined,
            secondary: true,
            onPressed: () => _add(context, ref),
          )
        else
          Text(
            l10n.stpMax(maxStops),
            key: const Key('stop-max'),
            style: theme.textTheme.bodySmall,
          ),
      ],
    );
  }
}

class _StopCard extends ConsumerStatefulWidget {
  const _StopCard({
    super.key,
    required this.index,
    required this.count,
    required this.draft,
    required this.showIssue,
  });

  final int index;
  final int count;
  final StopDraft draft;
  final bool showIssue;

  @override
  ConsumerState<_StopCard> createState() => _StopCardState();
}

class _StopCardState extends ConsumerState<_StopCard> {
  late final _name = TextEditingController(text: widget.draft.name);
  late final _phone = TextEditingController(text: widget.draft.phone);
  late final _note = TextEditingController(text: widget.draft.note);

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _note.dispose();
    super.dispose();
  }

  void _changed() => ref
      .read(bookingStopsProvider.notifier)
      .update(
        widget.index,
        (d) =>
            d.copyWith(name: _name.text, phone: _phone.text, note: _note.text),
      );

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final notifier = ref.read(bookingStopsProvider.notifier);
    final place = widget.draft.place;
    final issue = widget.showIssue ? widget.draft.issue : null;
    return Card(
      margin: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                const Icon(Icons.more_vert),
                const SizedBox(width: AppSpacing.xs),
                Expanded(
                  child: Text(
                    l10n.stpNumbered(
                      widget.index + 1,
                      place.title ?? place.address ?? l10n.mapPinnedLocation,
                    ),
                    style: theme.textTheme.titleSmall,
                  ),
                ),
                IconButton(
                  key: Key('stop-up-${widget.index}'),
                  tooltip: l10n.stpMoveUp,
                  onPressed: widget.index > 0
                      ? () => notifier.move(widget.index, -1)
                      : null,
                  icon: const Icon(Icons.arrow_upward),
                ),
                IconButton(
                  key: Key('stop-down-${widget.index}'),
                  tooltip: l10n.stpMoveDown,
                  onPressed: widget.index < widget.count - 1
                      ? () => notifier.move(widget.index, 1)
                      : null,
                  icon: const Icon(Icons.arrow_downward),
                ),
                IconButton(
                  key: Key('stop-remove-${widget.index}'),
                  tooltip: l10n.stpRemove,
                  onPressed: () => notifier.remove(widget.index),
                  icon: const Icon(Icons.delete_outline),
                ),
              ],
            ),
            TextField(
              key: Key('stop-name-${widget.index}'),
              controller: _name,
              maxLength: 100,
              decoration: InputDecoration(
                labelText: l10n.stpContactName,
                counterText: '',
                errorText: issue == StopIssue.nameMissing
                    ? l10n.stpErrName
                    : null,
              ),
              onChanged: (_) => _changed(),
            ),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              key: Key('stop-phone-${widget.index}'),
              controller: _phone,
              keyboardType: TextInputType.phone,
              textDirection: TextDirection.ltr,
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'[0-9٠-٩+\s\-()]')),
              ],
              decoration: InputDecoration(
                labelText: l10n.stpContactPhone,
                errorText: issue == StopIssue.phoneInvalid
                    ? l10n.formInvalidPhone
                    : null,
              ),
              onChanged: (_) => _changed(),
            ),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              key: Key('stop-note-${widget.index}'),
              controller: _note,
              maxLength: 200,
              decoration: InputDecoration(
                labelText: l10n.stpNote,
                counterText: '',
              ),
              onChanged: (_) => _changed(),
            ),
          ],
        ),
      ),
    );
  }
}
