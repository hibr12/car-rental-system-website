import 'package:flutter/material.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';

class PaginatedListBuilder<T> extends StatefulWidget {
  final List<T> items;
  final Widget Function(BuildContext context, int index, T item) itemBuilder;
  final Future<void> Function() onLoadMore;
  final Future<void> Function()? onRefresh;
  final bool hasMore;
  final bool isLoadingMore;
  final Widget? emptyWidget;
  final EdgeInsetsGeometry padding;
  final Axis scrollDirection;

  const PaginatedListBuilder({
    super.key,
    required this.items,
    required this.itemBuilder,
    required this.onLoadMore,
    this.onRefresh,
    this.hasMore = false,
    this.isLoadingMore = false,
    this.emptyWidget,
    this.padding = const EdgeInsets.all(AppSpacing.pagePadding),
    this.scrollDirection = Axis.vertical,
  });

  @override
  State<PaginatedListBuilder<T>> createState() =>
      _PaginatedListBuilderState<T>();
}

class _PaginatedListBuilderState<T> extends State<PaginatedListBuilder<T>> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      if (widget.hasMore && !widget.isLoadingMore) {
        widget.onLoadMore();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.items.isEmpty) {
      return widget.emptyWidget ?? const Center(child: Text('No items found'));
    }

    Widget listView = ListView.builder(
      controller: _scrollController,
      padding: widget.padding,
      scrollDirection: widget.scrollDirection,
      itemCount: widget.items.length + (widget.hasMore ? 1 : 0),
      itemBuilder: (context, index) {
        if (index == widget.items.length) {
          return const Padding(
            padding: EdgeInsets.all(AppSpacing.md),
            child: Center(
              child: CircularProgressIndicator(color: AppColors.primary),
            ),
          );
        }
        return widget.itemBuilder(context, index, widget.items[index]);
      },
    );

    if (widget.onRefresh != null && widget.scrollDirection == Axis.vertical) {
      return RefreshIndicator(
        color: AppColors.primary,
        onRefresh: widget.onRefresh!,
        child: listView,
      );
    }

    return listView;
  }
}
