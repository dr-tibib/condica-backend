import React, { useEffect } from 'react';
import { View, Text } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';

const ErrorScreen = () => {
    const { t } = useTranslation();
    const router = useRouter();
    const params = useLocalSearchParams();
    const message = (params.message as string) || 'An error occurred';

    useEffect(() => {
        const timer = setTimeout(() => {
            router.replace('/');
        }, 5000);
        return () => clearTimeout(timer);
    }, []);

    return (
        <SafeAreaView className="flex-1 bg-red-50 dark:bg-red-900/10 items-center justify-center p-4">
             <View className="bg-white dark:bg-[#1a202c] p-8 rounded-2xl shadow-xl items-center max-w-sm w-full">
                 <MaterialIcons name="error-outline" size={64} color="#dc2626" />
                 <Text className="text-2xl font-bold text-red-600 dark:text-red-400 mt-4 text-center">
                     Error
                 </Text>
                 <Text className="text-lg text-gray-700 dark:text-gray-300 mt-2 text-center">
                     {message}
                 </Text>
             </View>
        </SafeAreaView>
    );
};

export default ErrorScreen;