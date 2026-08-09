import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../widgets/buttons/app_buttons.dart';

class RentalAgreementScreen extends StatefulWidget {
  const RentalAgreementScreen({super.key});

  @override
  State<RentalAgreementScreen> createState() => _RentalAgreementScreenState();
}

class _RentalAgreementScreenState extends State<RentalAgreementScreen> {
  bool _hasSigned = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Rental Agreement')),
      body: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(AppSpacing.pagePadding),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Terms and Conditions',
                      style: AppTypography.textTheme.headlineMedium),
                  const SizedBox(height: AppSpacing.md),
                  Text(
                    '1. The Renter agrees to return the vehicle in the same condition as received.\n\n'
                    '2. The Renter is responsible for all traffic fines, tolls, and parking tickets incurred during the rental period.\n\n'
                    '3. In case of an accident, the Renter must immediately notify the rental company and local authorities.\n\n'
                    '4. The vehicle must not be used for racing, towing, or any illegal activities.\n\n'
                    '5. Smoking is strictly prohibited inside the vehicle. A cleaning fee will apply.\n\n'
                    '6. The Renter must hold a valid driver\'s license for the entire rental duration.\n\n'
                    '7. Late returns will be charged at a rate of 1.5x the daily rental rate.',
                    style: AppTypography.textTheme.bodyMedium,
                  ),
                  const SizedBox(height: AppSpacing.xxxl),
                  Text('Digital Signature',
                      style: AppTypography.textTheme.titleMedium),
                  const SizedBox(height: AppSpacing.sm),
                  _buildSignaturePad(),
                ],
              ),
            ),
          ),
          _buildBottomBar(),
        ],
      ),
    );
  }

  Widget _buildSignaturePad() {
    return GestureDetector(
      onPanUpdate: (details) {
        if (!_hasSigned) setState(() => _hasSigned = true);
      },
      child: Container(
        height: 150,
        width: double.infinity,
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(
              color: _hasSigned ? AppColors.primary : AppColors.border,
              width: 2),
        ),
        child: Center(
          child: _hasSigned
              ? const Text('Signed',
                  style: TextStyle(
                      fontFamily: 'cursive',
                      fontSize: 32,
                      color: AppColors.textPrimary))
              : Text('Draw your signature here',
                  style: AppTypography.textTheme.bodyMedium
                      ?.copyWith(color: AppColors.textTertiary)),
        ),
      ),
    );
  }

  Widget _buildBottomBar() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      decoration: BoxDecoration(
        color: AppColors.surface,
        boxShadow: [
          BoxShadow(
            color: AppColors.textPrimary.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Row(
        children: [
          if (_hasSigned)
            Expanded(
              child: Padding(
                padding: const EdgeInsets.only(right: AppSpacing.md),
                child: SecondaryButton(
                  text: 'Clear',
                  onPressed: () => setState(() => _hasSigned = false),
                ),
              ),
            ),
          Expanded(
            flex: 2,
            child: PrimaryButton(
              text: 'I Accept',
              onPressed: _hasSigned ? () => Navigator.pop(context) : null,
            ),
          ),
        ],
      ),
    );
  }
}
