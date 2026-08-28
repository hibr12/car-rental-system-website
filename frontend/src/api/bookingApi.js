import apiClient from './client';

export const bookingApi = {
  getUserBookings: (params = {}) => apiClient.get('/bookings', { params }),
  getById: (id) => apiClient.get(`/bookings/${id}`),
  create: (payload) => apiClient.post('/bookings', payload),
  cancel: (id) => apiClient.put(`/bookings/${id}/cancel`),
  checkAvailability: (params = {}) => apiClient.get('/bookings/check-availability', { params }),
  priceEstimate: (params = {}) => apiClient.get('/bookings/price-estimate', { params }),
  getAdminBookings: (params = {}) => apiClient.get('/admin/bookings', { params }),
  confirm: (id) => apiClient.put(`/admin/bookings/${id}/confirm`),
  reject: (id, reason) => apiClient.put(`/admin/bookings/${id}/reject`, { reason }),
  preparePickup: (id) => apiClient.put(`/admin/bookings/${id}/prepare-pickup`),
  pickup: (id, payload = {}) => apiClient.put(`/admin/bookings/${id}/pickup`, payload),
  returnVehicle: (id, payload = {}) => apiClient.put(`/admin/bookings/${id}/return`, payload),
  archive: (id, reason) => apiClient.put(`/admin/bookings/${id}/archive`, { reason }),
};

export default bookingApi;
