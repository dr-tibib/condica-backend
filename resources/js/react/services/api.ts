import axios from 'axios';
import useAppStore from '../store/appStore';

const apiService = axios.create({
  baseURL: '/api', // Adjust if your API is on a different domain
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add the JWT token
apiService.interceptors.request.use(
  (config) => {
    const token = useAppStore.getState().employee.token;
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle token expiration and other errors
apiService.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response && error.response.status === 401) {
      // Handle token expiration, e.g., by resetting the state and redirecting to the idle screen
      useAppStore.getState().reset();
      window.location.href = '/';
    }
    return Promise.reject(error);
  }
);

export const validateCode = (code: string) => {
    return apiService.post('/auth/validate-code', { code });
};

export const checkIn = (employeeId: number) => {
    return apiService.post('/events/checkin', { employee_id: employeeId });
};

export const checkOut = (employeeId: number) => {
    return apiService.post('/events/checkout', { employee_id: employeeId });
};

export const startDelegation = (employeeId: number, locationId: number) => {
    return apiService.post('/events/delegation-start', { employee_id: employeeId, location_id: locationId });
};

export const endDelegation = (employeeId: number) => {
    return apiService.post('/events/delegation-end', { employee_id: employeeId });
};

export const saveDelegationLocation = (location: any) => {
    return apiService.post('/delegation-locations', location);
};

export const uploadMedia = (eventId: number, media: Blob, type: 'photo' | 'video') => {
    const formData = new FormData();
    formData.append('media', media, `media.${type === 'photo' ? 'jpg' : 'mp4'}`);
    formData.append('media_type', type);
    return apiService.post(`/events/${eventId}/media`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
};

export const syncEvents = (events: any[]) => {
    return apiService.post('/sync/events', { events });
};


export default apiService;