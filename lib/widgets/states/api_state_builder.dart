import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../data/models/api_models.dart';
import 'package:lucide_icons/lucide_icons.dart';

class ApiStateBuilder<T> extends StatelessWidget {
  final Future<ApiResponse<T>> future;
  final Widget Function(BuildContext context, T data) onData;
  final Widget Function(BuildContext context)? onLoading;
  final Widget Function(BuildContext context, ApiError error)? onError;
  final Widget Function(BuildContext context)? onEmpty;
  final bool Function(T data)? isEmpty;
  final VoidCallback? onRetry;

  const ApiStateBuilder({
    super.key,
    required this.future,
    required this.onData,
    this.onLoading,
    this.onError,
    this.onEmpty,
    this.isEmpty,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<ApiResponse<T>>(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return onLoading?.call(context) ?? _buildDefaultLoading();
        }

        if (snapshot.hasError) {
          return onError?.call(
                context,
                ApiError(statusCode: 500, message: snapshot.error.toString()),
              ) ??
              _buildDefaultError(snapshot.error.toString());
        }

        if (snapshot.hasData) {
          final response = snapshot.data!;
          if (!response.success || response.error != null) {
            return onError?.call(
                  context,
                  response.error ??
                      ApiError(statusCode: 500, message: 'Unknown error'),
                ) ??
                _buildDefaultError(response.error?.message ?? 'Unknown error');
          }

          if (response.data == null ||
              (isEmpty != null && isEmpty!(response.data as T))) {
            return onEmpty?.call(context) ?? _buildDefaultEmpty();
          }

          return onData(context, response.data as T);
        }

        return _buildDefaultEmpty();
      },
    );
  }

  Widget _buildDefaultLoading() {
    return const Center(
      child: CircularProgressIndicator(color: AppColors.primary),
    );
  }

  Widget _buildDefaultError(String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(LucideIcons.alertCircle,
                size: 48, color: AppColors.error),
            const SizedBox(height: AppSpacing.md),
            Text(
              'Oops! Something went wrong.',
              style: AppTypography.textTheme.titleLarge,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              message,
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.textSecondary),
              textAlign: TextAlign.center,
            ),
            if (onRetry != null) ...[
              const SizedBox(height: AppSpacing.lg),
              ElevatedButton.icon(
                onPressed: onRetry,
                icon: const Icon(LucideIcons.refreshCw, size: 16),
                label: const Text('Try Again'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: AppColors.surface,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildDefaultEmpty() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(LucideIcons.inbox,
                size: 48, color: AppColors.textTertiary),
            const SizedBox(height: AppSpacing.md),
            Text(
              'Nothing to see here',
              style: AppTypography.textTheme.titleLarge,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              'Check back later for updates.',
              style: AppTypography.textTheme.bodyMedium
                  ?.copyWith(color: AppColors.textSecondary),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
