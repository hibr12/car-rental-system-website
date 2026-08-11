import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 15000,
});

// Request interceptor to attach Bearer token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response) {
      const status = error.response.status;
      const data = error.response.data;

      if (status === 401) {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        // Dispatch custom event if app needs to reset auth state
        window.dispatchEvent(new Event('unauthorized'));
      }

      const formattedError = {
        status,
        message: data?.message || 'An unexpected error occurred.',
        errors: data?.errors || null,
        success: false,
      };

      return Promise.reject(formattedError);
    } else if (error.request) {
      return Promise.reject({
        status: 0,
        message: 'Network error. Please check your internet connection or server availability.',
        success: false,
      });
    } else {
      return Promise.reject({
        status: 0,
        message: error.message || 'An error occurred setting up the request.',
        success: false,
      });
    }
  }
);

export default apiClient;
