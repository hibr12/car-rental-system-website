import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import 'package:go_router/go_router.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../models/user_model.dart';
import '../../widgets/buttons/app_buttons.dart';

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

  @override
  void initState() {
    super.initState();
    _fetchUser();
  }

  Future<void> _fetchUser() async {
    setState(() => _isLoading = true);
    final res = await UserRepository.instance.getCurrentUser();
    if (mounted) {
      setState(() {
        _currentUser = res.data;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        backgroundColor: AppColors.background,
        body: Center(child: CircularProgressIndicator()),
      );
    }

    final dummyUser = User(
      id: 'guest',
      fullName: 'Guest User',
      email: 'guest@example.com',
      phone: '',
      profileImageUrl: '',
      isVerified: false,
      memberSince: DateTime.now(),
    );

    final user = _currentUser ?? dummyUser;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _fetchUser,
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            child: Column(
              children: [
                _buildProfileHeader(user),
                const SizedBox(height: AppSpacing.lg),
                _buildPointsCard(context, user),
                const SizedBox(height: AppSpacing.lg),
                _buildMenuSection(
                  'Account Details',
                  [
                    _MenuItem(
                        icon: LucideIcons.user,
                        title: 'Personal Information',
                        onTap: () => context.push(AppRoutes.editProfile)),
                    _MenuItem(
                        icon: LucideIcons.creditCard,
                        title: 'Payment Methods',
                        onTap: () => context.push(AppRoutes.paymentMethods)),
                    _MenuItem(
                        icon: LucideIcons.fileBadge,
                        title: 'Driver\'s License',
                        onTap: () => context.push(AppRoutes.driverLicense)),
                    _MenuItem(
                        icon: LucideIcons.mapPin,
                        title: 'Saved Addresses',
                        onTap: () => context.push(AppRoutes.savedAddresses)),
                    _MenuItem(
                        icon: LucideIcons.lock,
                        title: 'Change Password',
                        onTap: () => context.push(AppRoutes.changePassword)),
                  ],
                ),
                _buildMenuSection(
                  'Preferences',
                  [
                    _MenuItem(
                        icon: LucideIcons.heart,
                        title: 'Favorites',
                        onTap: () => context.push(AppRoutes.favorites)),
                    _MenuItem(
                        icon: LucideIcons.bell,
                        title: 'Notifications',
                        onTap: () => context.push(AppRoutes.notifications)),
                    _MenuItem(
                        icon: LucideIcons.settings,
                        title: 'Settings',
                        onTap: () => context.push(AppRoutes.settings)),
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
      ),
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Log Out'),
        content: const Text('Are you sure you want to log out of DriveEase?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () async {
              Navigator.pop(ctx);
              await AuthState.clear();
              await UserRepository.instance.logout();
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
                        Text('Verified Driver',
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

  Widget _buildPointsCard(BuildContext context, User user) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
      child: InkWell(
        onTap: () => context.push(AppRoutes.rewards),
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        child: Container(
          padding: const EdgeInsets.all(AppSpacing.lg),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [AppColors.primary, AppColors.primaryDark],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withOpacity(0.3),
                blurRadius: 10,
                offset: const Offset(0, 5),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('DriveEase Rewards',
                      style: AppTypography.textTheme.labelLarge
                          ?.copyWith(color: AppColors.textSecondary)),
                  const SizedBox(height: AppSpacing.xs),
                  Text('${user.rewardPoints} pts',
                      style: AppTypography.textTheme.displayMedium
                          ?.copyWith(color: AppColors.surface)),
                ],
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
                ),
                child: Text('Redeem',
                    style: AppTypography.textTheme.labelLarge
                        ?.copyWith(color: AppColors.primary)),
              ),
            ],
          ),
        ),
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
