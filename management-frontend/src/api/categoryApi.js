import apiClient from './axios';

export const categoryApi = {
  getAll: () => apiClient.get('/categories'),
  getById: (id) => apiClient.get(`/categories/${id}`),
  create: (payload) => apiClient.post('/categories', payload),
  update: (id, payload) => apiClient.put(`/categories/${id}`, payload),
  delete: (id) => apiClient.delete(`/categories/${id}`),
};

export default categoryApi;
