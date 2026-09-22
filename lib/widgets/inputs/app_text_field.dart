import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

class AppTextField extends StatefulWidget {
  final String label;
  final String? hint;
  final TextEditingController? controller;
  final bool isPassword;

  /// Renders an inline show/hide affordance for password fields.
  /// Callers that supply their own [suffixIcon] don't need this.
  final bool showPasswordToggle;
  final TextInputType keyboardType;
  final IconData? prefixIcon;
  final Widget? suffixIcon;
  final String? Function(String?)? validator;
  final String? errorText;
  final TextInputAction? textInputAction;
  final ValueChanged<String>? onFieldSubmitted;
  final List<String>? autofillHints;
  final bool enabled;
  final String? semanticsLabel;

  const AppTextField({
    super.key,
    required this.label,
    this.hint,
    this.controller,
    this.isPassword = false,
    this.showPasswordToggle = false,
    this.keyboardType = TextInputType.text,
    this.prefixIcon,
    this.suffixIcon,
    this.validator,
    this.errorText,
    this.textInputAction,
    this.onFieldSubmitted,
    this.autofillHints,
    this.enabled = true,
    this.semanticsLabel,
  });

  @override
  State<AppTextField> createState() => _AppTextFieldState();
}

class _AppTextFieldState extends State<AppTextField> {
  late final FocusNode _focusNode;
  bool _focused = false;
  bool _obscured = true;

  @override
  void initState() {
    super.initState();
    _focusNode = FocusNode();
    _focusNode.addListener(_onFocusChanged);
  }

  void _onFocusChanged() {
    if (mounted) setState(() => _focused = _focusNode.hasFocus);
  }

  @override
  void dispose() {
    _focusNode.removeListener(_onFocusChanged);
    _focusNode.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final canToggle = widget.isPassword && widget.showPasswordToggle;
    final obscure = widget.isPassword && (!canToggle || _obscured);

    return Semantics(
      label: widget.semanticsLabel,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            widget.label,
            style: AppTypography.textTheme.labelLarge?.copyWith(
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: widget.controller,
            focusNode: _focusNode,
            enabled: widget.enabled,
            obscureText: obscure,
            keyboardType: widget.keyboardType,
            validator: widget.validator,
            textInputAction: widget.textInputAction,
            onFieldSubmitted: widget.onFieldSubmitted,
            autofillHints: widget.autofillHints,
            autocorrect: !widget.isPassword &&
                widget.keyboardType != TextInputType.emailAddress,
            enableSuggestions: !widget.isPassword,
            style: AppTypography.textTheme.bodyLarge?.copyWith(
              color: AppColors.textPrimary,
            ),
            scrollPadding: const EdgeInsets.only(bottom: 120),
            decoration: InputDecoration(
              hintText: widget.hint,
              errorText: widget.errorText,
              prefixIcon: widget.prefixIcon != null
                  ? AnimatedSwitcher(
                      duration: const Duration(milliseconds: 200),
                      child: Icon(
                        widget.prefixIcon,
                        key: ValueKey(_focused),
                        size: 20,
                        color: _focused
                            ? AppColors.primary
                            : AppColors.textTertiary,
                      ),
                    )
                  : null,
              suffixIcon: widget.suffixIcon ??
                  (canToggle
                      ? IconButton(
                          onPressed: () =>
                              setState(() => _obscured = !_obscured),
                          icon: AnimatedSwitcher(
                            duration: const Duration(milliseconds: 200),
                            transitionBuilder: (child, animation) =>
                                ScaleTransition(
                              scale: animation,
                              child: child,
                            ),
                            child: Icon(
                              _obscured
                                  ? LucideIcons.eyeOff
                                  : LucideIcons.eye,
                              key: ValueKey(_obscured),
                              size: 20,
                              color: AppColors.textTertiary,
                            ),
                          ),
                          tooltip: _obscured
                              ? 'Show password'
                              : 'Hide password',
                        )
                      : null),
            ),
          ),
        ],
      ),
    );
  }
}

class SearchBarWidget extends StatelessWidget {
  final String hint;
  final TextEditingController? controller;
  final VoidCallback? onTap;
  final ValueChanged<String>? onChanged;
  final bool readOnly;
  final VoidCallback? onFilterTap;

  const SearchBarWidget({
    super.key,
    this.hint = 'Search for a car...',
    this.controller,
    this.onTap,
    this.onChanged,
    this.readOnly = false,
    this.onFilterTap,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
        boxShadow: [
          BoxShadow(
            color: AppColors.textPrimary.withOpacity(0.05),
            blurRadius: 15,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: TextField(
        controller: controller,
        readOnly: readOnly,
        onTap: onTap,
        onChanged: onChanged,
        decoration: InputDecoration(
          hintText: hint,
          prefixIcon: const Padding(
            padding: EdgeInsets.only(left: AppSpacing.md, right: AppSpacing.sm),
            child: Icon(Icons.search, color: AppColors.textTertiary),
          ),
          prefixIconConstraints: const BoxConstraints(minWidth: 40),
          suffixIcon: onFilterTap != null
              ? IconButton(
                  icon: const Icon(Icons.tune, color: AppColors.primary),
                  onPressed: onFilterTap,
                )
              : null,
          filled: true,
          fillColor: AppColors.surface,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
            borderSide: const BorderSide(color: AppColors.primary, width: 1),
          ),
          contentPadding: const EdgeInsets.symmetric(
            vertical: AppSpacing.md,
          ),
        ),
      ),
    );
  }
}
