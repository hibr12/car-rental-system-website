import React, { useEffect, useState, useRef } from "react";
import { Bell, Check, CheckCheck, Trash2, X } from "lucide-react";
import useNotificationStore from "../../store/notificationStore";
import { formatDate } from "../../utils/formatters";

const NotificationCenter = () => {
  const [isOpen, setIsOpen] = useState(false);
  const {
    notifications,
    unreadCount,
    isLoading,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    deleteNotification,
  } = useNotificationStore();
  const dropdownRef = useRef(null);

  useEffect(() => {
    fetchUnreadCount();
    const interval = setInterval(fetchUnreadCount, 30000);
    return () => clearInterval(interval);
  }, [fetchUnreadCount]);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleOpen = () => {
    setIsOpen(!isOpen);
    if (!isOpen) {
      fetchNotifications();
    }
  };

  const getNotificationIcon = (type) => {
    switch (type) {
      case "reservation_created":
      case "booking_created":
        return "📋";
      case "booking_confirmed":
      case "reservation_confirmed":
        return "✅";
      case "booking_cancelled":
      case "reservation_cancelled":
        return "❌";
      case "payment_success":
        return "💰";
      case "payment_failed":
        return "💸";
      case "pickup_completed":
        return "🚗";
      case "booking_completed":
        return "🎉";
      case "license_verified":
        return "🪪";
      case "license_rejected":
        return "⚠️";
      default:
        return "🔔";
    }
  };

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={handleOpen}
        className="relative p-2 rounded-lg hover:bg-theme-hover transition-colors"
        aria-label="Notifications"
      >
        <Bell className="w-5 h-5 text-theme-secondary" />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white text-xs rounded-full flex items-center justify-center font-medium">
            {unreadCount > 99 ? "99+" : unreadCount}
          </span>
        )}
      </button>

      {isOpen && (
        <div className="absolute right-0 top-full mt-2 w-96 bg-theme-card border border-theme rounded-xl shadow-xl z-50 overflow-hidden">
          <div className="flex items-center justify-between p-4 border-b border-theme">
            <h3 className="font-semibold text-theme-primary">Notifications</h3>
            <div className="flex items-center gap-2">
              {unreadCount > 0 && (
                <button
                  onClick={markAllAsRead}
                  className="text-xs text-blue-500 hover:text-blue-600 flex items-center gap-1"
                >
                  <CheckCheck className="w-3 h-3" />
                  Mark all read
                </button>
              )}
              <button
                onClick={() => setIsOpen(false)}
                className="p-1 rounded hover:bg-theme-hover"
              >
                <X className="w-4 h-4 text-theme-secondary" />
              </button>
            </div>
          </div>

          <div className="max-h-96 overflow-y-auto">
            {isLoading ? (
              <div className="p-8 text-center text-theme-muted">
                <div className="animate-spin w-6 h-6 border-2 border-theme border-t-transparent rounded-full mx-auto" />
              </div>
            ) : notifications.length === 0 ? (
              <div className="p-8 text-center text-theme-muted">
                No notifications yet.
              </div>
            ) : (
              notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`p-4 border-b border-theme hover:bg-theme-hover transition-colors cursor-pointer ${
                    !notification.read_at ? "bg-blue-50 dark:bg-blue-900/10" : ""
                  }`}
                  onClick={() => {
                    if (!notification.read_at) {
                      markAsRead(notification.id);
                    }
                  }}
                >
                  <div className="flex items-start gap-3">
                    <span className="text-xl mt-0.5">
                      {getNotificationIcon(notification.type)}
                    </span>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <p className="font-medium text-theme-primary text-sm truncate">
                          {notification.title}
                        </p>
                        {!notification.read_at && (
                          <span className="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0" />
                        )}
                      </div>
                      <p className="text-theme-secondary text-xs mt-0.5 line-clamp-2">
                        {notification.message}
                      </p>
                      <p className="text-theme-muted text-xs mt-1">
                        {formatDate(notification.created_at, true)}
                      </p>
                    </div>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        deleteNotification(notification.id);
                      }}
                      className="p-1 rounded hover:bg-theme-hover text-theme-muted hover:text-rose-500 flex-shrink-0"
                    >
                      <Trash2 className="w-3 h-3" />
                    </button>
                  </div>
                </div>
              ))
            )}
          </div>

          {notifications.length > 0 && (
            <div className="p-3 border-t border-theme text-center">
              <button
                onClick={() => {
                  setIsOpen(false);
                }}
                className="text-sm text-blue-500 hover:text-blue-600 font-medium"
              >
                View all notifications
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default NotificationCenter;
