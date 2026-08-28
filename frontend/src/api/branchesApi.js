import apiClient from './client';

export const branchApi = {
  getAll: (params = {}) => apiClient.get('/branches', { params }),
  getById: (id) => apiClient.get(`/branches/${id}`),
};

export default branchApi;
