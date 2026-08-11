import apiClient from './axios';

export const adminApi = {
  getDashboard: () => apiClient.get('/admin/dashboard'),
  getUsers: (params = {}) => apiClient.get('/admin/users', { params }),
  getUserById: (id) => apiClient.get(`/admin/users/${id}`),
  updateUser: (id, payload) => apiClient.put(`/admin/users/${id}`, payload),
};

export default adminApi;
