import apiClient from './axios';

export const contactApi = {
  submit: (payload) => apiClient.post('/contact-messages', payload),
  getAll: (params = {}) => apiClient.get('/contact-messages', { params }),
  update: (id, payload) => apiClient.put(`/contact-messages/${id}`, payload),
  delete: (id) => apiClient.delete(`/contact-messages/${id}`),
};

export default contactApi;
