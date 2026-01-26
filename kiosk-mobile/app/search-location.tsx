import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { GooglePlacesAutocomplete } from 'react-native-google-places-autocomplete';
import useIdleTimer from '../src/hooks/useIdleTimer';

const GOOGLE_MAPS_API_KEY = process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY || '';

const SearchLocationScreen = () => {
    const { t } = useTranslation();
    const router = useRouter();
    const params = useLocalSearchParams();
    const [user, setUser] = useState<any>(null);

    useIdleTimer(60);

    useEffect(() => {
        if (params.user) {
            try {
                setUser(JSON.parse(params.user as string));
            } catch (e) {}
        }
    }, [params.user]);

    const handleBack = () => {
        router.back();
    };

    const handleSelectLocation = (details: any) => {
         const locationData = {
            place_id: details.place_id,
            name: details.name || details.formatted_address,
            address: details.formatted_address,
            latitude: details.geometry.location.lat,
            longitude: details.geometry.location.lng,
            icon: 'place'
        };

        router.push({
            pathname: '/confirm-location',
            params: {
                location: JSON.stringify(locationData),
                user: JSON.stringify(user)
            }
        });
    };

  return (
    <SafeAreaView className="flex-1 bg-white dark:bg-[#1a202c]">
        <View className="flex-1 max-w-[768px] w-full mx-auto">
             <View className="flex-row items-center gap-4 px-4 py-4 border-b border-gray-100 dark:border-gray-800 z-10">
                <TouchableOpacity onPress={handleBack} className="p-2">
                    <MaterialIcons name="arrow-back" size={28} color="#475569" />
                </TouchableOpacity>
                <Text className="text-xl font-bold text-slate-900 dark:text-white">
                    {t('search.select_location')}
                </Text>
            </View>

            <View className="flex-1 px-4 pt-4">
                 <Text className="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    {t('search.search_label', 'Search for a place')}
                 </Text>

                 <GooglePlacesAutocomplete
                    placeholder={t('search.placeholder', 'Start typing...')}
                    onPress={(data, details = null) => {
                        if (details) {
                            handleSelectLocation(details);
                        }
                    }}
                    query={{
                        key: GOOGLE_MAPS_API_KEY,
                        language: 'en',
                    }}
                    fetchDetails={true}
                    styles={{
                        textInputContainer: {
                            backgroundColor: 'transparent',
                            borderTopWidth: 0,
                            borderBottomWidth: 0,
                        },
                        textInput: {
                            height: 48,
                            color: '#000',
                            fontSize: 16,
                            backgroundColor: '#f1f5f9',
                            borderRadius: 12,
                            paddingLeft: 16,
                        },
                        predefinedPlacesDescription: {
                            color: '#1faadb',
                        },
                    }}
                    enablePoweredByContainer={false}
                 />
            </View>
        </View>
    </SafeAreaView>
  );
};

export default SearchLocationScreen;