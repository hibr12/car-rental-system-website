import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/config/auth_state.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../widgets/inputs/app_text_field.dart';
import '../../widgets/brand/app_logo.dart';
import '../../data/repositories/user_repository.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  bool _isLoading = false;
  String? _errorMessage;
  Map<String, String> _fieldErrors = {};

  static final RegExp _emailPattern = RegExp(
    r'^[^\s@]+@[^\s@]+\.[^\s@]+$',
  );

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _handleRegister() async {
    if (_isLoading) return;
    if (!(_formKey.currentState?.validate() ?? false)) return;

    FocusScope.of(context).unfocus();
    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _fieldErrors = {};
    });

    final response = await UserRepository.instance.register(
      name: _nameController.text.trim(),
      email: _emailController.text.trim(),
      password: _passwordController.text,
      passwordConfirmation: _confirmPasswordController.text,
      phone: _phoneController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (response.success && response.data != null) {
      // Set auth state with the token (attached to response.message)
      if (response.message != null && response.message!.isNotEmpty) {
        await AuthState.setToken(response.message!);
      }
      if (mounted) context.go(AppRoutes.home);
    } else {
      // Check for 422 per-field validation errors
      final validationError = response.error?.validationError;
      if (validationError != null) {
        final fieldErrs = <String, String>{};
        for (final entry in validationError.errors.entries) {
          if (entry.value.isNotEmpty) {
            fieldErrs[entry.key] = entry.value.first;
          }
        }
        setState(() => _fieldErrors = fieldErrs);
      }
      setState(() {
        _errorMessage =
            response.error?.friendlyMessage ?? 'Registration failed';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft),
          onPressed: () => context.pop(),
        ),
      ),
      body: SafeArea(
        child: GestureDetector(
          onTap: () => FocusScope.of(context).unfocus(),
          child: AutofillGroup(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.pagePadding,
              ),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const SizedBox(height: AppSpacing.sm),
                    AppLogo.lockup(markSize: 44),
                    const SizedBox(height: AppSpacing.xl),
                    Text(
                      'Create your account',
                      style: AppTypography.textTheme.displayMedium,
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    Text(
                      'Join Apex Rentals and book your first car in minutes.',
                      style: AppTypography.textTheme.bodyLarge,
                    ),
                    const SizedBox(height: AppSpacing.xxl),
                    if (_errorMessage != null) ...[
                      _ErrorBanner(message: _errorMessage!),
                      const SizedBox(height: AppSpacing.lg),
                    ],
                    AppTextField(
                      label: 'Full Name',
                      hint: 'Enter your full name',
                      controller: _nameController,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.name],
                      prefixIcon: LucideIcons.user,
                      errorText: _fieldErrors['name'],
                      validator: (val) {
                        final v = val?.trim() ?? '';
                        if (v.isEmpty) return 'Name is required';
                        return null;
                      },
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    AppTextField(
                      label: 'Email',
                      hint: 'you@example.com',
                      controller: _emailController,
                      keyboardType: TextInputType.emailAddress,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.email],
                      prefixIcon: LucideIcons.mail,
                      errorText: _fieldErrors['email'],
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
                      label: 'Phone',
                      hint: '+251 91 234 5678 (optional)',
                      controller: _phoneController,
                      keyboardType: TextInputType.phone,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.telephoneNumber],
                      prefixIcon: LucideIcons.phone,
                      errorText: _fieldErrors['phone'],
                      validator: (val) {
                        // Backend rule: nullable, string, max 20 chars.
                        final v = val?.trim() ?? '';
                        if (v.isEmpty) return null;
                        if (v.length > 20) {
                          return 'Phone number must be 20 characters or less';
                        }
                        // Accept local (09…) and international (+251…)
                        // formats with optional spaces/dashes.
                        final digits = v.replaceAll(RegExp(r'[\s\-()]'), '');
                        if (!RegExp(r'^\+?\d{9,15}$').hasMatch(digits)) {
                          return 'Enter a valid phone number';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    AppTextField(
                      label: 'Password',
                      hint: 'At least 8 characters',
                      controller: _passwordController,
                      isPassword: true,
                      showPasswordToggle: true,
                      textInputAction: TextInputAction.next,
                      autofillHints: const [AutofillHints.newPassword],
                      prefixIcon: LucideIcons.lock,
                      errorText: _fieldErrors['password'],
                      validator: (val) {
                        if (val == null || val.isEmpty) {
                          return 'Password is required';
                        }
                        // Backend rule: min 8 characters.
                        if (val.length < 8) {
                          return 'Must be at least 8 characters';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    AppTextField(
                      label: 'Confirm Password',
                      hint: 'Re-enter your password',
                      controller: _confirmPasswordController,
                      isPassword: true,
                      showPasswordToggle: true,
                      textInputAction: TextInputAction.done,
                      onFieldSubmitted: (_) => _handleRegister(),
                      autofillHints: const [AutofillHints.newPassword],
                      prefixIcon: LucideIcons.lock,
                      errorText: _fieldErrors['password_confirmation'],
                      validator: (val) {
                        if (val == null || val.isEmpty) {
                          return 'Please confirm your password';
                        }
                        if (val != _passwordController.text) {
                          return 'Passwords do not match';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: AppSpacing.xxl),
                    PrimaryButton(
                      text: 'Create Account',
                      isLoading: _isLoading,
                      onPressed: _handleRegister,
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'Already have an account?',
                          style: AppTypography.textTheme.bodyMedium,
                        ),
                        TextButton(
                          onPressed: () => context.pop(),
                          child: Text(
                            'Log in',
                            style: AppTypography.textTheme.labelLarge,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: AppSpacing.md),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Inline API-level failure banner (network / server / credential issues).
class _ErrorBanner extends StatelessWidget {
  final String message;

  const _ErrorBanner({required this.message});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.error.withOpacity(0.08),
        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
        border: Border.all(color: AppColors.error.withOpacity(0.25)),
      ),
      child: Row(
        children: [
          const Icon(LucideIcons.alertCircle, color: AppColors.error, size: 20),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Text(
              message,
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.error, height: 1.4),
            ),
          ),
        ],
      ),
    );
  }
}
