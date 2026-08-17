import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Send cookies with requests to preserve session in proxied or cross-origin setups
window.axios.defaults.withCredentials = true;

// Instruct Axios to automatically read XSRF-TOKEN cookie and send X-XSRF-TOKEN header
window.axios.defaults.withXSRFToken = true;

// Initialize CSRF token header from meta tag
const tokenMeta = document.head.querySelector('meta[name="csrf-token"]');
if (tokenMeta) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.content;
}

// ── Axios Request Interceptor: Always attach latest CSRF meta token ───────────
window.axios.interceptors.request.use(
  (config) => {
    const latestMeta = document.head.querySelector('meta[name="csrf-token"]');
    if (latestMeta && latestMeta.content) {
      config.headers['X-CSRF-TOKEN'] = latestMeta.content;
    }
    return config;
  },
  (error) => Promise.reject(error),
);

// ── Axios Response Interceptor: Self-Healing 419 (Page Expired / CSRF) ───────
let isRefreshingCsrf = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach((prom) => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });
  failedQueue = [];
};

window.axios.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // Detect 419 CSRF Token Mismatch or Session Expiration
    if (error.response && error.response.status === 419 && !originalRequest._retry) {
      if (isRefreshingCsrf) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then((token) => {
            originalRequest.headers['X-CSRF-TOKEN'] = token;
            return window.axios(originalRequest);
          })
          .catch((err) => Promise.reject(err));
      }

      originalRequest._retry = true;
      isRefreshingCsrf = true;

      try {
        // Fetch fresh CSRF token from dedicated endpoint
        const { data } = await window.axios.get('/refresh-csrf', { _retry: true });
        const newToken = data.csrf_token;

        if (newToken) {
          window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
          const meta = document.head.querySelector('meta[name="csrf-token"]');
          if (meta) {
            meta.content = newToken;
          }

          processQueue(null, newToken);
          originalRequest.headers['X-CSRF-TOKEN'] = newToken;
          return window.axios(originalRequest);
        }
      } catch (refreshErr) {
        processQueue(refreshErr, null);
        console.warn('Unable to auto-refresh CSRF session token:', refreshErr);
      } finally {
        isRefreshingCsrf = false;
      }
    }

    return Promise.reject(error);
  },
);
