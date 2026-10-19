# platform_core

Shared Dart/Flutter package for `customer_app` and `driver_app` (Plan section 6.1): `ApiClient` + envelope parsing,
`TokenStore`/`SessionController`, `Env` (`--dart-define`), backend-mirrored enums with `unknown` fallback, `Money` +
`MoneyFormatter`, `RealtimeClient` + `SequenceTracker`, `LocationService`, `DynamicFormRenderer`, design tokens, theme and
shared widgets. See docs/DECISIONS.md ADR-43..45.

```bash
flutter pub get && dart format --output=none --set-exit-if-changed . && flutter analyze && flutter test
```
