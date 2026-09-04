import apiClient from './client';

export const documentApi = {
  getAll: (params = {}) => apiClient.get('/vehicle-documents', { params }),
  create: (payload) => apiClient.post('/vehicle-documents', payload),
  update: (id, payload) => apiClient.put(`/vehicle-documents/${id}`, payload),
  delete: (id) => apiClient.delete(`/vehicle-documents/${id}`),
};

export default documentApi;
