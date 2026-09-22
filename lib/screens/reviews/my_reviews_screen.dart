import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../models/review_model.dart';
import '../../data/repositories/review_repository.dart';
import '../../widgets/states/empty_state_widget.dart';
import '../../widgets/states/error_state_widget.dart';

class MyReviewsScreen extends StatefulWidget {
  const MyReviewsScreen({super.key});

  @override
  State<MyReviewsScreen> createState() => _MyReviewsScreenState();
}

class _MyReviewsScreenState extends State<MyReviewsScreen> {
  final ScrollController _scrollController = ScrollController();
  List<Review> _reviews = [];
  bool _isLoading = true;
  bool _isLoadingMore = false;
  String? _error;
  int _currentPage = 1;
  bool _hasMore = false;

  @override
  void initState() {
    super.initState();
    _fetchReviews();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
            _scrollController.position.maxScrollExtent - 200 &&
        !_isLoadingMore &&
        _hasMore) {
      _loadMore();
    }
  }

  Future<void> _fetchReviews() async {
    setState(() {
      _isLoading = true;
      _error = null;
      _currentPage = 1;
    });

    final res = await ReviewRepository.instance.getUserReviews(page: 1);

    if (mounted) {
      setState(() {
        _isLoading = false;
        if (res.success && res.data != null) {
          _reviews = res.data!.data;
          _hasMore = res.data!.hasNextPage;
        } else {
          _error = res.error?.friendlyMessage ?? 'Failed to load your reviews';
        }
      });
    }
  }

  Future<void> _loadMore() async {
    setState(() => _isLoadingMore = true);

    _currentPage++;
    final res = await ReviewRepository.instance.getUserReviews(page: _currentPage);

    if (mounted) {
      setState(() {
        _isLoadingMore = false;
        if (res.success && res.data != null) {
          _reviews.addAll(res.data!.data);
          _hasMore = res.data!.hasNextPage;
        }
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('My Reviews')),
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
        onRetry: _fetchReviews,
      );
    }

    if (_reviews.isEmpty) {
      return const EmptyStateWidget(
        icon: LucideIcons.messageSquare,
        title: 'No Reviews Yet',
        message: 'You have not submitted any reviews yet. Complete a booking to share your experience!',
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchReviews,
      child: ListView.separated(
        controller: _scrollController,
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        itemCount: _reviews.length + (_hasMore ? 1 : 0),
        separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
        itemBuilder: (context, index) {
          if (index == _reviews.length) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(AppSpacing.md),
                child: CircularProgressIndicator(),
              ),
            );
          }
          return _buildReviewCard(_reviews[index]);
        },
      ),
    );
  }

  Widget _buildReviewCard(Review review) {
    return Container(
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
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: AppColors.warning.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                    ),
                    child: Row(
                      children: [
                        const Icon(LucideIcons.star, size: 14, color: AppColors.warning),
                        const SizedBox(width: AppSpacing.xs),
                        Text(
                          review.rating.toStringAsFixed(1),
                          style: AppTypography.textTheme.labelMedium
                              ?.copyWith(color: AppColors.warning),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  _buildStatusBadge(review.status),
                ],
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            review.comment.isNotEmpty ? review.comment : 'No comment provided',
            style: AppTypography.textTheme.bodyLarge,
          ),
          const SizedBox(height: AppSpacing.md),

          // Sub-ratings breakdown if present
          if (review.vehicleRating != null ||
              review.cleanlinessRating != null ||
              review.staffRating != null ||
              review.valueRating != null) ...[
            const Divider(),
            const SizedBox(height: AppSpacing.xs),
            Wrap(
              spacing: AppSpacing.md,
              runSpacing: AppSpacing.xs,
              children: [
                if (review.vehicleRating != null)
                  _buildSubRating('Vehicle', review.vehicleRating!),
                if (review.cleanlinessRating != null)
                  _buildSubRating('Cleanliness', review.cleanlinessRating!),
                if (review.staffRating != null)
                  _buildSubRating('Staff', review.staffRating!),
                if (review.valueRating != null)
                  _buildSubRating('Value', review.valueRating!),
              ],
            ),
            const SizedBox(height: AppSpacing.xs),
          ],

          // Admin Response if present
          if (review.adminResponse != null && review.adminResponse!.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.sm),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Response from Management',
                    style: AppTypography.textTheme.labelMedium
                        ?.copyWith(color: AppColors.primary),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    review.adminResponse!,
                    style: AppTypography.textTheme.bodySmall,
                  ),
                ],
              ),
            ),
          ],

          const SizedBox(height: AppSpacing.xs),
          Text(
            '${review.date.day}/${review.date.month}/${review.date.year}',
            style: AppTypography.textTheme.bodySmall
                ?.copyWith(color: AppColors.textTertiary),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusBadge(ReviewStatus status) {
    Color bg;
    Color fg;
    switch (status) {
      case ReviewStatus.published:
        bg = AppColors.success.withOpacity(0.1);
        fg = AppColors.success;
        break;
      case ReviewStatus.flagged:
      case ReviewStatus.hidden:
        bg = AppColors.warning.withOpacity(0.1);
        fg = AppColors.warning;
        break;
      case ReviewStatus.archived:
        bg = AppColors.textTertiary.withOpacity(0.15);
        fg = AppColors.textTertiary;
        break;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
      ),
      child: Text(
        status.label,
        style: AppTypography.textTheme.labelSmall?.copyWith(color: fg),
      ),
    );
  }

  Widget _buildSubRating(String label, int rating) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          '$label: ',
          style: AppTypography.textTheme.bodySmall
              ?.copyWith(color: AppColors.textSecondary),
        ),
        Text(
          '$rating ★',
          style: AppTypography.textTheme.labelSmall
              ?.copyWith(color: AppColors.warning),
        ),
      ],
    );
  }
}
