import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/utils/auth_error_messages.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../widgets/inputs/app_text_field.dart';
import '../../widgets/brand/app_logo.dart';
import '../../data/repositories/user_repository.dart';
import '../../core/config/auth_state.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;

  static final RegExp _emailPattern = RegExp(
    r'^[^\s@]+@[^\s@]+\.[^\s@]+$',
  );

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    // Guard against duplicate submissions (e.g. double taps or a
    // software "Go" press while a request is already in flight).
    if (_isLoading) return;
    final valid = _formKey.currentState?.validate() ?? false;
    if (!valid) return;

    FocusScope.of(context).unfocus();
    setState(() => _isLoading = true);

    final response = await UserRepository.instance.login(
      _emailController.text.trim(),
      _passwordController.text,
    );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (response.success) {
      if (response.message != null && response.message!.isNotEmpty) {
        await AuthState.setToken(response.message!);
      }
      if (mounted) context.go(AppRoutes.home);
    } else {
      _showError(AuthErrorMessages.messageFor(response.error));
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(
        SnackBar(
          content: Text(message),
          backgroundColor: AppColors.error,
        ),
      );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.surface,
      body: SafeArea(
        child: GestureDetector(
          onTap: () => FocusScope.of(context).unfocus(),
          child: AutofillGroup(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.pagePadding,
                vertical: AppSpacing.xl,
              ),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    AppLogo.lockup(markSize: 44),
                    const SizedBox(height: AppSpacing.xxl),
                    Text(
                      'Welcome back',
                      style: AppTypography.textTheme.displayMedium,
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Text(
                      'Log in to book your next ride.',
                      style: AppTypography.textTheme.bodyLarge,
                    ),
                    const SizedBox(height: AppSpacing.xxl),
                    AppTextField(
                      label: 'Email',
                      hint: 'you@example.com',
                      controller: _emailController,
                      keyboardType: TextInputType.emailAddress,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.email],
                      prefixIcon: LucideIcons.mail,
                      semanticsLabel: 'Email address',
                      validator: (val) {
                        final v = val?.trim() ?? '';
                        if (v.isEmpty) return 'Email is required';
                        if (!_emailPattern.hasMatch(v)) {
                          return 'Enter a valid email address';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    AppTextField(
                      label: 'Password',
                      hint: 'Enter your password',
                      controller: _passwordController,
                      isPassword: true,
                      showPasswordToggle: true,
                      textInputAction: TextInputAction.done,
                      onFieldSubmitted: (_) => _handleLogin(),
                      autofillHints: const [AutofillHints.password],
                      prefixIcon: LucideIcons.lock,
                      semanticsLabel: 'Password',
                      validator: (val) {
                        if (val == null || val.isEmpty) {
                          return 'Password is required';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Align(
                      alignment: Alignment.centerRight,
                      child: TextButton(
                        onPressed: _showForgotPasswordSheet,
                        style: TextButton.styleFrom(
                          padding: EdgeInsets.zero,
                          minimumSize: const Size(44, 44),
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        child: Text(
                          'Forgot password?',
                          style: AppTypography.textTheme.labelLarge,
                        ),
                      ),
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    PrimaryButton(
                      text: 'Log In',
                      isLoading: _isLoading,
                      onPressed: _handleLogin,
                    ),
                    const SizedBox(height: AppSpacing.xl),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'New to Apex Rentals?',
                          style: AppTypography.textTheme.bodyMedium,
                        ),
                        TextButton(
                          onPressed: () => context.push(AppRoutes.register),
                          child: Text(
                            'Create account',
                            style: AppTypography.textTheme.labelLarge,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showForgotPasswordSheet() {
    final emailController =
        TextEditingController(text: _emailController.text.trim());
    final sheetFormKey = GlobalKey<FormState>();
    bool isSubmitting = false;

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius:
            BorderRadius.vertical(top: Radius.circular(AppSpacing.radiusLg)),
      ),
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) => SafeArea(
          child: Padding(
            padding: EdgeInsets.only(
              left: AppSpacing.pagePadding,
              right: AppSpacing.pagePadding,
              top: AppSpacing.pagePadding,
              bottom: MediaQuery.of(sheetContext).viewInsets.bottom +
                  AppSpacing.pagePadding,
            ),
            child: Form(
              key: sheetFormKey,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight,
                          borderRadius:
                              BorderRadius.circular(AppSpacing.radiusSm),
                        ),
                        child: const Icon(
                          LucideIcons.keyRound,
                          size: 20,
                          color: AppColors.primary,
                        ),
                      ),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Text('Reset your password',
                            style: AppTypography.textTheme.headlineMedium),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.md),
                  Text(
                    'Enter your registered email address and we will send you '
                    'a link to reset your password.',
                    style: AppTypography.textTheme.bodyMedium?.copyWith(
                        color: AppColors.textSecondary, height: 1.5),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  AppTextField(
                    label: 'Email address',
                    hint: 'Enter your email',
                    controller: emailController,
                    keyboardType: TextInputType.emailAddress,
                    prefixIcon: LucideIcons.mail,
                    validator: (val) {
                      if (val == null || val.trim().isEmpty) {
                        return 'Email is required';
                      }
                      if (!RegExp(r'^[\w\.-]+@([\w-]+\.)+[\w-]{2,4}$')
                          .hasMatch(val.trim())) {
                        return 'Enter a valid email address';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  PrimaryButton(
                    text: 'Send Reset Link',
                    isLoading: isSubmitting,
                    icon: LucideIcons.send,
                    onPressed: isSubmitting
                        ? null
                        : () async {
                            if (!sheetFormKey.currentState!.validate()) return;
                            setSheetState(() => isSubmitting = true);

                            final navigator = Navigator.of(sheetContext);
                            final messenger = ScaffoldMessenger.of(context);
                            final sheetMessenger =
                                ScaffoldMessenger.of(sheetContext);

                            final res = await UserRepository.instance
                                .forgotPassword(emailController.text.trim());

                            if (res.success) {
                              navigator.pop();
                              messenger.showSnackBar(
                                SnackBar(
                                  content: Text(res.message ??
                                      'Password reset link sent to your email.'),
                                  backgroundColor: AppColors.success,
                                ),
                              );
                            } else {
                              setSheetState(() => isSubmitting = false);
                              sheetMessenger.showSnackBar(
                                SnackBar(
                                  content: Text(res.error?.message ??
                                      'Failed to send reset link. Please check your email and try again.'),
                                  backgroundColor: AppColors.error,
                                ),
                              );
                            }
                          },
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
