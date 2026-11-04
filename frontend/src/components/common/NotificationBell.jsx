import React, { useState, useEffect, useRef } from 'react';
import { Bell, Check, X, AlertCircle, RefreshCw } from 'lucide-react';
import { formatDate } from '../../utils/formatters';
import useNotificationStore from '../../store/notificationStore';

const NotificationBell = () => {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);
  const notifications = useNotificationStore((state) => state.notifications);
  const unreadCount = useNotificationStore((state) => state.unreadCount);
  const loading = useNotificationStore((state) => state.isLoading);
  const error = useNotificationStore((state) => state.error);
  const fetchNotifications = useNotificationStore((state) => state.fetchNotifications);
  const startPolling = useNotificationStore((state) => state.startPolling);
  const stopPolling = useNotificationStore((state) => state.stopPolling);
  const markAsRead = useNotificationStore((state) => state.markAsRead);
  const markAllAsRead = useNotificationStore((state) => state.markAllAsRead);
  const clearError = useNotificationStore((state) => state.clearError);

  useEffect(() => {
    startPolling();
    return stopPolling;
  }, [startPolling, stopPolling]);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleMarkAllRead = async () => {
    await markAllAsRead();
  };

  const handleMarkAsRead = async (id) => {
    await markAsRead(id);
  };

  const getNotificationTitle = (type) => {
    if (!type) return 'Notification';
    const name = type.split('\\').pop() || type;
    return name
      .replace(/([A-Z])/g, ' $1')
      .replace(/^./, (s) => s.toUpperCase())
      .trim();
  };

  const handleRetry = () => {
    fetchNotifications(1, { force: true }).catch(() => {});
  };

  const handleDismissError = () => {
    clearError();
  };

  const handleToggle = () => {
    const nextOpen = !isOpen;
    setIsOpen(nextOpen);
    if (nextOpen) fetchNotifications(1, { force: true }).catch(() => {});
  };

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={handleToggle}
        className="relative p-2 rounded-xl bg-theme-secondary border border-theme hover:border-theme-hover text-theme-secondary hover:text-theme-primary transition-all"
        aria-label="Notifications"
      >
        <Bell className="w-4 h-4" />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-white text-[9px] font-bold flex items-center justify-center">
            {unreadCount > 9 ? '9+' : unreadCount}
          </span>
        )}
      </button>

      {isOpen && (
        <div className="absolute right-0 top-12 w-80 sm:w-96 bg-theme-card border border-theme rounded-2xl shadow-2xl z-50 overflow-hidden">
          <div className="flex items-center justify-between p-4 border-b border-theme">
            <h3 className="font-bold text-theme-primary text-sm">Notifications</h3>
            <div className="flex items-center gap-2">
              {unreadCount > 0 && (
                <button
                  onClick={handleMarkAllRead}
                  className="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1"
                >
                  <Check className="w-3 h-3" />
                  Mark all read
                </button>
              )}
              <button
                onClick={() => setIsOpen(false)}
                className="p-1 rounded hover:bg-theme-hover text-theme-muted"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          </div>

          {error && (
            <div className="p-3 border-b border-theme bg-rose-500/10 text-rose-300 text-xs flex items-center justify-between gap-2">
              <div className="flex items-center gap-2 flex-1 min-w-0">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span className="truncate">{error}</span>
              </div>
              <div className="flex items-center gap-1 shrink-0">
                <button
                  onClick={handleRetry}
                  className="px-2 py-1 text-[10px] font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1"
                >
                  <RefreshCw className="w-3 h-3" />
                  Retry
                </button>
                <button
                  onClick={handleDismissError}
                  className="p-1 rounded hover:bg-theme-hover text-rose-300"
                >
                  <X className="w-3 h-3" />
                </button>
              </div>
            </div>
          )}

          <div className="max-h-80 overflow-y-auto">
            {loading && notifications.length === 0 ? (
              <div className="p-6 text-center text-theme-muted text-xs flex items-center justify-center gap-2">
                <RefreshCw className="w-4 h-4 animate-spin text-blue-400" />
                Loading notifications...
              </div>
            ) : notifications.length === 0 ? (
              <div className="p-6 text-center text-theme-muted text-xs">
                {error ? (
                  <span>Unable to load notifications</span>
                ) : (
                  <span>No notifications yet.</span>
                )}
              </div>
            ) : (
              notifications.map((n) => (
                <div
                  key={n.id}
                  className={`p-4 border-b border-theme hover:bg-theme-hover transition-colors ${
                    !n.read_at ? 'bg-blue-500/5' : ''
                  }`}
                >
                  <div className="flex items-start justify-between gap-2">
                    <div className="flex-1 min-w-0">
                      <p className="text-xs font-semibold text-theme-primary truncate">
                        {n.data?.title || getNotificationTitle(n.type)}
                      </p>
                      {n.data && typeof n.data === 'object' && (
                        <p className="text-[11px] text-theme-muted mt-0.5 line-clamp-2">
                          {n.data.message || n.data.booking_reference || JSON.stringify(n.data).slice(0, 100)}
                        </p>
                      )}
                      <p className="text-[10px] text-theme-muted/70 mt-1">{formatDate(n.created_at)}</p>
                    </div>
                    {!n.read_at && (
                      <button
                        onClick={() => handleMarkAsRead(n.id)}
                        className="p-1 rounded-lg hover:bg-theme-hover text-blue-400 shrink-0"
                        title="Mark as read"
                      >
                        <Check className="w-3.5 h-3.5" />
                      </button>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default NotificationBell;
