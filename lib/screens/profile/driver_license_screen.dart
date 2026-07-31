import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../widgets/states/status_badge.dart';

class DriverLicenseScreen extends StatefulWidget {
  const DriverLicenseScreen({super.key});

  @override
  State<DriverLicenseScreen> createState() => _DriverLicenseScreenState();
}

class _DriverLicenseScreenState extends State<DriverLicenseScreen> {
  final BadgeStatus _status =
      BadgeStatus.pending; // Using pending to show the state

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Driver\'s License')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildStatusCard(),
            const SizedBox(height: AppSpacing.xxl),
            Text('License Details',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            _buildDetailRow('State/Country', 'California, USA'),
            const Divider(height: AppSpacing.lg),
            _buildDetailRow('License Number', 'D1234567'),
            const Divider(height: AppSpacing.lg),
            _buildDetailRow('Expiration Date', '10/24/2028'),
            const SizedBox(height: AppSpacing.xxxl),
            Text('Update License',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.sm),
            Text(
                'If your license has expired or you moved, please upload a new one.',
                style: AppTypography.textTheme.bodyMedium),
            const SizedBox(height: AppSpacing.lg),
            SecondaryButton(
              text: 'Upload New License',
              icon: LucideIcons.uploadCloud,
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                      content: Text('Camera/Gallery picker would open here')),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusCard() {
    final isVerified = _status == BadgeStatus.approved;
    final isRejected = _status == BadgeStatus.rejected;

    Color color = isVerified
        ? AppColors.success
        : (isRejected ? AppColors.error : AppColors.warning);
    IconData icon = isVerified
        ? LucideIcons.checkCircle
        : (isRejected ? LucideIcons.xCircle : LucideIcons.clock);
    String title = isVerified
        ? 'License Verified'
        : (isRejected ? 'Verification Rejected' : 'Verification Pending');
    String msg = isVerified
        ? 'You are approved to drive all vehicles.'
        : (isRejected
            ? 'Please upload a clear, valid license.'
            : 'We are reviewing your document.');

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          StatusBadge(
            label: title,
            status: _status,
          ),
          const SizedBox(height: AppSpacing.lg),
          Icon(icon, size: 48, color: color),
          const SizedBox(height: AppSpacing.md),
          Text(
            title,
            style: AppTypography.textTheme.titleLarge?.copyWith(color: color),
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            msg,
            textAlign: TextAlign.center,
            style: AppTypography.textTheme.bodyMedium,
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: AppTypography.textTheme.bodyLarge
                  ?.copyWith(color: AppColors.textSecondary)),
          Text(value, style: AppTypography.textTheme.titleMedium),
        ],
      ),
    );
  }
}
