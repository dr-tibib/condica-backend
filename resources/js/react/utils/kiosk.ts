const KEY_WORKPLACE_ID = 'kiosk_workplace_id';
const KEY_ADMIN_TOKEN = 'kiosk_admin_token';

export const getKioskWorkplaceId = (): string | null => {
  return localStorage.getItem(KEY_WORKPLACE_ID);
};

export const setKioskWorkplaceId = (id: string | number) => {
  localStorage.setItem(KEY_WORKPLACE_ID, id.toString());
};

export const clearKioskWorkplaceId = () => {
  localStorage.removeItem(KEY_WORKPLACE_ID);
};

export const getAdminToken = (): string | null => {
  return localStorage.getItem(KEY_ADMIN_TOKEN);
};

export const setAdminToken = (token: string) => {
  localStorage.setItem(KEY_ADMIN_TOKEN, token);
};

export const clearAdminToken = () => {
  localStorage.removeItem(KEY_ADMIN_TOKEN);
};
