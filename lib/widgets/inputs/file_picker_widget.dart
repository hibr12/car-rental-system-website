import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

class FilePickerWidget extends StatefulWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final Function(String path)? onFileSelected;
  final String? initialValue;

  const FilePickerWidget({
    super.key,
    required this.title,
    required this.subtitle,
    this.icon = LucideIcons.upload,
    this.onFileSelected,
    this.initialValue,
  });

  @override
  State<FilePickerWidget> createState() => _FilePickerWidgetState();
}

class _FilePickerWidgetState extends State<FilePickerWidget> {
  String? _selectedFilePath;
  bool _isUploading = false;

  @override
  void initState() {
    super.initState();
    _selectedFilePath = widget.initialValue;
  }

  Future<void> _mockPickFile() async {
    setState(() {
      _isUploading = true;
    });

    // Simulate file picker delay
    await Future.delayed(const Duration(seconds: 1));

    // Simulate upload delay
    await Future.delayed(const Duration(seconds: 1));

    setState(() {
      _selectedFilePath = '/mock/path/to/uploaded/file.jpg';
      _isUploading = false;
    });

    widget.onFileSelected?.call(_selectedFilePath!);
  }

  void _removeFile() {
    setState(() {
      _selectedFilePath = null;
    });
    widget.onFileSelected?.call('');
  }

  @override
  Widget build(BuildContext context) {
    if (_selectedFilePath != null) {
      return _buildFilePreview();
    }

    return InkWell(
      onTap: _isUploading ? null : _mockPickFile,
      borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(AppSpacing.xl),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(
            color: AppColors.border,
            style: BorderStyle.solid,
            width: 2,
          ),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            if (_isUploading)
              const CircularProgressIndicator(color: AppColors.primary)
            else ...[
              Icon(widget.icon, size: 40, color: AppColors.primary),
              const SizedBox(height: AppSpacing.md),
              Text(widget.title, style: AppTypography.textTheme.titleMedium),
              const SizedBox(height: AppSpacing.xs),
              Text(
                widget.subtitle,
                style: AppTypography.textTheme.bodyMedium
                    ?.copyWith(color: AppColors.textSecondary),
                textAlign: TextAlign.center,
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildFilePreview() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.primary.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
            ),
            child: const Icon(LucideIcons.image, color: AppColors.primary),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Uploaded successfully',
                    style: AppTypography.textTheme.titleSmall),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  _selectedFilePath!.split('/').last,
                  style: AppTypography.textTheme.bodySmall
                      ?.copyWith(color: AppColors.textTertiary),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(LucideIcons.trash2, color: AppColors.error),
            onPressed: _removeFile,
          ),
        ],
      ),
    );
  }
}
