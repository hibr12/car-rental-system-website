import apiClient from './axios';

export const bookingApi = {
  getUserBookings: (params = {}) => apiClient.get('/bookings', { params }),
  getById: (id) => apiClient.get(`/bookings/${id}`),
  create: (payload) => apiClient.post('/bookings', payload),
  cancel: (id) => apiClient.put(`/bookings/${id}/cancel`),
  getAdminBookings: (params = {}) => apiClient.get('/admin/bookings', { params }),
  confirm: (id) => apiClient.put(`/admin/bookings/${id}/confirm`),
  reject: (id, reason) => apiClient.put(`/admin/bookings/${id}/reject`, { reason }),
  pickup: (id) => apiClient.put(`/admin/bookings/${id}/pickup`),
  returnVehicle: (id) => apiClient.put(`/admin/bookings/${id}/return`),
};

export default bookingApi;
