import apiClient from './client';

export const reviewApi = {
  getByVehicle: (vehicleId) => apiClient.get(`/vehicles/${vehicleId}/reviews`),
  getUserReviews: (params = {}) => apiClient.get('/reviews', { params }),
  create: (vehicleId, payload) => apiClient.post(`/vehicles/${vehicleId}/reviews`, payload),
  update: (id, payload) => apiClient.put(`/reviews/${id}`, payload),
  delete: (id) => apiClient.delete(`/reviews/${id}`),
};

export default reviewApi;
