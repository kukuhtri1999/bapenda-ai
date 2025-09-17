import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Send cookies with requests to preserve session in proxied or cross-origin setups
window.axios.defaults.withCredentials = true;

// Ensure CSRF token included for web POST requests
const tokenMeta = document.head.querySelector('meta[name="csrf-token"]');
if (tokenMeta) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.content;
}
