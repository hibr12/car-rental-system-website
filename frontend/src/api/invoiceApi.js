import apiClient from './axios';

const invoiceApi = {
  getAll: (params = {}) => apiClient.get('/invoices', { params }),
  get: (id) => apiClient.get(`/invoices/${id}`),
  generate: (bookingId) => apiClient.post(`/invoices/generate/${bookingId}`),
};

export default invoiceApi;
