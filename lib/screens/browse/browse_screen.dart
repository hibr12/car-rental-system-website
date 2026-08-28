import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';

import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../data/repositories/vehicle_repository.dart';
import '../../models/category_model.dart';
import '../../models/vehicle_model.dart';
import '../../core/routes/app_routes.dart';
import '../../widgets/bottom_sheets/filter_bottom_sheet.dart';
import '../../widgets/cards/vehicle_card.dart';
import '../../widgets/cards/vehicle_card_skeleton.dart';
import '../../widgets/inputs/app_text_field.dart';
import '../../widgets/states/empty_state_widget.dart';
import '../../widgets/states/error_state_widget.dart';

class BrowseScreen extends StatefulWidget {
  /// Optional initial category slug passed from the home screen chips.
  /// A non-empty string pre-selects that category; null/empty means "All".
  final String? initialCategorySlug;

  /// Optional backend branch filter (from the branch detail screen).
  final String? initialBranchId;

  const BrowseScreen({super.key, this.initialCategorySlug, this.initialBranchId});

  @override
  State<BrowseScreen> createState() => _BrowseScreenState();
}

class _BrowseScreenState extends State<BrowseScreen> {
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final VehicleRepository _repo = VehicleRepository.instance;

  // ── Category chips ────────────────────────────────────────────────
  List<VehicleCategory> _categories = [];
  late String? _selectedCategorySlug; // null = "All"
  late final String? _branchId; // null = all branches

  // ── Vehicle list state ────────────────────────────────────────────
  List<Vehicle> _vehicles = [];
  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _error;
  int _currentPage = 1;
  bool _hasMore = true;

  // ── Filters ────────────────────────────────────────────────────────
  VehicleFilter _filter = VehicleFilter.empty;

  // ── Debounced search term ──────────────────────────────────────────
  String _activeSearch = '';
  Timer? _debounce;

  bool _isGrid = false;

  @override
  void initState() {
    super.initState();
    _selectedCategorySlug = (widget.initialCategorySlug != null &&
            widget.initialCategorySlug!.isNotEmpty)
        ? widget.initialCategorySlug
        : null;
    _branchId = (widget.initialBranchId != null &&
            widget.initialBranchId!.isNotEmpty)
        ? widget.initialBranchId
        : null;
    _scrollController.addListener(_onScroll);
    _loadCategories();
    _loadFirstPage();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  // ─── Categories ────────────────────────────────────────────────────

  Future<void> _loadCategories() async {
    final res = await _repo.getCategories();
    if (mounted && res.success && res.data != null) {
      setState(() => _categories = res.data!);
    }
    // Category failure is non-fatal — chips just stay "All".
  }

  // ─── Vehicle fetching ──────────────────────────────────────────────

  Future<void> _loadFirstPage() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    _currentPage = 1;
    _hasMore = true;

    final res = await _repo.getVehicles(
      page: 1,
      search: _activeSearch,
      categorySlug: _selectedCategorySlug,
      branchId: _branchId,
      filter: _filter,
    );

    if (!mounted) return;
    setState(() {
      _isLoading = false;
      if (res.success && res.data != null) {
        final page = res.data!;
        _vehicles = page.data;
        _hasMore = page.hasNextPage;
      } else {
        _error = res.error?.friendlyMessage ?? 'Failed to load vehicles.';
      }
    });
  }

  Future<void> _loadMore() async {
    if (_isLoadingMore || !_hasMore) return;
    setState(() => _isLoadingMore = true);

    final nextPage = _currentPage + 1;
    final res = await _repo.getVehicles(
      page: nextPage,
      search: _activeSearch,
      categorySlug: _selectedCategorySlug,
      branchId: _branchId,
      filter: _filter,
    );

    if (!mounted) return;
    setState(() {
      _isLoadingMore = false;
      if (res.success && res.data != null) {
        final page = res.data!;
        _vehicles.addAll(page.data);
        _currentPage = nextPage;
        _hasMore = page.hasNextPage;
      }
      // Silent failure on pagination — keep existing list visible.
    });
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      _loadMore();
    }
  }

  // ─── Search (debounced) ────────────────────────────────────────────

  void _onSearchChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      final term = value.trim();
      if (term != _activeSearch) {
        _activeSearch = term;
        _loadFirstPage();
      }
    });
  }

  // ─── Category & filter changes ─────────────────────────────────────

  void _applyCategory(String? slug) {
    if (_selectedCategorySlug == slug) return;
    setState(() => _selectedCategorySlug = slug);
    _loadFirstPage();
  }

  Future<void> _openFilterSheet() async {
    final result = await showModalBottomSheet<VehicleFilter>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FilterBottomSheet(current: _filter),
    );
    if (result != null && result != _filter) {
      setState(() => _filter = result);
      _loadFirstPage();
    }
  }

  // ─── UI ────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Find a Car'),
        actions: [
          IconButton(
            icon: Icon(_isGrid ? LucideIcons.list : LucideIcons.layoutGrid),
            onPressed: () => setState(() => _isGrid = !_isGrid),
          ),
        ],
      ),
      body: Column(
        children: [
          // Search bar + filter button
          Padding(
            padding: const EdgeInsets.all(AppSpacing.pagePadding),
            child: SearchBarWidget(
              controller: _searchController,
              onChanged: _onSearchChanged,
              onFilterTap: _openFilterSheet,
            ),
          ),

          // Category chips
          _buildCategoryChips(),

          const SizedBox(height: AppSpacing.md),

          // Active filter summary
          if (_filter.isActive) _buildActiveFilterChip(),

          // Vehicle list / states
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildCategoryChips() {
    final chips = <Widget>[
      _categoryChip('All', null),
      ..._categories.map((c) => _categoryChip(c.name, c.slug)),
    ];
    return SizedBox(
      height: 44,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
        children: chips,
      ),
    );
  }

  Widget _categoryChip(String label, String? slug) {
    final selected = _selectedCategorySlug == slug;
    return Padding(
      padding: const EdgeInsets.only(right: AppSpacing.sm),
      child: FilterChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => _applyCategory(slug),
        selectedColor: AppColors.primary,
        checkmarkColor: AppColors.surface,
        labelStyle: TextStyle(
          color: selected ? AppColors.surface : AppColors.textPrimary,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusPill),
        ),
        side: BorderSide(
          color: selected ? AppColors.primary : AppColors.border,
        ),
      ),
    );
  }

  Widget _buildActiveFilterChip() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.pagePadding),
      child: Row(
        children: [
          Expanded(
            child: Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.sm,
              children: [
                if (_filter.minPrice != null || _filter.maxPrice != null)
                  _activePill(
                      'Price: ETB ${(_filter.minPrice ?? 0).toInt()}–${(_filter.maxPrice ?? 1000).toInt()}'),
                if (_filter.transmission != null &&
                    _filter.transmission!.toLowerCase() != 'any')
                  _activePill(_filter.transmission!),
                if (_filter.minSeats != null && _filter.minSeats! > 0)
                  _activePill('${_filter.minSeats}+ seats'),
              ],
            ),
          ),
          TextButton.icon(
            onPressed: () {
              setState(() => _filter = VehicleFilter.empty);
              _loadFirstPage();
            },
            icon: const Icon(LucideIcons.x, size: 16),
            label: const Text('Clear'),
          ),
        ],
      ),
    );
  }

  Widget _activePill(String text) {
    return Chip(
      label: Text(text, style: const TextStyle(fontSize: 12)),
      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
      visualDensity: VisualDensity.compact,
      backgroundColor: AppColors.primaryLight,
      labelStyle: const TextStyle(color: AppColors.primary),
    );
  }

  Widget _buildBody() {
    if (_isLoading && _vehicles.isEmpty) {
      // First load — show shimmer placeholders matching both layout modes.
      return _isGrid
          ? GridView.builder(
              padding: const EdgeInsets.all(AppSpacing.pagePadding),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                childAspectRatio: 0.78,
                crossAxisSpacing: AppSpacing.md,
                mainAxisSpacing: AppSpacing.md,
              ),
              itemCount: 6,
              itemBuilder: (_, __) => const VehicleCardSkeleton(),
            )
          : ListView.builder(
              padding: const EdgeInsets.all(AppSpacing.pagePadding),
              itemCount: 5,
              itemBuilder: (_, __) => const Padding(
                padding: EdgeInsets.only(bottom: AppSpacing.md),
                child: VehicleCardSkeleton(isHorizontal: true),
              ),
            );
    }
    if (_error != null) {
      return ErrorStateWidget(
        message: _error!,
        onRetry: _loadFirstPage,
      );
    }
    if (_vehicles.isEmpty) {
      return EmptyStateWidget(
        icon: LucideIcons.car,
        title: 'No vehicles found',
        message: _activeSearch.isEmpty && _selectedCategorySlug == null
            ? 'There are no vehicles available right now. Please check back later.'
            : 'Try adjusting your search or filters.',
        actionText: 'Clear filters',
        onAction: () {
          _searchController.clear();
          setState(() {
            _activeSearch = '';
            _selectedCategorySlug = null;
            _filter = VehicleFilter.empty;
          });
          _loadFirstPage();
        },
      );
    }
    return RefreshIndicator(
      onRefresh: _loadFirstPage,
      child: _isGrid ? _buildGridView() : _buildListView(),
    );
  }

  Widget _buildListView() {
    return ListView.builder(
      controller: _scrollController,
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      itemCount: _vehicles.length + (_hasMore ? 1 : 0),
      itemBuilder: (context, index) {
        if (index == _vehicles.length) {
          return _buildLoadingFooter();
        }
        return VehicleCard(
          vehicle: _vehicles[index],
          isHorizontal: true,
          onTap: () =>
              context.push(AppRoutes.vehicleDetails, extra: _vehicles[index]),
        );
      },
    );
  }

  Widget _buildGridView() {
    return GridView.builder(
      controller: _scrollController,
      padding: const EdgeInsets.all(AppSpacing.pagePadding),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.78,
        crossAxisSpacing: AppSpacing.md,
        mainAxisSpacing: AppSpacing.md,
      ),
      itemCount: _vehicles.length + (_hasMore ? 1 : 0),
      itemBuilder: (context, index) {
        if (index == _vehicles.length) {
          return _buildLoadingFooter();
        }
        return VehicleCard(
          vehicle: _vehicles[index],
          onTap: () =>
              context.push(AppRoutes.vehicleDetails, extra: _vehicles[index]),
        );
      },
    );
  }

  Widget _buildLoadingFooter() {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: AppSpacing.lg),
      child: Center(
        child: SizedBox(
          width: 24,
          height: 24,
          child: CircularProgressIndicator(strokeWidth: 2),
        ),
      ),
    );
  }
}
