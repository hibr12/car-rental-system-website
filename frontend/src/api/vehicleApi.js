import apiClient from './client';
import { invalidateCategories } from './categoryApi';

const invalidateCategoryCountsAfter = (request) => request.then((response) => {
  invalidateCategories();
  return response;
});

export const vehicleApi = {
  getAll: (params = {}) => apiClient.get('/vehicles', { params }),
  getOptions: (params = {}) => apiClient.get('/vehicles/options', { params }),
  getAvailable: (params = {}) => apiClient.get('/vehicles', {
    params: { ...params, available_only: true },
  }),
  getById: (id) => apiClient.get(`/vehicles/${id}`),
  create: (payload) => invalidateCategoryCountsAfter(apiClient.post('/vehicles', payload)),
  update: (id, payload) => invalidateCategoryCountsAfter(apiClient.put(`/vehicles/${id}`, payload)),
  delete: (id) => invalidateCategoryCountsAfter(apiClient.delete(`/vehicles/${id}`)),
};

export default vehicleApi;
