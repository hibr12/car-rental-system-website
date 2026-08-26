import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../widgets/inputs/app_text_field.dart';
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
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _handleLogin() async {
    if (_formKey.currentState?.validate() ?? false) {
      setState(() => _isLoading = true);

      final response = await UserRepository.instance.login(
        _emailController.text.trim(),
        _passwordController.text,
      );

      if (!mounted) return;
      setState(() => _isLoading = false);

      if (response.success) {
        // Set auth state with the token (attached to response.message)
        if (response.message != null && response.message!.isNotEmpty) {
          await AuthState.setToken(response.message!);
        }
        if (mounted) context.go(AppRoutes.home);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error?.friendlyMessage ?? 'Login failed'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft),
          onPressed: () => context.go(AppRoutes.onboarding),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppSpacing.pagePadding),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: AppSpacing.lg),
                Text(
                  'Welcome back',
                  style: AppTypography.textTheme.displayMedium,
                ),
                const SizedBox(height: AppSpacing.sm),
                Text(
                  'Enter your details to continue',
                  style: AppTypography.textTheme.bodyLarge,
                ),
                const SizedBox(height: AppSpacing.xxl),
                AppTextField(
                  label: 'Email',
                  hint: 'Enter your email',
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  prefixIcon: LucideIcons.mail,
                  validator: (val) {
                    if (val == null || val.isEmpty) return 'Email is required';
                    return null;
                  },
                ),
                const SizedBox(height: AppSpacing.lg),
                AppTextField(
                  label: 'Password',
                  hint: 'Enter your password',
                  controller: _passwordController,
                  isPassword: _obscurePassword,
                  prefixIcon: LucideIcons.lock,
                  suffixIcon: IconButton(
                    icon: Icon(
                        _obscurePassword ? LucideIcons.eye : LucideIcons.eyeOff,
                        color: AppColors.textTertiary),
                    onPressed: () =>
                        setState(() => _obscurePassword = !_obscurePassword),
                  ),
                  validator: (val) {
                    if (val == null || val.isEmpty) {
                      return 'Password is required';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: AppSpacing.md),
                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton(
                    onPressed: () => _showForgotPasswordSheet(context),
                    style: TextButton.styleFrom(
                      padding: EdgeInsets.zero,
                      minimumSize: Size.zero,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                    child: Text(
                      'Forgot password?',
                      style: AppTypography.textTheme.labelLarge,
                    ),
                  ),
                ),
                const SizedBox(height: AppSpacing.xxl),
                PrimaryButton(
                  text: 'Log in',
                  isLoading: _isLoading,
                  onPressed: _handleLogin,
                ),
                const SizedBox(height: AppSpacing.xl),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      'Don\'t have an account?',
                      style: AppTypography.textTheme.bodyMedium,
                    ),
                    TextButton(
                      onPressed: () => context.push(AppRoutes.register),
                      child: Text(
                        'Sign up',
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
    );
  }

  /// The backend has no self-service password reset endpoint, so recovery
  /// goes through the real support contact form.
  void _showForgotPasswordSheet(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius:
            BorderRadius.vertical(top: Radius.circular(AppSpacing.radiusLg)),
      ),
      builder: (sheetContext) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.pagePadding),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Reset your password',
                  style: AppTypography.textTheme.headlineMedium),
              const SizedBox(height: AppSpacing.sm),
              Text(
                'Password resets are handled by our support team. Send us a '
                'message with your account email and we will help you '
                'regain access.',
                style: AppTypography.textTheme.bodyMedium?.copyWith(
                    color: AppColors.textSecondary),
              ),
              const SizedBox(height: AppSpacing.lg),
              PrimaryButton(
                text: 'Contact Support',
                icon: LucideIcons.mail,
                onPressed: () {
                  Navigator.of(sheetContext).pop();
                  context.push(AppRoutes.support);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
