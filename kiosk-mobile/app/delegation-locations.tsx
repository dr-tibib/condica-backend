import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, Image, FlatList } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import useIdleTimer from '../src/hooks/useIdleTimer';

const API_URL = process.env.EXPO_PUBLIC_API_URL || 'http://10.0.2.2:8000/api';

const DelegationLocationsScreen = () => {
    const { t } = useTranslation();
    const router = useRouter();
    const params = useLocalSearchParams();
    const [user, setUser] = useState<any>(null);
    const [savedLocations, setSavedLocations] = useState<any[]>([]);

    useIdleTimer(30);

    useEffect(() => {
        if (params.user) {
            try {
                setUser(JSON.parse(params.user as string));
            } catch (e) {
                console.error("Failed to parse user", e);
            }
        }
    }, [params.user]);

    useEffect(() => {
        if (user?.id) {
            const fetchLocations = async () => {
                try {
                    const response = await fetch(`${API_URL}/delegations?user_id=${user.id}`);
                    const data = await response.json();
                    setSavedLocations(data.data.map((loc: any) => ({
                        id: loc.place_id,
                        name: loc.name,
                        address: loc.address,
                        icon: 'history',
                        place_id: loc.place_id,
                        latitude: loc.latitude,
                        longitude: loc.longitude,
                        fullWidth: true
                    })));
                } catch (error) {
                    console.error(error);
                }
            };
            fetchLocations();
        }
    }, [user]);

    const handleBack = () => {
        router.push({ pathname: '/code-entry', params: { flow: 'delegation' } });
    };

    const handleSearch = () => {
        router.push({ pathname: '/search-location', params: { user: JSON.stringify(user) } });
    };

    const handleSelectLocation = (location: any) => {
        router.push({
            pathname: '/confirm-location',
            params: {
                location: JSON.stringify(location),
                user: JSON.stringify(user)
            }
        });
    }

  return (
    <SafeAreaView className="flex-1 bg-background-light dark:bg-background-dark">
        <View className="flex-1 bg-white dark:bg-[#111621] max-w-[768px] w-full mx-auto">
          <View className="flex-row items-center justify-between px-6 py-4">
            <TouchableOpacity
              onPress={handleBack}
              className="w-12 h-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800"
            >
              <MaterialIcons name="arrow-back" size={28} color="#111318" />
            </TouchableOpacity>

            <View className="flex-row items-center gap-3 pl-1 pr-4 py-1 bg-gray-100 dark:bg-gray-800 rounded-full border border-gray-200 dark:border-gray-700">
              <View className="w-8 h-8 rounded-full overflow-hidden bg-gray-300">
                 <MaterialIcons name="person" size={24} color="white" style={{ alignSelf: 'center', marginTop: 2 }} />
              </View>
              <Text className="text-sm font-bold text-[#111318] dark:text-white truncate max-w-[120px]">
                {user?.name || 'User'}
              </Text>
            </View>
          </View>

          <View className="flex-1 px-6 pb-8">
            <View className="mt-4 mb-8">
              <Text className="text-[#111318] dark:text-white text-3xl font-black">
                {t('delegation.where_going')}
              </Text>
            </View>

            <TouchableOpacity onPress={handleSearch} className="mb-8">
              <View className="flex-row items-center w-full h-14 px-4 rounded-xl bg-gray-100 dark:bg-gray-800 border-2 border-transparent">
                  <MaterialIcons name="search" size={24} color="#616e89" />
                  <Text className="ml-3 text-lg text-gray-500">
                      {t('delegation.search_placeholder')}
                  </Text>
              </View>
            </TouchableOpacity>

            <Text className="text-[#111318] dark:text-white text-xl font-bold mb-4">
                {t('delegation.saved_locations')}
            </Text>

            <FlatList
                data={savedLocations}
                keyExtractor={(item) => item.id}
                ListEmptyComponent={
                    <Text className="text-gray-500 text-center mt-4">{t('delegation.no_saved_locations', 'No recent locations found.')}</Text>
                }
                renderItem={({ item }) => (
                    <TouchableOpacity
                        onPress={() => handleSelectLocation(item)}
                        className="mb-4 p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-row items-center"
                    >
                        <View className="w-12 h-12 rounded-lg bg-blue-50 items-center justify-center mr-4">
                            <MaterialIcons name="history" size={24} color="#3b82f6" />
                        </View>
                        <View className="flex-1">
                            <Text className="text-[#111318] dark:text-white text-lg font-bold">
                                {item.name}
                            </Text>
                            <Text className="text-gray-500 dark:text-gray-400 text-sm">
                                {item.address}
                            </Text>
                        </View>
                        <MaterialIcons name="chevron-right" size={24} color="#d1d5db" />
                    </TouchableOpacity>
                )}
            />
          </View>
        </View>
    </SafeAreaView>
  );
};

export default DelegationLocationsScreen;