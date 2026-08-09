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
  double _rating = 0;
  final TextEditingController _reviewController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _reviewController.dispose();
    super.dispose();
  }

  Future<void> _submitReview() async {
    if (_rating == 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a rating.')),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final res = await ReviewRepository.instance.addReview(
      vehicleId: widget.booking.vehicle.id,
      bookingId: widget.booking.id,
      rating: _rating.round(), // Backend requires int 1-5
      comment: _reviewController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Thank you for your review!'),
          backgroundColor: AppColors.success,
        ),
      );
      Navigator.pop(context, true);
    } else {
      // Handle 422 (already reviewed / booking not completed) and 403
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
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const SizedBox(height: AppSpacing.md),
            ClipRRect(
              borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
              child: Image.network(
                widget.booking.vehicle.imageUrls.first,
                width: 160,
                height: 100,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Container(
                  width: 160,
                  height: 100,
                  color: AppColors.textTertiary,
                  child: const Icon(Icons.image_not_supported, size: 32),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.md),
            Text(widget.booking.vehicle.fullName,
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.xs),
            Text(
              'Trip ended on ${widget.booking.returnDate.toString().split(' ')[0]}',
              style: AppTypography.textTheme.bodyMedium,
            ),
            const SizedBox(height: AppSpacing.xxxl),
            Text('How was your trip?',
                style: AppTypography.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.md),
            RatingBar.builder(
              initialRating: 0,
              minRating: 1,
              direction: Axis.horizontal,
              allowHalfRating: false, // Backend requires integer 1-5
              itemCount: 5,
              itemPadding: const EdgeInsets.symmetric(horizontal: 4.0),
              itemBuilder: (context, _) =>
                  const Icon(Icons.star, color: AppColors.warning),
              onRatingUpdate: (rating) {
                setState(() => _rating = rating);
              },
            ),
            const SizedBox(height: AppSpacing.xxxl),
            Align(
              alignment: Alignment.centerLeft,
              child: Text('Share your experience',
                  style: AppTypography.textTheme.titleMedium),
            ),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              controller: _reviewController,
              maxLines: 5,
              decoration: InputDecoration(
                hintText:
                    'What did you like about this vehicle? Would you recommend it?',
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
}
