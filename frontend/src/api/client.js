import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

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

// CSRF cookie is at /sanctum/csrf-cookie (not /api/sanctum/csrf-cookie)
const getCsrfCookie = () => {
  if (!csrfPromise) {
    csrfPromise = axios.get('http://localhost:8000/sanctum/csrf-cookie', {
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
  (error) => Promise.reject(error)
);

export default apiClient;
