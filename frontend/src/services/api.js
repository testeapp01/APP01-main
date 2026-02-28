import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost/api/v1', // Laragon padrão
  timeout: 15000,
});

api.interceptors.request.use(config => {
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
