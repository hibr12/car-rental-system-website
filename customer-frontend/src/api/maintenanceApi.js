import apiClient from './axios';

export const maintenanceApi = {
  getAll: (params = {}) => apiClient.get('/maintenance', { params }),
  getById: (id) => apiClient.get(`/maintenance/${id}`),
  create: (payload) => apiClient.post('/maintenance', payload),
  update: (id, payload) => apiClient.put(`/maintenance/${id}`, payload),
  delete: (id) => apiClient.delete(`/maintenance/${id}`),
};

export default maintenanceApi;
