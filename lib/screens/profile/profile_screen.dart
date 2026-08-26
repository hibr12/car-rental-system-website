import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../core/utils/formatters.dart';
import '../../models/user_model.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../widgets/states/error_state_widget.dart';

import '../../data/repositories/user_repository.dart';
import '../../core/config/auth_state.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  User? _currentUser;
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchUser();
  }

  Future<void> _fetchUser() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    final res = await UserRepository.instance.getCurrentUser();
    if (mounted) {
      setState(() {
        _currentUser = res.data;
        _errorMessage =
            res.success ? null : (res.error?.friendlyMessage ?? 'Failed to load profile');
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _fetchUser,
          child: _isLoading
              ? ListView(physics: const AlwaysScrollableScrollPhysics(), children: const [
                  SizedBox(height: 300),
                  Center(child: CircularProgressIndicator()),
                ])
              : _errorMessage != null
                  ? ListView(physics: const AlwaysScrollableScrollPhysics(), children: [
                      const SizedBox(height: 120),
                      ErrorStateWidget(
                        message: _errorMessage!,
                        onRetry: _fetchUser,
                      ),
                    ])
                  : ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: [
                        _buildProfileHeader(_currentUser!),
                        const SizedBox(height: AppSpacing.lg),
                        _buildMenuSection(
                          'Account Details',
                          [
                            _MenuItem(
                                icon: LucideIcons.user,
                                title: 'Personal Information',
                                onTap: () => context.push(AppRoutes.editProfile)),
                            _MenuItem(
                                icon: LucideIcons.fileBadge,
                                title: 'Driver\'s License',
                                onTap: () => context.push(AppRoutes.driverLicense)),
                          ],
                        ),
                        _buildMenuSection(
                          'Activity',
                          [
                            _MenuItem(
                                icon: LucideIcons.creditCard,
                                title: 'Payments & Receipts',
                                onTap: () => context.push(AppRoutes.transactionHistory)),
                            _MenuItem(
                                icon: LucideIcons.star,
                                title: 'My Reviews',
                                onTap: () => context.push(AppRoutes.myReviews)),
                            _MenuItem(
                                icon: LucideIcons.bell,
                                title: 'Notifications',
                                onTap: () => context.push(AppRoutes.notifications)),
                            _MenuItem(
                                icon: LucideIcons.mapPin,
                                title: 'Our Branches',
                                onTap: () => context.push(AppRoutes.branchList)),
                            _MenuItem(
                                icon: LucideIcons.heart,
                                title: 'Favorites',
                                onTap: () => context.push(AppRoutes.favorites)),
                          ],
                        ),
                        _buildMenuSection(
                          'Support',
                          [
                            _MenuItem(
                                icon: LucideIcons.helpCircle,
                                title: 'Help Center',
                                onTap: () => context.push(AppRoutes.support)),
                            _MenuItem(
                                icon: LucideIcons.shieldQuestion,
                                title: 'Terms & Privacy',
                                onTap: () => context.push(AppRoutes.legal)),
                          ],
                        ),
                        Padding(
                          padding: const EdgeInsets.all(AppSpacing.pagePadding),
                          child: SecondaryButton(
                            text: 'Log Out',
                            icon: LucideIcons.logOut,
                            onPressed: () => _showLogoutDialog(context),
                          ),
                        ),
                        const SizedBox(height: AppSpacing.xxl),
                      ],
                    ),
        ),
      ),
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Log Out'),
        content: const Text('Are you sure you want to log out of Apex Rentals?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () async {
              Navigator.pop(ctx);
              await UserRepository.instance.logout();
              await AuthState.clear();
              if (context.mounted) {
                context.go(AppRoutes.login);
              }
            },
            child:
                const Text('Log Out', style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
  }

  Widget _buildProfileHeader(User user) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      color: AppColors.surface,
      child: Row(
        children: [
          CircleAvatar(
            radius: 40,
            backgroundImage: user.profileImageUrl.isNotEmpty
                ? NetworkImage(user.profileImageUrl)
                : null,
            child: user.profileImageUrl.isEmpty
                ? const Icon(Icons.person, size: 40)
                : null,
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(user.fullName,
                    style: AppTypography.textTheme.displaySmall),
                const SizedBox(height: AppSpacing.xs),
                Text(user.email, style: AppTypography.textTheme.bodyMedium),
                if (user.phone.isNotEmpty) ...[
                  const SizedBox(height: AppSpacing.xs),
                  Text(user.phone,
                      style: AppTypography.textTheme.bodyMedium?.copyWith(
                          color: AppColors.textSecondary)),
                ],
                const SizedBox(height: AppSpacing.xs),
                Text(
                  'Member since ${Formatters.date(user.memberSince)}',
                  style: AppTypography.textTheme.bodySmall
                      ?.copyWith(color: AppColors.textTertiary),
                ),
                const SizedBox(height: AppSpacing.sm),
                if (user.isVerified)
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppColors.success.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(LucideIcons.checkCircle,
                            size: 14, color: AppColors.success),
                        const SizedBox(width: AppSpacing.xs),
                        Text('Email Verified',
                            style: AppTypography.textTheme.labelSmall
                                ?.copyWith(color: AppColors.success)),
                      ],
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuSection(String title, List<_MenuItem> items) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.pagePadding, vertical: AppSpacing.md),
          child: Text(title, style: AppTypography.textTheme.headlineMedium),
        ),
        Container(
          color: AppColors.surface,
          child: Column(
            children: items.map((item) {
              return ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(AppSpacing.sm),
                  decoration: BoxDecoration(
                    color: AppColors.background,
                    borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                  ),
                  child: Icon(item.icon, size: 20, color: AppColors.primary),
                ),
                title:
                    Text(item.title, style: AppTypography.textTheme.titleLarge),
                trailing: const Icon(LucideIcons.chevronRight,
                    size: 20, color: AppColors.textTertiary),
                onTap: item.onTap,
                contentPadding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.pagePadding, vertical: 4),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }
}

class _MenuItem {
  final IconData icon;
  final String title;
  final VoidCallback onTap;

  _MenuItem({required this.icon, required this.title, required this.onTap});
}
