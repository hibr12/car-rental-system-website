import apiClient from './client';

export const licenseApi = {
  // Customer endpoints
  getMyLicense: () => apiClient.get('/customer/license'),
  submit: (data) => apiClient.post('/customer/license', data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  updateDocuments: (data) => apiClient.post('/customer/license/documents', data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  eligibility: (vehicleId) => apiClient.get('/customer/license/eligibility', { params: { vehicle_id: vehicleId } }),

  // Admin/Staff endpoints
  adminList: (params = {}) => apiClient.get('/admin/licenses', { params }),
  adminShow: (id) => apiClient.get(`/admin/licenses/${id}`),
  approve: (id) => apiClient.post(`/admin/licenses/${id}/approve`),
  reject: (id, reason) => apiClient.post(`/admin/licenses/${id}/reject`, { reason }),

  // Legacy/alias endpoints (for backward compatibility)
  get: () => apiClient.get('/customer/license'),
  upload: (data) => apiClient.post('/customer/license', data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  update: (data) => apiClient.put('/customer/license', data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
};

export default licenseApi;