import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart' as latlong;
import 'package:lucide_icons/lucide_icons.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../data/repositories/branch_repository.dart';
import '../../models/branch_model.dart';
import '../../widgets/buttons/app_buttons.dart';
import '../../widgets/states/empty_state_widget.dart';
import '../../widgets/states/error_state_widget.dart';

/// Parameters passed to [BranchMapPickerScreen] through the router extra.
class BranchPickerArgs {
  /// Drives the screen title ("Choose a pickup/return location").
  final bool isPickup;

  /// Branch currently selected before opening the picker, if any.
  final Branch? initialSelection;

  const BranchPickerArgs({required this.isPickup, this.initialSelection});
}

/// Map-based branch picker.
///
/// Customers always select a real Apex Rentals branch — never arbitrary
/// GPS coordinates. Markers come from the live branch API; only branches
/// that actually carry coordinates appear on the map, while every branch
/// remains reachable through the list view.
class BranchMapPickerScreen extends StatefulWidget {
  final BranchPickerArgs args;

  const BranchMapPickerScreen({super.key, required this.args});

  @override
  State<BranchMapPickerScreen> createState() => _BranchMapPickerScreenState();
}

class _BranchMapPickerScreenState extends State<BranchMapPickerScreen> {
  final MapController _mapController = MapController();

  List<Branch>? _branches;
  String? _error;
  Branch? _selected;

  // Tile-layer failure is non-fatal: branch data still renders as pins on
  // a blank canvas and the customer can retry or pick from the list.
  int _tileGeneration = 0;
  bool _tileLoadFailed = false;

  /// True once [MapOptions.onMapReady] fires; guards camera commands.
  bool _mapReady = false;

  static const _osmTileUrl = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

  bool get _isLoading => _branches == null && _error == null;

  List<Branch> get _mappableBranches =>
      (_branches ?? []).where((b) => b.hasLocation).toList();

  String get _title => widget.args.isPickup
      ? 'Choose pickup location'
      : 'Choose return location';

  @override
  void initState() {
    super.initState();
    _selected = widget.args.initialSelection;
    _loadBranches();
  }

  Future<void> _loadBranches() async {
    setState(() => _error = null);

    final res = await BranchRepository.instance.getActiveBranches();
    if (!mounted) return;

    if (!res.success) {
      setState(() {
        _error = res.error?.friendlyMessage ??
            'Unable to load rental locations.';
        _branches = null;
      });
      return;
    }

    final active = res.data!.where((b) => b.isActive).toList()
      ..sort((a, b) => a.name.compareTo(b.name));

    setState(() => _branches = active);

    // Keep an initially-selected branch visible once the map appears.
    if (_selected != null &&
        active.any((b) => b.id == _selected!.id && b.hasLocation)) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _flyTo(_selected!));
    }
  }

  void _flyTo(Branch branch) {
    if (!_mapReady || !mounted || !branch.hasLocation) return;
    final zoom = _mapController.camera.zoom;
    _mapController.move(
      latlong.LatLng(branch.latitude!, branch.longitude!),
      zoom < 14 ? 15 : zoom,
    );
  }

  void _fitAllBranches() {
    final points = _mappableBranches
        .map((b) => latlong.LatLng(b.latitude!, b.longitude!))
        .toList();
    if (points.isEmpty || !_mapReady || !mounted) return;
    if (points.length == 1) {
      _mapController.move(points.first, 15);
    } else {
      _mapController.fitCamera(
        CameraFit.coordinates(
          coordinates: points,
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.lg,
            AppSpacing.xxl + 56,
            AppSpacing.lg,
            220,
          ),
        ),
      );
    }
  }

  void _select(Branch branch, {bool flyToIt = false}) {
    setState(() => _selected = branch);
    if (flyToIt) _flyTo(branch);
  }

  void _confirm() {
    if (_selected == null) return;
    context.pop(_selected);
  }

  Future<void> _openDirections(Branch branch) async {
    if (!branch.hasLocation) return;
    final lat = branch.latitude!;
    final lng = branch.longitude!;
    final name = Uri.encodeComponent(branch.name);

    // geo: lets Android route to whichever maps app the customer uses.
    final geoUri =
        Uri.parse('geo:$lat,$lng?q=$lat,$lng($name)');
    final webUri =
        Uri.parse('https://www.google.com/maps/search/?api=1&query=$lat,$lng');
    try {
      await launchUrl(geoUri, mode: LaunchMode.externalApplication);
    } catch (_) {
      try {
        await launchUrl(webUri, mode: LaunchMode.externalApplication);
      } catch (_) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('No maps app is available on this device.'),
            ),
          );
        }
      }
    }
  }

  void _showBranchList() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) => _BranchListSheet(
        branches: _branches ?? [],
        selectedId: _selected?.id,
        onSelect: (b) {
          Navigator.of(sheetContext).pop();
          _select(b, flyToIt: true);
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: Text(_title)),
      body: SafeArea(
        child: _buildBody(),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: AppSpacing.lg),
            Text('Finding rental locations…'),
          ],
        ),
      );
    }

    if (_error != null) {
      return ErrorStateWidget(
        title: 'Unable to load rental locations',
        message: 'Please check your connection and try again.',
        onRetry: _loadBranches,
      );
    }

    if (_branches!.isEmpty) {
      return const EmptyStateWidget(
        icon: LucideIcons.mapPin,
        title: 'No locations available',
        message: 'No rental locations are currently available.',
      );
    }

    return Stack(
      children: [
        FlutterMap(
          mapController: _mapController,
          options: MapOptions(
            initialCenter: _initialCenter(),
            initialZoom: 12,
            initialCameraFit: _mappableBranches.length > 1
                ? CameraFit.coordinates(
                    coordinates: _mappableBranches
                        .map((b) =>
                            latlong.LatLng(b.latitude!, b.longitude!))
                        .toList(),
                    padding: const EdgeInsets.fromLTRB(
                      AppSpacing.lg,
                      AppSpacing.xxl + 56,
                      AppSpacing.lg,
                      180,
                    ),
                  )
                : null,
            onTap: (_, __) {}, // let gestures pass; keeps default panning
            onMapReady: () => _mapReady = true,
          ),
          children: [
            TileLayer(
              key: ValueKey(_tileGeneration),
              urlTemplate: _osmTileUrl,
              userAgentPackageName: 'com.apexrentals.mobile',
              errorTileCallback: (_, __, ___) {
                if (!_tileLoadFailed && mounted) {
                  setState(() => _tileLoadFailed = true);
                }
              },
            ),
            MarkerLayer(
              markers: _mappableBranches.map((b) {
                final isSelected = _selected?.id == b.id;
                return Marker(
                  point: latlong.LatLng(b.latitude!, b.longitude!),
                  width: 48,
                  height: isSelected ? 76 : 52,
                  alignment: Alignment.topCenter,
                  child: _BranchMarker(
                    branch: b,
                    selected: isSelected,
                    onTap: () => _select(b, flyToIt: true),
                  ),
                );
              }).toList(),
            ),
            // OSM tile usage requires visible attribution.
            Positioned(
              left: AppSpacing.sm,
              bottom: 2,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                color: AppColors.surface.withOpacity(0.75),
                child: const Text(
                  '© OpenStreetMap contributors',
                  style: TextStyle(fontSize: 9, color: AppColors.textSecondary),
                ),
              ),
            ),
          ],
        ),

        // Tile-failure notice (non-blocking).
        if (_tileLoadFailed)
          Positioned(
            top: AppSpacing.md,
            left: AppSpacing.pagePadding,
            right: AppSpacing.pagePadding,
            child: _TileErrorBar(
              onRetry: () => setState(() {
                _tileGeneration++;
                _tileLoadFailed = false;
              }),
            ),
          ),

        // Map controls: fit-all + open list.
        Positioned(
          right: AppSpacing.md,
          top: AppSpacing.xxl + 40,
          child: Column(
            children: [
              _MapControlButton(
                icon: LucideIcons.maximize,
                tooltip: 'Show all branches',
                onPressed: _fitAllBranches,
              ),
              const SizedBox(height: AppSpacing.sm),
              _MapControlButton(
                icon: LucideIcons.list,
                tooltip: 'View as list',
                onPressed: _showBranchList,
              ),
            ],
          ),
        ),

        // Selection card.
        Positioned(
          left: 0,
          right: 0,
          bottom: 0,
          child: AnimatedSwitcher(
            duration: const Duration(milliseconds: 250),
            switchInCurve: Curves.easeOutCubic,
            transitionBuilder: (child, animation) => SlideTransition(
              position: Tween<Offset>(
                begin: const Offset(0, 0.25),
                end: Offset.zero,
              ).animate(animation),
              child: FadeTransition(opacity: animation, child: child),
            ),
            child: _selected != null
                ? _BranchCard(
                    key: ValueKey('card-${_selected!.id}'),
                    branch: _selected!,
                    onSelect: _confirm,
                    onDirections: () => _openDirections(_selected!),
                  )
                : const _HintBar(
                    key: ValueKey('hint'),
                  ),
          ),
        ),
      ],
    );
  }

  latlong.LatLng _initialCenter() {
    if (_mappableBranches.isNotEmpty) {
      return latlong.LatLng(
        _mappableBranches.first.latitude!,
        _mappableBranches.first.longitude!,
      );
    }
    // Neutral Addis Ababa center; only used when no branch has coordinates.
    return const latlong.LatLng(9.03, 38.74);
  }
}

// ─── Markers ─────────────────────────────────────────────────────────────

class _BranchMarker extends StatelessWidget {
  final Branch branch;
  final bool selected;
  final VoidCallback onTap;

  const _BranchMarker({
    required this.branch,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          AnimatedScale(
            scale: selected ? 1.15 : 1.0,
            duration: const Duration(milliseconds: 200),
            curve: Curves.easeOutCubic,
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: selected ? AppColors.primary : AppColors.surface,
                shape: BoxShape.circle,
                border: Border.all(
                  color: AppColors.primary,
                  width: selected ? 3 : 2,
                ),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.textPrimary.withOpacity(0.25),
                    blurRadius: selected ? 10 : 5,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Icon(
                LucideIcons.mapPin,
                size: 20,
                color: selected ? AppColors.surface : AppColors.primary,
              ),
            ),
          ),
          if (selected) ...[
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
              ),
              child: Text(
                branch.name,
                style: AppTypography.textTheme.labelSmall?.copyWith(
                  color: AppColors.surface,
                  fontWeight: FontWeight.w700,
                  fontSize: 10,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ─── Overlays ────────────────────────────────────────────────────────────

class _BranchCard extends StatelessWidget {
  final Branch branch;
  final VoidCallback onSelect;
  final VoidCallback onDirections;

  const _BranchCard({
    super.key,
    required this.branch,
    required this.onSelect,
    required this.onDirections,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.all(AppSpacing.pagePadding),
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: AppColors.textPrimary.withOpacity(0.12),
            blurRadius: 18,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius:
                        BorderRadius.circular(AppSpacing.radiusMd),
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
                        branch.name,
                        style: AppTypography.textTheme.titleLarge,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      if (branch.locationLine.isNotEmpty)
                        Text(
                          branch.locationLine,
                          style:
                              AppTypography.textTheme.bodySmall?.copyWith(
                            color: AppColors.textSecondary,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      if (branch.hoursLine != null) ...[
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            const Icon(
                              LucideIcons.clock,
                              size: 12,
                              color: AppColors.textTertiary,
                            ),
                            const SizedBox(width: AppSpacing.xs),
                            Flexible(
                              child: Text(
                                branch.hoursLine!,
                                style: AppTypography.textTheme.bodySmall,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                if (branch.hasLocation) ...[
                  AppIconButton(
                    icon: LucideIcons.navigation,
                    onPressed: onDirections,
                    iconColor: AppColors.primary,
                  ),
                  const SizedBox(width: AppSpacing.md),
                ],
                Expanded(
                  child: PrimaryButton(
                    text: 'Select this branch',
                    onPressed: onSelect,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _HintBar extends StatelessWidget {
  const _HintBar({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.all(AppSpacing.pagePadding),
      padding: const EdgeInsets.symmetric(
        horizontal: AppSpacing.md,
        vertical: AppSpacing.md,
      ),
      decoration: BoxDecoration(
        color: AppColors.textPrimary.withOpacity(0.85),
        borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(
            LucideIcons.mapPin,
            size: 16,
            color: AppColors.surface,
          ),
          const SizedBox(width: AppSpacing.sm),
          Flexible(
            child: Text(
              'Tap a branch on the map to see details',
              style: AppTypography.textTheme.bodyMedium?.copyWith(
                color: AppColors.surface,
              ),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}

class _TileErrorBar extends StatelessWidget {
  final VoidCallback onRetry;

  const _TileErrorBar({required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppSpacing.md,
        vertical: AppSpacing.sm,
      ),
      decoration: BoxDecoration(
        color: AppColors.warning.withOpacity(0.12),
        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
        border: Border.all(color: AppColors.warning.withOpacity(0.35)),
      ),
      child: Row(
        children: [
          const Icon(LucideIcons.wifiOff,
              size: 16, color: AppColors.warning),
          const SizedBox(width: AppSpacing.sm),
          const Expanded(
            child: Text(
              'Map tiles could not be loaded.',
              style: TextStyle(fontSize: 12),
            ),
          ),
          GestureDetector(
            onTap: onRetry,
            behavior: HitTestBehavior.opaque,
            child: Padding(
              padding: const EdgeInsets.all(4),
              child: Text(
                'Retry',
                style: AppTypography.textTheme.labelMedium?.copyWith(
                  color: AppColors.primary,
                  fontWeight: FontWeight.w700,
                  fontSize: 12,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MapControlButton extends StatelessWidget {
  final IconData icon;
  final String tooltip;
  final VoidCallback onPressed;

  const _MapControlButton({
    required this.icon,
    required this.tooltip,
    required this.onPressed,
  });

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: Material(
        color: AppColors.surface,
        shape: const CircleBorder(
          side: BorderSide(color: AppColors.border),
        ),
        elevation: 2,
        shadowColor: AppColors.textPrimary.withOpacity(0.2),
        child: InkWell(
          customBorder: const CircleBorder(),
          onTap: onPressed,
          child: SizedBox(
            width: 44,
            height: 44,
            child: Icon(icon, size: 20, color: AppColors.textPrimary),
          ),
        ),
      ),
    );
  }
}

// ─── Branch list sheet ───────────────────────────────────────────────────

class _BranchListSheet extends StatefulWidget {
  final List<Branch> branches;
  final int? selectedId;
  final ValueChanged<Branch> onSelect;

  const _BranchListSheet({
    required this.branches,
    required this.selectedId,
    required this.onSelect,
  });

  @override
  State<_BranchListSheet> createState() => _BranchListSheetState();
}

class _BranchListSheetState extends State<_BranchListSheet> {
  String _query = '';

  @override
  Widget build(BuildContext context) {
    final filtered = widget.branches
        .where((b) =>
            _query.isEmpty ||
            b.name.toLowerCase().contains(_query) ||
            b.city.toLowerCase().contains(_query) ||
            b.address.toLowerCase().contains(_query))
        .toList();

    return SafeArea(
      child: ConstrainedBox(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.sizeOf(context).height * 0.75,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(
                AppSpacing.pagePadding,
                0,
                AppSpacing.pagePadding,
                AppSpacing.md,
              ),
              child: Text(
                'All branches (${widget.branches.length})',
                style: AppTypography.textTheme.headlineMedium,
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.pagePadding,
              ),
              child: TextField(
                onChanged: (v) => setState(() => _query = v.toLowerCase()),
                decoration: const InputDecoration(
                  hintText: 'Search branches…',
                  prefixIcon: Icon(LucideIcons.search, size: 20),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            Flexible(
              child: filtered.isEmpty
                  ? const EmptyStateWidget(
                      icon: LucideIcons.searchX,
                      title: 'No matches',
                      message: 'No branches match your search.',
                    )
                  : ListView.separated(
                      shrinkWrap: true,
                      padding: const EdgeInsets.symmetric(
                        horizontal: AppSpacing.pagePadding,
                        vertical: AppSpacing.sm,
                      ),
                      itemCount: filtered.length,
                      separatorBuilder: (_, __) =>
                          const SizedBox(height: AppSpacing.sm),
                      itemBuilder: (context, index) {
                        final b = filtered[index];
                        final isSelected = widget.selectedId == b.id;
                        return _BranchListTile(
                          branch: b,
                          selected: isSelected,
                          onTap: () => widget.onSelect(b),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BranchListTile extends StatelessWidget {
  final Branch branch;
  final bool selected;
  final VoidCallback onTap;

  const _BranchListTile({
    required this.branch,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: selected ? AppColors.primaryLight : AppColors.surface,
      borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
        child: Container(
          padding: const EdgeInsets.all(AppSpacing.md),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
            border: Border.all(
              color: selected ? AppColors.primary : AppColors.border,
            ),
          ),
          child: Row(
            children: [
              Icon(
                LucideIcons.mapPin,
                size: 20,
                color: selected ? AppColors.primary : AppColors.textTertiary,
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      branch.name,
                      style: AppTypography.textTheme.titleMedium,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (branch.locationLine.isNotEmpty)
                      Text(
                        branch.locationLine,
                        style: AppTypography.textTheme.bodySmall,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                  ],
                ),
              ),
              if (selected)
                const Icon(Icons.check_circle,
                    size: 20, color: AppColors.primary),
            ],
          ),
        ),
      ),
    );
  }
}
