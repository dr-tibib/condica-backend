import { create } from 'zustand';
import NetInfo from '@react-native-community/netinfo';

interface EmployeeState {
  id: number | null;
  full_name: string | null;
  has_delegation_permission: boolean;
  current_state: 'not_checked_in' | 'checked_in' | 'on_delegation' | null;
  token: string | null;
  token_expiry: number | null;
}

interface UiState {
  current_screen: string;
  is_loading: boolean;
  error_message: string | null;
  idle_timer: number | null;
  is_offline: boolean;
  pending_sync_count: number;
}

interface LocationState {
    company_templates: any[];
    personal_templates: any[];
    search_results: any[];
    selected_location: any | null;
}

interface SettingsState {
    workplace_id: number | null;
    workplace_name: string | null;
    workplace_location: { lat: number, lng: number } | null;
    device_id: number | null;
    camera_mode: 'photo' | 'video';
    video_duration: number;
    auto_timeout: number;
    language: string;
}

interface OfflineState {
    pending_events: any[];
    pending_media: any[];
    last_sync: number | null;
}

interface AppState {
  employee: EmployeeState;
  ui: UiState;
  locations: LocationState;
  settings: SettingsState;
  offline: OfflineState;
  setEmployee: (employee: Partial<EmployeeState>) => void;
  setUi: (ui: Partial<UiState>) => void;
  setLocations: (locations: Partial<LocationState>) => void;
  setSettings: (settings: Partial<SettingsState>) => void;
  setOffline: (offline: Partial<OfflineState>) => void;
  reset: () => void;
}

NetInfo.fetch().then(state => {
    useAppStore.getState().setUi({ is_offline: !state.isConnected });
});

NetInfo.addEventListener(state => {
    useAppStore.getState().setUi({ is_offline: !state.isConnected });
});

const useAppStore = create<AppState>((set) => ({
  employee: {
    id: null,
    full_name: null,
    has_delegation_permission: false,
    current_state: null,
    token: null,
    token_expiry: null,
  },
  ui: {
    current_screen: '/',
    is_loading: false,
    error_message: null,
    idle_timer: null,
    is_offline: false,
    pending_sync_count: 0,
  },
    locations: {
        company_templates: [],
        personal_templates: [],
        search_results: [],
        selected_location: null,
    },
    settings: {
        workplace_id: null,
        workplace_name: null,
        workplace_location: null,
        device_id: null,
        camera_mode: 'photo',
        video_duration: 3,
        auto_timeout: 20,
        language: 'en',
    },
    offline: {
        pending_events: [],
        pending_media: [],
        last_sync: null,
    },
  setEmployee: (employee) => set((state) => ({ employee: { ...state.employee, ...employee } })),
    setUi: (ui) => set((state) => ({ ui: { ...state.ui, ...ui } })),
    setLocations: (locations) => set((state) => ({ locations: { ...state.locations, ...locations } })),
    setSettings: (settings) => set((state) => ({ settings: { ...state.settings, ...settings } })),
    setOffline: (offline) => set((state) => ({ offline: { ...state.offline, ...offline } })),
  reset: () => set((state) => ({
    employee: {
      id: null,
      full_name: null,
      has_delegation_permission: false,
      current_state: null,
      token: null,
      token_expiry: null,
    },
    ui: {
      ...state.ui,
      current_screen: '/',
      is_loading: false,
      error_message: null,
      idle_timer: null,
    },
    locations: {
        company_templates: [],
        personal_templates: [],
        search_results: [],
        selected_location: null,
    },
  })),
}));

export default useAppStore;