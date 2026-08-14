import { create } from 'zustand';
import notificationApi from '../api/notificationApi';

const useNotificationStore = create((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isLoading: false,
  pagination: null,

  fetchNotifications: async (page = 1) => {
    set({ isLoading: true });
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
      });
    } catch (err) {
      console.error('Failed to fetch notifications:', err);
      set({ isLoading: false });
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
      }));
    } catch (err) {
      console.error('Failed to mark notification as read:', err);
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
    }
  },
}));

export default useNotificationStore;
