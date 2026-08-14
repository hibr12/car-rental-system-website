import apiClient from './axios';

const licenseApi = {
  get: () => apiClient.get('/customer/license'),
  upload: (data) => apiClient.post('/customer/license', data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  update: (data) => apiClient.put('/customer/license', data, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  getPending: () => apiClient.get('/licenses/pending'),
  verify: (userId, data) => apiClient.put(`/licenses/${userId}/verify`, data),
};

export default licenseApi;
