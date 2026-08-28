import apiClient from './client';

export const inspectionApi = {
  getAll: (params = {}) => apiClient.get('/vehicle-inspections', { params }),
  getById: (id) => apiClient.get(`/vehicle-inspections/${id}`),
  create: (payload) => apiClient.post('/vehicle-inspections', payload),
  complete: (id, payload) => apiClient.put(`/vehicle-inspections/${id}/complete`, payload),
};

export default inspectionApi;
