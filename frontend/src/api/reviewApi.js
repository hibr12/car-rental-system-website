import apiClient from './axios';

export const reviewApi = {
  getByVehicle: (vehicleId) => apiClient.get(`/vehicles/${vehicleId}/reviews`),
  create: (vehicleId, payload) => apiClient.post(`/vehicles/${vehicleId}/reviews`, payload),
  delete: (id) => apiClient.delete(`/reviews/${id}`),
};

export default reviewApi;
