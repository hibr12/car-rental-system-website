import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../widgets/states/empty_state_widget.dart';
import '../../widgets/states/error_state_widget.dart';
import '../../data/repositories/notification_repository.dart';
import '../../models/notification_model.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  bool _isLoading = true;
  String? _error;
  List<AppNotification> _notifications = [];
  int _currentPage = 1;
  bool _hasMore = true;
  bool _isLoadingMore = false;
  int _unreadCount = 0;
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200 &&
        !_isLoadingMore &&
        _hasMore &&
        !_isLoading) {
      _loadMore();
    }
  }

  Future<void> _fetchNotifications() async {
    setState(() {
      _isLoading = true;
      _error = null;
      _currentPage = 1;
    });

    final res = await NotificationRepository.instance.getNotifications(page: _currentPage);

    if (mounted) {
      setState(() {
        _isLoading = false;
        if (res.success) {
          _notifications = res.data?.data ?? [];
          _hasMore = _currentPage < (res.data?.lastPage ?? 1);
          _unreadCount = (res.data?.meta['unread_count'] as num?)?.toInt() ??
              _notifications.where((n) => !n.isRead).length;
        } else {
          _error = res.error?.friendlyMessage ?? 'Failed to load notifications';
        }
      });
    }
  }

  Future<void> _loadMore() async {
    setState(() => _isLoadingMore = true);

    _currentPage++;
    final res = await NotificationRepository.instance.getNotifications(page: _currentPage);

    if (mounted) {
      setState(() {
        _isLoadingMore = false;
        if (res.success) {
          _notifications.addAll(res.data?.data ?? []);
          _hasMore = _currentPage < (res.data?.lastPage ?? 1);
        }
      });
    }
  }

  Future<void> _markAllAsRead() async {
    final res = await NotificationRepository.instance.markAllAsRead();
    if (res.success && mounted) {
      setState(() {
        _notifications = _notifications
            .map((n) => AppNotification(
                  id: n.id,
                  title: n.title,
                  message: n.message,
                  semanticType: n.semanticType,
                  readAt: n.readAt ?? DateTime.now(),
                  data: n.data,
                  createdAt: n.createdAt,
                  bookingId: n.bookingId,
                  paymentId: n.paymentId,
                  vehicleId: n.vehicleId,
                ))
            .toList();
        _unreadCount = 0;
      });
    }
  }

  Future<void> _markAsRead(AppNotification notification, int index) async {
    if (notification.isRead) return;

    // Optimistic update
    setState(() {
      _notifications[index] = AppNotification(
        id: notification.id,
        title: notification.title,
        message: notification.message,
        semanticType: notification.semanticType,
        readAt: DateTime.now(),
        data: notification.data,
        createdAt: notification.createdAt,
        bookingId: notification.bookingId,
        paymentId: notification.paymentId,
        vehicleId: notification.vehicleId,
      );
      if (_unreadCount > 0) _unreadCount--;
    });

    await NotificationRepository.instance.markAsRead(notification.id.toString());
  }

  Future<void> _deleteNotification(AppNotification notification, int index) async {
    // Optimistic remove
    final removed = _notifications.removeAt(index);
    setState(() {});

    final res = await NotificationRepository.instance.deleteNotification(notification.id.toString());
    if (!res.success && mounted) {
      setState(() {
        _notifications.insert(index, removed);
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to delete notification')),
      );
    }
  }

  String _timeAgo(DateTime date) {
    final diff = DateTime.now().difference(date);
    if (diff.inDays > 7) {
      return "${date.month}/${date.day}/${date.year}";
    } else if (diff.inDays > 0) {
      return "${diff.inDays}d ago";
    } else if (diff.inHours > 0) {
      return "${diff.inHours}h ago";
    } else if (diff.inMinutes > 0) {
      return "${diff.inMinutes}m ago";
    } else {
      return "Just now";
    }
  }

  IconData _getIconForType(String type) {
    switch (type.toLowerCase()) {
      case 'booking_confirmed':
      case 'booking_created':
      case 'booking_cancelled':
      case 'booking_completed':
      case 'booking_branch_approved':
      case 'booking_admin_approved':
      case 'booking_pickup_reminder':
      case 'review_reminder':
        return LucideIcons.calendarCheck;
      case 'payment_success':
      case 'payment_failed':
      case 'payment_refunded':
      case 'payment_initialized':
      case 'cash_payment_initialized':
        return LucideIcons.creditCard;
      case 'license_verified':
      case 'license_approved':
      case 'license_rejected':
      case 'license_expiring_soon':
        return LucideIcons.fileBadge;
      default:
        return LucideIcons.bell;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(_unreadCount > 0
            ? 'Notifications ($_unreadCount unread)'
            : 'Notifications'),
        actions: [
          if (_unreadCount > 0)
            IconButton(
              icon: const Icon(LucideIcons.checkCheck),
              tooltip: 'Mark all as read',
              onPressed: _markAllAsRead,
            ),
        ],
      ),
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
        onRetry: _fetchNotifications,
      );
    }

    if (_notifications.isEmpty) {
      return const EmptyStateWidget(
        icon: LucideIcons.bellOff,
        title: 'No Notifications',
        message: 'You are all caught up! Check back later for updates.',
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchNotifications,
      child: ListView.builder(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount: _notifications.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index == _notifications.length) {
            return const Padding(
              padding: EdgeInsets.all(AppSpacing.md),
              child: Center(child: CircularProgressIndicator()),
            );
          }

          final notification = _notifications[index];
          return Dismissible(
            key: ValueKey(notification.id),
            direction: DismissDirection.endToStart,
            background: Container(
              color: AppColors.error,
              alignment: Alignment.centerRight,
              padding: const EdgeInsets.only(right: AppSpacing.lg),
              child: const Icon(LucideIcons.trash2, color: AppColors.surface),
            ),
            onDismissed: (_) => _deleteNotification(notification, index),
            child: _buildNotificationItem(notification, index),
          );
        },
      ),
    );
  }

  Widget _buildNotificationItem(AppNotification notification, int index) {
    final isUnread = !notification.isRead;
    
    return InkWell(
      onTap: () => _markAsRead(notification, index),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: isUnread ? AppColors.primary.withOpacity(0.05) : AppColors.surface,
          border: const Border(bottom: BorderSide(color: AppColors.border)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: isUnread ? AppColors.primary : AppColors.surface,
                shape: BoxShape.circle,
                border: isUnread ? null : Border.all(color: AppColors.border),
              ),
              child: Icon(
                _getIconForType(notification.semanticType),
                size: 20,
                color: isUnread ? AppColors.surface : AppColors.textSecondary,
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          notification.title,
                          style: AppTypography.textTheme.titleMedium?.copyWith(
                            fontWeight: isUnread ? FontWeight.w700 : FontWeight.w500,
                          ),
                        ),
                      ),
                      Text(
                        _timeAgo(notification.createdAt),
                        style: AppTypography.textTheme.labelSmall?.copyWith(
                          color: AppColors.textTertiary,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    notification.message,
                    style: AppTypography.textTheme.bodyMedium?.copyWith(
                      color: isUnread ? AppColors.textPrimary : AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            if (isUnread) ...[
              const SizedBox(width: AppSpacing.sm),
              Container(
                width: 8,
                height: 8,
                decoration: const BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
              ),
            ]
          ],
        ),
      ),
    );
  }
}

