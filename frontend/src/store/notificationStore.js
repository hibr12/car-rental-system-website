import { create } from 'zustand';
import notificationApi from '../api/notificationApi';
import { ApiError } from '../api/client';

let notificationPromise = null;
let unreadPromise = null;
let pollTimer = null;
let pollConsumers = 0;
let visibilityHandler = null;
let lastListFetchedAt = 0;
let notificationEpoch = 0;

const isVisible = () => typeof document === 'undefined' || !document.hidden;

const errorMessageFor = (err, fallback) => {
  if (!(err instanceof ApiError)) return fallback;
  if (err.status === 401) return 'Your session has expired. Please sign in again.';
  if (err.status === 403) return 'You do not have permission to view notifications.';
  return err.message || fallback;
};

const useNotificationStore = create((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isLoading: false,
  pagination: null,
  error: null,

  fetchNotifications: async (page = 1, { force = false } = {}) => {
    if (!force && Date.now() - lastListFetchedAt < 15_000) {
      return get().notifications;
    }
    if (notificationPromise) return notificationPromise;

    const requestEpoch = notificationEpoch;
    set({ isLoading: true, error: null });
    const request = notificationApi.getAll({ page, per_page: 10 })
      .then((response) => {
        if (requestEpoch !== notificationEpoch) return [];

        const notifications = response.data || [];
        lastListFetchedAt = Date.now();
        set({
          notifications,
          unreadCount: response.meta?.unread_count ?? get().unreadCount,
          pagination: response.meta || null,
          isLoading: false,
          error: null,
        });
        return notifications;
      })
      .catch((err) => {
        if (requestEpoch !== notificationEpoch) return [];

        set({
          isLoading: false,
          error: errorMessageFor(err, 'Failed to load notifications. Please try again.'),
        });
        throw err;
      })
      .finally(() => {
        if (notificationPromise === request) notificationPromise = null;
      });

    notificationPromise = request;
    return request;
  },

  fetchUnreadCount: async () => {
    if (!isVisible()) return get().unreadCount;
    if (unreadPromise) return unreadPromise;

    const requestEpoch = notificationEpoch;
    const request = notificationApi.getUnreadCount()
      .then((response) => {
        if (requestEpoch !== notificationEpoch) return get().unreadCount;

        const unreadCount = response.data?.unread_count || 0;
        set({ unreadCount });
        return unreadCount;
      })
      .catch((err) => {
        if (requestEpoch !== notificationEpoch) return get().unreadCount;

        // Background polling should not replace the current UI with an error.
        console.warn('Failed to refresh notification count:', err);
        return get().unreadCount;
      })
      .finally(() => {
        if (unreadPromise === request) unreadPromise = null;
      });

    unreadPromise = request;
    return request;
  },

  startPolling: () => {
    pollConsumers += 1;
    if (pollConsumers > 1) return;

    get().fetchUnreadCount();
    pollTimer = setInterval(() => get().fetchUnreadCount(), 30_000);

    if (typeof document !== 'undefined') {
      visibilityHandler = () => {
        if (!document.hidden) get().fetchUnreadCount();
      };
      document.addEventListener('visibilitychange', visibilityHandler);
    }
  },

  stopPolling: () => {
    pollConsumers = Math.max(0, pollConsumers - 1);
    if (pollConsumers > 0) return;

    if (pollTimer) clearInterval(pollTimer);
    pollTimer = null;

    if (visibilityHandler && typeof document !== 'undefined') {
      document.removeEventListener('visibilitychange', visibilityHandler);
    }
    visibilityHandler = null;
  },

  markAsRead: async (id) => {
    const requestEpoch = notificationEpoch;
    try {
      await notificationApi.markAsRead(id);
      if (requestEpoch !== notificationEpoch) return;

      set((state) => ({
        notifications: state.notifications.map((notification) => (
          notification.id === id
            ? { ...notification, read_at: new Date().toISOString() }
            : notification
        )),
        unreadCount: Math.max(0, state.unreadCount - 1),
      }));
    } catch (err) {
      if (requestEpoch !== notificationEpoch) return;
      set({ error: errorMessageFor(err, 'Failed to mark notification as read.') });
    }
  },

  markAllAsRead: async () => {
    const requestEpoch = notificationEpoch;
    try {
      await notificationApi.markAllAsRead();
      if (requestEpoch !== notificationEpoch) return;

      set((state) => ({
        notifications: state.notifications.map((notification) => ({
          ...notification,
          read_at: notification.read_at || new Date().toISOString(),
        })),
        unreadCount: 0,
      }));
    } catch (err) {
      if (requestEpoch !== notificationEpoch) return;
      set({ error: errorMessageFor(err, 'Failed to mark all notifications as read.') });
    }
  },

  deleteNotification: async (id) => {
    const requestEpoch = notificationEpoch;
    try {
      await notificationApi.delete(id);
      if (requestEpoch !== notificationEpoch) return;

      set((state) => {
        const deleted = state.notifications.find((notification) => notification.id === id);
        return {
          notifications: state.notifications.filter((notification) => notification.id !== id),
          unreadCount: deleted && !deleted.read_at
            ? Math.max(0, state.unreadCount - 1)
            : state.unreadCount,
        };
      });
    } catch (err) {
      if (requestEpoch !== notificationEpoch) return;
      set({ error: errorMessageFor(err, 'Failed to delete notification.') });
    }
  },

  reset: () => {
    notificationEpoch += 1;
    notificationPromise = null;
    unreadPromise = null;
    lastListFetchedAt = 0;

    set({
      notifications: [],
      unreadCount: 0,
      isLoading: false,
      pagination: null,
      error: null,
    });
  },

  clearError: () => set({ error: null }),
}));

export default useNotificationStore;
