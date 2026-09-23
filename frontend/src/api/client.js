import axios from 'axios';

// Always same-origin: the Vite dev proxy (vite.config.js) and the Vercel
// rewrites (vercel.json) forward /api and /sanctum to the backend. Sanctum's
// cookie auth needs the SPA and API on the same site — calling the Render
// domain directly would make the session/XSRF cookies third-party (419s).
const API_URL = '/api';
const BASE_URL = '';

const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  // Render's free tier can take ~60s to wake from idle.
  timeout: 60000,
  withCredentials: true,
});

let csrfPromise = null;

// CSRF cookie is at /sanctum/csrf-cookie
const getCsrfCookie = () => {
  if (!csrfPromise) {
    csrfPromise = axios.get(`${BASE_URL}/sanctum/csrf-cookie`, {
      withCredentials: true,
    }).finally(() => {
      csrfPromise = null;
    });
  }
  return csrfPromise;
};

const needsCsrf = (method) => ['post', 'put', 'patch', 'delete'].includes(method.toLowerCase());

apiClient.interceptors.request.use(async (config) => {
  if (needsCsrf(config.method)) {
    await getCsrfCookie();
  }
  // Let the browser set the correct Content-Type with boundary for file uploads
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type'];
  }
  return config;
});

// Custom error class to carry structured error data
export class ApiError extends Error {
  constructor(message, status, errors = [], friendlyErrors = []) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;           // Raw Laravel validation errors (field => [messages])
    this.friendlyErrors = friendlyErrors; // Translated user-friendly messages
  }
}

// Response interceptor to extract data from axios response
apiClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    const status = error.response?.status;
    const responseData = error.response?.data;
    let message = 'Something went wrong. Please try again later.';
    let errors = {};
    let friendlyErrors = [];

    if (status === 429) {
      message = 'Too many requests. Please wait a minute before trying again.';
    } else if (status === 422) {
      // Validation errors - extract all errors
      errors = responseData?.errors || {};
      friendlyErrors = responseData?.friendly_errors || [];
      
      if (Object.keys(errors).length > 0) {
        // Use first friendly error as main message, or fall back to first raw error
        message = friendlyErrors[0] || Object.values(errors)[0][0] || 'Validation failed. Please check your input.';
      } else {
        message = responseData?.message || 'Validation failed. Please check your input.';
      }
    } // For login endpoint, 401 means invalid credentials - preserve original message
    // For other endpoints, 401 means session expired
    const isLoginRequest = error.config?.url?.includes('/auth/login');
    if (status === 401) {
      if (isLoginRequest && responseData?.message) {
        message = responseData.message;
      } else {
        message = 'Your session has expired. Please sign in again.';
      }
    } else if (status === 403) {
      message = 'You don\'t have permission to perform this action.';
    } else if (status === 404) {
      message = 'The requested resource was not found.';
    } else if (status === 500) {
      message = 'Something went wrong on our end. Please try again later.';
    } else if (status === 400) {
      message = responseData?.message || 'Invalid request.';
    } else if (status === 405) {
      message = 'This action is not allowed.';
    } else if (responseData?.message) {
      message = responseData.message;
    }

    // Reject with a structured error object
    return Promise.reject(new ApiError(message, status, errors, friendlyErrors));
  }
);

export default apiClient;
