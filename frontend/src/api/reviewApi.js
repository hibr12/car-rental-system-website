import apiClient from './client';

export const reviewApi = {
  getByVehicle: (vehicleId, params = {}) => apiClient.get(`/vehicles/${vehicleId}/reviews`, { params }),
  getByBranch: (branchId, params = {}) => apiClient.get(`/branches/${branchId}/reviews`, { params }),
  getUserReviews: (params = {}) => apiClient.get('/reviews', { params }),
  getEligibleBookings: () => apiClient.get('/reviews/eligible-bookings'),
  getEligibility: (bookingId) => apiClient.get(`/bookings/${bookingId}/review-eligibility`),
  getById: (id) => apiClient.get(`/reviews/${id}`),
  createForBooking: (bookingId, payload) => apiClient.post(`/bookings/${bookingId}/reviews`, payload),
  create: (vehicleId, payload) => apiClient.post(`/vehicles/${vehicleId}/reviews`, payload),
  update: (id, payload) => apiClient.put(`/reviews/${id}`, payload),
  archive: (id) => apiClient.delete(`/reviews/${id}`),
};

export default reviewApi;
