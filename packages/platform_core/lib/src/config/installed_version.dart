import 'package:package_info_plus/package_info_plus.dart';

/// Version name of the installed app (`pubspec` `version` before the `+`).
Future<String> installedAppVersion() async =>
    (await PackageInfo.fromPlatform()).version;
