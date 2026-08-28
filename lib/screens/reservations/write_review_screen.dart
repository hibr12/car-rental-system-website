import 'package:flutter/material.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../models/booking_model.dart';
import '../../data/repositories/review_repository.dart';

class WriteReviewScreen extends StatefulWidget {
  final Booking booking;
  const WriteReviewScreen({super.key, required this.booking});

  @override
  State<WriteReviewScreen> createState() => _WriteReviewScreenState();
}

class _WriteReviewScreenState extends State<WriteReviewScreen> {
  double _overallRating = 5;
  double _vehicleRating = 5;
  double _cleanlinessRating = 5;
  double _staffRating = 5;
  double _valueRating = 5;

  final TextEditingController _commentController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _submitReview() async {
    if (_overallRating < 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select an overall rating.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final res = await ReviewRepository.instance.storeForBooking(
      bookingId: widget.booking.id,
      overallRating: _overallRating.round(),
      vehicleRating: _vehicleRating.round(),
      cleanlinessRating: _cleanlinessRating.round(),
      staffRating: _staffRating.round(),
      valueRating: _valueRating.round(),
      comment: _commentController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Thank you! Your review has been submitted.'),
          backgroundColor: AppColors.success,
        ),
      );
      Navigator.pop(context, true);
    } else {
      final errorMsg = res.error?.friendlyMessage ??
          'Failed to submit review. Please try again.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(errorMsg),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Write a Review')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Vehicle card header
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
                    child: Image.network(
                      widget.booking.vehicle.imageUrls.first,
                      width: 80,
                      height: 60,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        width: 80,
                        height: 60,
                        color: AppColors.surfaceElevated,
                        child: const Icon(Icons.directions_car, size: 24),
                      ),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.booking.vehicle.fullName,
                          style: AppTypography.textTheme.titleMedium,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: AppSpacing.xs),
                        Text(
                          'Booking Ref: ${widget.booking.bookingReference.isNotEmpty ? widget.booking.bookingReference : widget.booking.id}',
                          style: AppTypography.textTheme.bodySmall
                              ?.copyWith(color: AppColors.textTertiary),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xxl),

            // Overall rating
            Text('Overall Experience',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.sm),
            Center(
              child: RatingBar.builder(
                initialRating: _overallRating,
                minRating: 1,
                direction: Axis.horizontal,
                allowHalfRating: false,
                itemCount: 5,
                itemSize: 36,
                itemPadding: const EdgeInsets.symmetric(horizontal: 4.0),
                itemBuilder: (context, _) =>
                    const Icon(Icons.star, color: AppColors.warning),
                onRatingUpdate: (rating) =>
                    setState(() => _overallRating = rating),
              ),
            ),
            const SizedBox(height: AppSpacing.xxl),

            // Detailed sub-ratings
            Text('Category Ratings',
                style: AppTypography.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.md),
            _buildRatingRow('Vehicle Condition', _vehicleRating, (r) => setState(() => _vehicleRating = r)),
            _buildRatingRow('Cleanliness', _cleanlinessRating, (r) => setState(() => _cleanlinessRating = r)),
            _buildRatingRow('Staff & Service', _staffRating, (r) => setState(() => _staffRating = r)),
            _buildRatingRow('Value for Money', _valueRating, (r) => setState(() => _valueRating = r)),

            const SizedBox(height: AppSpacing.xxl),

            // Comment
            Text('Comments & Feedback',
                style: AppTypography.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              controller: _commentController,
              maxLines: 4,
              maxLength: 1000,
              decoration: InputDecoration(
                hintText:
                    'Share your feedback about the car, pickup experience, or any tips for other renters.',
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
                  borderSide:
                      const BorderSide(color: AppColors.primary, width: 1.5),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.xxxl),

            PrimaryButton(
              text: 'Submit Review',
              isLoading: _isSubmitting,
              onPressed: _isSubmitting ? null : _submitReview,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRatingRow(
      String title, double currentRating, ValueChanged<double> onUpdate) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.xs),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(title, style: AppTypography.textTheme.bodyLarge),
          RatingBar.builder(
            initialRating: currentRating,
            minRating: 1,
            direction: Axis.horizontal,
            allowHalfRating: false,
            itemCount: 5,
            itemSize: 22,
            itemPadding: const EdgeInsets.symmetric(horizontal: 2.0),
            itemBuilder: (context, _) =>
                const Icon(Icons.star, color: AppColors.warning),
            onRatingUpdate: onUpdate,
          ),
        ],
      ),
    );
  }
}
