import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../providers/auth_providers.dart';
import '../widgets/auth_error_text.dart';

/// OTP entry with a resend countdown and typed error messages. Verification
/// success signs in; the router then picks the next step from `/me`.
class OtpScreen extends ConsumerStatefulWidget {
  const OtpScreen({super.key});

  @override
  ConsumerState<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends ConsumerState<OtpScreen> {
  final _code = TextEditingController();
  Timer? _ticker;
  int _secondsLeft = 0;
  bool _busy = false;
  bool _needsNewCode = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _startCountdown(ref.read(signInDraftProvider)?.otp.resendInSeconds ?? 60);
  }

  @override
  void dispose() {
    _ticker?.cancel();
    _code.dispose();
    super.dispose();
  }

  void _startCountdown(int seconds) {
    _ticker?.cancel();
    setState(() => _secondsLeft = seconds);
    _ticker = Timer.periodic(const Duration(seconds: 1), (t) {
      if (!mounted) return t.cancel();
      setState(() => _secondsLeft = _secondsLeft > 0 ? _secondsLeft - 1 : 0);
      if (_secondsLeft == 0) t.cancel();
    });
  }

  Future<void> _verify(String code) async {
    final draft = ref.read(signInDraftProvider);
    if (draft == null || _busy) return;
    final l10n = AppLocalizations.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(authActionsProvider).verifyOtp(draft.phone, code);
    } on Object catch (e) {
      if (!mounted) return;
      _code.clear();
      setState(() {
        _error = authErrorText(l10n, e);
        _needsNewCode = otpNeedsNewCode(e);
      });
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _resend() async {
    final draft = ref.read(signInDraftProvider);
    if (draft == null) return;
    final l10n = AppLocalizations.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final result = await ref
          .read(authActionsProvider)
          .requestOtp(draft.phone);
      if (!mounted) return;
      _code.clear();
      setState(() => _needsNewCode = false);
      _startCountdown(result.resendInSeconds);
    } on Object catch (e) {
      if (!mounted) return;
      setState(() => _error = authErrorText(l10n, e));
      if (e is ApiException && e.retryAfterSeconds != null) {
        _startCountdown(e.retryAfterSeconds!);
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final draft = ref.watch(signInDraftProvider);
    if (draft == null) {
      // Opened without a request in progress (for example after a restart).
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) context.go(Routes.auth);
      });
      return const Scaffold(body: SizedBox.shrink());
    }
    return Scaffold(
      appBar: AppBar(title: Text(l10n.otpTitle)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          children: [
            Text(
              l10n.otpSubtitle(draft.otp.length, draft.phone),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppSpacing.lg),
            OtpField(
              length: draft.otp.length,
              controller: _code,
              enabled: !_busy && !_needsNewCode,
              errorText: _error,
              onCompleted: _verify,
            ),
            const SizedBox(height: AppSpacing.lg),
            if (_busy)
              const Center(child: CircularProgressIndicator())
            else if (_secondsLeft > 0)
              Center(
                child: Text(
                  l10n.otpResendIn(_secondsLeft),
                  key: const Key('otp-countdown'),
                ),
              )
            else
              AppButton(
                key: const Key('otp-resend'),
                label: l10n.otpResend,
                secondary: true,
                onPressed: _resend,
              ),
            const SizedBox(height: AppSpacing.sm),
            TextButton(
              onPressed: () => context.go(Routes.auth),
              child: Text(l10n.otpChangeNumber),
            ),
          ],
        ),
      ),
    );
  }
}
