import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../models/booking_draft.dart';
import '../../models/vehicle_model.dart';
import '../../data/repositories/branch_repository.dart';
import '../../widgets/buttons/app_buttons.dart';
import 'components/booking_date_components.dart';

class BookingDateScreen extends StatefulWidget {
  final Vehicle vehicle;

  const BookingDateScreen({super.key, required this.vehicle});

  @override
  State<BookingDateScreen> createState() => _BookingDateScreenState();
}

class _BookingDateScreenState extends State<BookingDateScreen> {
  DateTime? _startDate = DateTime.now().add(const Duration(days: 1));
  DateTime? _endDate = DateTime.now().add(const Duration(days: 3));

  final _formKey = GlobalKey<FormState>();
  final _pickupController = TextEditingController();
  final _returnController = TextEditingController();
  bool _locationLoaded = false;

  @override
  void initState() {
    super.initState();
    // Pre-fill with the car's home branch (the branch that actually holds
    // the vehicle) once its full address is fetched; the user can still edit.
    _prefillLocations();
  }

  Future<void> _prefillLocations() async {
    final fallback = widget.vehicle.branchName.isNotEmpty
        ? widget.vehicle.branchName
        : widget.vehicle.location;
    if (!mounted) return;
    setState(() {
      _pickupController.text = fallback;
      _returnController.text = fallback;
    });

    final bid = int.tryParse(widget.vehicle.branchId);
    if (bid == null || bid == 0) return;

    final res = await BranchRepository.instance.getBranchById(widget.vehicle.branchId);
    if (!mounted || !res.success || res.data == null || _locationLoaded) return;
    final b = res.data!;
    final address = b['address']?.toString() ?? '';
    final city = b['city']?.toString() ?? '';
    final name = b['name']?.toString() ?? '';
    final parts = [
      name,
      [address, city].where((s) => s.trim().isNotEmpty).join(', '),
    ].where((s) => s.trim().isNotEmpty).join(' · ');
    if (parts.isNotEmpty) {
      setState(() {
        _pickupController.text = parts;
        _returnController.text = parts;
        _locationLoaded = true;
      });
    }
  }

  @override
  void dispose() {
    _pickupController.dispose();
    _returnController.dispose();
    super.dispose();
  }

  Future<void> _selectDateRange() async {
    final initialDateRange = DateTimeRange(
      start: _startDate ?? DateTime.now().add(const Duration(days: 1)),
      end: _endDate ?? DateTime.now().add(const Duration(days: 3)),
    );

    final newDateRange = await showDateRangePicker(
      context: context,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      initialDateRange: initialDateRange,
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: AppColors.primary,
              onPrimary: AppColors.surface,
              onSurface: AppColors.textPrimary,
            ),
          ),
          child: child!,
        );
      },
    );

    if (newDateRange != null) {
      setState(() {
        _startDate = newDateRange.start;
        _endDate = newDateRange.end;
      });
    }
  }

  bool get _canProceed => _startDate != null && _endDate != null;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Select Dates'),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(AppSpacing.pagePadding),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    VehicleSummaryCard(vehicle: widget.vehicle),
                    const SizedBox(height: AppSpacing.xxl),
                    Text('Rental Period',
                        style: AppTypography.textTheme.headlineMedium),
                    const SizedBox(height: AppSpacing.md),
                    DateSelectorCard(
                      startDate: _startDate,
                      endDate: _endDate,
                      onTap: _selectDateRange,
                    ),
                    const SizedBox(height: AppSpacing.md),
                    // Pickup/return times are arranged with the branch — the
                    // backend books whole days only, so we don't pretend
                    // otherwise.
                    Container(
                      padding: const EdgeInsets.all(AppSpacing.md),
                      decoration: BoxDecoration(
                        color: AppColors.backgroundSecondary,
                        borderRadius:
                            BorderRadius.circular(AppSpacing.radiusMd),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          const Icon(LucideIcons.info,
                              size: 18, color: AppColors.textTertiary),
                          const SizedBox(width: AppSpacing.sm),
                          Expanded(
                            child: Text(
                              'Exact pickup and return times are confirmed '
                              'with the branch after your booking is approved.',
                              style: AppTypography.textTheme.bodySmall
                                  ?.copyWith(color: AppColors.textSecondary),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: AppSpacing.xxl),
                    Text('Pickup & Return Location',
                        style: AppTypography.textTheme.headlineMedium),
                    const SizedBox(height: AppSpacing.md),
                    Form(
                      key: _formKey,
                      child: Column(
                        children: [
                          TextFormField(
                            controller: _pickupController,
                            textCapitalization: TextCapitalization.words,
                            decoration: const InputDecoration(
                              labelText: 'Pickup Location',
                              prefixIcon:
                                  Icon(LucideIcons.mapPin, size: 20),
                            ),
                            validator: (v) =>
                                v == null || v.trim().isEmpty
                                    ? 'Pickup location is required'
                                    : null,
                          ),
                          const SizedBox(height: AppSpacing.md),
                          TextFormField(
                            controller: _returnController,
                            textCapitalization: TextCapitalization.words,
                            decoration: const InputDecoration(
                              labelText: 'Return Location',
                              prefixIcon: Icon(
                                  LucideIcons.mapPin,
                                  size: 20),
                            ),
                            validator: (v) =>
                                v == null || v.trim().isEmpty
                                    ? 'Return location is required'
                                    : null,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: AppSpacing.md),
                    Container(
                      padding: const EdgeInsets.all(AppSpacing.md),
                      decoration: BoxDecoration(
                        color: AppColors.backgroundSecondary,
                        borderRadius:
                            BorderRadius.circular(AppSpacing.radiusMd),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          const Icon(LucideIcons.info,
                              size: 18, color: AppColors.textTertiary),
                          const SizedBox(width: AppSpacing.sm),
                          Expanded(
                            child: Text(
                              'The car is kept at its home branch — you can '
                              'fine-tune the address text if needed.',
                              style: AppTypography.textTheme.bodySmall
                                  ?.copyWith(color: AppColors.textSecondary),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            _buildBottomBar(),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomBar() {
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
        text: 'Continue to Summary',
        onPressed: _canProceed
            ? () {
                if (!(_formKey.currentState?.validate() ?? false)) return;
                final draft = BookingDraft(
                  vehicle: widget.vehicle,
                  pickupDate: _startDate!,
                  returnDate: _endDate!,
                  pickupLocation: _pickupController.text.trim(),
                  returnLocation: _returnController.text.trim(),
                );
                context.push(AppRoutes.bookingSummary, extra: draft);
              }
            : null,
      ),
    );
  }
}
