import axios from 'axios';
import useAppStore from '../store/appStore';
import { router } from 'expo-router';

const baseURL = process.env.EXPO_PUBLIC_API_URL || 'http://10.0.2.2:8000/api';

const apiService = axios.create({
  baseURL: baseURL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

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

apiService.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response && error.response.status === 401) {
      useAppStore.getState().reset();
      try {
          router.replace('/');
      } catch (e) {
          console.error("Navigation failed", e);
      }
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

export const uploadMedia = (eventId: number, media: any, type: 'photo' | 'video') => {
    const formData = new FormData();
    formData.append('media', {
        uri: media.uri,
        name: `media.${type === 'photo' ? 'jpg' : 'mp4'}`,
        type: type === 'photo' ? 'image/jpeg' : 'video/mp4'
    } as any);
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