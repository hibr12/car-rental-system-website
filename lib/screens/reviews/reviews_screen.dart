import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../models/vehicle_model.dart';
import '../../models/review_model.dart';
import '../../widgets/states/empty_state_widget.dart';
import '../../widgets/states/error_state_widget.dart';
import '../../widgets/buttons/app_buttons.dart';

import '../../data/repositories/review_repository.dart';

class ReviewsScreen extends StatefulWidget {
  final Vehicle vehicle;

  const ReviewsScreen({super.key, required this.vehicle});

  @override
  State<ReviewsScreen> createState() => _ReviewsScreenState();
}

class _ReviewsScreenState extends State<ReviewsScreen> {
  List<Review> _reviews = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchReviews();
  }

  Future<void> _fetchReviews() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    final res =
        await ReviewRepository.instance.getVehicleReviews(widget.vehicle.id);
    if (mounted) {
      setState(() {
        if (res.success && res.data != null) {
          _reviews = res.data!.data;
          _errorMessage = null;
        } else {
          _reviews = [];
          _errorMessage =
              res.error?.friendlyMessage ?? 'Failed to load reviews';
        }
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Reviews'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? ErrorStateWidget(
                  message: _errorMessage!,
                  onRetry: _fetchReviews,
                )
              : _reviews.isEmpty
                  ? EmptyStateWidget(
                      icon: LucideIcons.messageSquare,
                      title: 'No Reviews Yet',
                      message:
                          'Be the first to review this ${widget.vehicle.brand} ${widget.vehicle.model}!',
                    )
                  : Column(
                      children: [
                        _buildReviewSummary(_reviews),
                        const Divider(),
                        Expanded(
                          child: RefreshIndicator(
                            onRefresh: _fetchReviews,
                            child: ListView.separated(
                              padding:
                                  const EdgeInsets.all(AppSpacing.pagePadding),
                              itemCount: _reviews.length,
                              separatorBuilder: (context, index) =>
                                  const Divider(height: AppSpacing.xxl),
                              itemBuilder: (context, index) {
                                return _buildReviewItem(_reviews[index]);
                              },
                            ),
                          ),
                        ),
                        _buildBottomBar(context),
                      ],
                    ),
    );
  }

  Widget _buildReviewSummary(List<Review> reviews) {
    final avgRating =
        reviews.map((r) => r.rating).reduce((a, b) => a + b) / reviews.length;

    return Padding(
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      child: Row(
        children: [
          Text(
            avgRating.toStringAsFixed(1),
            style: AppTypography.textTheme.displayMedium,
          ),
          const SizedBox(width: AppSpacing.md),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: List.generate(5, (index) {
                  return Icon(
                    index < avgRating.floor()
                        ? LucideIcons.star
                        : LucideIcons.starHalf,
                    color: AppColors.warning,
                    size: 20,
                  );
                }),
              ),
              const SizedBox(height: AppSpacing.xs),
              Text('Based on ${reviews.length} reviews',
                  style: AppTypography.textTheme.bodyMedium),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildReviewItem(Review review) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            CircleAvatar(
              backgroundImage: review.userProfileImageUrl.isNotEmpty
                  ? NetworkImage(review.userProfileImageUrl)
                  : null,
              radius: 20,
              child: review.userProfileImageUrl.isEmpty
                  ? const Icon(Icons.person, size: 20)
                  : null,
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(review.userName,
                      style: AppTypography.textTheme.titleMedium),
                  Text(
                    '${review.date.day}/${review.date.month}/${review.date.year}',
                    style: AppTypography.textTheme.bodySmall,
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.warning.withOpacity(0.1),
                borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
              ),
              child: Row(
                children: [
                  const Icon(LucideIcons.star,
                      size: 14, color: AppColors.warning),
                  const SizedBox(width: AppSpacing.xs),
                  Text(
                    review.rating.toStringAsFixed(1),
                    style: AppTypography.textTheme.labelMedium
                        ?.copyWith(color: AppColors.warning),
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(review.comment, style: AppTypography.textTheme.bodyLarge),
      ],
    );
  }

  Widget _buildBottomBar(BuildContext context) {
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
      child: PrimaryButton(
        text: 'Write a Review',
        onPressed: () {
          // Can't navigate to WriteReviewScreen without a Booking object
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                'To write a review, go to My Bookings → completed trip → Write a Review.',
              ),
              duration: Duration(seconds: 4),
            ),
          );
        },
        icon: LucideIcons.edit3,
      ),
    );
  }
}
