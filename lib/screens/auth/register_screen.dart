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
    if (!(_formKey.currentState?.validate() ?? false)) return;

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
      backgroundColor: AppColors.background,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(LucideIcons.arrowLeft),
          onPressed: () => context.pop(),
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
                const SizedBox(height: AppSpacing.md),
                Text(
                  'Create an account',
                  style: AppTypography.textTheme.displayMedium,
                ),
                const SizedBox(height: AppSpacing.sm),
                Text(
                  'Join Apex Rentals to start renting cars today.',
                  style: AppTypography.textTheme.bodyLarge,
                ),
                const SizedBox(height: AppSpacing.xxl),
                if (_errorMessage != null) ...[
                  Container(
                    padding: const EdgeInsets.all(AppSpacing.md),
                    decoration: BoxDecoration(
                      color: AppColors.error.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                    ),
                    child: Row(
                      children: [
                        const Icon(LucideIcons.alertCircle,
                            color: AppColors.error, size: 20),
                        const SizedBox(width: AppSpacing.sm),
                        Expanded(
                          child: Text(_errorMessage!,
                              style: AppTypography.textTheme.bodyMedium
                                  ?.copyWith(color: AppColors.error)),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                ],
                AppTextField(
                  label: 'Full Name',
                  hint: 'Enter your full name',
                  controller: _nameController,
                  prefixIcon: LucideIcons.user,
                  errorText: _fieldErrors['name'],
                  validator: (val) {
                    if (val == null || val.isEmpty) return 'Name is required';
                    return null;
                  },
                ),
                const SizedBox(height: AppSpacing.lg),
                AppTextField(
                  label: 'Email',
                  hint: 'Enter your email',
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  prefixIcon: LucideIcons.mail,
                  errorText: _fieldErrors['email'],
                  validator: (val) {
                    if (val == null || val.isEmpty) return 'Email is required';
                    return null;
                  },
                ),
                const SizedBox(height: AppSpacing.lg),
                AppTextField(
                  label: 'Phone',
                  hint: 'Enter your phone number (optional)',
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  prefixIcon: LucideIcons.phone,
                  errorText: _fieldErrors['phone'],
                  validator: (val) {
                    // Backend rule: nullable, string, max 20 chars.
                    final v = val?.trim() ?? '';
                    if (v.length > 20) {
                      return 'Phone number must be 20 characters or less';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: AppSpacing.lg),
                AppTextField(
                  label: 'Password',
                  hint: 'Create a password',
                  controller: _passwordController,
                  isPassword: true,
                  prefixIcon: LucideIcons.lock,
                  errorText: _fieldErrors['password'],
                  validator: (val) {
                    if (val == null || val.isEmpty) {
                      return 'Password is required';
                    }
                    // Backend rule: min 8 characters.
                    if (val.length < 8) return 'Must be at least 8 characters';
                    return null;
                  },
                ),
                const SizedBox(height: AppSpacing.lg),
                AppTextField(
                  label: 'Confirm Password',
                  hint: 'Confirm your password',
                  controller: _confirmPasswordController,
                  isPassword: true,
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
                  text: 'Sign up',
                  isLoading: _isLoading,
                  onPressed: _handleRegister,
                ),
                const SizedBox(height: AppSpacing.xl),
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
              ],
            ),
          ),
        ),
      ),
    );
  }
}
