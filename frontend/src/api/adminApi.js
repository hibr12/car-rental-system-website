import apiClient from './client';

export const adminApi = {
  // Dashboard
  getDashboard: () => apiClient.get('/admin/dashboard'),

  // Company
  getCompany:    () => apiClient.get('/admin/company'),
  updateCompany: (data) => apiClient.put('/admin/company', data),

  // Branches
  getBranches:          (params = {}) => apiClient.get('/admin/branches', { params }),
  getBranch:            (id) => apiClient.get(`/admin/branches/${id}`),
  createBranch:         (data) => apiClient.post('/admin/branches', data),
  updateBranch:         (id, data) => apiClient.put(`/admin/branches/${id}`, data),
  activateBranch:       (id) => apiClient.put(`/admin/branches/${id}/activate`),
  deactivateBranch:     (id) => apiClient.put(`/admin/branches/${id}/deactivate`),
  getBranchDashboard:   (id) => apiClient.get(`/admin/branches/${id}/dashboard`),
  getBranchVehicles:    (id, params = {}) => apiClient.get(`/admin/branches/${id}/vehicles`, { params }),
  getBranchStaff:       (id) => apiClient.get(`/admin/branches/${id}/staff`),
  getBranchBookings:    (id, params = {}) => apiClient.get(`/admin/branches/${id}/bookings`, { params }),
  getBranchPayments:    (id, params = {}) => apiClient.get(`/admin/branches/${id}/payments`, { params }),

  // Users
  getUsers:    (params = {}) => apiClient.get('/admin/users', { params }),
  getUserById: (id) => apiClient.get(`/admin/users/${id}`),
  updateUser:  (id, data) => apiClient.put(`/admin/users/${id}`, data),

  // Reports
  getRevenueReport: (params = {}) => apiClient.get('/admin/reports/revenue', { params }),
  getFleetReport:   (params = {}) => apiClient.get('/admin/reports/fleet', { params }),

  // Reviews
  getReviews: (params = {}) => apiClient.get('/admin/reviews', { params }),
  getReviewStats: (params = {}) => apiClient.get('/admin/reviews/stats', { params }),
  updateReviewStatus: (id, payload) => apiClient.patch(`/admin/reviews/${id}/status`, payload),
  respondToReview: (id, payload) => apiClient.post(`/admin/reviews/${id}/respond`, payload),
  getReview: (id) => apiClient.get(`/reviews/${id}`),
  archiveReview: (id) => apiClient.delete(`/reviews/${id}`),
};

export default adminApi;
