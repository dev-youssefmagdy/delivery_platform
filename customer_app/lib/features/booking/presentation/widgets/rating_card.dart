import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/booking_entities.dart';
import '../providers/booking_providers.dart';
import 'booking_sections.dart';
import 'booking_texts.dart';

/// Rating of the provider with tags and an optional comment. Shown only while
/// the server lists `rate` in `allowed_actions`.
class RatingCard extends ConsumerStatefulWidget {
  const RatingCard({super.key, required this.bookingId});

  final String bookingId;

  @override
  ConsumerState<RatingCard> createState() => _RatingCardState();
}

class _RatingCardState extends ConsumerState<RatingCard> {
  final _comment = TextEditingController();
  int _stars = 0;
  final _tags = <ProviderRatingTag>{};
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _comment.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final l10n = AppLocalizations.of(context);
    if (_stars == 0) {
      setState(() => _error = l10n.bkRatingPickStars);
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref
          .read(bookingActionsProvider)
          .rate(
            widget.bookingId,
            stars: _stars,
            tags: [for (final t in _tags) t.value],
            comment: _comment.text,
          );
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(l10n.bkRatingThanks)));
      }
    } on Object catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = bookingErrorText(l10n, e);
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    // Positive tags for 4-5 stars, issues for 1-3; none before a choice.
    final tags = [
      for (final t in ProviderRatingTag.values)
        if (_stars == 0 || t.positive == (_stars >= 4)) t,
    ];
    return Card(
      key: const Key('rating-card'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.bkRatingTitle, style: theme.textTheme.titleMedium),
            Center(
              child: RatingStars(
                value: _stars,
                semanticLabel: l10n.bkRatingTitle,
                onChanged: _busy
                    ? null
                    : (v) => setState(() {
                        _stars = v;
                        _tags.removeWhere((t) => t.positive != (v >= 4));
                      }),
              ),
            ),
            if (_stars > 0) Center(child: Text(ratingTone(l10n, _stars))),
            if (_stars > 0) ...[
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  for (final tag in tags)
                    FilterChip(
                      key: Key('rating-tag-${tag.value}'),
                      label: Text(ratingTagText(l10n, tag)),
                      selected: _tags.contains(tag),
                      onSelected: _busy
                          ? null
                          : (on) => setState(
                              () => on ? _tags.add(tag) : _tags.remove(tag),
                            ),
                    ),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              TextField(
                key: const Key('rating-comment'),
                controller: _comment,
                enabled: !_busy,
                maxLines: 3,
                maxLength: 1000,
                decoration: InputDecoration(labelText: l10n.bkRatingComment),
              ),
            ],
            if (_error != null)
              Text(_error!, style: TextStyle(color: theme.colorScheme.error)),
            const SizedBox(height: AppSpacing.sm),
            AppButton(
              key: const Key('rating-submit'),
              label: l10n.bkRatingSubmit,
              loading: _busy,
              onPressed: _submit,
            ),
          ],
        ),
      ),
    );
  }
}
