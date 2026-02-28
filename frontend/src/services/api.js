import axios from 'axios';

const envBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').trim();
const API_BASE_URL = import.meta.env.PROD
  ? '/api/v1'
  : (envBaseUrl || 'http://localhost:8000/api/v1');

const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 15000,
});

api.interceptors.request.use(config => {
  if (typeof config.url === 'string') {
    if (config.url.startsWith('/api/v1/')) {
      config.url = config.url.replace('/api/v1', '')
    } else if (config.url.startsWith('api/v1/')) {
      config.url = config.url.replace('api/v1', '')
    }
  }

  // attach auth token if exists
  const token = localStorage.getItem('hf_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
}, error => Promise.reject(error));

api.interceptors.response.use(response => response, error => {
  // global error handling
  if (error.response && error.response.status === 401) {
    // example: redirect to login or emit event
    localStorage.removeItem('hf_token');
    if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
      window.location.assign('/login');
    }
  }
  return Promise.reject(error);
});

export default api;
