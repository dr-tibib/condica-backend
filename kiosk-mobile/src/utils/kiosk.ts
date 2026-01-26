import AsyncStorage from '@react-native-async-storage/async-storage';

const KEY_WORKPLACE_ID = 'kiosk_workplace_id';
const KEY_ADMIN_TOKEN = 'kiosk_admin_token';

export const getKioskWorkplaceId = async (): Promise<string | null> => {
  return await AsyncStorage.getItem(KEY_WORKPLACE_ID);
};

export const setKioskWorkplaceId = async (id: string | number) => {
  await AsyncStorage.setItem(KEY_WORKPLACE_ID, id.toString());
};

export const clearKioskWorkplaceId = async () => {
  await AsyncStorage.removeItem(KEY_WORKPLACE_ID);
};

export const getAdminToken = async (): Promise<string | null> => {
  return await AsyncStorage.getItem(KEY_ADMIN_TOKEN);
};

export const setAdminToken = async (token: string) => {
  await AsyncStorage.setItem(KEY_ADMIN_TOKEN, token);
};

export const clearAdminToken = async () => {
  await AsyncStorage.removeItem(KEY_ADMIN_TOKEN);
};