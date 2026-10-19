import 'package:flutter/material.dart';

import 'tokens.dart';

/// Light Material 3 theme shared by both apps.
abstract final class AppTheme {
  static ThemeData light({UiDensity density = UiDensity.standard}) {
    final scheme = ColorScheme.fromSeed(
      seedColor: AppColors.brand,
      secondary: AppColors.accent,
      brightness: Brightness.light,
    );
    final base = ThemeData(useMaterial3: true, colorScheme: scheme);
    final text = Typography.englishLike2021
        .merge(base.textTheme)
        .apply(
          fontSizeFactor: density.textScale,
          bodyColor: scheme.onSurface,
          displayColor: scheme.onSurface,
        );
    final shape = RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(AppRadius.md),
    );
    return base.copyWith(
      textTheme: text,
      scaffoldBackgroundColor: scheme.surface,
      visualDensity: VisualDensity.standard,
      extensions: [AppDensityExtension(density)],
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: Size(64, density.buttonHeight),
          shape: shape,
          textStyle: text.titleMedium,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: Size(64, density.buttonHeight),
          shape: shape,
          textStyle: text.titleMedium,
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          minimumSize: Size(64, density.minTouchTarget),
        ),
      ),
      iconButtonTheme: IconButtonThemeData(
        style: IconButton.styleFrom(
          minimumSize: Size.square(density.minTouchTarget),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        contentPadding: EdgeInsetsDirectional.symmetric(
          horizontal: AppSpacing.md,
          vertical: density.minTouchTarget > 48 ? 20 : 14,
        ),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          side: BorderSide(color: scheme.outlineVariant),
        ),
      ),
      appBarTheme: AppBarTheme(
        centerTitle: true,
        backgroundColor: scheme.surface,
        foregroundColor: scheme.onSurface,
        elevation: 0,
      ),
    );
  }
}
