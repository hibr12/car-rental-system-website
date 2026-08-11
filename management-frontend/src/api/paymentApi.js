import apiClient from './axios';

export const paymentApi = {
  getAll: (params = {}) => apiClient.get('/payments', { params }),
  getById: (id) => apiClient.get(`/payments/${id}`),
  create: (payload) => apiClient.post('/payments', payload),
  initialize: (payload) => apiClient.post('/payments/initialize', payload),
  verify: (txRef) => apiClient.get(`/payments/verify/${txRef}`),
  adminMarkAsFailed: (id) => apiClient.put(`/admin/payments/${id}/fail`),
  adminRefund: (id) => apiClient.put(`/admin/payments/${id}/refund`),
  adminDelete: (id) => apiClient.delete(`/admin/payments/${id}`),
};

export default paymentApi;
