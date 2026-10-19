import 'package:flutter/material.dart';

/// Design tokens. Original brand: deep teal with a warm amber accent.
abstract final class AppColors {
  static const brand = Color(0xFF0F766E);
  static const accent = Color(0xFFF59E0B);
  static const success = Color(0xFF15803D);
  static const warning = Color(0xFFB45309);
  static const danger = Color(0xFFB91C1C);
  static const info = Color(0xFF1D4ED8);
  static const neutral = Color(0xFF475569);
}

abstract final class AppSpacing {
  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 16.0;
  static const lg = 24.0;
  static const xl = 32.0;
}

abstract final class AppRadius {
  static const sm = 8.0;
  static const md = 12.0;
  static const lg = 20.0;
}

/// Per-app sizing profile. The driver app is used while driving, so it gets
/// bigger touch targets and type (Plan §10 P0.4 step 3).
class UiDensity {
  const UiDensity({
    required this.minTouchTarget,
    required this.buttonHeight,
    required this.textScale,
  });

  static const standard = UiDensity(
    minTouchTarget: 48,
    buttonHeight: 52,
    textScale: 1.0,
  );
  static const driver = UiDensity(
    minTouchTarget: 64,
    buttonHeight: 64,
    textScale: 1.15,
  );

  final double minTouchTarget;
  final double buttonHeight;
  final double textScale;
}

/// Theme extension giving widgets access to the density profile.
class AppDensityExtension extends ThemeExtension<AppDensityExtension> {
  const AppDensityExtension(this.density);
  final UiDensity density;

  @override
  AppDensityExtension copyWith({UiDensity? density}) =>
      AppDensityExtension(density ?? this.density);

  @override
  AppDensityExtension lerp(AppDensityExtension? other, double t) => this;
}

extension AppThemeContext on BuildContext {
  UiDensity get density =>
      Theme.of(this).extension<AppDensityExtension>()?.density ??
      UiDensity.standard;
}
