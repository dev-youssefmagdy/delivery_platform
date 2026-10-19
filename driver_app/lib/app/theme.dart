import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

/// Driver app is used while driving: bigger touch targets and type.
ThemeData buildAppTheme() => AppTheme.light(density: UiDensity.driver);
