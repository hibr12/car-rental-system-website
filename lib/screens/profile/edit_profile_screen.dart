import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../models/user_model.dart';
import '../../widgets/buttons/app_buttons.dart';

import '../../data/repositories/user_repository.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  late final TextEditingController _nameController;
  late final TextEditingController _emailController;
  late final TextEditingController _phoneController;

  bool _isLoading = true;
  bool _isSaving = false;
  User? _user;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController();
    _emailController = TextEditingController();
    _phoneController = TextEditingController();
    _fetchUser();
  }

  Future<void> _fetchUser() async {
    setState(() => _isLoading = true);
    final res = await UserRepository.instance.getCurrentUser();
    if (mounted && res.data != null) {
      setState(() {
        _user = res.data;
        _nameController.text = _user!.fullName;
        _emailController.text = _user!.email;
        _phoneController.text = _user!.phone;
        _isLoading = false;
      });
    } else if (mounted) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _saveProfile() async {
    setState(() => _isSaving = true);
    final res = await UserRepository.instance.updateProfile(
      User(
        id: _user?.id ?? '0',
        fullName: _nameController.text.trim(),
        email: _emailController.text.trim(),
        phone: _phoneController.text.trim(),
        profileImageUrl: _user?.profileImageUrl ?? '',
        memberSince: _user?.memberSince ?? DateTime.now(),
      ),
    );

    if (mounted) {
      setState(() => _isSaving = false);
      if (res.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Profile updated successfully'),
              backgroundColor: AppColors.success),
        );
        Navigator.pop(context);
      } else {
        // Show per-field validation errors if present
        final validationError = res.error?.validationError;
        if (validationError != null) {
          final messages =
              validationError.errors.values.expand((list) => list).join('\n');
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(messages), backgroundColor: AppColors.error),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                  res.error?.friendlyMessage ?? 'Failed to update profile'),
              backgroundColor: AppColors.error,
            ),
          );
        }
      }
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        backgroundColor: AppColors.background,
        body: Center(child: CircularProgressIndicator()),
      );
    }

    final user = _user ??
        User(
          id: 'guest',
          fullName: 'Guest User',
          email: '',
          phone: '',
          profileImageUrl: '',
          memberSince: DateTime.now(),
        );

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Edit Profile')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          children: [
            Center(
              child: CircleAvatar(
                radius: 56,
                backgroundImage: (_user?.profileImageUrl ?? '').isNotEmpty
                    ? NetworkImage(_user!.profileImageUrl) as ImageProvider
                    : null,
                child: (_user?.profileImageUrl ?? '').isEmpty
                    ? const Icon(Icons.person, size: 56)
                    : null,
              ),
            ),
            // The backend accepts profile details as JSON only — there is no
            // avatar upload endpoint, so no picker is shown.
            const SizedBox(height: AppSpacing.xxl),
            _buildTextField('Full Name', _nameController, LucideIcons.user),
            const SizedBox(height: AppSpacing.md),
            _buildTextField(
                'Email Address', _emailController, LucideIcons.mail),
            const SizedBox(height: AppSpacing.md),
            _buildTextField(
                'Phone Number', _phoneController, LucideIcons.phone,
                keyboardType: TextInputType.phone),
            const SizedBox(height: AppSpacing.xxl),
            _buildInfoRow('Member Since',
                '${user.memberSince.year}-${user.memberSince.month.toString().padLeft(2, '0')}'),
            const SizedBox(height: AppSpacing.sm),
            _buildInfoRow(
                'Verification', user.isVerified ? 'Verified ✓' : 'Pending'),
            const SizedBox(height: AppSpacing.xxxl),
            PrimaryButton(
              text: 'Save Changes',
              isLoading: _isSaving,
              onPressed: _saveProfile,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTextField(
      String label, TextEditingController controller, IconData icon,
      {TextInputType? keyboardType}) {
    return TextField(
      controller: controller,
      keyboardType: keyboardType,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: AppColors.primary),
        filled: true,
        fillColor: AppColors.surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Container(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.md, vertical: AppSpacing.sm),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: AppTypography.textTheme.bodyLarge),
          Text(value, style: AppTypography.textTheme.titleMedium),
        ],
      ),
    );
  }
}
