import apiClient from './client';

export const transferApi = {
  getAll:       (params = {}) => apiClient.get('/vehicle-transfers', { params }),
  getOne:       (id) => apiClient.get(`/vehicle-transfers/${id}`),
  getHistory:  (id) => apiClient.get(`/vehicle-transfers/${id}/history`),
  create:       (data) => apiClient.post('/vehicle-transfers', data),
  approve:      (id, data = {}) => apiClient.put(`/vehicle-transfers/${id}/approve`, data),
  reject:       (id, reason) => apiClient.put(`/vehicle-transfers/${id}/reject`, { reason }),
  cancel:       (id, reason) => apiClient.put(`/vehicle-transfers/${id}/cancel`, { reason }),
  prepareRelease:(id, data) => apiClient.post(`/vehicle-transfers/${id}/prepare-release`, data),
  markInTransit:(id, data = {}) => apiClient.put(`/vehicle-transfers/${id}/in-transit`, data),
  receive:      (id, data) => apiClient.put(`/vehicle-transfers/${id}/receive`, data),
  complete:     (id) => apiClient.put(`/vehicle-transfers/${id}/complete`),
  markFailed:   (id, reason) => apiClient.put(`/vehicle-transfers/${id}/fail`, { reason }),
  executeNow:   (id) => apiClient.put(`/vehicle-transfers/${id}/execute`),
};

export default transferApi;
