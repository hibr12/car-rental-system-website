import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  bool _pushNotifications = true;
  bool _emailNotifications = false;
  bool _smsNotifications = true;
  bool _darkMode = false;
  String _selectedLanguage = 'English (US)';
  String _selectedRegion = 'United States';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Settings'),
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
        children: [
          _buildSectionTitle('Appearance'),
          _buildSwitchTile(
            icon: LucideIcons.moon,
            title: 'Dark Mode',
            value: _darkMode,
            onChanged: (val) => setState(() => _darkMode = val),
          ),
          const Divider(height: AppSpacing.xxl),
          _buildSectionTitle('Notifications'),
          _buildSwitchTile(
            icon: LucideIcons.bell,
            title: 'Push Notifications',
            value: _pushNotifications,
            onChanged: (val) => setState(() => _pushNotifications = val),
          ),
          _buildSwitchTile(
            icon: LucideIcons.mail,
            title: 'Email Alerts',
            value: _emailNotifications,
            onChanged: (val) => setState(() => _emailNotifications = val),
          ),
          _buildSwitchTile(
            icon: LucideIcons.messageSquare,
            title: 'SMS Alerts',
            value: _smsNotifications,
            onChanged: (val) => setState(() => _smsNotifications = val),
          ),
          const Divider(height: AppSpacing.xxl),
          _buildSectionTitle('Language & Region'),
          _buildListTile(
            icon: LucideIcons.globe,
            title: 'Language',
            subtitle: _selectedLanguage,
            onTap: _showLanguagePicker,
          ),
          _buildListTile(
            icon: LucideIcons.mapPin,
            title: 'Region',
            subtitle: _selectedRegion,
            onTap: _showRegionPicker,
          ),
        ],
      ),
    );
  }

  void _showLanguagePicker() {
    final languages = [
      'English (US)',
      'English (UK)',
      'Spanish',
      'French',
      'German'
    ];
    _showSelectionSheet('Select Language', languages, _selectedLanguage, (val) {
      setState(() => _selectedLanguage = val);
    });
  }

  void _showRegionPicker() {
    final regions = [
      'United States',
      'Canada',
      'United Kingdom',
      'Australia',
      'Europe'
    ];
    _showSelectionSheet('Select Region', regions, _selectedRegion, (val) {
      setState(() => _selectedRegion = val);
    });
  }

  void _showSelectionSheet(String title, List<String> options,
      String currentSelection, ValueChanged<String> onSelected) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius:
            BorderRadius.vertical(top: Radius.circular(AppSpacing.radiusXl)),
      ),
      builder: (context) {
        return Container(
          padding: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
          decoration: const BoxDecoration(
            color: AppColors.background,
            borderRadius: BorderRadius.vertical(
                top: Radius.circular(AppSpacing.radiusXl)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.pagePadding),
                child:
                    Text(title, style: AppTypography.textTheme.headlineMedium),
              ),
              const SizedBox(height: AppSpacing.md),
              ...options.map((option) {
                final isSelected = option == currentSelection;
                return ListTile(
                  title: Text(
                    option,
                    style: AppTypography.textTheme.titleMedium?.copyWith(
                      color: isSelected
                          ? AppColors.primary
                          : AppColors.textPrimary,
                    ),
                  ),
                  trailing: isSelected
                      ? const Icon(LucideIcons.check, color: AppColors.primary)
                      : null,
                  contentPadding: const EdgeInsets.symmetric(
                      horizontal: AppSpacing.pagePadding),
                  onTap: () {
                    onSelected(option);
                    Navigator.pop(context);
                  },
                );
              }),
              const SizedBox(height: AppSpacing.xl),
            ],
          ),
        );
      },
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.pagePadding, vertical: AppSpacing.sm),
      child: Text(
        title,
        style: AppTypography.textTheme.headlineMedium,
      ),
    );
  }

  Widget _buildSwitchTile({
    required IconData icon,
    required String title,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return Container(
      color: AppColors.surface,
      child: SwitchListTile(
        secondary: Icon(icon, color: AppColors.primary),
        title: Text(title, style: AppTypography.textTheme.titleMedium),
        value: value,
        onChanged: onChanged,
        activeColor: AppColors.primary,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
      ),
    );
  }

  Widget _buildListTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Container(
      color: AppColors.surface,
      child: ListTile(
        leading: Icon(icon, color: AppColors.primary),
        title: Text(title, style: AppTypography.textTheme.titleMedium),
        subtitle: Text(subtitle, style: AppTypography.textTheme.bodyMedium),
        trailing: const Icon(LucideIcons.chevronRight,
            size: 20, color: AppColors.textTertiary),
        onTap: onTap,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
      ),
    );
  }
}
