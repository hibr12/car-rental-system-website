import apiClient from './client';

export const vehicleApi = {
  getAll: (params = {}) => apiClient.get('/vehicles', { params }),
  getAvailable: (params = {}) => apiClient.get('/vehicles', {
    params: { ...params, available_only: true },
  }),
  getById: (id) => apiClient.get(`/vehicles/${id}`),
  create: (payload) => apiClient.post('/vehicles', payload),
  update: (id, payload) => apiClient.put(`/vehicles/${id}`, payload),
  delete: (id) => apiClient.delete(`/vehicles/${id}`),
};

export default vehicleApi;
