import axios from 'axios';

const isDev = import.meta.env.DEV;
const API_URL = isDev ? '/api' : (import.meta.env.VITE_API_URL || 'http://localhost:8000/api');
const BASE_URL = isDev ? '' : (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000');

const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 15000,
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
  return config;
});

// Response interceptor to extract data from axios response
apiClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    const status = error.response?.status
    let message = 'Something went wrong. Please try again later.'

    if (status === 429) {
      message = 'Too many requests. Please wait a minute before trying again.'
    } else if (status === 422) {
      // Validation errors - extract first error message
      const errors = error.response?.data?.errors
      if (errors && Object.keys(errors).length > 0) {
        const firstField = Object.keys(errors)[0]
        message = errors[firstField][0] || message
      } else {
        message = 'Validation failed. Please check your input.'
      }
    } else if (status === 401 || status === 404) {
      message = 'This link has expired or is invalid. Please request a new one.'
    } else if (status === 500) {
      message = 'Something went wrong on our end. Please try again later.'
    }

    // Reject with a standardized error object
    return Promise.reject(new Error(message))
  }
);

export default apiClient;
