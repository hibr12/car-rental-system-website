import apiClient from './client';

export const authApi = {
  register: (payload) => apiClient.post('/auth/register', payload),
  login: (payload) => apiClient.post('/auth/login', payload),
  logout: () => apiClient.post('/auth/logout'),
  me: () => apiClient.get('/auth/me'),
  updateProfile: (payload) => apiClient.put('/auth/profile', payload),

  // Email verification
  verifyEmail: (id, hash) => apiClient.get(`/auth/verify-email/${id}/${hash}`),
  resendVerification: () => apiClient.post('/auth/verification/resend'),

  // Password reset
  forgotPassword: (email) => apiClient.post('/auth/forgot-password', { email }),
  resetPassword: (payload) => apiClient.post('/auth/reset-password', payload),
};

export default authApi;
