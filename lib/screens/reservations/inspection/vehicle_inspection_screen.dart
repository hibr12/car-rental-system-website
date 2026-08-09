import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../../../core/colors/app_colors.dart';
import '../../../core/spacing/app_spacing.dart';
import '../../../core/typography/app_typography.dart';
import '../../../widgets/buttons/app_buttons.dart';
import '../../../core/routes/app_routes.dart';

class VehicleInspectionScreen extends StatefulWidget {
  final bool isReturn;

  const VehicleInspectionScreen({super.key, this.isReturn = false});

  @override
  State<VehicleInspectionScreen> createState() =>
      _VehicleInspectionScreenState();
}

class _VehicleInspectionScreenState extends State<VehicleInspectionScreen> {
  final PageController _pageController = PageController();
  int _currentPage = 0;
  final int _totalPages = 4;

  double _fuelLevel = 1.0; // 100%
  String _mileage = '45,200';
  bool _hasDamage = false;

  void _nextPage() {
    if (_currentPage < _totalPages - 1) {
      _pageController.nextPage(
          duration: const Duration(milliseconds: 300), curve: Curves.easeInOut);
    } else {
      context.pushReplacement(AppRoutes.inspectionSummary,
          extra: widget.isReturn);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: AppBar(
        title:
            Text(widget.isReturn ? 'Return Inspection' : 'Pickup Inspection'),
        leading: IconButton(
          icon: const Icon(LucideIcons.x),
          onPressed: () => _showCancelDialog(context),
        ),
      ),
      body: Column(
        children: [
          _buildProgressIndicator(),
          Expanded(
            child: PageView(
              controller: _pageController,
              physics: const NeverScrollableScrollPhysics(), // Disable swipe
              onPageChanged: (idx) => setState(() => _currentPage = idx),
              children: [
                _buildPhotosStep(),
                _buildFuelMileageStep(),
                _buildDamageStep(),
                _buildSignatureStep(),
              ],
            ),
          ),
          _buildBottomBar(),
        ],
      ),
    );
  }

  Widget _buildProgressIndicator() {
    return LinearProgressIndicator(
      value: (_currentPage + 1) / _totalPages,
      backgroundColor: AppColors.surface,
      valueColor: const AlwaysStoppedAnimation<Color>(AppColors.primary),
    );
  }

  Widget _buildPhotosStep() {
    return _buildStepContainer(
      title: 'Vehicle Photos',
      subtitle: 'Take clear photos of all 4 sides of the vehicle.',
      child: GridView.count(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        crossAxisCount: 2,
        mainAxisSpacing: AppSpacing.md,
        crossAxisSpacing: AppSpacing.md,
        children: [
          _buildPhotoBox('Front'),
          _buildPhotoBox('Left Side'),
          _buildPhotoBox('Right Side'),
          _buildPhotoBox('Rear'),
        ],
      ),
    );
  }

  Widget _buildPhotoBox(String label) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border, style: BorderStyle.solid),
      ),
      child: InkWell(
        onTap: () {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
              content: Text('Opening camera... (Not implemented yet)')));
        },
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(LucideIcons.camera,
                size: 32, color: AppColors.textTertiary),
            const SizedBox(height: AppSpacing.sm),
            Text(label, style: AppTypography.textTheme.bodyMedium),
          ],
        ),
      ),
    );
  }

  Widget _buildFuelMileageStep() {
    return _buildStepContainer(
      title: 'Fuel & Mileage',
      subtitle: 'Record the current fuel level and odometer reading.',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Fuel Level', style: AppTypography.textTheme.titleMedium),
          const SizedBox(height: AppSpacing.sm),
          Slider(
            value: _fuelLevel,
            onChanged: (val) => setState(() => _fuelLevel = val),
            divisions: 4,
            activeColor: AppColors.primary,
            label: '${(_fuelLevel * 100).toInt()}%',
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Empty', style: AppTypography.textTheme.bodySmall),
              Text('Full', style: AppTypography.textTheme.bodySmall),
            ],
          ),
          const SizedBox(height: AppSpacing.xxxl),
          Text('Odometer Reading (mi)',
              style: AppTypography.textTheme.titleMedium),
          const SizedBox(height: AppSpacing.md),
          TextField(
            controller: TextEditingController(text: _mileage),
            decoration: InputDecoration(
              filled: true,
              fillColor: AppColors.surface,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                borderSide: const BorderSide(color: AppColors.border),
              ),
              suffixIcon:
                  const Icon(LucideIcons.gauge, color: AppColors.textTertiary),
            ),
            keyboardType: TextInputType.number,
            onChanged: (val) => _mileage = val,
          ),
        ],
      ),
    );
  }

  Widget _buildDamageStep() {
    return _buildStepContainer(
      title: 'Damage Report',
      subtitle: 'Any existing or new damage on the vehicle?',
      child: Column(
        children: [
          SwitchListTile(
            title: Text('Report Damage',
                style: AppTypography.textTheme.titleMedium),
            subtitle: Text('Scratches, dents, interior stains',
                style: AppTypography.textTheme.bodyMedium),
            value: _hasDamage,
            onChanged: (val) => setState(() => _hasDamage = val),
            activeColor: AppColors.primary,
            contentPadding: EdgeInsets.zero,
          ),
          if (_hasDamage) ...[
            const SizedBox(height: AppSpacing.lg),
            TextField(
              maxLines: 4,
              decoration: InputDecoration(
                hintText: 'Describe the damage...',
                filled: true,
                fillColor: AppColors.surface,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            OutlinedButton.icon(
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
                    content: Text('Opening camera... (Not implemented yet)')));
              },
              icon: const Icon(LucideIcons.camera),
              label: const Text('Add Damage Photos'),
            ),
          ]
        ],
      ),
    );
  }

  Widget _buildSignatureStep() {
    return _buildStepContainer(
      title: 'Digital Signature',
      subtitle: 'Please sign below to confirm the inspection.',
      child: Column(
        children: [
          Container(
            height: 200,
            width: double.infinity,
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
              border: Border.all(color: AppColors.border),
            ),
            child: const Center(
              child: Text('Sign Here (Mock Canvas)',
                  style: TextStyle(color: AppColors.textTertiary)),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          TextButton(
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Signature cleared')));
            },
            child: const Text('Clear Signature'),
          ),
        ],
      ),
    );
  }

  Widget _buildStepContainer(
      {required String title,
      required String subtitle,
      required Widget child}) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: AppTypography.textTheme.displaySmall),
          const SizedBox(height: AppSpacing.sm),
          Text(subtitle, style: AppTypography.textTheme.bodyLarge),
          const SizedBox(height: AppSpacing.xxxl),
          child,
        ],
      ),
    );
  }

  Widget _buildBottomBar() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      decoration: const BoxDecoration(
        color: AppColors.surface,
        border: Border(top: BorderSide(color: AppColors.border)),
      ),
      child: SafeArea(
        child: Row(
          children: [
            if (_currentPage > 0) ...[
              Expanded(
                child: SecondaryButton(
                  text: 'Back',
                  onPressed: () {
                    _pageController.previousPage(
                        duration: const Duration(milliseconds: 300),
                        curve: Curves.easeInOut);
                  },
                ),
              ),
              const SizedBox(width: AppSpacing.md),
            ],
            Expanded(
              flex: 2,
              child: PrimaryButton(
                text: _currentPage == _totalPages - 1
                    ? 'Complete Inspection'
                    : 'Next Step',
                onPressed: _nextPage,
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showCancelDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cancel Inspection?'),
        content: const Text('Your progress will be lost. Are you sure?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx), child: const Text('Resume')),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              context.pop();
            },
            child:
                const Text('Cancel', style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
  }
}
