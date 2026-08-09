import apiClient from './axios';

const inspectionApi = {
  getAll: (params = {}) => apiClient.get('/inspections', { params }),
  get: (id) => apiClient.get(`/inspections/${id}`),
  create: (data) => apiClient.post('/inspections', data),
};

export default inspectionApi;
