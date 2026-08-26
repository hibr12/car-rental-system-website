import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/routes/app_routes.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../data/repositories/branch_repository.dart';
import '../../models/branch_model.dart';
import '../../widgets/states/empty_state_widget.dart';
import '../../widgets/states/error_state_widget.dart';

class BranchListScreen extends StatefulWidget {
  const BranchListScreen({super.key});

  @override
  State<BranchListScreen> createState() => _BranchListScreenState();
}

class _BranchListScreenState extends State<BranchListScreen> {
  bool _isLoading = true;
  String? _error;
  List<Branch> _branches = [];

  @override
  void initState() {
    super.initState();
    _fetchBranches();
  }

  Future<void> _fetchBranches() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    final res = await BranchRepository.instance.getBranches();
    if (!mounted) return;

    setState(() {
      _isLoading = false;
      if (res.success && res.data != null) {
        _branches = res.data!.map(Branch.fromJson).toList();
      } else {
        _error =
            res.error?.friendlyMessage ?? 'Failed to load branches. Please check your connection.';
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Our Branches')),
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
        onRetry: _fetchBranches,
      );
    }

    if (_branches.isEmpty) {
      return const EmptyStateWidget(
        icon: LucideIcons.building,
        title: 'No Branches Found',
        message: 'There are currently no active rental branches listed.',
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchBranches,
      child: ListView.separated(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        itemCount: _branches.length,
        separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
        itemBuilder: (context, index) {
          final branch = _branches[index];
          return _buildBranchCard(branch);
        },
      ),
    );
  }

  Widget _buildBranchCard(Branch branch) {
    return InkWell(
      onTap: () => context.push(AppRoutes.branchDetail, extra: branch),
      borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(AppSpacing.sm),
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                  ),
                  child: const Icon(LucideIcons.building,
                      color: AppColors.primary, size: 20),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(branch.name,
                          style: AppTypography.textTheme.titleMedium),
                      const SizedBox(height: AppSpacing.xs),
                      Text(branch.city,
                          style: AppTypography.textTheme.bodySmall
                              ?.copyWith(color: AppColors.textTertiary)),
                    ],
                  ),
                ),
                 Container(
                   padding:
                       const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                   decoration: BoxDecoration(
                     color: branch.isActive
                         ? AppColors.success.withOpacity(0.1)
                         : AppColors.textTertiary.withOpacity(0.15),
                     borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                   ),
                   child: Text(
                     branch.isActive ? 'Open' : 'Closed',
                     style: AppTypography.textTheme.labelSmall?.copyWith(
                       color:
                           branch.isActive ? AppColors.success : AppColors.textTertiary,
                     ),
                   ),
                 ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                const Icon(LucideIcons.mapPin,
                    size: 14, color: AppColors.textTertiary),
                const SizedBox(width: AppSpacing.xs),
                Expanded(
                  child: Text(
                    branch.address,
                    style: AppTypography.textTheme.bodySmall
                        ?.copyWith(color: AppColors.textSecondary),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            if (branch.phone.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.xs),
              Row(
                children: [
                  const Icon(LucideIcons.phone,
                      size: 14, color: AppColors.textTertiary),
                  const SizedBox(width: AppSpacing.xs),
                  Text(
                    branch.phone,
                    style: AppTypography.textTheme.bodySmall
                        ?.copyWith(color: AppColors.textSecondary),
                  ),
                ],
              ),
            ],
            if (branch.vehiclesCount > 0) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(
                '${branch.vehiclesCount} vehicle${branch.vehiclesCount == 1 ? '' : 's'} available',
                style: AppTypography.textTheme.labelMedium
                    ?.copyWith(color: AppColors.primary),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
