import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../domain/amount_input.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/job_providers.dart';
import 'job_texts.dart';
import 'keypad.dart';
import 'scope_builder.dart';

/// Opens the counter-offer keypad. Returns the submitted offer, or null when
/// the driver closed it.
Future<MyOffer?> showCounterOfferSheet(
  BuildContext context, {
  required RequestCard card,
  MyOffer? current,
}) => showModalBottomSheet<MyOffer>(
  context: context,
  isScrollControlled: true,
  useSafeArea: true,
  showDragHandle: true,
  builder: (_) => CounterOfferSheet(card: card, current: current),
);

/// Counter-offer: a large numeric keypad (no system keyboard), the bounds
/// from the API, an inline check before sending and the earnings preview the
/// server returns with the submission (only when it computes one).
class CounterOfferSheet extends ConsumerStatefulWidget {
  const CounterOfferSheet({super.key, required this.card, this.current});

  final RequestCard card;
  final MyOffer? current;

  @override
  ConsumerState<CounterOfferSheet> createState() => _CounterOfferSheetState();
}

class _CounterOfferSheetState extends ConsumerState<CounterOfferSheet> {
  late String _text;
  bool _busy = false;
  Object? _error;
  MyOffer? _result;

  /// Moving / freight: the scope is built first, the price second.
  late bool _scopeStep = widget.card.scopeRequired;
  OfferScope? _scope;
  Map<String, ScopeIssue> _scopeIssues = const {};

  int get _exponent => widget.card.proposedFare.exponent;

  @override
  void initState() {
    super.initState();
    final start = widget.current?.amount ?? widget.card.proposedFare;
    _text = amountInputText(start.minor, exponent: _exponent);
  }

  void _digit(String d) {
    if (_busy) return;
    setState(() {
      _error = null;
      if (_text == '0') {
        _text = d;
      } else if (_text.length < 9) {
        final next = '$_text$d';
        if (parseAmountMinor(next, exponent: _exponent) != null) _text = next;
      }
    });
  }

  void _decimal() {
    if (_busy || _exponent == 0 || _text.contains('.')) return;
    setState(() {
      _error = null;
      _text = _text.isEmpty ? '0.' : '$_text.';
    });
  }

  void _back() {
    if (_busy || _text.isEmpty) return;
    setState(() {
      _error = null;
      _text = _text.substring(0, _text.length - 1);
    });
  }

  int? get _minor => parseAmountMinor(_text, exponent: _exponent);

  AmountCheck get _check => checkAmount(
    _minor,
    minMinor: widget.card.counter.minMinor,
    maxMinor: widget.card.counter.maxMinor,
    fareMinor: widget.card.proposedFare.minor,
  );

  /// The draft to start from: the offer being revised, else what the customer
  /// asked for, else the template defaults.
  OfferScope _initialScope(ScopeTemplate template) {
    final vehicle = template.vehicleClass ?? '';
    final current = widget.current?.scope;
    if (current != null) {
      return OfferScope.fromJson(current, vehicleClass: vehicle);
    }
    final requested = widget.card.requestedScope;
    if (requested != null) {
      return OfferScope.fromJson(requested, vehicleClass: vehicle);
    }
    return OfferScope(vehicleClass: vehicle);
  }

  void _continueFromScope(ScopeTemplate template) {
    final scope = _scope ?? _initialScope(template);
    final issues = scope.validate(template);
    setState(() {
      _scopeIssues = issues;
      if (issues.isEmpty) _scopeStep = false;
    });
  }

  Future<void> _submit() async {
    final minor = _minor;
    if (_busy || minor == null || _check != AmountCheck.ok) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final offer = await ref
          .read(jobActionsProvider)
          .counterOffer(
            widget.card.id,
            minor,
            currency: widget.card.proposedFare.currency,
            scope: _scope?.toJson(),
          );
      if (!mounted) return;
      if (offer.earningsPreview == null) {
        Navigator.of(context).pop(offer);
      } else {
        setState(() {
          _busy = false;
          _result = offer;
        });
      }
    } on Object catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = e;
        // The server refused the scope: show its reasons on the same fields.
        if (e is ApiException && e.code == 'offer_scope_invalid') {
          _scopeIssues = scopeIssuesFromServer(e.meta['scope_errors']);
          _scopeStep = true;
        } else if (e is ApiException &&
            e.code == 'offer_scope_vehicle_mismatch') {
          _scopeIssues = const {'vehicle_class': ScopeIssue.invalidChoice};
          _scopeStep = true;
        }
      });
      if (errorMeansLost(e)) Navigator.of(context).pop();
    }
  }

  Widget _scopePage(
    BuildContext context,
    AppLocalizations l10n,
    ThemeData theme,
  ) {
    final template = ref.watch(scopeTemplateProvider);
    return Padding(
      padding: const EdgeInsetsDirectional.fromSTEB(
        AppSpacing.md,
        0,
        AppSpacing.md,
        AppSpacing.md,
      ),
      child: template.when(
        loading: () => const SizedBox(
          height: 240,
          child: Center(child: CircularProgressIndicator()),
        ),
        error: (e, _) => Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(jobErrorText(l10n, e), textAlign: TextAlign.center),
            const SizedBox(height: AppSpacing.md),
            AppButton(
              key: const Key('scope-retry'),
              label: l10n.actionRetry,
              onPressed: () => ref.invalidate(scopeTemplateProvider),
            ),
          ],
        ),
        data: (t) {
          if (t.vehicleClass == null) {
            return Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  l10n.scNoVehicle,
                  key: const Key('scope-no-vehicle'),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: AppSpacing.md),
                AppButton(
                  label: l10n.actionDone,
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ],
            );
          }
          final scope = _scope ??= _initialScope(t);
          final hint = _error == null ? null : jobErrorText(l10n, _error!);
          return Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                l10n.scTitle,
                style: theme.textTheme.titleLarge,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(
                l10n.scIntro,
                textAlign: TextAlign.center,
                style: theme.textTheme.bodySmall,
              ),
              if (widget.card.requestedScopeSummary.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.sm),
                ExpansionTile(
                  key: const Key('scope-requested'),
                  title: Text(l10n.scRequestedTitle),
                  children: [
                    ScopeSummaryList(
                      lines: widget.card.requestedScopeSummary,
                      scope: widget.card.requestedScope,
                      strings: scopeStrings(l10n),
                      showTitle: false,
                      dense: true,
                    ),
                  ],
                ),
              ],
              const SizedBox(height: AppSpacing.sm),
              Flexible(
                child: SingleChildScrollView(
                  child: ScopeBuilder(
                    scope: scope,
                    template: t,
                    issues: _scopeIssues,
                    onChanged: (v) => setState(() {
                      _scope = v;
                      _error = null;
                      if (_scopeIssues.isNotEmpty) _scopeIssues = v.validate(t);
                    }),
                  ),
                ),
              ),
              if (hint != null)
                Padding(
                  padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                  child: Text(
                    hint,
                    key: const Key('scope-error'),
                    textAlign: TextAlign.center,
                    style: TextStyle(color: theme.colorScheme.error),
                  ),
                ),
              const SizedBox(height: AppSpacing.sm),
              AppButton(
                key: const Key('scope-continue'),
                label: l10n.scContinue,
                onPressed: () => _continueFromScope(t),
              ),
            ],
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final card = widget.card;
    final fare = card.proposedFare;
    if (_scopeStep) return _scopePage(context, l10n, theme);
    final result = _result;
    final preview = result?.earningsPreview;
    final minor = _minor;
    final check = _check;
    final bounds = card.counter;

    String? hint;
    if (_error != null) {
      hint = jobErrorText(l10n, _error!);
    } else if (_text.isNotEmpty) {
      hint = switch (check) {
        AmountCheck.belowMin => l10n.jbAmountBelow(
          formatter.format(
            Money(
              minor: bounds.minMinor ?? 0,
              currency: fare.currency,
              exponent: fare.exponent,
            ),
          ),
        ),
        AmountCheck.aboveMax => l10n.jbAmountAbove(
          formatter.format(
            Money(
              minor: bounds.maxMinor ?? 0,
              currency: fare.currency,
              exponent: fare.exponent,
            ),
          ),
        ),
        AmountCheck.sameAsFare => l10n.jbAmountSame,
        _ => null,
      };
    }

    return Padding(
      padding: const EdgeInsetsDirectional.fromSTEB(
        AppSpacing.md,
        0,
        AppSpacing.md,
        AppSpacing.md,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              l10n.jbCounterTitle,
              style: theme.textTheme.titleLarge,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              l10n.jbCustomerFareIs(formatter.format(fare)),
              textAlign: TextAlign.center,
            ),
            if (bounds.minMinor != null && bounds.maxMinor != null)
              Text(
                l10n.jbAllowedRange(
                  formatter.format(
                    Money(
                      minor: bounds.minMinor!,
                      currency: fare.currency,
                      exponent: fare.exponent,
                    ),
                  ),
                  formatter.format(
                    Money(
                      minor: bounds.maxMinor!,
                      currency: fare.currency,
                      exponent: fare.exponent,
                    ),
                  ),
                ),
                key: const Key('counter-bounds'),
                textAlign: TextAlign.center,
                style: theme.textTheme.bodySmall,
              ),
            const SizedBox(height: AppSpacing.md),
            if (preview != null && result != null) ...[
              Icon(
                Icons.check_circle,
                size: 48,
                color: theme.colorScheme.primary,
              ),
              Text(
                l10n.jbOfferSent,
                textAlign: TextAlign.center,
                style: theme.textTheme.titleMedium,
              ),
              const SizedBox(height: AppSpacing.sm),
              _PreviewRow(
                label: l10n.jbPreviewGross,
                value: formatter.format(
                  Money(
                    minor: preview.grossMinor,
                    currency: fare.currency,
                    exponent: fare.exponent,
                  ),
                ),
              ),
              _PreviewRow(
                label: l10n.jbPreviewCommission,
                value: formatter.format(
                  Money(
                    minor: preview.commissionMinor,
                    currency: fare.currency,
                    exponent: fare.exponent,
                  ),
                ),
              ),
              _PreviewRow(
                label: l10n.jbPreviewNet,
                strong: true,
                value: formatter.format(
                  Money(
                    minor: preview.netMinor,
                    currency: fare.currency,
                    exponent: fare.exponent,
                  ),
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              AppButton(
                key: const Key('counter-done'),
                label: l10n.actionDone,
                onPressed: () => Navigator.of(context).pop(result),
              ),
            ] else ...[
              Container(
                padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                decoration: BoxDecoration(
                  color: theme.colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(AppRadius.md),
                ),
                child: IsolatedText(
                  _text.isEmpty ? '0' : _text,
                  key: const Key('counter-amount'),
                  textAlign: TextAlign.center,
                  style: theme.textTheme.displayMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              ConstrainedBox(
                constraints: const BoxConstraints(minHeight: 48),
                child: Center(
                  child: hint == null
                      ? (minor != null && check == AmountCheck.ok
                            ? Text(
                                formatter.format(
                                  Money(
                                    minor: minor,
                                    currency: fare.currency,
                                    exponent: fare.exponent,
                                  ),
                                ),
                                style: theme.textTheme.titleMedium,
                              )
                            : null)
                      : Text(
                          hint,
                          key: const Key('counter-hint'),
                          textAlign: TextAlign.center,
                          style: TextStyle(color: theme.colorScheme.error),
                        ),
                ),
              ),
              if (card.scopeRequired)
                TextButton.icon(
                  key: const Key('scope-edit'),
                  onPressed: _busy
                      ? null
                      : () => setState(() => _scopeStep = true),
                  icon: const Icon(Icons.tune),
                  label: Text(l10n.scEditScope),
                ),
              BigKeypad(
                onDigit: _digit,
                onBackspace: _back,
                onDecimal: _exponent == 0 ? null : _decimal,
                backspaceLabel: l10n.jbKeypadBackspace,
                decimalLabel: l10n.jbKeypadDecimal,
              ),
              const SizedBox(height: AppSpacing.sm),
              AppButton(
                key: const Key('counter-submit'),
                label: l10n.jbSendOffer,
                loading: _busy,
                onPressed: check == AmountCheck.ok ? _submit : null,
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _PreviewRow extends StatelessWidget {
  const _PreviewRow({
    required this.label,
    required this.value,
    this.strong = false,
  });

  final String label;
  final String value;
  final bool strong;

  @override
  Widget build(BuildContext context) {
    final style = strong
        ? Theme.of(context).textTheme.titleMedium
        : Theme.of(context).textTheme.bodyLarge;
    return Padding(
      padding: const EdgeInsetsDirectional.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(child: Text(label, style: style)),
          Text(value, style: style),
        ],
      ),
    );
  }
}
