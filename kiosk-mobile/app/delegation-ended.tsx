import React, { useEffect, useState, useRef } from 'react';
import { View, Text, TouchableOpacity, ActivityIndicator, Animated } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { getKioskWorkplaceId } from '../src/utils/kiosk';

const API_URL = process.env.EXPO_PUBLIC_API_URL || 'http://10.0.2.2:8000/api';

const DelegationEndedScreen = () => {
    const { t } = useTranslation();
    const router = useRouter();
    const params = useLocalSearchParams();
    const [user, setUser] = useState<any>(null);
    const [code, setCode] = useState<string>('');
    const [isLoading, setIsLoading] = useState(false);
    const progress = useRef(new Animated.Value(0)).current;

    useEffect(() => {
        if (params.user) {
            try { setUser(JSON.parse(params.user as string)); } catch (e) {}
        }
        if (params.code) {
            setCode(params.code as string);
        }
    }, [params]);

    useEffect(() => {
        Animated.timing(progress, {
            toValue: 100,
            duration: 10000,
            useNativeDriver: false,
        }).start();

        const timer = setTimeout(() => {
            router.replace('/');
        }, 10000);

        return () => clearTimeout(timer);
    }, []);

    const handleCheckOut = async () => {
        if (!code) return;
        setIsLoading(true);
        const workplaceId = await getKioskWorkplaceId();

        try {
            const response = await fetch(`${API_URL}/kiosk/submit-code`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    code: code,
                    flow: 'regular',
                    workplace_id: workplaceId,
                })
            });

            const data = await response.json();

            if (!response.ok) {
                 throw new Error(data.message || 'Checkout failed');
            }

            router.push({
                pathname: '/success',
                params: {
                    type: 'checkout',
                    name: user?.name,
                    time: data.time || new Date().toLocaleTimeString()
                }
            });

        } catch (error) {
            console.error(error);
        } finally {
            setIsLoading(false);
        }
    };

    const widthInterpolated = progress.interpolate({
        inputRange: [0, 100],
        outputRange: ['0%', '100%']
    });

    return (
        <SafeAreaView className="flex-1 bg-background-light dark:bg-background-dark items-center justify-center p-4">
             <View className="relative w-full max-w-[768px] bg-white dark:bg-[#1a202c] shadow-2xl rounded-xl p-12 items-center border border-slate-200 dark:border-slate-800 gap-8">

                 <View className="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                     <MaterialIcons name="flag-circle" size={60} color="#2563eb" />
                 </View>

                 <Text className="text-4xl font-bold text-slate-900 dark:text-white text-center">
                     {t('delegation.ended_title', 'Delegation Ended')}
                 </Text>

                 <Text className="text-xl text-slate-500 dark:text-slate-400 text-center max-w-md">
                     {t('delegation.ended_message', 'You have returned from delegation. You remain checked in.')}
                 </Text>

                 <View className="w-full max-w-sm mt-8 gap-4">
                     <TouchableOpacity
                        onPress={handleCheckOut}
                        disabled={isLoading}
                        className="w-full h-16 bg-red-600 rounded-xl flex-row items-center justify-center shadow-lg active:opacity-90"
                     >
                         <MaterialIcons name="logout" size={30} color="white" style={{ marginRight: 10 }} />
                         {isLoading ? (
                             <ActivityIndicator color="white" />
                         ) : (
                             <Text className="text-white text-xl font-bold">
                                 {t('common.check_out', 'Check Out Now')}
                             </Text>
                         )}
                     </TouchableOpacity>

                     <View className="w-full items-center gap-4 mt-4">
                         <View className="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                             <Animated.View
                                className="h-full bg-primary rounded-full"
                                style={{ width: widthInterpolated }}
                             />
                         </View>
                         <Text className="text-slate-400 dark:text-slate-500 text-sm font-medium">
                             {t('success.returning_home')}
                         </Text>
                     </View>
                 </View>
             </View>
        </SafeAreaView>
    );
};

export default DelegationEndedScreen;