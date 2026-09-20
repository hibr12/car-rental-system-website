import { create } from 'zustand';
import notificationApi from '../api/notificationApi';
import { ApiError } from '../api/client';

const useNotificationStore = create((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isLoading: false,
  pagination: null,
  error: null,

  fetchNotifications: async (page = 1) => {
    set({ isLoading: true, error: null });
    try {
      const response = await notificationApi.getAll({ page });
      const data = response.data;
      set({
        notifications: data.data || [],
        pagination: {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
        },
        isLoading: false,
        error: null,
      });
    } catch (err) {
      console.error('Failed to fetch notifications:', err);
      let errorMessage = 'Failed to load notifications. Please try again.';
      if (err instanceof ApiError) {
        if (err.status === 401) {
          errorMessage = 'Your session has expired. Please sign in again.';
        } else if (err.status === 403) {
          errorMessage = 'You do not have permission to view notifications.';
        } else {
          errorMessage = err.message || errorMessage;
        }
      }
      set({ isLoading: false, error: errorMessage });
    }
  },

  fetchUnreadCount: async () => {
    try {
      const response = await notificationApi.getUnreadCount();
      set({ unreadCount: response.data?.unread_count || 0 });
    } catch (err) {
      console.error('Failed to fetch unread count:', err);
    }
  },

  markAsRead: async (id) => {
    try {
      await notificationApi.markAsRead(id);
      set((state) => ({
        notifications: state.notifications.map((n) =>
          n.id === id ? { ...n, read_at: new Date().toISOString() } : n
        ),
        unreadCount: Math.max(0, state.unreadCount - 1),
      ));
    } catch (err) {
      console.error('Failed to mark notification as read:', err);
      if (err instanceof ApiError) {
        set({ error: err.message || 'Failed to mark as read.' });
      }
    }
  },

  markAllAsRead: async () => {
    try {
      await notificationApi.markAllAsRead();
      set((state) => ({
        notifications: state.notifications.map((n) => ({
          ...n,
          read_at: n.read_at || new Date().toISOString(),
        })),
        unreadCount: 0,
      }));
    } catch (err) {
      console.error('Failed to mark all as read:', err);
      if (err instanceof ApiError) {
        set({ error: err.message || 'Failed to mark all as read.' });
      }
    }
  },

  deleteNotification: async (id) => {
    try {
      await notificationApi.delete(id);
      set((state) => ({
        notifications: state.notifications.filter((n) => n.id !== id),
      }));
    } catch (err) {
      console.error('Failed to delete notification:', err);
      if (err instanceof ApiError) {
        set({ error: err.message || 'Failed to delete notification.' });
      }
    }
  },

  clearError: () => set({ error: null }),
}));

export default useNotificationStore;
