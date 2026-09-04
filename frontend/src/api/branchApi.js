import apiClient from './client';

export const branchApi = {
  getDashboard:  (params = {}) => apiClient.get('/branch/dashboard', { params }),
  getCustomers:  (params = {}) => apiClient.get('/branch/customers', { params }),
  getReports:    (params = {}) => apiClient.get('/branch/reports', { params }),
  getFleetReport:(params = {}) => apiClient.get('/branch/reports/fleet', { params }),

  getMaintenanceRequests: (params = {}) => apiClient.get('/maintenance-requests', { params }),
  createMaintenanceRequest: (data) => apiClient.post('/maintenance-requests', data),

  // Shared: vehicles, bookings, payments, maintenance, rentals
  getVehicles:   (params = {}) => apiClient.get('/vehicles', { params }),
  getBookings:   (params = {}) => apiClient.get('/admin/bookings', { params }),
  getPayments:   (params = {}) => apiClient.get('/payments', { params }),
  getMaintenance:(params = {}) => apiClient.get('/maintenance', { params }),
  getRentals:    (params = {}) => apiClient.get('/rentals', { params }),

  // Check-in / Check-out
  checkOut: (bookingId, data) => apiClient.put(`/rentals/${bookingId}/checkout`, data),
  checkIn:  (bookingId, data) => apiClient.put(`/rentals/${bookingId}/checkin`, data),

  // Transfers
  getTransfers:  (params = {}) => apiClient.get('/vehicle-transfers', { params }),
  createTransfer:(data) => apiClient.post('/vehicle-transfers', data),

  // Staff
  getStaff:    (params = {}) => apiClient.get('/staff', { params }),
  createStaff: (data) => apiClient.post('/staff', data),
  updateStaff: (id, data) => apiClient.put(`/staff/${id}`, data),
  deleteStaff: (id) => apiClient.delete(`/staff/${id}`),
};

export default branchApi;
