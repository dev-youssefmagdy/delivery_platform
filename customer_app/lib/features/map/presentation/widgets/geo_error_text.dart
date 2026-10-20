import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

/// Maps a geography/maps failure to ARB text by the server's stable code (no
/// server strings in widgets).
String geoErrorText(AppLocalizations l10n, Object error) {
  if (error is! ApiException) return l10n.errorGenericBody;
  if (error.isOffline) return l10n.errorOfflineBody;
  switch (error.code) {
    case 'maps_unavailable':
      return l10n.mapsUnavailable;
    case 'outside_service_area':
      return l10n.mapOutsideBody;
    case 'saved_place_type_taken':
      return l10n.savedPlaceTypeTaken;
    case 'saved_place_limit_reached':
      return l10n.savedPlaceLimit;
    case 'maps_query_too_short':
      return l10n.searchMinChars;
  }
  return l10n.errorGenericBody;
}

/// True when the failure is the maps provider (search/reverse can degrade to
/// "choose on the map") rather than the network or the account.
bool isMapsOutage(Object error) =>
    error is ApiException && error.code == 'maps_unavailable';
