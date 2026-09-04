import React, { useState, useEffect, useCallback } from 'react';
import { Bell, BellOff, Check, Trash2, CheckCheck, AlertCircle } from 'lucide-react';
import notificationApi from '../../api/notificationApi';
import Pagination from '../../components/common/Pagination';
import { useToast } from '../../components/common/Toast';

export const NotificationsPage = () => {
  const toast = useToast();
  const [notifications, setNotifications] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [deleteConfirmId, setDeleteConfirmId] = useState(null);

  const fetchNotifications = useCallback(async () => {
    try {
      setLoading(true);
      const res = await notificationApi.getAll({ page, per_page: 10 });
      setNotifications(res.data || []);
      if (res.meta) setMeta(res.meta);
    } catch (err) {
      toast.error(err.message || 'Failed to load notifications.');
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => {
    fetchNotifications();
  }, [fetchNotifications]);

  const handleMarkAsRead = async (id) => {
    try {
      await notificationApi.markAsRead(id);
      setNotifications((prev) =>
        prev.map((n) => (n.id === id ? { ...n, is_read: true } : n))
      );
    } catch (err) {
      toast.error(err.message || 'Failed to mark notification as read.');
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      await notificationApi.markAllAsRead();
      setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
      toast.success('All notifications marked as read.');
    } catch (err) {
      toast.error(err.message || 'Failed to mark all as read.');
    }
  };

  const handleDelete = async (id) => {
    try {
      await notificationApi.delete(id);
      setNotifications((prev) => prev.filter((n) => n.id !== id));
      setDeleteConfirmId(null);
      toast.success('Notification deleted.');
    } catch (err) {
      toast.error(err.message || 'Failed to delete notification.');
    }
  };

  const unreadCount = notifications.filter((n) => !n.is_read).length;

  const formatTimestamp = (timestamp) => {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
  };

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-theme pb-6">
        <div>
          <h1 className="text-3xl font-extrabold text-theme-primary tracking-tight">Notifications</h1>
          <p className="text-sm text-theme-muted">Stay updated on your bookings, payments, and account activity.</p>
        </div>
        {unreadCount > 0 && (
          <button
            onClick={handleMarkAllAsRead}
            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg transition-all"
          >
            <CheckCheck className="w-4 h-4" />
            Mark All as Read ({unreadCount})
          </button>
        )}
      </div>

      {/* Notifications List */}
      <div className="bg-theme-card border border-theme rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl transition-colors duration-200">
        {loading ? (
          <div className="py-12 text-center text-theme-muted text-sm">Loading notifications...</div>
        ) : notifications.length === 0 ? (
          <div className="text-center py-16 space-y-4">
            <BellOff className="w-16 h-16 text-theme-muted mx-auto opacity-40" />
            <p className="text-lg font-bold text-theme-primary">No Notifications</p>
            <p className="text-sm text-theme-muted max-w-sm mx-auto">
              You're all caught up! Notifications about your bookings, payments, and account will appear here.
            </p>
          </div>
        ) : (
          <div className="space-y-3">
            {notifications.map((notification) => (
              <div
                key={notification.id}
                className={`relative p-5 rounded-2xl border transition-all duration-200 ${
                  notification.is_read
                    ? 'bg-theme-secondary border-theme'
                    : 'bg-theme-secondary/60 border-blue-500/20 shadow-md shadow-blue-500/5'
                }`}
              >
                <div className="flex items-start gap-4">
                  {/* Icon */}
                  <div
                    className={`p-2.5 rounded-xl shrink-0 ${
                      notification.is_read
                        ? 'bg-theme-hover text-theme-muted'
                        : 'bg-blue-500/10 text-blue-400'
                    }`}
                  >
                    <Bell className="w-5 h-5" />
                  </div>

                  {/* Content */}
                  <div className="flex-1 min-w-0 space-y-1.5">
                    <div className="flex items-start justify-between gap-3">
                      <h4
                        className={`text-sm font-bold leading-snug ${
                          notification.is_read ? 'text-theme-secondary' : 'text-theme-primary'
                        }`}
                      >
                        {notification.title}
                      </h4>
                      <div className="flex items-center gap-1.5 shrink-0">
                        {!notification.is_read && (
                          <span className="w-2 h-2 rounded-full bg-blue-400 shrink-0" />
                        )}
                        <span className="text-[10px] text-theme-muted whitespace-nowrap">
                          {formatTimestamp(notification.created_at || notification.timestamp)}
                        </span>
                      </div>
                    </div>

                    <p className="text-xs text-theme-muted leading-relaxed">{notification.message}</p>

                    {/* Actions */}
                    <div className="flex items-center gap-2 pt-2">
                      {!notification.is_read && (
                        <button
                          onClick={() => handleMarkAsRead(notification.id)}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold text-blue-400 hover:bg-blue-500/10 border border-blue-500/20 transition-colors"
                        >
                          <Check className="w-3 h-3" />
                          Mark as Read
                        </button>
                      )}

                      {/* Inline delete confirmation */}
                      {deleteConfirmId === notification.id ? (
                        <div className="flex items-center gap-2 bg-red-500/10 border border-red-500/20 rounded-xl px-3 py-1.5">
                          <AlertCircle className="w-3.5 h-3.5 text-red-400 shrink-0" />
                          <span className="text-[11px] text-red-400">Delete?</span>
                          <button
                            onClick={() => setDeleteConfirmId(null)}
                            className="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-theme-card border border-theme text-theme-secondary hover:bg-theme-hover transition-colors"
                          >
                            Cancel
                          </button>
                          <button
                            onClick={() => handleDelete(notification.id)}
                            className="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-red-600 text-white hover:bg-red-500 transition-colors"
                          >
                            Delete
                          </button>
                        </div>
                      ) : (
                        <button
                          onClick={() => setDeleteConfirmId(notification.id)}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold text-theme-muted hover:bg-red-500/10 hover:text-red-400 border border-transparent hover:border-red-500/20 transition-colors"
                        >
                          <Trash2 className="w-3 h-3" />
                          Delete
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {meta.last_page > 1 && (
          <Pagination
            currentPage={meta.current_page}
            lastPage={meta.last_page}
            total={meta.total}
            onPageChange={(p) => setPage(p)}
          />
        )}
      </div>
    </div>
  );
};

export default NotificationsPage;
