import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../models/booking_draft.dart';
import '../../models/branch_model.dart';
import '../../models/vehicle_model.dart';
import '../../data/repositories/branch_repository.dart';
import '../../widgets/buttons/app_buttons.dart';
import 'branch_map_picker_screen.dart';
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

  Branch? _pickupBranch;
  bool _returnAtPickup = true;
  Branch? _returnBranch;

  /// Display text sent to the backend (`pickup_location`/`return_location`
  /// are plain strings in `StoreBookingRequest`). Composed only from real
  /// branch data.
  static String _locationText(Branch branch) {
    final line = branch.locationLine;
    return line.isEmpty ? branch.name : '${branch.name} · $line';
  }

  @override
  void initState() {
    super.initState();
    _prefillHomeBranch();
  }

  /// Preselects the car's home branch (the branch that actually holds the
  /// vehicle). A name-only placeholder is used immediately so the UI is
  /// never blank; the API result refines it with full details.
  Future<void> _prefillHomeBranch() async {
    final homeBranch = _homeBranchFromVehicle();
    if (!mounted) return;
    setState(() {
      _pickupBranch = homeBranch;
      _returnBranch = null; // follows pickup while the toggle is on
    });

    final id = int.tryParse(widget.vehicle.branchId);
    if (id == null || id == 0) return;

    final res = await BranchRepository.instance
        .getBranchTyped(widget.vehicle.branchId);
    if (!mounted) return;

    if (res.success && res.data != null) {
      setState(() => _pickupBranch = res.data);
    }
    // On failure the name-only placeholder stays usable; the customer can
    // also pick another branch from the map.
  }

  Branch? _homeBranchFromVehicle() {
    final id = int.tryParse(widget.vehicle.branchId) ?? 0;
    final name = widget.vehicle.branchName;
    if (id == 0 || name.isEmpty) return null;
    return Branch(
      id: id,
      name: name,
      address: '',
      city: '',
      phone: '',
      email: '',
      status: 'active',
    );
  }

  Future<void> _pickBranch({required bool isPickup}) async {
    final current = isPickup ? _pickupBranch : _returnBranch ?? _pickupBranch;
    final args = BranchPickerArgs(
      isPickup: isPickup,
      initialSelection: current,
    );

    final result = await context.push<Branch>(
      AppRoutes.branchPicker,
      extra: args,
    );
    if (!mounted || result == null) return;

    setState(() {
      if (isPickup) {
        _pickupBranch = result;
        if (_returnAtPickup) _returnBranch = null;
      } else {
        _returnBranch = result;
        if (_returnBranch!.id == _pickupBranch?.id) {
          _returnAtPickup = true;
          _returnBranch = null;
        }
      }
    });
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

  bool get _canProceed =>
      _startDate != null && _endDate != null && _pickupBranch != null;

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
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      'Select where you would like to collect and return '
                      'the vehicle.',
                      style: AppTypography.textTheme.bodySmall?.copyWith(
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: AppSpacing.md),
                    _BranchSelectionCard(
                      label: 'Pickup location',
                      branch: _pickupBranch,
                      onTap: () => _pickBranch(isPickup: true),
                    ),
                    const SizedBox(height: AppSpacing.sm),
                    _ReturnAtPickupToggle(
                      value: _returnAtPickup,
                      onChanged: (v) => setState(() {
                        _returnAtPickup = v;
                        if (v) _returnBranch = null;
                      }),
                    ),
                    if (!_returnAtPickup) ...[
                      const SizedBox(height: AppSpacing.sm),
                      _BranchSelectionCard(
                        label: 'Return location',
                        branch: _returnBranch,
                        fallbackBranch: _pickupBranch,
                        onTap: () => _pickBranch(isPickup: false),
                      ),
                    ],
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
        onPressed: !_canProceed
            ? null
            : () {
                final draft = BookingDraft(
                  vehicle: widget.vehicle,
                  pickupDate: _startDate!,
                  returnDate: _endDate!,
                  pickupLocation: _locationText(_pickupBranch!),
                  returnLocation: _returnAtPickup
                      ? _locationText(_pickupBranch!)
                      : _locationText(_returnBranch!),
                );
                context.push(AppRoutes.bookingSummary, extra: draft);
              },
      ),
    );
  }
}

// ─── Location section widgets ────────────────────────────────────────────

/// Tappable card summarizing the chosen branch ("📍 Bole Branch / Bole
/// Road… / Tap to change"). Shows an empty state when nothing is selected.
class _BranchSelectionCard extends StatelessWidget {
  final String label;
  final Branch? branch;

  /// Shown as a hint of the effective selection when [branch] is null
  /// (e.g. return defaults to the pickup branch).
  final Branch? fallbackBranch;
  final VoidCallback onTap;

  const _BranchSelectionCard({
    required this.label,
    required this.branch,
    required this.onTap,
    this.fallbackBranch,
  });

  @override
  Widget build(BuildContext context) {
    final effective = branch ?? fallbackBranch;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
              ),
              child: const Icon(
                LucideIcons.mapPin,
                size: 20,
                color: AppColors.primary,
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: AppTypography.textTheme.labelLarge?.copyWith(
                      color: AppColors.textTertiary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  if (effective != null) ...[
                    Text(
                      effective.name,
                      style: AppTypography.textTheme.titleMedium,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (effective.locationLine.isNotEmpty)
                      Text(
                        effective.locationLine,
                        style: AppTypography.textTheme.bodySmall,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                  ] else
                    Text(
                      'Tap to select a branch',
                      style: AppTypography.textTheme.titleMedium?.copyWith(
                        color: AppColors.textTertiary,
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            Text(
              'Change',
              style: AppTypography.textTheme.labelLarge,
            ),
            const SizedBox(width: 2),
            const Icon(
              LucideIcons.chevronRight,
              size: 18,
              color: AppColors.primary,
            ),
          ],
        ),
      ),
    );
  }
}

class _ReturnAtPickupToggle extends StatelessWidget {
  final bool value;
  final ValueChanged<bool> onChanged;

  const _ReturnAtPickupToggle({
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => onChanged(!value),
      borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm),
        decoration: BoxDecoration(
          color: AppColors.backgroundSecondary,
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            SizedBox(
              width: 40,
              height: 40,
              child: Checkbox(
                value: value,
                activeColor: AppColors.primary,
                materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                onChanged: (v) => onChanged(v ?? false),
              ),
            ),
            Expanded(
              child: Text(
                'Return at pickup location',
                style: AppTypography.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
