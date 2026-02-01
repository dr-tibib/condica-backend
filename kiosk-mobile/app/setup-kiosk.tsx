import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, ActivityIndicator, FlatList } from 'react-native';
import { useRouter } from 'expo-router';
import { getAdminToken, setKioskWorkplaceId, clearAdminToken } from '../src/utils/kiosk';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';

const API_URL = process.env.EXPO_PUBLIC_API_URL || 'https://oaksoft-condica.lndo.site/api';

interface Workplace {
  id: number;
  name: string;
  address: string | null;
}

const KioskSetupScreen = () => {
  const [workplaces, setWorkplaces] = useState<Workplace[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();

  useEffect(() => {
    const fetchWorkplaces = async () => {
      const token = await getAdminToken();
      if (!token) {
        router.replace('/login');
        return;
      }

      try {
        const response = await fetch(`${API_URL}/workplaces`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        });

        if (!response.ok) {
           if (response.status === 401) {
               await clearAdminToken();
               router.replace('/login');
               return;
           }
           throw new Error('Failed to fetch workplaces');
        }

        const data = await response.json();
        setWorkplaces(data.data || []);
      } catch (err: any) {
        setError(err.message);
      } finally {
        setIsLoading(false);
      }
    };

    fetchWorkplaces();
  }, []);

  const handleSelectWorkplace = async (workplace: Workplace) => {
    await setKioskWorkplaceId(workplace.id);
    await clearAdminToken();
    router.replace('/');
  };

  if (isLoading) {
    return (
      <View className="flex-1 items-center justify-center bg-gray-50 dark:bg-gray-900">
        <ActivityIndicator size="large" color="#3b82f6" />
      </View>
    );
  }

  return (
    <SafeAreaView className="flex-1 bg-gray-50 dark:bg-gray-900 p-4">
        <View className="mb-8 items-center">
          <Text className="text-3xl font-extrabold text-gray-900 dark:text-white">
            Setup Kiosk
          </Text>
          <Text className="mt-4 text-lg text-gray-600 dark:text-gray-400 text-center">
            Select the workplace for this kiosk
          </Text>
        </View>

        {error && (
          <View className="mb-8 bg-red-50 dark:bg-red-900/20 p-4 rounded-md">
            <Text className="text-red-700 dark:text-red-300 text-center">{error}</Text>
          </View>
        )}

        <FlatList
          data={workplaces}
          keyExtractor={(item) => item.id.toString()}
          ListEmptyComponent={
            !error ? (
                <Text className="text-center text-gray-500 py-12">No workplaces found.</Text>
            ) : null
          }
          renderItem={({ item }) => (
            <TouchableOpacity
              onPress={() => handleSelectWorkplace(item)}
              className="mb-4 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm flex-row items-center"
            >
              <View className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                <MaterialIcons name="domain" size={24} color="#3b82f6" />
              </View>
              <View className="flex-1">
                  <Text className="text-sm font-medium text-gray-900 dark:text-white">
                    {item.name}
                  </Text>
                  <Text className="text-sm text-gray-500 dark:text-gray-400" numberOfLines={1}>
                    {item.address || 'No address'}
                  </Text>
              </View>
            </TouchableOpacity>
          )}
        />
    </SafeAreaView>
  );
};

export default KioskSetupScreen;