import apiClient from './client';

export const paymentApi = {
  getAll: (params = {}) => apiClient.get('/payments', { params }),
  getHistory: (params = {}) => apiClient.get('/admin/payment-history', { params }),
  getById: (id) => apiClient.get(`/payments/${id}`),
  create: (payload) => apiClient.post('/payments', payload),
  initialize: (payload) => apiClient.post('/payments/initialize', payload),
  verify: (txRef) => apiClient.get(`/payments/verify/${encodeURIComponent(txRef)}`),
  getStatus: (id, params = {}) => apiClient.get(`/payments/${id}/status`, { params }),
  verifyById: (id) => apiClient.post(`/payments/${id}/verify`),
  confirmCash: (id) => apiClient.post(`/admin/payments/${id}/confirm-cash`),
  archive: (id, reason) => apiClient.put(`/admin/payments/${id}/archive`, { reason }),
  getBookingPaymentStatus: (bookingId, params = {}) =>
    apiClient.get(`/bookings/${bookingId}/payment-status`, { params }),
  adminMarkAsFailed: (id) => apiClient.put(`/admin/payments/${id}/fail`),
  adminRefund: (id) => apiClient.put(`/admin/payments/${id}/refund`),
};

export default paymentApi;
