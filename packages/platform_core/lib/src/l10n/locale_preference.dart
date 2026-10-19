import 'package:flutter/widgets.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Locales supported by both apps. Arabic is the default and the ARB template.
const supportedLanguageCodes = ['ar', 'en'];
const defaultLanguageCode = 'ar';

/// Persists the user's language choice. Read it before the first frame
/// (bootstrap) so the first built frame already has the right locale.
class LocalePreference {
  LocalePreference(this._prefs, {this._key = 'language_code'});

  final SharedPreferences _prefs;
  final String _key;

  /// Stored language code, or null when the user has never chosen one
  /// (the onboarding flow shows the language picker in that case).
  String? get stored {
    final v = _prefs.getString(_key);
    return supportedLanguageCodes.contains(v) ? v : null;
  }

  Locale get locale => Locale(stored ?? defaultLanguageCode);

  Future<void> save(String languageCode) {
    assert(supportedLanguageCodes.contains(languageCode));
    return _prefs.setString(_key, languageCode);
  }
}

TextDirection directionFor(String languageCode) =>
    languageCode == 'ar' ? TextDirection.rtl : TextDirection.ltr;
