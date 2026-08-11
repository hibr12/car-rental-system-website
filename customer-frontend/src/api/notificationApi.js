import apiClient from './axios';

export const notificationApi = {
  getAll: (params = {}) => apiClient.get('/notifications', { params }),
  getById: (id) => apiClient.get(`/notifications/${id}`),
  markAsRead: (id) => apiClient.put(`/notifications/${id}/read`),
  markAllAsRead: () => apiClient.put('/notifications/read-all'),
  delete: (id) => apiClient.delete(`/notifications/${id}`),
};

export default notificationApi;
