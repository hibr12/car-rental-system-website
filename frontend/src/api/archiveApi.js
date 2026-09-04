import apiClient from './client';

export const archiveApi = {
  getArchivedBookings: (params = {}) => apiClient.get('/admin/archive/bookings', { params }),
  archiveBooking: (id, reason = '') => apiClient.put(`/admin/bookings/${id}/archive`, { reason }),
  getArchivedPayments: (params = {}) => apiClient.get('/admin/archive/payments', { params }),
  archivePayment: (id, reason = '') => apiClient.put(`/admin/payments/${id}/archive`, { reason }),
};

export default archiveApi;
