import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, Image, ScrollView, Alert, ActivityIndicator } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { getKioskWorkplaceId } from '../src/utils/kiosk';
import useIdleTimer from '../src/hooks/useIdleTimer';

const API_URL = process.env.EXPO_PUBLIC_API_URL || 'http://10.0.2.2:8000/api';
const GOOGLE_MAPS_API_KEY = process.env.EXPO_PUBLIC_GOOGLE_MAPS_API_KEY || '';

const ConfirmLocationScreen = () => {
    const { t } = useTranslation();
    const router = useRouter();
    const params = useLocalSearchParams();
    const [user, setUser] = useState<any>(null);
    const [location, setLocation] = useState<any>(null);
    const [isLoading, setIsLoading] = useState(false);

    useIdleTimer(30);

    useEffect(() => {
        if (params.user) {
            try { setUser(JSON.parse(params.user as string)); } catch (e) {}
        }
        if (params.location) {
            try { setLocation(JSON.parse(params.location as string)); } catch (e) {}
        }
    }, [params]);

    const handleBack = () => {
        router.back();
    };

    const handleStartDelegation = async () => {
        if (!user || !location) return;

        setIsLoading(true);

        try {
            const workplaceId = await getKioskWorkplaceId();

            const response = await fetch(`${API_URL}/delegations`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    user_id: user.id,
                    place_id: location.place_id,
                    name: location.name,
                    address: location.address,
                    latitude: location.latitude,
                    longitude: location.longitude,
                    workplace_id: workplaceId,
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || t('confirm.delegation_failed'));
            }

            router.push({
                pathname: '/success',
                params: {
                   type: data.type,
                   name: data.user.name,
                   time: data.time
                }
            });

        } catch (error: any) {
            console.error(error);
            Alert.alert("Error", error.message);
        } finally {
            setIsLoading(false);
        }
    };

    const mapUrl = location
        ? `https://maps.googleapis.com/maps/api/staticmap?center=${location.latitude},${location.longitude}&zoom=15&size=600x300&maptype=roadmap&markers=color:red%7C${location.latitude},${location.longitude}&key=${GOOGLE_MAPS_API_KEY}`
        : null;

  return (
    <SafeAreaView className="flex-1 bg-background-light dark:bg-background-dark">
      <View className="flex-1 max-w-[768px] w-full mx-auto bg-white dark:bg-[#111621]">
        <View className="flex-row items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
             <View className="flex-row items-center gap-4">
                 <MaterialIcons name="domain" size={24} color="#3b82f6" />
                 <Text className="text-lg font-bold text-[#111318] dark:text-white">
                     {t('confirm.workplace_presence')}
                 </Text>
             </View>
             <TouchableOpacity onPress={handleBack} className="flex-row items-center gap-2">
                 <MaterialIcons name="arrow-back" size={20} color="#111318" />
                 <Text className="text-sm font-medium text-[#111318] dark:text-white">
                     {t('common.back')}
                 </Text>
             </TouchableOpacity>
        </View>

        <ScrollView className="flex-1 px-6 py-8">
            <View className="mb-6">
                <Text className="text-3xl font-black text-[#111318] dark:text-white mb-2">
                    {t('confirm.title')}
                </Text>
                <Text className="text-base text-gray-500 dark:text-gray-400">
                    {t('confirm.subtitle')}
                </Text>
            </View>

            <View className="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1a202c] shadow-sm mb-6">
                <View className="w-full h-48 bg-gray-200 relative">
                    {mapUrl && (
                        <Image
                            source={{ uri: mapUrl }}
                            className="w-full h-full"
                            resizeMode="cover"
                        />
                    )}
                    <View className="absolute bottom-4 right-4 bg-white dark:bg-[#1a202c] p-2 rounded-lg shadow-md flex-row items-center gap-2">
                         <MaterialIcons name="my-location" size={16} color="#3b82f6" />
                         <Text className="text-xs font-bold text-[#111318] dark:text-white">
                             {t('confirm.current_location')}
                         </Text>
                    </View>
                </View>

                <View className="p-6 flex-row items-start gap-4">
                     <View className="w-12 h-12 rounded-full bg-blue-50 items-center justify-center">
                         <MaterialIcons name="storefront" size={24} color="#3b82f6" />
                     </View>
                     <View className="flex-1">
                         <Text className="text-xl font-bold text-[#111318] dark:text-white mb-1">
                             {location?.name || t('confirm.no_location')}
                         </Text>
                         <Text className="text-base text-gray-500 dark:text-gray-400">
                             {location?.address}
                         </Text>
                     </View>
                </View>
            </View>
        </ScrollView>

        <View className="p-6 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-[#1a202c]">
            <View className="flex-row gap-4">
                 <TouchableOpacity
                    onPress={handleStartDelegation}
                    disabled={isLoading}
                    className="flex-1 h-14 bg-primary rounded-xl items-center justify-center shadow-md active:opacity-90"
                 >
                     {isLoading ? (
                         <ActivityIndicator color="white" />
                     ) : (
                         <Text className="text-lg font-bold text-white">
                             {t('confirm.start_delegation')}
                         </Text>
                     )}
                 </TouchableOpacity>

                 <TouchableOpacity
                    onPress={handleBack}
                    disabled={isLoading}
                    className="flex-1 h-14 bg-transparent border border-gray-300 dark:border-gray-600 rounded-xl items-center justify-center active:bg-gray-50 dark:active:bg-gray-800"
                 >
                     <Text className="text-base font-medium text-gray-500 dark:text-gray-400">
                         {t('common.cancel')}
                     </Text>
                 </TouchableOpacity>
            </View>
        </View>
      </View>
    </SafeAreaView>
  );
};

export default ConfirmLocationScreen;