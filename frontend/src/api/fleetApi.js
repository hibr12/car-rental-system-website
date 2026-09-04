import apiClient from './client';

export const fleetApi = {
  getDashboard: (params = {}) => apiClient.get('/fleet/dashboard', { params }),
  getFleetReport: (params = {}) => apiClient.get('/reports/fleet', { params }),
};

export default fleetApi;
