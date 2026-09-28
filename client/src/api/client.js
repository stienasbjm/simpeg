// client/src/api/client.js
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor: Sisipkan JWT Token ke setiap request jika ada
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('simpeg_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Interceptor: Handle status 401 Unauthorized (Auto Logout jika session expired)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Hapus session jika token sudah kadaluarsa
      if (localStorage.getItem('simpeg_token')) {
        localStorage.removeItem('simpeg_token');
        localStorage.removeItem('simpeg_user');
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export default api;
