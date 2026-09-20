import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

/// Privacy Policy screen.
///
/// Meets Apple App Store Guideline 5.1.1 (Data Collection & Storage)
/// and Google Play Store User Data policy requirements.
class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Privacy Policy')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Privacy Policy',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.xs),
            Text(
              'Last updated: September 2026',
              style: AppTypography.textTheme.bodySmall
                  ?.copyWith(color: AppColors.textTertiary),
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '1. Information We Collect',
              content:
                  'Abay Car Rentals collects personal information necessary to deliver our car rental services:\n\n'
                  '• Account Information: Your full name, email address, and contact phone number when you register.\n'
                  '• Identification & Verification: Driver\'s license details, license category, expiry dates, and document photos for legal identity and driving eligibility verification.\n'
                  '• Rental & Booking Data: Pickup and drop-off locations, rental dates, vehicle preferences, and transaction records.\n'
                  '• Location Data: Optional branch distance estimates and branch selection maps with your device permission.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '2. How We Use Your Information',
              content:
                  'We use the data collected strictly for legitimate business and regulatory purposes:\n\n'
                  '• Facilitating vehicle reservations, pickups, inspections, and returns.\n'
                  '• Verifying driving eligibility according to national transport regulations.\n'
                  '• Processing secure payments and deposits via licensed payment processors.\n'
                  '• Sending booking confirmations, status updates, and critical rental alerts.\n'
                  '• Preventing fraudulent bookings and ensuring vehicle safety.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '3. Data Security & Storage',
              content:
                  'We take data protection seriously and implement industry-standard safeguards:\n\n'
                  '• Authentication tokens and sensitive credentials are encrypted using hardware-backed keystores on Android and Keychain on iOS.\n'
                  '• Driver\'s license document uploads are securely transmitted via encrypted channels and stored in restricted cloud storage.\n'
                  '• Access to verification documents is restricted strictly to authorized fleet and branch personnel.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '4. Third-Party Services',
              content:
                  'We partner with reputable third-party services to operate the platform:\n\n'
                  '• Payment Gateways (e.g. Chapa): For secure electronic payments. Abay Car Rentals does not store your credit card or bank credentials.\n'
                  '• Map Services: OpenStreetMap tiles are used to display branch locations and directions.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '5. Your Rights & Account Deletion',
              content:
                  'You have the right to access, update, and delete your personal information at any time.\n\n'
                  '• In-App Deletion: You can permanently delete your account and personal data at any time via Settings → Delete Account.\n'
                  '• Ongoing Bookings: For security and legal requirements, accounts with active or in-progress rentals cannot be deleted until the vehicle has been safely returned and inspected.\n'
                  '• Data Purge: Once deleted, your personal profile, credentials, and verification documents are permanently removed.',
            ),
            const SizedBox(height: AppSpacing.lg),

            _buildSection(
              title: '6. Contact Us',
              content:
                  'If you have any questions or privacy concerns regarding this policy, please reach out to our privacy and support team at support@abaycarrentals.com or visit any of our official branches.',
            ),
            const SizedBox(height: AppSpacing.xxl),
          ],
        ),
      ),
    );
  }

  Widget _buildSection({required String title, required String content}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: AppTypography.textTheme.titleMedium
                ?.copyWith(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            content,
            style: AppTypography.textTheme.bodyMedium?.copyWith(
              color: AppColors.textSecondary,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }
}
