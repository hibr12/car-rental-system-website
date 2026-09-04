import apiClient from './client';

export const damageApi = {
  getAll: (params = {}) => apiClient.get('/vehicle-damages', { params }),
  create: (payload) => apiClient.post('/vehicle-damages', payload),
  update: (id, payload) => apiClient.put(`/vehicle-damages/${id}`, payload),
};

export default damageApi;
