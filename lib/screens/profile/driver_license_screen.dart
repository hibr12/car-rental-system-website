import 'dart:io';
import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../widgets/states/status_badge.dart';
import '../../widgets/states/error_state_widget.dart';
import '../../widgets/inputs/app_text_field.dart';
import '../../data/repositories/driver_license_repository.dart';
import '../../models/driver_license_model.dart';

class DriverLicenseScreen extends StatefulWidget {
  const DriverLicenseScreen({super.key});

  @override
  State<DriverLicenseScreen> createState() => _DriverLicenseScreenState();
}

class _DriverLicenseScreenState extends State<DriverLicenseScreen> {
  bool _isLoading = true;
  String? _error;
  DriverLicense? _license;
  bool _isSubmitting = false;

  final _formKey = GlobalKey<FormState>();
  final _licenseNumberController = TextEditingController();
  final _fullNameController = TextEditingController();
  final _issueDateController = TextEditingController();
  final _expiryDateController = TextEditingController();
  final _authorityController = TextEditingController();
  String _selectedCategory = 'automobile';

  File? _frontImage;
  File? _backImage;
  final ImagePicker _picker = ImagePicker();

  static const List<Map<String, String>> _categories = [
    {'value': 'automobile', 'label': 'Automobile (Standard Car)'},
    {'value': 'motorcycle', 'label': 'Motorcycle'},
    {'value': 'commercial', 'label': 'Commercial Vehicle'},
    {'value': 'minibus', 'label': 'Minibus'},
    {'value': 'heavy', 'label': 'Heavy Vehicle'},
  ];

  @override
  void initState() {
    super.initState();
    _fetchLicense();
  }

  @override
  void dispose() {
    _licenseNumberController.dispose();
    _fullNameController.dispose();
    _issueDateController.dispose();
    _expiryDateController.dispose();
    _authorityController.dispose();
    super.dispose();
  }

  Future<void> _fetchLicense() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    final res = await DriverLicenseRepository.instance.getMyLicense();

    if (mounted) {
      setState(() {
        _isLoading = false;
        if (res.success) {
          _license = res.data;
        } else {
          _error = res.error?.friendlyMessage ?? 'Failed to load license';
        }
      });
    }
  }

  Future<void> _pickImage(bool isFront) async {
    final XFile? image = await _picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1200,
      maxHeight: 1200,
      imageQuality: 85,
    );
    if (image != null) {
      setState(() {
        if (isFront) {
          _frontImage = File(image.path);
        } else {
          _backImage = File(image.path);
        }
      });
    }
  }

  Future<void> _selectDate(BuildContext context, TextEditingController controller, {bool isPast = false}) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: isPast ? now.subtract(const Duration(days: 365)) : now.add(const Duration(days: 365)),
      firstDate: isPast ? DateTime(1970) : now,
      lastDate: isPast ? now : DateTime(2045),
    );
    if (picked != null) {
      controller.text = DateFormat('yyyy-MM-dd').format(picked);
    }
  }

  Future<void> _submitLicense() async {
    if (!_formKey.currentState!.validate()) return;
    if (_frontImage == null || _backImage == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please upload both front and back document images'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final fields = {
      'license_number': _licenseNumberController.text.trim(),
      'full_name': _fullNameController.text.trim(),
      'license_category': _selectedCategory,
      'issue_date': _issueDateController.text.trim(),
      'expiry_date': _expiryDateController.text.trim(),
      if (_authorityController.text.trim().isNotEmpty)
        'issuing_authority': _authorityController.text.trim(),
    };

    final res = await DriverLicenseRepository.instance.submitLicense(
      fields: fields,
      frontDocument: _frontImage!,
      backDocument: _backImage!,
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res.success) {
      setState(() => _license = res.data);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('License submitted for verification successfully'),
          backgroundColor: AppColors.success,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res.error?.friendlyMessage ?? 'Failed to submit license'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Driver\'s License')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return ErrorStateWidget(
        message: _error!,
        onRetry: _fetchLicense,
      );
    }

    if (_license == null) {
      return _buildForm();
    }

    return RefreshIndicator(
      onRefresh: _fetchLicense,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildStatusCard(),
            const SizedBox(height: AppSpacing.xxl),
            Text('License Details', style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            Container(
              padding: const EdgeInsets.all(AppSpacing.lg),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                border: Border.all(color: AppColors.border),
              ),
              child: Column(
                children: [
                  _buildDetailRow('Full Name',
                      _license!.fullName.isEmpty ? 'N/A' : _license!.fullName),
                  const Divider(),
                  _buildDetailRow('License Number', _license!.licenseNumberMasked ?? _license!.licenseNumber ?? 'N/A'),
                  const Divider(),
                  _buildDetailRow('Category', _license!.licenseCategory.isEmpty
                      ? 'AUTOMOBILE'
                      : _license!.licenseCategory.toUpperCase()),
                  const Divider(),
                  _buildDetailRow('Issue Date', _license!.issueDate),
                  const Divider(),
                  _buildDetailRow('Expiry Date', _license!.expiryDate),
                  if (_license!.issuingAuthority != null && _license!.issuingAuthority!.isNotEmpty) ...[
                    const Divider(),
                    _buildDetailRow('Issuing Authority', _license!.issuingAuthority!),
                  ],
                ],
              ),
            ),
            if (_license!.status == LicenseStatus.rejected ||
                _license!.status == LicenseStatus.expired) ...[
              const SizedBox(height: AppSpacing.xxl),
              Text(
                  _license!.status == LicenseStatus.expired
                      ? 'Renew Your License'
                      : 'Resubmit License',
                  style: AppTypography.textTheme.headlineMedium),
              const SizedBox(height: AppSpacing.md),
              _buildForm(isResubmit: true),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildStatusCard() {
    final isVerified = _license!.status == LicenseStatus.verified;
    final isRejected = _license!.status == LicenseStatus.rejected;
    final isExpired = _license!.status == LicenseStatus.expired;

    Color color = isVerified
        ? AppColors.success
        : (isRejected || isExpired ? AppColors.error : AppColors.warning);
    IconData icon = isVerified
        ? LucideIcons.checkCircle
        : (isRejected || isExpired ? LucideIcons.xCircle : LucideIcons.clock);
    String title = isVerified
        ? 'License Verified'
        : isRejected
            ? 'Verification Rejected'
            : isExpired
                ? 'License Expired'
                : 'Verification Pending';
    String msg = isVerified
        ? 'You are approved to drive vehicles in this category.'
        : isRejected
            ? (_license!.rejectionReason ?? 'Please upload a clear, valid license.')
            : isExpired
                ? (_license!.rejectionReason ??
                    'Your license has expired. Please upload your renewed license.')
                : 'We are reviewing your document. You will be notified once it is approved.';

    BadgeStatus badgeStatus = isVerified ? BadgeStatus.approved : (isRejected ? BadgeStatus.rejected : BadgeStatus.pending);

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
            status: badgeStatus,
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

  Widget _buildForm({bool isResubmit = false}) {
    return SingleChildScrollView(
      padding: EdgeInsets.all(isResubmit ? 0 : AppSpacing.pagePadding),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (!isResubmit) ...[
              Text('Submit License',
                  style: AppTypography.textTheme.headlineMedium),
              const SizedBox(height: AppSpacing.sm),
              Text(
                  'Please provide your license details to get verified.',
                  style: AppTypography.textTheme.bodyMedium),
              const SizedBox(height: AppSpacing.xl),
            ],
            AppTextField(
              controller: _fullNameController,
              label: 'Full Name (as on license) *',
              hint: 'e.g. Abebe Bikila',
              validator: (v) => v!.trim().isEmpty ? 'Required' : null,
            ),
            const SizedBox(height: AppSpacing.md),
            AppTextField(
              controller: _licenseNumberController,
              label: 'License Number *',
              hint: 'e.g. ETH-12345678',
              validator: (v) => v!.trim().isEmpty ? 'Required' : null,
            ),
            const SizedBox(height: AppSpacing.md),

            // Category Dropdown
            Text('License Category *', style: AppTypography.textTheme.titleSmall),
            const SizedBox(height: AppSpacing.xs),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                border: Border.all(color: AppColors.border),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<String>(
                  isExpanded: true,
                  value: _selectedCategory,
                  items: _categories.map((c) {
                    return DropdownMenuItem<String>(
                      value: c['value'],
                      child: Text(c['label']!, style: AppTypography.textTheme.bodyLarge),
                    );
                  }).toList(),
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedCategory = val);
                  },
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.md),

            // Issue Date
            GestureDetector(
              onTap: () => _selectDate(context, _issueDateController, isPast: true),
              child: AbsorbPointer(
                child: AppTextField(
                  controller: _issueDateController,
                  label: 'Issue Date *',
                  hint: 'YYYY-MM-DD',
                  validator: (v) => v!.trim().isEmpty ? 'Required' : null,
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.md),

            // Expiry Date
            GestureDetector(
              onTap: () => _selectDate(context, _expiryDateController, isPast: false),
              child: AbsorbPointer(
                child: AppTextField(
                  controller: _expiryDateController,
                  label: 'Expiry Date *',
                  hint: 'YYYY-MM-DD',
                  validator: (v) => v!.trim().isEmpty ? 'Required' : null,
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.md),

            AppTextField(
              controller: _authorityController,
              label: 'Issuing Authority (Optional)',
              hint: 'e.g. Federal Transport Authority',
            ),
            const SizedBox(height: AppSpacing.xl),

            Text('License Document Images *', style: AppTypography.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.sm),
            Text('Upload clear photos of both sides of your license card.', style: AppTypography.textTheme.bodySmall),
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                Expanded(child: _buildImagePicker(true)),
                const SizedBox(width: AppSpacing.md),
                Expanded(child: _buildImagePicker(false)),
              ],
            ),
            const SizedBox(height: AppSpacing.xxl),
            PrimaryButton(
              text: isResubmit ? 'Resubmit License' : 'Submit for Verification',
              isLoading: _isSubmitting,
              onPressed: _submitLicense,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildImagePicker(bool isFront) {
    final file = isFront ? _frontImage : _backImage;
    return GestureDetector(
      onTap: () => _pickImage(isFront),
      child: Container(
        height: 120,
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          border: Border.all(color: file != null ? AppColors.primary : AppColors.border),
        ),
        child: file != null
            ? ClipRRect(
                borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                child: Image.file(file, fit: BoxFit.cover),
              )
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(LucideIcons.camera, color: AppColors.primary),
                  const SizedBox(height: AppSpacing.xs),
                  Text(isFront ? 'Front Side *' : 'Back Side *',
                      style: AppTypography.textTheme.bodyMedium
                          ?.copyWith(color: AppColors.textSecondary)),
                ],
              ),
      ),
    );
  }
}
