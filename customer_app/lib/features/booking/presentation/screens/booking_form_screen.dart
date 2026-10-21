import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../catalog/presentation/providers/catalog_providers.dart';
import '../../../catalog/presentation/widgets/form_strings.dart';
import '../../../map/domain/place_selection.dart';
import '../../../map/presentation/providers/map_providers.dart';
import '../../../map/presentation/widgets/booking_map.dart';
import '../../domain/entities/booking_entities.dart';
import '../../domain/logic/fare_input.dart';
import '../../domain/logic/polyline.dart';
import '../providers/booking_providers.dart';
import '../widgets/booking_texts.dart';

PlaceInfo placeInfoOf(PlaceSelection s) => PlaceInfo(
  lat: s.point.latitude,
  lng: s.point.longitude,
  title: s.title,
  line: s.address,
);

/// Booking form (SRS 6.4): route confirmation, the category's dynamic form,
/// the reference estimate as guidance, the customer's own fare with bounds,
/// payment (cash), a summary, and a submit that keeps all input on errors.
class BookingFormScreen extends ConsumerStatefulWidget {
  const BookingFormScreen({super.key});

  @override
  ConsumerState<BookingFormScreen> createState() => _BookingFormScreenState();
}

class _BookingFormScreenState extends ConsumerState<BookingFormScreen> {
  final _fareController = TextEditingController();
  DynamicFormController? _form;
  int? _formCategoryId;
  FareCheck? _fareError;
  bool _prefilledFare = false;

  @override
  void initState() {
    super.initState();
    final fare = ref.read(bookingDraftProvider).fareMinor;
    if (fare != null) {
      _fareController.text = fareInputText(fare, exponent: _exponent());
      _prefilledFare = true;
    }
  }

  @override
  void dispose() {
    _fareController.dispose();
    _form?.dispose();
    super.dispose();
  }

  int _exponent() =>
      ref.read(appConfigProvider).value?.currencyExponent ?? 2;

  DynamicFormController _formFor(ServiceCategoryInfo category) {
    if (_formCategoryId != category.id) {
      _form?.dispose();
      _formCategoryId = category.id;
      _form = DynamicFormController(
        fields: category.formSchema.fields,
        initialValues:
            ref.read(serviceDetailsProvider)[category.id] ?? const {},
      );
    }
    return _form!;
  }

  void _onFareChanged(String text) {
    final notifier = ref.read(bookingDraftProvider.notifier);
    final minor = parseFareMinor(text, exponent: _exponent());
    notifier.setFare(minor);
    setState(() => _fareError = null);
  }

  void _setFare(int minor) {
    _fareController.text = fareInputText(minor, exponent: _exponent());
    ref.read(bookingDraftProvider.notifier).setFare(minor);
    setState(() => _fareError = null);
  }

  Future<void> _submit(
    ServiceCategoryInfo category,
    TripEndpoints endpoints,
    String currency,
  ) async {
    final pickup = endpoints.pickup;
    final destination = endpoints.destination;
    if (pickup == null || destination == null) return;
    final draft = ref.read(bookingDraftProvider);
    final check = _fareController.text.trim().isEmpty
        ? FareCheck.empty
        : checkFare(
            draft.fareMinor,
            minMinor: category.minFareMinor,
            maxMinor: category.maxFareMinor,
          );
    final form = _formFor(category);
    final formOk = category.formSchema.fields.isEmpty || form.validate();
    if (check != FareCheck.ok) {
      setState(() => _fareError = check);
    }
    if (check != FareCheck.ok || !formOk) return;

    final details = form.payload;
    ref.read(serviceDetailsProvider.notifier).save(category.id, details);
    final booking = await ref
        .read(bookingDraftProvider.notifier)
        .submit(
          BookingRequest(
            serviceCategoryId: category.id,
            pickup: placeInfoOf(pickup),
            dropoff: placeInfoOf(destination),
            serviceDetails: details,
            passengers: category.maxPassengers == null ? null : draft.passengers,
            proposedFareMinor: draft.fareMinor!,
            currency: currency,
            paymentMethod: draft.payment,
          ),
        );
    if (booking != null && mounted) {
      context.pushReplacement(Routes.booking(booking.id));
    }
  }

  String _fareErrorText(AppLocalizations l10n, ServiceCategoryInfo c, MoneyFormatter f, String currency) {
    final exp = _exponent();
    return switch (_fareError) {
      FareCheck.belowMinimum => l10n.bkFareBelowMin(
        f.format(Money(minor: c.minFareMinor!, currency: currency, exponent: exp)),
      ),
      FareCheck.aboveMaximum => l10n.bkFareAboveMax(
        f.format(Money(minor: c.maxFareMinor!, currency: currency, exponent: exp)),
      ),
      FareCheck.invalid => l10n.bkFareInvalid,
      _ => l10n.bkFareRequired,
    };
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final category = ref.watch(selectedCategoryProvider);
    final endpoints = ref.watch(tripEndpointsProvider);
    final draft = ref.watch(bookingDraftProvider);
    final config = ref.watch(appConfigProvider).value;
    final formatter = ref.watch(moneyFormatterProvider);
    final currency = config?.currencyCode ?? 'EGP';
    final exponent = config?.currencyExponent ?? 2;

    final pickup = endpoints.pickup;
    final destination = endpoints.destination;
    if (category == null || pickup == null || destination == null) {
      return Scaffold(
        appBar: AppBar(title: Text(l10n.bkFormTitle)),
        body: EmptyState(
          icon: Icons.route_outlined,
          title: l10n.bkFormIncompleteTitle,
          message: l10n.bkFormIncompleteBody,
          actionLabel: l10n.bkFormBackHome,
          onAction: () => context.pop(),
        ),
      );
    }

    final estimateArgs = EstimateArgs(
      origin: pickup.point,
      destination: destination.point,
      serviceCategoryId: category.id,
      details: ref.watch(serviceDetailsProvider)[category.id] ?? const {},
    );
    final estimate = ref.watch(routeEstimateProvider(estimateArgs));
    ref.listen(routeEstimateProvider(estimateArgs), (_, next) {
      final guidance = next.value?.guidance;
      if (guidance != null &&
          !_prefilledFare &&
          _fareController.text.trim().isEmpty) {
        _prefilledFare = true;
        _setFare(guidance.recommendedMinor);
      }
    });

    final route = estimate.value == null
        ? const <LatLng>[]
        : decodePolyline(estimate.value!.polyline);
    final form = _formFor(category);
    final suggestions = _suggestions(estimate.value?.guidance, category);
    final cashOnly = category.paymentMethods.isEmpty ||
        category.paymentMethods.contains(PaymentMethodType.cash);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.bkFormTitle)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          children: [
            SizedBox(
              height: 180,
              child: ClipRRect(
                borderRadius: BorderRadius.circular(AppRadius.md),
                child: BookingMap(
                  pickup: pickup.point,
                  destination: destination.point,
                  route: route,
                  semanticLabel: l10n.bkFormRouteLabel,
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            _RouteSummary(
              pickup: pickup.title ?? pickup.address ?? l10n.mapCurrentLocation,
              destination:
                  destination.title ?? destination.address ?? l10n.mapPinnedLocation,
              estimate: estimate,
              onRetry: () => ref.invalidate(routeEstimateProvider(estimateArgs)),
            ),
            const SizedBox(height: AppSpacing.md),
            Text(
              category.name.resolve(language),
              style: Theme.of(context).textTheme.titleLarge,
            ),
            if (category.formSchema.fields.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(
                l10n.catalogDetailsIntro,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: AppSpacing.sm),
              DynamicFormRenderer(
                controller: form,
                languageCode: language,
                strings: dynamicFormStrings(l10n),
                photoHandler: ImagePickerPhotoHandler(
                  cameraLabel: l10n.formPhotoCamera,
                  galleryLabel: l10n.formPhotoGallery,
                ),
              ),
            ],
            if (category.maxPassengers != null) ...[
              const SizedBox(height: AppSpacing.md),
              _PassengerStepper(
                value: draft.passengers,
                max: category.maxPassengers!,
                label: l10n.bkPassengers,
                onChanged: ref.read(bookingDraftProvider.notifier).setPassengers,
              ),
            ],
            const SizedBox(height: AppSpacing.md),
            _GuidanceCard(
              estimate: estimate,
              formatter: formatter,
              exponent: exponent,
            ),
            const SizedBox(height: AppSpacing.md),
            Text(l10n.bkFareTitle, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              key: const Key('fare-field'),
              controller: _fareController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'[0-9٠-٩.,٫]')),
              ],
              textInputAction: TextInputAction.done,
              onChanged: _onFareChanged,
              decoration: InputDecoration(
                labelText: l10n.bkFareLabel(currency),
                helperText: _bounds(l10n, category, formatter, currency, exponent),
                errorText: _fareError == null || _fareError == FareCheck.ok
                    ? null
                    : _fareErrorText(l10n, category, formatter, currency),
              ),
            ),
            if (suggestions.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  for (final minor in suggestions)
                    ActionChip(
                      key: Key('fare-suggestion-$minor'),
                      label: Text(
                        formatter.format(
                          Money(minor: minor, currency: currency, exponent: exponent),
                        ),
                      ),
                      onPressed: () => _setFare(minor),
                    ),
                ],
              ),
            ],
            const SizedBox(height: AppSpacing.md),
            Text(l10n.bkPaymentTitle, style: Theme.of(context).textTheme.titleMedium),
            ListTile(
              key: const Key('payment-cash'),
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.payments_outlined),
              title: Text(l10n.bkPaymentCash),
              subtitle: cashOnly ? null : Text(l10n.bkPaymentCashUnavailable),
              trailing: Icon(
                Icons.check_circle,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            _Summary(
              category: category.name.resolve(language),
              fare: draft.fareMinor == null
                  ? null
                  : formatter.format(
                      Money(
                        minor: draft.fareMinor!,
                        currency: currency,
                        exponent: exponent,
                      ),
                    ),
              estimate: estimate.value,
            ),
            const SizedBox(height: AppSpacing.md),
            if (draft.error != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
                child: Text(
                  bookingErrorText(l10n, draft.error!),
                  key: const Key('booking-error'),
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            AppButton(
              key: const Key('submit-booking'),
              label: l10n.bkSubmit,
              loading: draft.submitting,
              onPressed: () => _submit(category, endpoints, currency),
            ),
            const SizedBox(height: AppSpacing.md),
          ],
        ),
      ),
    );
  }

  String? _bounds(AppLocalizations l10n, ServiceCategoryInfo c, MoneyFormatter f, String currency, int exp) {
    final min = c.minFareMinor;
    final max = c.maxFareMinor;
    String fmt(int v) => f.format(Money(minor: v, currency: currency, exponent: exp));
    if (min != null && max != null) return l10n.bkFareBoundsBoth(fmt(min), fmt(max));
    if (min != null) return l10n.bkFareBoundsMin(fmt(min));
    if (max != null) return l10n.bkFareBoundsMax(fmt(max));
    return null;
  }

  List<int> _suggestions(PriceGuidance? g, ServiceCategoryInfo c) {
    if (g == null) return const [];
    final values = <int>{g.lowMinor, g.recommendedMinor, g.highMinor};
    return [
      for (final v in values)
        if ((c.minFareMinor == null || v >= c.minFareMinor!) &&
            (c.maxFareMinor == null || v <= c.maxFareMinor!))
          v,
    ]..sort();
  }
}

class _RouteSummary extends StatelessWidget {
  const _RouteSummary({
    required this.pickup,
    required this.destination,
    required this.estimate,
    required this.onRetry,
  });

  final String pickup;
  final String destination;
  final AsyncValue<RouteEstimate> estimate;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.trip_origin, size: 18),
                const SizedBox(width: AppSpacing.sm),
                Expanded(child: Text(pickup)),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            Row(
              children: [
                Icon(Icons.location_on, size: 18, color: theme.colorScheme.error),
                const SizedBox(width: AppSpacing.sm),
                Expanded(child: Text(destination)),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            estimate.when(
              loading: () => const Skeleton(height: 14, width: 160),
              error: (e, _) => Row(
                children: [
                  Expanded(
                    child: Text(
                      e is ApiException && e.isOffline
                          ? l10n.errorOfflineBody
                          : l10n.bkEstimateFailed,
                      style: theme.textTheme.bodySmall,
                    ),
                  ),
                  TextButton(onPressed: onRetry, child: Text(l10n.actionRetry)),
                ],
              ),
              data: (e) => Text(
                l10n.bkRouteFacts(
                  (e.distanceMeters / 1000).toStringAsFixed(1),
                  (e.durationSeconds / 60).ceil(),
                ),
                style: theme.textTheme.bodyMedium,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _GuidanceCard extends StatelessWidget {
  const _GuidanceCard({
    required this.estimate,
    required this.formatter,
    required this.exponent,
  });

  final AsyncValue<RouteEstimate> estimate;
  final MoneyFormatter formatter;
  final int exponent;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final guidance = estimate.value?.guidance;
    if (estimate.isLoading) return const Skeleton(height: 64);
    if (guidance == null) {
      if (estimate.hasError) return const SizedBox.shrink();
      return Text(
        l10n.bkGuidanceUnavailable,
        key: const Key('guidance-unavailable'),
        style: Theme.of(context).textTheme.bodySmall,
      );
    }
    String fmt(int v) => formatter.format(
      Money(minor: v, currency: guidance.currency, exponent: exponent),
    );
    return Card(
      key: const Key('guidance-card'),
      margin: EdgeInsets.zero,
      color: Theme.of(context).colorScheme.surfaceContainerLow,
      child: ExpansionTile(
        title: Text(l10n.bkGuidanceTitle),
        subtitle: Text(
          l10n.bkGuidanceRange(fmt(guidance.lowMinor), fmt(guidance.highMinor)),
        ),
        childrenPadding: const EdgeInsetsDirectional.all(AppSpacing.md),
        expandedCrossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(l10n.bkGuidanceRecommended(fmt(guidance.recommendedMinor))),
          const SizedBox(height: AppSpacing.sm),
          Text(
            l10n.bkGuidanceFactors,
            style: Theme.of(context).textTheme.labelLarge,
          ),
          for (final f in guidance.factors)
            Padding(
              padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
              child: Row(
                children: [
                  Expanded(child: Text(priceFactorText(l10n, f))),
                  Text(fmt(f.amountMinor)),
                ],
              ),
            ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            guidance.notice ?? l10n.bkGuidanceNotice,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
      ),
    );
  }
}

class _PassengerStepper extends StatelessWidget {
  const _PassengerStepper({
    required this.value,
    required this.max,
    required this.label,
    required this.onChanged,
  });

  final int value;
  final int max;
  final String label;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Expanded(child: Text(label, style: Theme.of(context).textTheme.titleMedium)),
      IconButton(
        key: const Key('passengers-minus'),
        onPressed: value > 1 ? () => onChanged(value - 1) : null,
        icon: const Icon(Icons.remove_circle_outline),
      ),
      Text('$value', style: Theme.of(context).textTheme.titleMedium),
      IconButton(
        key: const Key('passengers-plus'),
        onPressed: value < max ? () => onChanged(value + 1) : null,
        icon: const Icon(Icons.add_circle_outline),
      ),
    ],
  );
}

class _Summary extends StatelessWidget {
  const _Summary({required this.category, required this.fare, required this.estimate});

  final String category;
  final String? fare;
  final RouteEstimate? estimate;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l10n.bkSummaryTitle, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: AppSpacing.xs),
            Text(category),
            Text(l10n.bkSummaryFare(fare ?? l10n.bkSummaryFareNone)),
            Text(l10n.bkSummaryPayment(l10n.bkPaymentCash)),
          ],
        ),
      ),
    );
  }
}
