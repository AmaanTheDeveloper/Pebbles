import axios from 'axios';
import { apiBaseUrl } from './config';

const api = axios.create({
    baseURL: apiBaseUrl,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

export default api;
