import apiClient from './axios';

export const paymentApi = {
  getAll: (params = {}) => apiClient.get('/payments', { params }),
  getById: (id) => apiClient.get(`/payments/${id}`),
  create: (payload) => apiClient.post('/payments', payload),
};

export default paymentApi;
