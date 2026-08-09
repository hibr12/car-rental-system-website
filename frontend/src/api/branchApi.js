import apiClient from './axios';

const branchApi = {
  getAll: (params = {}) => apiClient.get('/branches', { params }),
  get: (id) => apiClient.get(`/branches/${id}`),
  create: (data) => apiClient.post('/branches', data),
  update: (id, data) => apiClient.put(`/branches/${id}`, data),
  delete: (id) => apiClient.delete(`/branches/${id}`),
  getDashboard: () => apiClient.get('/branch-manager/dashboard'),
};

export default branchApi;
